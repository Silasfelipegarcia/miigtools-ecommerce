<?php
/**
 * SEO bootstrap: rich application landings, meta fill, cross-ref codes, sitemap route.
 *
 * @param mysqli $mysqli
 * @param string $db_prefix
 */
function bootstrap_seo_growth(mysqli $mysqli, string $db_prefix): void {
	$info_table = $db_prefix . 'information_description';
	$lang_pt = 2;

	bootstrap_seo_url($mysqli, $db_prefix, 0, $lang_pt, 'route', 'information/sitemap_xml', 'sitemap.xml', -1);
	echo "bootstrap-seo: sitemap.xml SEO keyword\n";

	$app_pages = [
		[
			10,
			'Machos para aço',
			'machos-para-aco',
			'59_63',
			'Machos máquina e laminadores para usinagem em aço. Filtre por medida e norma DIN.',
			'Machos para aço | DIN e medidas | MIIGTOOLS',
			[
				'quando' => 'Use machos máquina ou laminadores quando precisar abrir rosca interna em aço carbono e ligas comuns. Em aços mais duros ou produção contínua, prefira linhas HSS com cobalto.',
				'normas' => 'DIN 371 (máquinas curtos), DIN 376 (máquinas longos) e variantes laminadoras. Confira passo e tipo de rosca (métrica/UNC) na ficha.',
				'material' => 'HSS cobre a maioria das operações. ~10% Co melhora resistência a calor e vida útil em aços mais abrasivos.',
				'erros' => 'Comprar só pela medida em mm sem conferir o passo; ignorar o tipo de haste; usar macho manual em centro de usinagem sem o suporte adequado.',
				'faq'   => [
					['Qual DIN escolher?', 'Para furos passantes profundos costuma-se DIN 376; furos curtos ou cegos, avalie DIN 371 e a profundidade útil na ficha.'],
					['Macho laminador ou corte?', 'Laminador deforma o material (melhor em aços dúcteis e produção); corte remove cavaco (mais versátil em materiais diversos).'],
				],
			],
		],
		[
			11,
			'Bits para torno',
			'bits-para-torno',
			'59_60',
			'Bits quadrado HSS e cobalto para torno. Compare medidas na matriz do produto.',
			'Bits para torno HSS e cobalto | MIIGTOOLS',
			[
				'quando' => 'Bits (ferramentas de corte soldadas ou sólidas de seção quadrada) são o padrão em torno convencional e ferramentaria para desbaste e acabamento.',
				'normas' => 'Seções comuns em polegadas e métricas; ângulos e quebras de aresta variam por aplicação. Use a matriz “outras medidas” no produto para achar o irmão certo.',
				'material' => 'HSS para uso geral; cobalto quando a peça gera mais calor ou o material é mais resistente.',
				'erros' => 'Escolher bit sem conferir a seção da ferramenta e o suporte/porta-ferramenta; forçar avanço sem refrigeração adequada.',
				'faq'   => [
					['Como achar a medida certa?', 'Abra o produto da linha e use a tabela de família (medida × preço × estoque) para selecionar a seção desejada.'],
					['Preciso de porta-bit?', 'Sim — a fixação correta no carro do torno evita vibração e quebra precoce. Veja também a landing de porta-ferramentas.'],
				],
			],
		],
		[
			12,
			'Pontas rotativas CM',
			'pontas-rotativas-cm',
			'59_68',
			'Pontas rotativas standard, tubular e copiadora — cone Morse CM2 a CM5.',
			'Pontas rotativas CM2–CM5 | MIIGTOOLS',
			[
				'quando' => 'Apoiam a peça no cabeçote móvel em torneamento. Escolha standard, tubular ou copiadora conforme o tipo de trabalho e o furo/centro da peça.',
				'normas' => 'Cone Morse CM2 a CM5 deve bater com o fuso do cabeçote móvel. Não force cones diferentes.',
				'material' => 'Corpo e rolamentos dimensionados para carga axial típica de usinagem; confira a ficha para capacidade e tipo.',
				'erros' => 'Comprar CM errado; usar ponta danificada (folga) que marca a peça; sobrecarregar ponta leve em desbaste pesado.',
				'faq'   => [
					['Como sei o CM da máquina?', 'Consta no manual do torno ou na marcação do furo cônico do cabeçote móvel. Em dúvida, meça com gabarito ou envie foto no WhatsApp.'],
					['Tubular vs standard?', 'Tubular passa barra/tubo; standard apoia centro cheio; copiadora segue contornos em operações específicas.'],
				],
			],
		],
		[
			13,
			'Ferramentas DIN',
			'ferramentas-din',
			'59',
			'Catálogo com normas DIN nas fichas e filtros. Ideal para quem compra por especificação.',
			'Ferramentas com norma DIN | MIIGTOOLS',
			[
				'quando' => 'Quando a engenharia ou o processo exige ferramenta conforme norma DIN (geometria, tolerância e identificação padronizada).',
				'normas' => 'As fichas e filtros da MIIGTOOLS destacam DIN quando aplicável (ex.: machos, alargadores). Use os filtros paramétricos nas categorias.',
				'material' => 'Depende da família: HSS, HSS-Co, metal duro em linhas específicas. Sempre confirme material e revestimento na ficha.',
				'erros' => 'Buscar só por marca e ignorar a norma pedida no desenho; misturar tolerâncias H7/H8 sem conferir o furo.',
				'faq'   => [
					['Não acho a norma na busca?', 'Tente o número DIN + família (ex.: “DIN 376 macho”) ou abra a categoria e use os filtros.'],
					['Posso pedir equivalente?', 'Sim — informe o código ou norma no WhatsApp. Códigos equivalentes também entram na busca quando cadastrados.'],
				],
			],
		],
		[
			14,
			'Alargadores H7',
			'alargadores-h7',
			'59_65',
			'Alargadores manuais e de máquina conforme DIN, tolerância H7.',
			'Alargadores H7 | MIIGTOOLS',
			[
				'quando' => 'Para ajustar furos à tolerância H7 após furação, com alargador manual ou de máquina conforme o setup.',
				'normas' => 'Alargadores DIN com diâmetro e tolerância indicados na ficha. Confira se é manual (cabo) ou máquina (haste).',
				'material' => 'HSS e variantes conforme linha. Material da peça e refrigeração influenciam acabamento e vida útil.',
				'erros' => 'Usar diâmetro errado para a tolerância desejada; alargar em excesso de material de uma vez; velocidade inadequada.',
				'faq'   => [
					['H7 significa o quê?', 'Faixa de tolerância do furo. O alargador é escolhido para entregar o diâmetro na classe H7 especificada.'],
					['Manual ou máquina?', 'Manual para ajuste em bancada/montagem; máquina para produção no torno/fresadora com haste adequada.'],
				],
			],
		],
		[
			15,
			'Porta-ferramentas',
			'porta-ferramentas',
			'59_62',
			'Porta bits, porta bedame e acessórios para fixação no torno.',
			'Porta-ferramentas para torno | MIIGTOOLS',
			[
				'quando' => 'Para fixar bits, bedames e ferramentas de corte com rigidez no carro do torno, reduzindo vibração e setup.',
				'normas' => 'Compatibilidade com a seção do bit e com o tipo de porta (quadrado, bedame, etc.). Veja medidas na ficha.',
				'material' => 'Corpos metálicos dimensionados para carga de usinagem convencional; confira capacidade na descrição.',
				'erros' => 'Usar porta inadequado à seção do bit; aperto insuficiente; desalinhamento que gera vibração.',
				'faq'   => [
					['Serve para meu bit?', 'Confira a seção do bit (ex.: 3/8", 1/2") e o modelo do porta na ficha ou na matriz de medidas.'],
					['Também tenho bits na loja?', 'Sim — veja a landing Bits para torno e monte o conjunto bit + porta.'],
				],
			],
		],
	];

	$related = [
		10 => [13, 14],
		11 => [15, 13],
		12 => [13],
		13 => [10, 14, 11],
		14 => [13, 10],
		15 => [11, 13],
	];

	$titles = [];

	foreach ($app_pages as $row) {
		$titles[(int)$row[0]] = $row[1];
	}

	foreach ($app_pages as [$iid, $title, $slug, $path, $lead, $meta, $body]) {
		$mysqli->query(
			"INSERT IGNORE INTO `{$db_prefix}information` (`information_id`, `sort_order`, `status`) VALUES ({$iid}, " . (10 + $iid) . ", 1)"
		);
		$mysqli->query(
			"INSERT IGNORE INTO `{$db_prefix}information_to_store` (`information_id`, `store_id`) VALUES ({$iid}, 0)"
		);

		$cat_id = (int)substr(strrchr('_' . $path, '_'), 1);
		$product_links = '';
		$prod_sql = "SELECT `p`.`product_id`, `pd`.`name`, `p`.`model`, `p`.`price`
			FROM `{$db_prefix}product` `p`
			INNER JOIN `{$db_prefix}product_to_category` `p2c` ON (`p`.`product_id` = `p2c`.`product_id`)
			INNER JOIN `{$db_prefix}category_path` `cp` ON (`cp`.`category_id` = `p2c`.`category_id` AND `cp`.`path_id` = {$cat_id})
			INNER JOIN `{$db_prefix}product_description` `pd` ON (`p`.`product_id` = `pd`.`product_id` AND `pd`.`language_id` = {$lang_pt})
			INNER JOIN `{$db_prefix}product_to_store` `p2s` ON (`p`.`product_id` = `p2s`.`product_id` AND `p2s`.`store_id` = 0)
			WHERE `p`.`status` = 1 AND `p`.`price` > 0
			ORDER BY `p`.`viewed` DESC, `p`.`product_id` ASC
			LIMIT 8";
		$prod_res = $mysqli->query($prod_sql);

		if ($prod_res && $prod_res->num_rows > 0) {
			$product_links .= '<h2>Produtos em destaque nesta aplicação</h2><ul>';

			while ($p = $prod_res->fetch_assoc()) {
				$pid = (int)$p['product_id'];
				$pname = htmlspecialchars((string)$p['name'], ENT_QUOTES, 'UTF-8');
				$pmodel = htmlspecialchars((string)$p['model'], ENT_QUOTES, 'UTF-8');
				$product_links .= '<li><a href="index.php?route=product/product&amp;language=pt-br&amp;product_id=' . $pid . '">' . $pname . '</a> <small>(' . $pmodel . ')</small></li>';
			}

			$product_links .= '</ul>';
		}

		$rel_html = '';

		if (!empty($related[$iid])) {
			$rel_html .= '<h2>Veja também</h2><ul>';

			foreach ($related[$iid] as $rid) {
				$rname = htmlspecialchars($titles[$rid] ?? ('Aplicação ' . $rid), ENT_QUOTES, 'UTF-8');
				$rel_html .= '<li><a href="index.php?route=information/information&amp;language=pt-br&amp;information_id=' . (int)$rid . '">' . $rname . '</a></li>';
			}

			$rel_html .= '</ul>';
		}

		$faq_html = '<h2>Perguntas frequentes</h2>';

		foreach ($body['faq'] as $faq) {
			$faq_html .= '<h3>' . htmlspecialchars($faq[0], ENT_QUOTES, 'UTF-8') . '</h3><p>' . htmlspecialchars($faq[1], ENT_QUOTES, 'UTF-8') . '</p>';
		}

		$html = '<p><strong>' . htmlspecialchars($lead, ENT_QUOTES, 'UTF-8') . '</strong></p>'
			. '<h2>Quando usar</h2><p>' . htmlspecialchars($body['quando'], ENT_QUOTES, 'UTF-8') . '</p>'
			. '<h2>Normas e especificações</h2><p>' . htmlspecialchars($body['normas'], ENT_QUOTES, 'UTF-8') . '</p>'
			. '<h2>Materiais (HSS / cobalto)</h2><p>' . htmlspecialchars($body['material'], ENT_QUOTES, 'UTF-8') . '</p>'
			. '<h2>Erros comuns na compra</h2><p>' . htmlspecialchars($body['erros'], ENT_QUOTES, 'UTF-8') . '</p>'
			. $product_links
			. $faq_html
			. $rel_html
			. '<p><a class="btn btn-primary" href="index.php?route=product/category&amp;language=pt-br&amp;path=' . htmlspecialchars($path, ENT_QUOTES, 'UTF-8') . '">Ver catálogo desta aplicação</a></p>'
			. '<p>Prefere ajuda humana? <a href="https://wa.me/551122360122" target="_blank" rel="noopener">Fale no WhatsApp</a> com a medida ou norma do desenho.</p>';

		$stmt = $mysqli->prepare(
			"INSERT INTO `{$info_table}` (`information_id`, `language_id`, `title`, `description`, `meta_title`, `meta_description`, `meta_keyword`)
			 VALUES (?, ?, ?, ?, ?, ?, ?)
			 ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `description` = VALUES(`description`), `meta_title` = VALUES(`meta_title`), `meta_description` = VALUES(`meta_description`), `meta_keyword` = VALUES(`meta_keyword`)"
		);

		if ($stmt) {
			$kw = $title . ', DIN, HSS, usinagem, MIIGTOOLS';
			$stmt->bind_param('iisssss', $iid, $lang_pt, $title, $html, $meta, $lead, $kw);
			$stmt->execute();
			$stmt->close();
		}

		bootstrap_seo_url($mysqli, $db_prefix, 0, $lang_pt, 'information_id', (string)$iid, $slug, 0);
	}

	$faq_html = '<h2>Normas DIN</h2><p>Quando a ficha indica DIN, a ferramenta segue a geometria e tolerâncias da norma citada (ex.: DIN 376 para machos).</p>'
		. '<h2>HSS vs HSS com cobalto</h2><p>HSS (aço rápido) cobre a maioria das operações. Linhas com 10% Co resistem melhor a calor e materiais mais duros.</p>'
		. '<h2>Cone Morse (CM)</h2><p>CM2–CM5 identificam o cone da ponta rotativa ou bucha. Confira o fuso da máquina antes de comprar.</p>'
		. '<h2>Como medir</h2><p>Use a medida da ficha (polegada ou métrica) e a matriz “outras medidas desta linha” no produto para achar o irmão certo.</p>'
		. '<h2>Frete e prazo</h2><p>Informe o CEP na página do produto para ver PAC/SEDEX a partir de Imirim/SP. Pedidos com estoque confirmados até 14h (SP) seguem no mesmo dia útil.</p>'
		. '<h2>Por aplicação</h2><ul>'
		. '<li><a href="index.php?route=information/information&amp;language=pt-br&amp;information_id=10">Machos para aço</a></li>'
		. '<li><a href="index.php?route=information/information&amp;language=pt-br&amp;information_id=11">Bits para torno</a></li>'
		. '<li><a href="index.php?route=information/information&amp;language=pt-br&amp;information_id=12">Pontas rotativas CM</a></li>'
		. '<li><a href="index.php?route=information/information&amp;language=pt-br&amp;information_id=13">Ferramentas DIN</a></li>'
		. '<li><a href="index.php?route=information/information&amp;language=pt-br&amp;information_id=14">Alargadores H7</a></li>'
		. '<li><a href="index.php?route=information/information&amp;language=pt-br&amp;information_id=15">Porta-ferramentas</a></li>'
		. '</ul>';

	$mysqli->query("INSERT IGNORE INTO `{$db_prefix}information` (`information_id`, `sort_order`, `status`) VALUES (16, 20, 1)");
	$mysqli->query("INSERT IGNORE INTO `{$db_prefix}information_to_store` (`information_id`, `store_id`) VALUES (16, 0)");
	$mysqli->query(
		"INSERT INTO `{$info_table}` (`information_id`, `language_id`, `title`, `description`, `meta_title`, `meta_description`, `meta_keyword`)
		 VALUES (16, {$lang_pt}, 'Dúvidas técnicas (FAQ)', '" . $mysqli->real_escape_string($faq_html) . "', 'FAQ técnico | MIIGTOOLS', 'DIN, HSS, cone Morse, frete e como escolher a medida.', 'FAQ, DIN, HSS, CM, frete')
		 ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `description` = VALUES(`description`), `meta_title` = VALUES(`meta_title`), `meta_description` = VALUES(`meta_description`), `meta_keyword` = VALUES(`meta_keyword`)"
	);
	bootstrap_seo_url($mysqli, $db_prefix, 0, $lang_pt, 'information_id', '16', 'faq-tecnico', 0);
	echo "bootstrap-seo: landings 10–15 densas + FAQ 16\n";

	// Meta audit: fill empty product meta (pt-br)
	$mysqli->query(
		"UPDATE `{$db_prefix}product_description` `pd`
		 INNER JOIN `{$db_prefix}product` `p` ON (`p`.`product_id` = `pd`.`product_id`)
		 SET
		 	`pd`.`meta_title` = IF(`pd`.`meta_title` = '' OR `pd`.`meta_title` IS NULL, CONCAT(LEFT(`pd`.`name`, 50), IF(`p`.`model` <> '', CONCAT(' | ', `p`.`model`), ''), ' | MIIGTOOLS'), `pd`.`meta_title`),
		 	`pd`.`meta_description` = IF(`pd`.`meta_description` = '' OR `pd`.`meta_description` IS NULL, CONCAT('Compre ', LEFT(`pd`.`name`, 80), IF(`p`.`model` <> '', CONCAT(' (', `p`.`model`, ')'), ''), ' na MIIGTOOLS. Estoque, frete por CEP e atendimento técnico.'), `pd`.`meta_description`)
		 WHERE `pd`.`language_id` = {$lang_pt}"
	);
	echo "bootstrap-seo: meta title/description produtos (preenchimento de vazios)\n";

	$mysqli->query(
		"UPDATE `{$db_prefix}category_description` `cd`
		 SET
		 	`cd`.`meta_title` = IF(`cd`.`meta_title` = '' OR `cd`.`meta_title` IS NULL, CONCAT(`cd`.`name`, ' | MIIGTOOLS'), `cd`.`meta_title`),
		 	`cd`.`meta_description` = IF(`cd`.`meta_description` = '' OR `cd`.`meta_description` IS NULL, CONCAT('Catálogo de ', `cd`.`name`, ' com filtros por especificação, estoque e frete. MIIGTOOLS — ferramentas para usinagem.'), `cd`.`meta_description`)
		 WHERE `cd`.`language_id` = {$lang_pt}"
	);
	echo "bootstrap-seo: meta categorias (preenchimento de vazios)\n";

	// Unique short descriptions for empty category bodies (top-level + children with products)
	$cats = $mysqli->query(
		"SELECT `cd`.`category_id`, `cd`.`name`, `cd`.`description`
		 FROM `{$db_prefix}category_description` `cd`
		 INNER JOIN `{$db_prefix}category` `c` ON (`c`.`category_id` = `cd`.`category_id` AND `c`.`status` = 1)
		 WHERE `cd`.`language_id` = {$lang_pt}"
	);

	if ($cats) {
		while ($c = $cats->fetch_assoc()) {
			$desc = trim(strip_tags(html_entity_decode((string)$c['description'], ENT_QUOTES, 'UTF-8')));

			if ($desc !== '') {
				continue;
			}

			$name = (string)$c['name'];
			$html = '<p>Explore ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8')
				. ' com fichas técnicas, normas DIN quando aplicável e cotação de frete por CEP. Use os filtros para achar a medida certa.</p>';
			$cid = (int)$c['category_id'];
			$mysqli->query(
				"UPDATE `{$db_prefix}category_description`
				 SET `description` = '" . $mysqli->real_escape_string($html) . "'
				 WHERE `category_id` = {$cid} AND `language_id` = {$lang_pt}"
			);
		}
	}

	echo "bootstrap-seo: descrições de categoria vazias preenchidas\n";

	// Cross-ref identifier + top 50 EQUIV codes
	$mysqli->query(
		"INSERT INTO `{$db_prefix}identifier` (`name`, `code`, `validation`, `status`)
		 SELECT 'Código equivalente', 'EQUIV', '', 1 FROM DUAL
		 WHERE NOT EXISTS (SELECT 1 FROM `{$db_prefix}identifier` WHERE `code` = 'EQUIV')"
	);

	$top = $mysqli->query(
		"SELECT `p`.`product_id`, `p`.`model`, `p`.`sku`, `p`.`mpn`, `pd`.`name`
		 FROM `{$db_prefix}product` `p`
		 INNER JOIN `{$db_prefix}product_description` `pd` ON (`p`.`product_id` = `pd`.`product_id` AND `pd`.`language_id` = {$lang_pt})
		 WHERE `p`.`status` = 1 AND `p`.`price` > 0 AND `p`.`model` <> ''
		 ORDER BY `p`.`viewed` DESC, `p`.`product_id` ASC
		 LIMIT 50"
	);

	$cross_count = 0;

	if ($top) {
		while ($row = $top->fetch_assoc()) {
			$pid = (int)$row['product_id'];
			$aliases = [];

			foreach ([(string)$row['model'], (string)$row['sku'], (string)$row['mpn']] as $raw) {
				$raw = trim($raw);

				if ($raw === '') {
					continue;
				}

				$aliases[$raw] = true;
				$compact = strtoupper(preg_replace('/[\s\-_\/]+/', '', $raw) ?? $raw);

				if ($compact !== '' && $compact !== strtoupper($raw)) {
					$aliases[$compact] = true;
				}

				$spaced = strtoupper(preg_replace('/[\-_\/]+/', ' ', $raw) ?? $raw);

				if ($spaced !== '' && strtoupper($spaced) !== strtoupper($raw)) {
					$aliases[$spaced] = true;
				}
			}

			// Sibling models sharing a numeric stem (e.g. same family code)
			$model = (string)$row['model'];
			$stem = preg_replace('/[0-9].*$/', '', $model) ?? '';
			$stem = trim($stem);

			if (strlen($stem) >= 3) {
				$sib = $mysqli->query(
					"SELECT `model` FROM `{$db_prefix}product`
					 WHERE `status` = 1 AND `price` > 0 AND `product_id` <> {$pid}
					 AND `model` LIKE '" . $mysqli->real_escape_string($stem) . "%'
					 ORDER BY `viewed` DESC LIMIT 3"
				);

				if ($sib) {
					while ($s = $sib->fetch_assoc()) {
						$aliases[trim((string)$s['model'])] = true;
					}
				}
			}

			unset($aliases[$model]);

			$added = 0;

			foreach (array_keys($aliases) as $value) {
				if ($value === '' || $added >= 3) {
					break;
				}

				$value = mb_substr($value, 0, 255);
				$check = $mysqli->query(
					"SELECT `product_code_id` FROM `{$db_prefix}product_code`
					 WHERE `product_id` = {$pid} AND `code` = 'EQUIV' AND `value` = '" . $mysqli->real_escape_string($value) . "' LIMIT 1"
				);

				if ($check && $check->num_rows > 0) {
					continue;
				}

				$mysqli->query(
					"INSERT INTO `{$db_prefix}product_code` (`product_id`, `code`, `value`)
					 VALUES ({$pid}, 'EQUIV', '" . $mysqli->real_escape_string($value) . "')"
				);
				$added++;
				$cross_count++;
			}

			// Tag for search findability
			$mysqli->query(
				"UPDATE `{$db_prefix}product_description`
				 SET `tag` = IF(`tag` = '' OR `tag` IS NULL, 'equivalente, cross-ref, OEM', IF(`tag` LIKE '%equivalente%', `tag`, CONCAT(`tag`, ', equivalente, cross-ref')))
				 WHERE `product_id` = {$pid} AND `language_id` = {$lang_pt}"
			);
		}
	}

	echo "bootstrap-seo: cross-ref EQUIV codes adicionados/atualizados (~{$cross_count} códigos nos top 50)\n";
}
