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
