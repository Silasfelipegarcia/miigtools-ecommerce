#!/usr/bin/env php
<?php
function env(string $key, string $default = ''): string {
	$value = getenv($key);

	return $value !== false ? $value : $default;
}

function bootstrap_setting(\mysqli $mysqli, string $table, string $code, string $key, string $value, int $serialized = 0): void {
	$update = $mysqli->prepare(
		"UPDATE `{$table}` SET `value` = ?, `serialized` = ?, `code` = ? WHERE `key` = ? AND `store_id` = 0"
	);

	if ($update) {
		$update->bind_param('siss', $value, $serialized, $code, $key);
		$update->execute();
		$affected = $update->affected_rows;
		$update->close();

		if ($affected > 0) {
			// Collapse accidental duplicates left by previous inserts without unique key.
			$mysqli->query(
				"DELETE FROM `{$table}` WHERE `key` = '" . $mysqli->real_escape_string($key) . "' AND `store_id` = 0 AND `setting_id` NOT IN (
					SELECT `setting_id` FROM (
						SELECT MIN(`setting_id`) AS `setting_id` FROM `{$table}` WHERE `key` = '" . $mysqli->real_escape_string($key) . "' AND `store_id` = 0
					) AS `keep`
				)"
			);

			return;
		}
	}

	$insert = $mysqli->prepare(
		"INSERT INTO `{$table}` (`store_id`, `code`, `key`, `value`, `serialized`) VALUES (0, ?, ?, ?, ?)"
	);

	if ($insert) {
		$insert->bind_param('sssi', $code, $key, $value, $serialized);
		$insert->execute();
		$insert->close();
	}
}

function miig_seo_slug(string $text): string {
	$map = [
		'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
		'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
		'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
		'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o',
		'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
		'ç' => 'c', 'ñ' => 'n',
		'Á' => 'a', 'À' => 'a', 'Ã' => 'a', 'Â' => 'a', 'Ä' => 'a',
		'É' => 'e', 'È' => 'e', 'Ê' => 'e', 'Ë' => 'e',
		'Í' => 'i', 'Ì' => 'i', 'Î' => 'i', 'Ï' => 'i',
		'Ó' => 'o', 'Ò' => 'o', 'Õ' => 'o', 'Ô' => 'o', 'Ö' => 'o',
		'Ú' => 'u', 'Ù' => 'u', 'Û' => 'u', 'Ü' => 'u',
		'Ç' => 'c', 'Ñ' => 'n',
	];

	$text = strtr($text, $map);
	$text = strtolower($text);
	$text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
	$text = trim($text, '-');

	return $text !== '' ? $text : 'item';
}

function bootstrap_seo_url(\mysqli $mysqli, string $prefix, int $store_id, int $language_id, string $key, string $value, string $keyword, int $sort_order = 0): void {
	$table = $prefix . 'seo_url';
	$keyword = trim($keyword, '/');

	if ($keyword === '') {
		return;
	}

	$exists = $mysqli->prepare(
		"SELECT `seo_url_id` FROM `{$table}` WHERE `store_id` = ? AND `language_id` = ? AND `key` = ? AND `value` = ? LIMIT 1"
	);

	if (!$exists) {
		return;
	}

	$exists->bind_param('iiss', $store_id, $language_id, $key, $value);
	$exists->execute();
	$exists->store_result();

	if ($exists->num_rows > 0) {
		$exists->bind_result($seo_url_id);
		$exists->fetch();
		$exists->close();

		$update = $mysqli->prepare(
			"UPDATE `{$table}` SET `keyword` = ?, `sort_order` = ? WHERE `seo_url_id` = ?"
		);

		if ($update) {
			$update->bind_param('sii', $keyword, $sort_order, $seo_url_id);
			$update->execute();
			$update->close();
		}

		return;
	}

	$exists->close();

	$insert = $mysqli->prepare(
		"INSERT INTO `{$table}` (`store_id`, `language_id`, `key`, `value`, `keyword`, `sort_order`) VALUES (?, ?, ?, ?, ?, ?)"
	);

	if ($insert) {
		$insert->bind_param('iisssi', $store_id, $language_id, $key, $value, $keyword, $sort_order);
		$insert->execute();
		$insert->close();
	}
}

function miig_privacy_html_path(string $basename): string {
	$dir_opencart = rtrim(env('DIR_OPENCART', '/var/www/html/'), '/') . '/';

	$candidates = [
		$dir_opencart . 'system/miigtools/' . $basename,
		'/usr/local/share/miigtools/' . $basename,
		dirname(__DIR__) . '/scripts/' . $basename,
		__DIR__ . '/' . $basename,
	];

	foreach ($candidates as $path) {
		if (is_file($path)) {
			return $path;
		}
	}

	return '';
}

function bootstrap_privacy_policy(\mysqli $mysqli, string $db_prefix): void {
	$pt_path = miig_privacy_html_path('privacy-policy-pt-br.html');
	$en_path = miig_privacy_html_path('privacy-policy-en-gb.html');

	if ($pt_path === '' || $en_path === '') {
		echo "bootstrap-db: política de privacidade HTML não encontrada (mantém stub)\n";

		return;
	}

	$pt_html = trim((string) file_get_contents($pt_path));
	$en_html = trim((string) file_get_contents($en_path));

	if ($pt_html === '' || $en_html === '') {
		echo "bootstrap-db: política de privacidade HTML vazia\n";

		return;
	}

	$info_table = $db_prefix . 'information_description';
	$mysqli->query(
		"INSERT IGNORE INTO `{$db_prefix}information` (`information_id`, `sort_order`, `status`) VALUES (3, 4, 1)"
	);
	$mysqli->query(
		"INSERT IGNORE INTO `{$db_prefix}information_to_store` (`information_id`, `store_id`) VALUES (3, 0)"
	);

	$meta_pt = 'Política de Privacidade da MIIGTOOLS conforme a LGPD (Lei 13.709/2018): dados coletados, finalidades, bases legais, cookies, compartilhamento e direitos do titular.';
	$meta_en = 'MIIGTOOLS Privacy Policy under Brazil LGPD: data collected, purposes, legal bases, cookies, sharing and data subject rights.';
	$kw_pt = 'privacidade, LGPD, proteção de dados, cookies, MIIGTOOLS';
	$kw_en = 'privacy, LGPD, data protection, cookies, MIIGTOOLS';

	$stmt = $mysqli->prepare(
		"INSERT INTO `{$info_table}` (`information_id`, `language_id`, `title`, `description`, `meta_title`, `meta_description`, `meta_keyword`)
		VALUES (3, ?, ?, ?, ?, ?, ?)
		ON DUPLICATE KEY UPDATE
			`title` = VALUES(`title`),
			`description` = VALUES(`description`),
			`meta_title` = VALUES(`meta_title`),
			`meta_description` = VALUES(`meta_description`),
			`meta_keyword` = VALUES(`meta_keyword`)"
	);

	if (!$stmt) {
		echo "bootstrap-db: falha ao preparar política de privacidade\n";

		return;
	}

	$lang_id = 2;
	$title = 'Política de Privacidade';
	$meta_title = 'Política de Privacidade | MIIGTOOLS';
	$stmt->bind_param('isssss', $lang_id, $title, $pt_html, $meta_title, $meta_pt, $kw_pt);
	$stmt->execute();

	$lang_id = 1;
	$title = 'Privacy Policy';
	$meta_title = 'Privacy Policy | MIIGTOOLS';
	$stmt->bind_param('isssss', $lang_id, $title, $en_html, $meta_title, $meta_en, $kw_en);
	$stmt->execute();
	$stmt->close();

	bootstrap_setting($mysqli, $db_prefix . 'setting', 'config', 'config_cookie_id', '3', 0);
	bootstrap_setting($mysqli, $db_prefix . 'setting', 'config', 'config_gdpr_id', '0', 0);

	echo "bootstrap-db: Política de Privacidade LGPD completa (id 3, " . strlen($pt_html) . " bytes pt-br)\n";
}

function write_php_config(string $path, array $defines): void {
	$lines = ['<?php'];

	foreach ($defines as $name => $value) {
		$lines[] = 'define(' . var_export($name, true) . ', ' . var_export($value, true) . ');';
	}

	file_put_contents($path, implode("\n", $lines) . "\n");
}

$dir_opencart = rtrim(env('DIR_OPENCART', '/var/www/html/'), '/') . '/';
$dir_storage = rtrim(env('DIR_STORAGE', '/storage/'), '/') . '/';

$http_host = env('RAILWAY_PUBLIC_DOMAIN', env('OPENCART_HTTP_HOST', 'localhost'));
$http_scheme = env('OPENCART_HTTP_SCHEME', 'https');
$http_server = $http_scheme . '://' . $http_host . '/';
$http_admin = $http_scheme . '://' . $http_host . '/admin/';

$db = [
	'DB_DRIVER'   => 'mysqli',
	'DB_HOSTNAME' => env('DB_HOSTNAME', env('MYSQLHOST', '')),
	'DB_USERNAME' => env('DB_USERNAME', env('MYSQLUSER', 'root')),
	'DB_PASSWORD' => env('DB_PASSWORD', env('MYSQLPASSWORD', '')),
	'DB_DATABASE' => env('DB_DATABASE', env('MYSQLDATABASE', 'railway')),
	'DB_PORT'     => env('DB_PORT', env('MYSQLPORT', '3306')),
	'DB_PREFIX'   => env('DB_PREFIX', 'ws_'),
	'DB_SSL_KEY'  => '',
	'DB_SSL_CERT' => '',
	'DB_SSL_CA'   => '',
];

write_php_config($dir_opencart . 'config.php', array_merge([
	'APPLICATION'     => 'Catalog',
	'HTTP_SERVER'     => $http_server,
	'DIR_OPENCART'    => $dir_opencart,
	'DIR_APPLICATION' => $dir_opencart . 'catalog/',
	'DIR_EXTENSION'   => $dir_opencart . 'extension/',
	'DIR_IMAGE'       => $dir_opencart . 'image/',
	'DIR_SYSTEM'      => $dir_opencart . 'system/',
	'DIR_STORAGE'     => $dir_storage,
	'DIR_LANGUAGE'    => $dir_opencart . 'catalog/language/',
	'DIR_TEMPLATE'    => $dir_opencart . 'catalog/view/template/',
	'DIR_CONFIG'      => $dir_opencart . 'system/config/',
	'DIR_CACHE'       => $dir_storage . 'cache/',
	'DIR_DOWNLOAD'    => $dir_storage . 'download/',
	'DIR_LOGS'        => $dir_storage . 'logs/',
	'DIR_SESSION'     => $dir_storage . 'session/',
	'DIR_UPLOAD'      => $dir_storage . 'upload/',
], $db));

write_php_config($dir_opencart . 'admin/config.php', array_merge([
	'APPLICATION'     => 'Admin',
	'HTTP_SERVER'     => $http_admin,
	'HTTP_CATALOG'    => $http_server,
	'DIR_OPENCART'    => $dir_opencart,
	'DIR_APPLICATION' => $dir_opencart . 'admin/',
	'DIR_EXTENSION'   => $dir_opencart . 'extension/',
	'DIR_IMAGE'       => $dir_opencart . 'image/',
	'DIR_SYSTEM'      => $dir_opencart . 'system/',
	'DIR_CATALOG'     => $dir_opencart . 'catalog/',
	'DIR_STORAGE'     => $dir_storage,
	'DIR_LANGUAGE'    => $dir_opencart . 'admin/language/',
	'DIR_TEMPLATE'    => $dir_opencart . 'admin/view/template/',
	'DIR_CONFIG'      => $dir_opencart . 'system/config/',
	'DIR_CACHE'       => $dir_storage . 'cache/',
	'DIR_DOWNLOAD'    => $dir_storage . 'download/',
	'DIR_LOGS'        => $dir_storage . 'logs/',
	'DIR_SESSION'     => $dir_storage . 'session/',
	'DIR_UPLOAD'      => $dir_storage . 'upload/',
	'OPENCART_SERVER' => 'https://www.opencart.com/',
], $db));

echo "config.php gerado\n";

// ── Update store URL in the database ─────────────────────────────────────────
// OpenCart caches the store URL in ws_setting. If it still points to localhost
// the browser will refuse to load CSS/JS/images. We update it here, right after
// writing the PHP config files, so every deploy stays in sync automatically.

$db_host = $db['DB_HOSTNAME'];
$db_user = $db['DB_USERNAME'];
$db_pass = $db['DB_PASSWORD'];
$db_name = $db['DB_DATABASE'];
$db_port = (int) $db['DB_PORT'];
$db_prefix = $db['DB_PREFIX'];

if ($db_host === '') {
	echo "update-store-url: DB_HOSTNAME não definido, pulando atualização do banco.\n";
} else {
	$mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

	if ($mysqli->connect_errno) {
		echo "update-store-url: não foi possível conectar ao banco (" . $mysqli->connect_error . "), pulando.\n";
	} else {
		$table = $db_prefix . 'setting';

		$updates = [
			'config_url'    => $http_server,
			'config_secure' => $http_server,
		];

		foreach ($updates as $key => $value) {
			bootstrap_setting($mysqli, $table, 'config', $key, $value, 0);
			echo "update-store-url: {$key} → {$value}\n";
		}

		// Enforce stock visibility and block checkout over stock
		$stock_settings = [
			'config_stock_display'  => '1',
			'config_stock_warning'  => '1',
			'config_stock_checkout' => '0',
		];

		foreach ($stock_settings as $key => $value) {
			bootstrap_setting($mysqli, $table, 'config', $key, $value, 0);
			echo "bootstrap-db: {$key} → {$value}\n";
		}

		// Garante grupo de clientes para cadastro (evita "tipo de conta não permitido").
		$customer_group_updates = [
			'config_customer_group_id'      => ['1', 0],
			'config_customer_group_display' => ['["1"]', 1],
		];

		foreach ($customer_group_updates as $key => [$value, $serialized]) {
			$stmt = $mysqli->prepare(
				"UPDATE `{$table}` SET `value` = ?, `serialized` = ? WHERE `key` = ? AND `store_id` = 0"
			);

			if ($stmt) {
				$stmt->bind_param('sis', $value, $serialized, $key);
				$stmt->execute();
				$affected = $stmt->affected_rows;
				$stmt->close();

				if ($affected === 0) {
					$insert = $mysqli->prepare(
						"INSERT INTO `{$table}` (`store_id`, `code`, `key`, `value`, `serialized`) VALUES (0, 'config', ?, ?, ?)"
					);

					if ($insert) {
						$insert->bind_param('ssi', $key, $value, $serialized);
						$insert->execute();
						$insert->close();
						echo "bootstrap-db: {$key} inserido\n";
					}
				} else {
					echo "bootstrap-db: {$key} atualizado\n";
				}
			}
		}

		$config_description = '{"1":{"meta_title":"MIIGTOOLS — machining tools","meta_description":"Cutting tools for machining: drills, end mills, taps, reamers, bits, tool bits and collets. Nationwide shipping in Brazil.","meta_keyword":"machining, drill, end mill, tap, reamer, bits, collet, cutting tools"},"2":{"meta_title":"MIIGTOOLS — ferramentas para usinagem","meta_description":"Ferramentas para usinagem e corte de metal: brocas, fresas, machos, alargadores, bits, bedames e pinças. Envio para todo o Brasil.","meta_keyword":"usinagem, broca, fresa, macho, alargador, bits, bedame, pinça, ferramenta de corte"}}';

		$stmt = $mysqli->prepare(
			"UPDATE `{$table}` SET `value` = ?, `serialized` = 1 WHERE `key` = 'config_description' AND `store_id` = 0"
		);

		if ($stmt) {
			$stmt->bind_param('s', $config_description);
			$stmt->execute();
			$stmt->close();
			echo "bootstrap-db: config_description atualizado\n";
		}

		$category_table = $db_prefix . 'category_description';
		$category_updates = [
			[59, 1, 'Machining tools', '<p>Drills, end mills, taps, reamers, bits, tool bits, collets and accessories for machining and industrial maintenance.</p>', 'Machining tools | MIIGTOOLS', 'Drills, end mills, taps, reamers, bits, tool bits and collets for machining. Nationwide shipping in Brazil.', 'machining, drill, end mill, tap, reamer, bits, collet, cutting tools'],
			[59, 2, 'Ferramentas para usinagem', '<p>Brocas, fresas, machos, alargadores, bits, bedames, pinças e acessórios para usinagem e manutenção industrial.</p>', 'Ferramentas para usinagem | MIIGTOOLS', 'Brocas, fresas, machos, alargadores, bits, bedames e pinças para usinagem. Envio para todo o Brasil.', 'usinagem, broca, fresa, macho, alargador, bits, bedame, pinça, ferramenta de corte'],
		];

		$cat_stmt = $mysqli->prepare(
			"UPDATE `{$category_table}` SET `name` = ?, `description` = ?, `meta_title` = ?, `meta_description` = ?, `meta_keyword` = ? WHERE `category_id` = ? AND `language_id` = ?"
		);

		if ($cat_stmt) {
			foreach ($category_updates as [$category_id, $language_id, $name, $description, $meta_title, $meta_description, $meta_keyword]) {
				$cat_stmt->bind_param('sssssii', $name, $description, $meta_title, $meta_description, $meta_keyword, $category_id, $language_id);
				$cat_stmt->execute();
			}

			$cat_stmt->close();
			echo "bootstrap-db: categoria 59 atualizada\n";
		}

		$info_table = $db_prefix . 'information_description';
		$info_updates = [
			[5, 1, '<p><strong>MIIGTOOLS</strong> is an online store specialized in cutting tools for machining and industrial maintenance. We serve machine shops, tool rooms and manufacturers that need reliable products every day.</p>
<p>We offer tools built to international standards, in cobalt high-speed steel for greater heat and wear resistance.</p>
<h3>Our mission</h3>
<p>To deliver efficient solutions and products that exceed our customers\' expectations, with the convenience of online shopping and close support.</p>
<p>Our goal is to make communication easy! Contact us on WhatsApp for news and special offers.</p>'],
			[5, 2, '<p><strong>MIIGTOOLS</strong> é uma loja online especializada em ferramentas de corte para usinagem e manutenção industrial. Atendemos oficinas mecânicas, ferramentarias e indústrias que precisam de produtos confiáveis no dia a dia.</p>
<p>Trabalhamos com ferramentas fabricadas conforme normas internacionais, em aço rápido e em aço com cobalto, oferecendo maior resistência a altas temperaturas e ao desgaste.</p>
<h3>Nossa missão</h3>
<p>Oferecer soluções eficientes e produtos que superam as expectativas dos nossos clientes, com praticidade de compra online e atendimento próximo.</p>
<p>Nosso objetivo é facilitar a comunicação com você! Entre em contato pelo WhatsApp e fique por dentro das novidades e condições especiais.</p>'],
		];

		$info_stmt = $mysqli->prepare(
			"UPDATE `{$info_table}` SET `description` = ? WHERE `information_id` = ? AND `language_id` = ?"
		);

		if ($info_stmt) {
			foreach ($info_updates as [$information_id, $language_id, $description]) {
				$info_stmt->bind_param('sii', $description, $information_id, $language_id);
				$info_stmt->execute();
			}

			$info_stmt->close();
			echo "bootstrap-db: página Sobre (id 5) atualizada\n";
		}

		// Descrições dos grupos de clientes em pt-br (language_id 2) — dump só tinha inglês.
		$cgd_table = $db_prefix . 'customer_group_description';
		$group_descriptions = [
			[1, 2, 'Padrão', 'Grupo de clientes padrão'],
			[2, 2, 'Varejo', 'Clientes de varejo'],
			[3, 2, 'Atacado', 'Clientes atacado'],
		];

		$cgd_stmt = $mysqli->prepare(
			"INSERT IGNORE INTO `{$cgd_table}` (`customer_group_id`, `language_id`, `name`, `description`) VALUES (?, ?, ?, ?)"
		);

		if ($cgd_stmt) {
			foreach ($group_descriptions as [$group_id, $language_id, $name, $description]) {
				$cgd_stmt->bind_param('iiss', $group_id, $language_id, $name, $description);
				$cgd_stmt->execute();
			}

			$cgd_stmt->close();
			echo "bootstrap-db: grupos de clientes pt-br garantidos\n";
		}

		$mysqli->query(
			"UPDATE `{$db_prefix}country_description` SET `name` = 'Brasil' WHERE `country_id` = 30 AND `language_id` = 2"
		);
		echo "bootstrap-db: país Brasil (pt-br)\n";

		$address_format = "{firstname} {lastname}\n{address_1}\n{address_2}\n{company}\n{city} - {zone_code}\nCEP {postcode}\n{country}";
		$format_stmt = $mysqli->prepare(
			"UPDATE `{$db_prefix}address_format` SET `address_format` = ? WHERE `address_format_id` = 1"
		);

		if ($format_stmt) {
			$format_stmt->bind_param('s', $address_format);
			$format_stmt->execute();
			$format_stmt->close();
			echo "bootstrap-db: formato de endereço BR\n";
		}

		$info_pages = [
			[2, 'Termos e Condições', '<p>Termos e condições de uso da loja MIIGTOOLS. Ao comprar, você concorda com as regras de pagamento, entrega e trocas descritas nesta página.</p>', 'Termos e Condições | MIIGTOOLS'],
			[4, 'Informações de Entrega', '<p>Enviamos para todo o Brasil. O prazo e o valor do frete são calculados no checkout conforme o CEP e o peso dos produtos.</p>', 'Entrega | MIIGTOOLS'],
			[6, 'Trocas e Devoluções', '<p>Todos os nossos produtos possuem garantia fornecida diretamente pelos fabricantes, com prazos e condições que podem variar de acordo com cada item e estarão informados na página do produto. Em caso de devolução, o produto deverá ser enviado em sua embalagem original, acompanhado de todos os acessórios e sem sinais de mau uso. Caso seja constatada utilização inadequada, os custos envolvidos poderão ficar sob responsabilidade do comprador. Para mais informações sobre garantias, trocas ou devoluções, entre em contato conosco por um de nossos canais de atendimento.</p>', 'Trocas e Devoluções | MIIGTOOLS'],
		];

		$info_insert = $mysqli->prepare(
			"INSERT INTO `{$info_table}` (`information_id`, `language_id`, `title`, `description`, `meta_title`, `meta_description`, `meta_keyword`)
			VALUES (?, 2, ?, ?, ?, '', '')
			ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `description` = VALUES(`description`), `meta_title` = VALUES(`meta_title`)"
		);

		if ($info_insert) {
			foreach ($info_pages as [$information_id, $title, $description, $meta_title]) {
				$info_insert->bind_param('isss', $information_id, $title, $description, $meta_title);
				$info_insert->execute();
			}

			$info_insert->close();
			echo "bootstrap-db: páginas institucionais pt-br (2, 4, 6)\n";
		}

		bootstrap_privacy_policy($mysqli, $db_prefix);

		bootstrap_setting($mysqli, $table, 'config', 'config_return_id', '6', 0);
		echo "bootstrap-db: config_return_id → 6 (Trocas e Devoluções)\n";

		$mysqli->query(
			"INSERT IGNORE INTO `{$db_prefix}information` (`information_id`, `sort_order`, `status`) VALUES (6, 5, 1)"
		);
		$mysqli->query(
			"INSERT IGNORE INTO `{$db_prefix}information_to_store` (`information_id`, `store_id`) VALUES (6, 0)"
		);
		$mysqli->query(
			"INSERT INTO `{$info_table}` (`information_id`, `language_id`, `title`, `description`, `meta_title`, `meta_description`, `meta_keyword`)
			VALUES (6, 1, 'Returns &amp; Exchanges', '<p>All products carry manufacturer warranties. Returns must be in original packaging with all accessories and without signs of misuse. Contact us for warranty, exchange or return questions.</p>', 'Returns &amp; Exchanges | MIIGTOOLS', '', '')
			ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `description` = VALUES(`description`), `meta_title` = VALUES(`meta_title`)"
		);
		echo "bootstrap-db: página Trocas e Devoluções (id 6)\n";

		// Landings por aplicação + FAQ técnico
		$app_pages = [
			[10, 'Machos para aço', 'machos-para-aco', '59_63', 'Machos máquina e laminadores para usinagem em aço. Filtre por medida e norma DIN na categoria.', 'Machos para aço | MIIGTOOLS'],
			[11, 'Bits para torno', 'bits-para-torno', '59_60', 'Bits quadrado HSS e cobalto para torno. Compare medidas na matriz do produto.', 'Bits para torno | MIIGTOOLS'],
			[12, 'Pontas rotativas CM', 'pontas-rotativas-cm', '59_68', 'Pontas rotativas standard, tubular e copiadora — cone Morse CM2 a CM5.', 'Pontas rotativas CM | MIIGTOOLS'],
			[13, 'Ferramentas DIN', 'ferramentas-din', '59', 'Catálogo com normas DIN nas fichas e filtros. Ideal para quem compra por especificação.', 'Ferramentas DIN | MIIGTOOLS'],
			[14, 'Alargadores H7', 'alargadores-h7', '59_65', 'Alargadores manuais e de máquina conforme DIN, tolerância H7.', 'Alargadores H7 | MIIGTOOLS'],
			[15, 'Porta-ferramentas', 'porta-ferramentas', '59_62', 'Porta bits, porta bedame e acessórios para fixação no torno.', 'Porta-ferramentas | MIIGTOOLS'],
		];

		foreach ($app_pages as [$iid, $title, $slug, $path, $lead, $meta]) {
			$mysqli->query(
				"INSERT IGNORE INTO `{$db_prefix}information` (`information_id`, `sort_order`, `status`) VALUES ({$iid}, " . (10 + $iid) . ", 1)"
			);
			$mysqli->query(
				"INSERT IGNORE INTO `{$db_prefix}information_to_store` (`information_id`, `store_id`) VALUES ({$iid}, 0)"
			);

			$html = '<p>' . htmlspecialchars($lead, ENT_QUOTES, 'UTF-8') . '</p>'
				. '<p><a href="index.php?route=product/category&amp;language=pt-br&amp;path=' . htmlspecialchars($path, ENT_QUOTES, 'UTF-8') . '">Ver produtos desta aplicação</a></p>'
				. '<p>Prefere ajuda humana? <a href="https://wa.me/551122360122" target="_blank" rel="noopener">Fale no WhatsApp</a>.</p>';

			$stmt = $mysqli->prepare(
				"INSERT INTO `{$info_table}` (`information_id`, `language_id`, `title`, `description`, `meta_title`, `meta_description`, `meta_keyword`)
				 VALUES (?, 2, ?, ?, ?, ?, '')
				 ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `description` = VALUES(`description`), `meta_title` = VALUES(`meta_title`), `meta_description` = VALUES(`meta_description`)"
			);

			if ($stmt) {
				$stmt->bind_param('issss', $iid, $title, $html, $meta, $lead);
				$stmt->execute();
				$stmt->close();
			}

			bootstrap_seo_url($mysqli, $db_prefix, 0, 2, 'information_id', (string) $iid, $slug, 0);
		}

		$faq_html = '<h2>Normas DIN</h2><p>Quando a ficha indica DIN, a ferramenta segue a geometria e tolerâncias da norma citada (ex.: DIN 376 para machos).</p>'
			. '<h2>HSS vs HSS com cobalto</h2><p>HSS (aço rápido) cobre a maioria das operações. Linhas com 10% Co resistem melhor a calor e materiais mais duros.</p>'
			. '<h2>Cone Morse (CM)</h2><p>CM2–CM5 identificam o cone da ponta rotativa ou bucha. Confira o fuso da máquina antes de comprar.</p>'
			. '<h2>Como medir</h2><p>Use a medida da ficha (polegada ou métrica) e a matriz “outras medidas desta linha” no produto para achar o irmão certo.</p>'
			. '<h2>Frete e prazo</h2><p>Informe o CEP na página do produto para ver PAC/SEDEX a partir de Imirim/SP. Pedidos com estoque confirmados até 14h (SP) seguem no mesmo dia útil.</p>';

		$mysqli->query("INSERT IGNORE INTO `{$db_prefix}information` (`information_id`, `sort_order`, `status`) VALUES (16, 20, 1)");
		$mysqli->query("INSERT IGNORE INTO `{$db_prefix}information_to_store` (`information_id`, `store_id`) VALUES (16, 0)");
		$mysqli->query(
			"INSERT INTO `{$info_table}` (`information_id`, `language_id`, `title`, `description`, `meta_title`, `meta_description`, `meta_keyword`)
			 VALUES (16, 2, 'Dúvidas técnicas (FAQ)', '" . $mysqli->real_escape_string($faq_html) . "', 'FAQ técnico | MIIGTOOLS', 'DIN, HSS, cone Morse, frete e como escolher a medida.', '')
			 ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `description` = VALUES(`description`), `meta_title` = VALUES(`meta_title`)"
		);
		bootstrap_seo_url($mysqli, $db_prefix, 0, 2, 'information_id', '16', 'faq-tecnico', 0);
		echo "bootstrap-db: landings por aplicação (10–15) + FAQ técnico (16)\n";

		bootstrap_setting($mysqli, $table, 'config', 'config_seo_url', '1', 0);
		echo "bootstrap-db: config_seo_url → 1\n";

		$seo_routes_pt = [
			['route', 'information/information', 'information', -1],
			['route', 'information/contact', 'contato', -1],
			['route', 'product/category', 'catalogo', -1],
			['route', 'product/manufacturer', 'marcas', -1],
			['route', 'product/product', 'produto', -1],
			['information_id', '1', 'sobre', 0],
			['information_id', '2', 'termos', 0],
			['information_id', '3', 'privacidade', 0],
			['information_id', '4', 'entrega', 0],
			['information_id', '5', 'sobre-miigtools', 0],
			['information_id', '6', 'devolucao', 0],
		];

		foreach ($seo_routes_pt as [$key, $value, $keyword, $sort_order]) {
			bootstrap_seo_url($mysqli, $db_prefix, 0, 2, $key, $value, $keyword, $sort_order);
		}

		bootstrap_seo_url($mysqli, $db_prefix, 0, 1, 'route', 'information/contact', 'contact', -1);
		bootstrap_seo_url($mysqli, $db_prefix, 0, 1, 'information_id', '6', 'returns', 0);

		$used_keywords = [];
		$kw_res = $mysqli->query("SELECT `keyword` FROM `{$db_prefix}seo_url` WHERE `store_id` = 0");

		if ($kw_res) {
			while ($row = $kw_res->fetch_assoc()) {
				$used_keywords[$row['keyword']] = true;
			}
		}

		$prod_res = $mysqli->query(
			"SELECT pd.`product_id`, pd.`name`
			FROM `{$db_prefix}product_description` pd
			INNER JOIN `{$db_prefix}product` p ON p.`product_id` = pd.`product_id`
			WHERE pd.`language_id` = 2 AND p.`status` = 1"
		);

		$product_seo_count = 0;

		if ($prod_res) {
			while ($row = $prod_res->fetch_assoc()) {
				$product_id = (int) $row['product_id'];
				$check = $mysqli->query(
					"SELECT `seo_url_id` FROM `{$db_prefix}seo_url`
					WHERE `store_id` = 0 AND `language_id` = 2 AND `key` = 'product_id' AND `value` = '{$product_id}' LIMIT 1"
				);

				if ($check && $check->num_rows > 0) {
					continue;
				}

				$base = miig_seo_slug((string) $row['name']);
				$keyword = $base;
				$n = 2;

				while (isset($used_keywords[$keyword])) {
					$keyword = $base . '-' . $product_id;

					if (!isset($used_keywords[$keyword])) {
						break;
					}

					$keyword = $base . '-' . $n;
					$n++;
				}

				$used_keywords[$keyword] = true;
				bootstrap_seo_url($mysqli, $db_prefix, 0, 2, 'product_id', (string) $product_id, $keyword, 0);
				$product_seo_count++;
			}
		}

		$cat_res = $mysqli->query(
			"SELECT cd.`category_id`, cd.`name`
			FROM `{$db_prefix}category_description` cd
			INNER JOIN `{$db_prefix}category` c ON c.`category_id` = cd.`category_id`
			WHERE cd.`language_id` = 2 AND c.`status` = 1"
		);

		$category_seo_count = 0;

		if ($cat_res) {
			while ($row = $cat_res->fetch_assoc()) {
				$category_id = (int) $row['category_id'];
				$path_value = (string) $category_id;
				$check = $mysqli->query(
					"SELECT `seo_url_id` FROM `{$db_prefix}seo_url`
					WHERE `store_id` = 0 AND `language_id` = 2 AND `key` = 'path' AND `value` = '{$path_value}' LIMIT 1"
				);

				if ($check && $check->num_rows > 0) {
					continue;
				}

				$base = miig_seo_slug((string) $row['name']);
				$keyword = $base;
				$n = 2;

				while (isset($used_keywords[$keyword])) {
					$keyword = $base . '-' . $category_id;

					if (!isset($used_keywords[$keyword])) {
						break;
					}

					$keyword = $base . '-' . $n;
					$n++;
				}

				$used_keywords[$keyword] = true;
				bootstrap_seo_url($mysqli, $db_prefix, 0, 2, 'path', $path_value, $keyword, 0);
				$category_seo_count++;
			}
		}

		echo "bootstrap-db: SEO keywords pt-br (produtos novos: {$product_seo_count}, categorias novas: {$category_seo_count})\n";

		$specs_file = $dir_opencart . 'system/miigtools/bootstrap_specs.php';

		if (is_file($specs_file)) {
			require_once $specs_file;
			bootstrap_catalog_specs($mysqli, $db_prefix);
		}

		// Home: destaques com preço no content_top; remove slideshow de foto solta.
		$home_layout_id = 0;
		$layout_res = $mysqli->query(
			"SELECT `layout_id` FROM `{$db_prefix}layout_route` WHERE `route` = 'common/home' AND `store_id` = 0 LIMIT 1"
		);

		if ($layout_res && ($layout_row = $layout_res->fetch_assoc())) {
			$home_layout_id = (int) $layout_row['layout_id'];
		}

		if ($home_layout_id > 0) {
			$mysqli->query(
				"DELETE FROM `{$db_prefix}layout_module`
				WHERE `layout_id` = {$home_layout_id}
				AND `code` LIKE 'opencart.banner.%'"
			);

			$mysqli->query(
				"DELETE FROM `{$db_prefix}layout_module`
				WHERE `layout_id` = {$home_layout_id}
				AND `position` = 'content_top'
				AND `code` <> 'opencart.featured.2'"
			);

			$featured_exists = $mysqli->query(
				"SELECT `layout_module_id` FROM `{$db_prefix}layout_module`
				WHERE `layout_id` = {$home_layout_id} AND `code` = 'opencart.featured.2' LIMIT 1"
			);

			if ($featured_exists && $featured_exists->num_rows > 0) {
				$mysqli->query(
					"UPDATE `{$db_prefix}layout_module`
					SET `position` = 'content_top', `sort_order` = 0
					WHERE `layout_id` = {$home_layout_id} AND `code` = 'opencart.featured.2'"
				);
			} else {
				$mysqli->query(
					"INSERT INTO `{$db_prefix}layout_module` (`layout_id`, `code`, `position`, `sort_order`)
					VALUES ({$home_layout_id}, 'opencart.featured.2', 'content_top', 0)"
				);
			}

			echo "bootstrap-db: home layout → featured no content_top (sem banner)\n";
		}

		$featured_setting = json_encode([
			'name'         => 'Destaques',
			'product_name' => '',
			'product'      => ['2', '3', '5', '7', '28', '198', '201', '186'],
			'axis'         => 'horizontal',
			'limit'        => '8',
			'width'        => '400',
			'height'       => '400',
			'status'       => '1',
		], JSON_UNESCAPED_UNICODE);

		$featured_stmt = $mysqli->prepare(
			"UPDATE `{$db_prefix}module` SET `setting` = ? WHERE `module_id` = 2 AND `code` = 'opencart.featured'"
		);

		if ($featured_stmt) {
			$featured_stmt->bind_param('s', $featured_setting);
			$featured_stmt->execute();
			$featured_stmt->close();
			echo "bootstrap-db: módulo Featured atualizado (produtos com preço)\n";
		}

		$mysqli->query(
			"INSERT IGNORE INTO `{$db_prefix}extension` (`extension`, `type`, `code`) VALUES ('opencart', 'payment', 'bank_transfer')"
		);

		$bank_pt = "Titular: MIIGTOOLS\nBanco: [atualize no admin]\nAgência: [atualize no admin]\nConta: [atualize no admin]\nPIX: [atualize no admin]";
		$bank_en = "Account holder: MIIGTOOLS\nBank: [update in admin]\nBranch: [update]\nAccount: [update]\nPIX: [update]";

		$payment_bootstrap = [
			['payment_cod', 'payment_cod_status', '0'],
			['payment_cod', 'payment_cod_sort_order', '1'],
			['payment_cod', 'payment_cod_order_status_id', '1'],
			['payment_cod', 'payment_cod_geo_zone_id', '0'],
			['payment_bank_transfer', 'payment_bank_transfer_status', '1'],
			['payment_bank_transfer', 'payment_bank_transfer_sort_order', '1'],
			['payment_bank_transfer', 'payment_bank_transfer_order_status_id', '1'],
			['payment_bank_transfer', 'payment_bank_transfer_geo_zone_id', '0'],
			['payment_bank_transfer', 'payment_bank_transfer_bank_2', $bank_pt],
			['payment_bank_transfer', 'payment_bank_transfer_bank_1', $bank_en],
		];

		foreach ($payment_bootstrap as [$code, $key, $value]) {
			bootstrap_setting($mysqli, $table, $code, $key, $value, 0);
		}

		echo "bootstrap-db: pagamentos checkout (transferência bancária; COD desativado)\n";

		// Frete: desativa flat R$5 e ativa PAC/SEDEX (origem Imirim/SP)
		$mysqli->query(
			"INSERT IGNORE INTO `{$db_prefix}extension` (`extension`, `type`, `code`) VALUES ('miigtools', 'shipping', 'correios')"
		);
		bootstrap_setting($mysqli, $table, 'shipping_flat', 'shipping_flat_status', '0', 0);
		bootstrap_setting($mysqli, $table, 'shipping_correios', 'shipping_correios_status', '1', 0);
		bootstrap_setting($mysqli, $table, 'shipping_correios', 'shipping_correios_sort_order', '1', 0);
		bootstrap_setting($mysqli, $table, 'shipping_correios', 'shipping_correios_tax_class_id', '0', 0);
		bootstrap_setting($mysqli, $table, 'config', 'config_postcode', '02465-000', 0);
		echo "bootstrap-db: frete Correios PAC/SEDEX (origem Imirim/SP); flat desativado\n";

		// Telefone da loja = mesmo número do WhatsApp comercial
		bootstrap_setting($mysqli, $table, 'config', 'config_telephone', '(11) 2236-0122', 0);
		echo "bootstrap-db: config_telephone = (11) 2236-0122\n";

		// SKUs sem preço saem da vitrine (não inventar valor)
		$zero_res = $mysqli->query(
			"UPDATE `{$db_prefix}product` SET `status` = 0 WHERE `price` <= 0 AND `status` = 1"
		);
		$zero_n = $mysqli->affected_rows;
		echo "bootstrap-db: produtos preço zero desativados ({$zero_n})\n";

		// Situações de pedido (evita falha ao salvar Configurações)
		$processing_ids = [];
		$complete_ids = [];
		// OpenCart 4: nome fica em order_status (por language_id), sem tabela description
		$status_q = $mysqli->query(
			"SELECT order_status_id, name FROM `{$db_prefix}order_status`"
		);

		if ($status_q) {
			while ($s = $status_q->fetch_assoc()) {
				$name = mb_strtolower((string) $s['name']);
				$id = (int) $s['order_status_id'];

				if (str_contains($name, 'process') || str_contains($name, 'pago') || str_contains($name, 'enviado') || str_contains($name, 'shipped')) {
					$processing_ids[] = (string) $id;
				}

				if (str_contains($name, 'completo') || str_contains($name, 'complete') || str_contains($name, 'entregue') || str_contains($name, 'finaliz')) {
					$complete_ids[] = (string) $id;
				}
			}
		}

		if (!$processing_ids) {
			$processing_ids = ['2'];
		}

		if (!$complete_ids) {
			$complete_ids = ['5'];
		}

		$proc_json = json_encode(array_values(array_unique($processing_ids)));
		$comp_json = json_encode(array_values(array_unique($complete_ids)));

		// Só preenche se estiver vazio (não sobrescreve escolha do admin)
		$proc_check = $mysqli->query("SELECT value FROM `{$table}` WHERE `key` = 'config_processing_status' AND store_id = 0 LIMIT 1");
		$proc_row = $proc_check ? $proc_check->fetch_assoc() : null;
		$proc_val = $proc_row['value'] ?? '';

		if ($proc_val === '' || $proc_val === '[]' || $proc_val === 'null') {
			bootstrap_setting($mysqli, $table, 'config', 'config_processing_status', $proc_json, 1);
		}

		$comp_check = $mysqli->query("SELECT value FROM `{$table}` WHERE `key` = 'config_complete_status' AND store_id = 0 LIMIT 1");
		$comp_row = $comp_check ? $comp_check->fetch_assoc() : null;
		$comp_val = $comp_row['value'] ?? '';

		if ($comp_val === '' || $comp_val === '[]' || $comp_val === 'null') {
			bootstrap_setting($mysqli, $table, 'config', 'config_complete_status', $comp_json, 1);
		}

		echo "bootstrap-db: situações de pedido processando/finalizado garantidas\n";

		$mp_token = '';
		$mp_test = '';

		$mp_res = $mysqli->query(
			"SELECT `value` FROM `{$table}` WHERE `key` = 'payment_mercadopago_access_token' AND `store_id` = 0 LIMIT 1"
		);

		if ($mp_res && ($row = $mp_res->fetch_assoc())) {
			$mp_token = trim((string) $row['value']);
		}

		$mp_test_res = $mysqli->query(
			"SELECT `value` FROM `{$table}` WHERE `key` = 'payment_mercadopago_access_token_test' AND `store_id` = 0 LIMIT 1"
		);

		if ($mp_test_res && ($row = $mp_test_res->fetch_assoc())) {
			$mp_test = trim((string) $row['value']);
		}

		if ($mp_token === '' && $mp_test === '') {
			bootstrap_setting($mysqli, $table, 'payment_mercadopago', 'payment_mercadopago_status', '0', 0);
			echo "bootstrap-db: Mercado Pago desativado (sem credenciais)\n";
		}

		$mysqli->close();
	}
}
