<?php
// HTTP
define('HTTP_SERVER', 'https://{{DOMAIN}}/admin/');
define('HTTP_CATALOG', 'https://{{DOMAIN}}/');

// HTTPS
define('HTTPS_SERVER', 'https://{{DOMAIN}}/admin/');
define('HTTPS_CATALOG', 'https://{{DOMAIN}}/');

// DIR
define('DIR_APPLICATION', '{{UPLOAD_PATH}}/admin/');
define('DIR_SYSTEM', '{{UPLOAD_PATH}}/system/');
define('DIR_IMAGE', '{{UPLOAD_PATH}}/image/');
define('DIR_STORAGE', '{{STORAGE_PATH}}/');
define('DIR_CATALOG', '{{UPLOAD_PATH}}/catalog/');
define('DIR_LANGUAGE', DIR_APPLICATION . 'language/');
define('DIR_TEMPLATE', DIR_APPLICATION . 'view/template/');
define('DIR_CONFIG', DIR_SYSTEM . 'config/');
define('DIR_CACHE', DIR_STORAGE . 'cache/');
define('DIR_DOWNLOAD', DIR_STORAGE . 'download/');
define('DIR_LOGS', DIR_STORAGE . 'logs/');
define('DIR_MODIFICATION', DIR_STORAGE . 'modification/');
define('DIR_SESSION', DIR_STORAGE . 'session/');
define('DIR_UPLOAD', DIR_STORAGE . 'upload/');

// DB
define('DB_DRIVER', 'mysqli');
define('DB_HOSTNAME', 'localhost');
define('DB_USERNAME', '{{DB_USER}}');
define('DB_PASSWORD', '{{DB_PASSWORD}}');
define('DB_DATABASE', '{{DB_NAME}}');
define('DB_PORT', '3306');
define('DB_PREFIX', 'oc_');

define('OPENCART_SERVER', 'http://www.opencart.com/');
