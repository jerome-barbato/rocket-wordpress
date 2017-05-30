<?php

/*
 * Wordpress config file, do not edit, use app/config/wordpress.yml
 */

//ini_set('display_errors', 1);
//error_reporting(~0);

if (!defined('BASE_URI'))
{
    $base_uri = preg_replace( "/\/web$/", '', dirname( __DIR__ ) );
    $base_uri = preg_replace( "/\/vendor\/metabolism$/", '', $base_uri );

    define( 'BASE_URI', $base_uri);
}


if ( !defined('AUTOLOAD') )
{
    define( 'AUTOLOAD', true);
    define( 'WP_DIRECT_LOADING', true);
    require_once BASE_URI.'/vendor/autoload.php';
}
else
{
    define( 'WP_DIRECT_LOADING', false);
}

use Dflydev\DotAccessData\Data;


/**
 * Load App configuration
 */
$data = array();

foreach (['global', 'wordpress', 'local'] as $config)
{
    $file = BASE_URI . '/app/config/' . $config . '.yml';

    if (file_exists($file))
        $data = array_merge($data, \Spyc::YAMLLoad($file));
}

$config = new Data($data);

if( $config->get('environment', 'production') == 'production' )
    $config->set('debug', false);

define( 'WP_ENV', $config->get('environment', 'production'));
define( 'WP_DEBUG', $config->get('debug.php_error', 0));
define( 'WC_TEMPLATE_DEBUG_MODE', $config->get('debug.woocommerce', 0) );

if( $config->get('cache.http', 0) and !WP_DEBUG )
    define( 'WP_CACHE', true);

/**
 * URLs
 */
$isSecure = false;

if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')
    $isSecure = true;
elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' || !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on')
    $isSecure = true;

$base_uri = $isSecure ? 'https' : 'http'.'://'.$_SERVER['HTTP_HOST'];


if (!defined('BASE_PATH'))
{
    $request_uri = explode('/edition/', strtok($_SERVER["REQUEST_URI"],'?'));
    define( 'BASE_PATH', $request_uri[0]);
}

define( 'WP_HOME', $base_uri.BASE_PATH);
define( 'WP_SITEURL', $base_uri.BASE_PATH . '/edition');

/**
 * DB settings
 */
define( 'DB_NAME', $config->get('database.name'));
define( 'DB_USER', $config->get('database.user'));
define( 'DB_PASSWORD', $config->get('database.password'));
define( 'DB_HOST', $config->get('database.host', 'localhost'));
define( 'DB_CHARSET', $config->get('database.charset', 'utf8mb4'));
define( 'DB_COLLATE', '');

$table_prefix = $config->get('database.prefix', 'wp_');


/**
 * Authentication Unique Keys and Salts
 */
define( 'AUTH_KEY', $config->get('key.auth','O-} !h|JpOq^w,CXn+O5=o3MvkN_So+ O0-chs$+a>KJq*i~/!ykEd<]IsPdgI#6'));
define( 'SECURE_AUTH_KEY', $config->get('key.secure_auth','r HvS?mdf^4xc.Iy^G*<ZliwL5r_w.]CUWIu|j0{sfq-M)k:Lhi-),qCDcN<Yy+w'));
define( 'LOGGED_IN_KEY', $config->get('key.logged_in','u`UAyT)Wp0bT&Z.^e3RWTWDs?Je9K0UBQDJqG$W*yb9YG1yl,|*:LQV^ZUt|Q~#.'));
define( 'NONCE_KEY', $config->get('key.nonce','cV9Q^z7H{oI>H6>>vLHQYB[)1N&#ur(# Iqw*k?r-FkQ+#eo9<R^1N?uo.*N~!J5'));

define( 'AUTH_SALT', $config->get('salt.auth','w9J/dNw/bv}@Z#/YcrjPcH$^_[ni&4tji0JA0?na}yTw#0}yuZW>BXDVVjVGA+vk'));
define( 'SECURE_AUTH_SALT', $config->get('salt.secure_auth','T7ntE>-j*2G3Qosn;0?|7{aqs&SU) }_S ~6f5k~PTedeX^jNe&T h)9(k4nT2Rq'));
define( 'LOGGED_IN_SALT', $config->get('salt.logged_in','w/iowiks]_i5b#/SqYuD2`28o</-L|P4H3vq@!<OrH 7Q!gxB[Q4m`/*CiVdylGs'));
define( 'NONCE_SALT', $config->get('salt.nonce','gelPRQb4NzO=4pOG_5YnuN(5G~YJCIutY*BL%!:ds(TqwDd;F[PsI,gT_1J-9;;D'));


/**
 * Custom Content Directory
 */
define( 'CONTENT_DIR', '/web/app');
define( 'WP_CONTENT_DIR', BASE_URI . CONTENT_DIR);
define( 'WP_CONTENT_URL', WP_HOME . CONTENT_DIR);


/**
 * Custom Settings
 */
define( 'DISALLOW_FILE_EDIT', true);
define( 'WP_DEFAULT_THEME', 'rocket');
define( 'WP_POST_REVISIONS', 3);


/**
 * Bootstrap WordPress
 */

if (!defined('WP_USE_THEMES'))
    define( 'WP_USE_THEMES', true);

if (!defined('CMS_URI'))
    define( 'CMS_URI', BASE_URI.'/web/edition');

if (!defined('ABSPATH'))
    define( 'ABSPATH', CMS_URI .'/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
