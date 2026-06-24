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

		$mysqli->close();
	}
}
