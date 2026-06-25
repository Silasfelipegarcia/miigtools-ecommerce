#!/usr/bin/env php
<?php
function env(string $key, string $default = ''): string {
	$value = getenv($key);

	return $value !== false ? $value : $default;
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
			$stmt = $mysqli->prepare(
				"UPDATE `{$table}` SET `value` = ? WHERE `key` = ? AND `store_id` = 0"
			);

			if ($stmt) {
				$stmt->bind_param('ss', $value, $key);
				$stmt->execute();
				$affected = $stmt->affected_rows;
				$stmt->close();
				echo "update-store-url: {$key} → {$value} ({$affected} linha(s) afetada(s))\n";
			} else {
				echo "update-store-url: falha ao preparar statement para {$key}: " . $mysqli->error . "\n";
			}
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
<p>Trabalhamos com ferramentas fabricadas conforme normas internacionais, em aço rápido com cobalto, oferecendo maior resistência a altas temperaturas e ao desgaste.</p>
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

		$mysqli->close();
	}
}
