<?php
/**
 * Seed technical attributes/filters from product names for MIIGTOOLS catalog.
 * Called from scripts/write-config.php on every boot.
 */

function miig_specs_ensure_id(\mysqli $mysqli, string $sql_insert, string $sql_find): int {
	$find = $mysqli->query($sql_find);

	if ($find && ($row = $find->fetch_assoc())) {
		return (int) array_values($row)[0];
	}

	$mysqli->query($sql_insert);

	return (int) $mysqli->insert_id;
}

function miig_specs_parse_name(string $name): array {
	$out = [
		'medida'   => '',
		'norma'    => '',
		'material' => '',
		'tipo'     => '',
		'cm'       => '',
	];

	if (preg_match('/(\d+\/\d+"?\s*x\s*\d+"?)/iu', $name, $m)) {
		$out['medida'] = trim(str_replace(['″', '"'], '"', $m[1]));
	} elseif (preg_match('/\b(M\d+(?:x[\d,\.]+)?)\b/iu', $name, $m)) {
		$out['medida'] = strtoupper($m[1]);
	} elseif (preg_match('/-\s*(\d+\/\d+")\s*$/u', $name, $m)) {
		$out['medida'] = $m[1];
	}

	if (preg_match('/\bDIN\s*(\d+)\b/iu', $name, $m)) {
		$out['norma'] = 'DIN ' . $m[1];
	}

	if (preg_match('/\b10%\s*Co\b/iu', $name)) {
		$out['material'] = 'HSS 10% Co';
	} elseif (preg_match('/\b50%\b/u', $name) && stripos($name, 'Bits') !== false) {
		$out['material'] = 'HSS 50%';
	} elseif (preg_match('/\bHSS-E\b/iu', $name)) {
		$out['material'] = 'HSS-E';
	} elseif (preg_match('/\bHSS\b/iu', $name)) {
		$out['material'] = 'HSS';
	} elseif (stripos($name, 'Winner Steel') !== false) {
		$out['material'] = 'Winner Steel';
	}

	if (preg_match('/\bCM\s*(\d+)\b/iu', $name, $m)) {
		$out['cm'] = 'CM' . $m[1];
	}

	$tipos = [
		'Bits Quadrado'           => 'Bits Quadrado',
		'Porta Bits'              => 'Porta Bits',
		'Porta Bedame'            => 'Porta Bedame',
		'Porta Recartilha'        => 'Porta Recartilha',
		'Ponta Rotativa Tubular'  => 'Ponta Rotativa Tubular',
		'Ponta Rotativa Copiadora'=> 'Ponta Rotativa Copiadora',
		'Ponta Rotativa Super'    => 'Ponta Rotativa Super',
		'Ponta Rotativa Standard' => 'Ponta Rotativa Standard',
		'Ponta Rotativa'          => 'Ponta Rotativa',
		'Macho Máquina'           => 'Macho Máquina',
		'Macho Laminador'         => 'Macho Laminador',
		'Macho'                   => 'Macho',
		'Bedame'                  => 'Bedame',
		'Maleta'                  => 'Maleta / Kit',
	];

	foreach ($tipos as $needle => $label) {
		if (stripos($name, $needle) !== false) {
			$out['tipo'] = $label;
			break;
		}
	}

	return $out;
}

function miig_specs_upsert_filter(\mysqli $mysqli, string $prefix, int $group_id, string $name_pt, string $name_en, int $sort): int {
	$esc = $mysqli->real_escape_string($name_pt);
	$find = $mysqli->query(
		"SELECT f.filter_id FROM `{$prefix}filter` f
		JOIN `{$prefix}filter_description` fd ON fd.filter_id = f.filter_id AND fd.language_id = 2
		WHERE f.filter_group_id = {$group_id} AND fd.name = '{$esc}' LIMIT 1"
	);

	if ($find && ($row = $find->fetch_assoc())) {
		return (int) $row['filter_id'];
	}

	$mysqli->query("INSERT INTO `{$prefix}filter` SET filter_group_id = {$group_id}, sort_order = {$sort}");
	$fid = (int) $mysqli->insert_id;
	$en = $mysqli->real_escape_string($name_en);
	$mysqli->query(
		"INSERT INTO `{$prefix}filter_description` (filter_id, language_id, name) VALUES
		({$fid}, 2, '{$esc}'), ({$fid}, 1, '{$en}')"
	);

	return $fid;
}

/**
 * @param \mysqli $mysqli
 * @param string  $db_prefix
 */
function bootstrap_catalog_specs(\mysqli $mysqli, string $db_prefix): void {
	$p = $db_prefix;

	// Attribute group
	$ag = miig_specs_ensure_id(
		$mysqli,
		"INSERT INTO `{$p}attribute_group` SET sort_order = 1",
		"SELECT ag.attribute_group_id FROM `{$p}attribute_group` ag
		 JOIN `{$p}attribute_group_description` agd ON agd.attribute_group_id = ag.attribute_group_id AND agd.language_id = 2
		 WHERE agd.name = 'Especificações' LIMIT 1"
	);
	$mysqli->query(
		"INSERT IGNORE INTO `{$p}attribute_group_description` (attribute_group_id, language_id, name) VALUES
		({$ag}, 2, 'Especificações'), ({$ag}, 1, 'Specifications')"
	);

	$attr_defs = [
		'medida'   => ['Medida', 'Size', 1],
		'norma'    => ['Norma', 'Standard', 2],
		'material' => ['Material', 'Material', 3],
		'tipo'     => ['Tipo', 'Type', 4],
		'cm'       => ['Cone Morse', 'Morse Taper', 5],
	];

	$attr_ids = [];

	foreach ($attr_defs as $key => [$pt, $en, $sort]) {
		$esc = $mysqli->real_escape_string($pt);
		$id = miig_specs_ensure_id(
			$mysqli,
			"INSERT INTO `{$p}attribute` SET attribute_group_id = {$ag}, sort_order = {$sort}",
			"SELECT a.attribute_id FROM `{$p}attribute` a
			 JOIN `{$p}attribute_description` ad ON ad.attribute_id = a.attribute_id AND ad.language_id = 2
			 WHERE a.attribute_group_id = {$ag} AND ad.name = '{$esc}' LIMIT 1"
		);
		$mysqli->query(
			"INSERT IGNORE INTO `{$p}attribute_description` (attribute_id, language_id, name) VALUES
			({$id}, 2, '{$esc}'), ({$id}, 1, '" . $mysqli->real_escape_string($en) . "')"
		);
		$attr_ids[$key] = $id;
	}

	// Filter groups
	$fg_defs = [
		'material' => ['Material', 'Material', 1],
		'tipo'     => ['Tipo', 'Type', 2],
		'norma'    => ['Norma', 'Standard', 3],
		'cm'       => ['Cone Morse', 'Morse Taper', 4],
		'medida'   => ['Medida', 'Size', 5],
	];

	$fg_ids = [];

	foreach ($fg_defs as $key => [$pt, $en, $sort]) {
		$esc = $mysqli->real_escape_string($pt);
		$id = miig_specs_ensure_id(
			$mysqli,
			"INSERT INTO `{$p}filter_group` SET sort_order = {$sort}",
			"SELECT fg.filter_group_id FROM `{$p}filter_group` fg
			 JOIN `{$p}filter_group_description` fgd ON fgd.filter_group_id = fg.filter_group_id AND fgd.language_id = 2
			 WHERE fgd.name = '{$esc}' LIMIT 1"
		);
		$mysqli->query(
			"INSERT IGNORE INTO `{$p}filter_group_description` (filter_group_id, language_id, name) VALUES
			({$id}, 2, '{$esc}'), ({$id}, 1, '" . $mysqli->real_escape_string($en) . "')"
		);
		$fg_ids[$key] = $id;
	}

	$products = $mysqli->query(
		"SELECT p.product_id, pd.name
		 FROM `{$p}product` p
		 JOIN `{$p}product_description` pd ON pd.product_id = p.product_id AND pd.language_id = 2
		 WHERE p.status = 1"
	);

	$linked_filters = [];
	$count = 0;

	if ($products) {
		while ($row = $products->fetch_assoc()) {
			$pid = (int) $row['product_id'];
			$parsed = miig_specs_parse_name((string) $row['name']);
			$has = false;

			foreach ($parsed as $key => $value) {
				if ($value === '' || !isset($attr_ids[$key])) {
					continue;
				}

				$has = true;
				$text = $mysqli->real_escape_string($value);
				$aid = $attr_ids[$key];

				$mysqli->query(
					"INSERT INTO `{$p}product_attribute` (product_id, attribute_id, language_id, text)
					 VALUES ({$pid}, {$aid}, 2, '{$text}')
					 ON DUPLICATE KEY UPDATE text = VALUES(text)"
				);
				$mysqli->query(
					"INSERT INTO `{$p}product_attribute` (product_id, attribute_id, language_id, text)
					 VALUES ({$pid}, {$aid}, 1, '{$text}')
					 ON DUPLICATE KEY UPDATE text = VALUES(text)"
				);

				if (isset($fg_ids[$key])) {
					$fid = miig_specs_upsert_filter($mysqli, $p, $fg_ids[$key], $value, $value, 0);
					$mysqli->query("INSERT IGNORE INTO `{$p}product_filter` (product_id, filter_id) VALUES ({$pid}, {$fid})");
					$linked_filters[$fid] = true;
				}
			}

			if ($has) {
				$count++;
			}
		}
	}

	// Expose filters only on categories that actually contain matching products
	$category_ids = [59, 60, 61, 62, 63, 64, 67, 68];

	foreach (array_keys($linked_filters) as $fid) {
		$fid = (int) $fid;

		foreach ($category_ids as $cid) {
			$cid = (int) $cid;
			$has = $mysqli->query(
				"SELECT 1 FROM `{$p}product_filter` pf
				 JOIN `{$p}product_to_category` p2c ON p2c.product_id = pf.product_id
				 WHERE pf.filter_id = {$fid} AND p2c.category_id = {$cid}
				 LIMIT 1"
			);

			if ($has && $has->num_rows > 0) {
				$mysqli->query("INSERT IGNORE INTO `{$p}category_filter` (category_id, filter_id) VALUES ({$cid}, {$fid})");
			}
		}

		// Parent catalog always gets filters that exist on any child
		$mysqli->query("INSERT IGNORE INTO `{$p}category_filter` (category_id, filter_id) VALUES (59, {$fid})");
	}

	// Enable filter module on category layout
	$mysqli->query("INSERT IGNORE INTO `{$p}extension` (`extension`, `type`, `code`) VALUES ('opencart', 'module', 'filter')");

	$setting_table = $p . 'setting';
	$mysqli->query(
		"INSERT INTO `{$setting_table}` (`store_id`, `code`, `key`, `value`, `serialized`)
		 VALUES (0, 'module_filter', 'module_filter_status', '1', 0)
		 ON DUPLICATE KEY UPDATE `value` = '1'"
	);
	// Fix duplicates for module_filter_status
	$mysqli->query(
		"UPDATE `{$setting_table}` SET `value` = '1' WHERE `key` = 'module_filter_status' AND `store_id` = 0"
	);

	$layout_id = 0;
	$lr = $mysqli->query("SELECT layout_id FROM `{$p}layout_route` WHERE route = 'product/category' AND store_id = 0 LIMIT 1");

	if ($lr && ($lrow = $lr->fetch_assoc())) {
		$layout_id = (int) $lrow['layout_id'];
	}

	if ($layout_id > 0) {
		$exists = $mysqli->query(
			"SELECT layout_module_id FROM `{$p}layout_module`
			 WHERE layout_id = {$layout_id} AND code = 'opencart.filter' LIMIT 1"
		);

		if (!$exists || $exists->num_rows === 0) {
			$mysqli->query(
				"INSERT INTO `{$p}layout_module` (layout_id, code, position, sort_order)
				 VALUES ({$layout_id}, 'opencart.filter', 'column_left', 0)"
			);
		}
	}

	// Quantity discounts for featured/top products (10+)
	$discount_products = $mysqli->query(
		"SELECT product_id, price FROM `{$p}product` WHERE status = 1 AND price > 0 ORDER BY product_id ASC LIMIT 40"
	);

	$disc_count = 0;

	if ($discount_products) {
		while ($d = $discount_products->fetch_assoc()) {
			$pid = (int) $d['product_id'];
			$price = (float) $d['price'];
			$p10 = round($price * 0.95, 4);
			$p25 = round($price * 0.90, 4);

			$mysqli->query("DELETE FROM `{$p}product_discount` WHERE product_id = {$pid} AND customer_group_id = 1 AND quantity IN (10, 25)");
			$mysqli->query(
				"INSERT INTO `{$p}product_discount` (product_id, customer_group_id, quantity, priority, price, type, special, date_start, date_end)
				 VALUES
				 ({$pid}, 1, 10, 1, {$p10}, 'P', 0, NULL, NULL),
				 ({$pid}, 1, 25, 1, {$p25}, 'P', 0, NULL, NULL)"
			);
			$disc_count++;
		}
	}

	echo "bootstrap-db: specs/filtros em {$count} produtos; descontos qty em {$disc_count}; filtro no layout categoria\n";

	// PJ: Inscrição Estadual as optional address custom field
	$ie_find = $mysqli->query(
		"SELECT cf.custom_field_id FROM `{$p}custom_field` cf
		 JOIN `{$p}custom_field_description` cfd ON cfd.custom_field_id = cf.custom_field_id AND cfd.language_id = 2
		 WHERE cfd.name = 'Inscrição Estadual' AND cf.location = 'address' LIMIT 1"
	);

	if ($ie_find && ($ie_row = $ie_find->fetch_assoc())) {
		$ie_id = (int) $ie_row['custom_field_id'];
	} else {
		$mysqli->query(
			"INSERT INTO `{$p}custom_field` SET type = 'text', value = '', validation = '', location = 'address', status = 1, sort_order = 5"
		);
		$ie_id = (int) $mysqli->insert_id;
		$mysqli->query(
			"INSERT INTO `{$p}custom_field_description` (custom_field_id, language_id, name) VALUES
			({$ie_id}, 2, 'Inscrição Estadual'), ({$ie_id}, 1, 'State Tax ID (IE)')"
		);
	}

	$cg = $mysqli->query("SELECT customer_group_id FROM `{$p}customer_group`");

	if ($cg) {
		while ($g = $cg->fetch_assoc()) {
			$gid = (int) $g['customer_group_id'];
			$mysqli->query(
				"INSERT IGNORE INTO `{$p}custom_field_customer_group` (custom_field_id, customer_group_id, required)
				 VALUES ({$ie_id}, {$gid}, 0)"
			);
		}
	}
}
