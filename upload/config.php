<?php
define('APPLICATION', 'Catalog');

// HTTP
define('HTTP_SERVER', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/');

// Directories
define('DIR_OPENCART', __DIR__ . '/');
define('DIR_APPLICATION', DIR_OPENCART . 'catalog/');
define('DIR_EXTENSION', DIR_OPENCART . 'extension/');
define('DIR_IMAGE', DIR_OPENCART . 'image/');
define('DIR_SYSTEM', DIR_OPENCART . 'system/');
define('DIR_STORAGE', '/storage/');
define('DIR_LANGUAGE', DIR_APPLICATION . 'language/');
define('DIR_TEMPLATE', DIR_APPLICATION . 'view/template/');
define('DIR_CONFIG', DIR_SYSTEM . 'config/');
define('DIR_CACHE', DIR_STORAGE . 'cache/');
define('DIR_DOWNLOAD', DIR_STORAGE . 'download/');
define('DIR_LOGS', DIR_STORAGE . 'logs/');
define('DIR_SESSION', DIR_STORAGE . 'session/');
define('DIR_UPLOAD', DIR_STORAGE . 'upload/');

// Database — values are read from environment variables at runtime.
// Fallback values are provided for local development only.
define('DB_DRIVER', 'mysqli');
define('DB_HOSTNAME', getenv('DB_HOSTNAME') !== false ? getenv('DB_HOSTNAME') : 'localhost');
define('DB_USERNAME', getenv('DB_USERNAME') !== false ? getenv('DB_USERNAME') : 'root');
define('DB_PASSWORD', getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '');
define('DB_DATABASE', getenv('DB_DATABASE') !== false ? getenv('DB_DATABASE') : 'opencart');
define('DB_PORT',     getenv('DB_PORT')     !== false ? getenv('DB_PORT')     : '3306');
define('DB_PREFIX', getenv('DB_PREFIX') !== false ? getenv('DB_PREFIX') : 'ws_');
define('DB_SSL_KEY', '');
define('DB_SSL_CERT', '');
define('DB_SSL_CA', '');
