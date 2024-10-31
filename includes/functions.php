<?php
defined('ABSPATH') or exit();

/**
 * Redforts Hotel Booking Engine
 * Front core functions
 *
 * @author Redforts Software SL
 * @link https://redforts.com
 * @since 0.1.1
 */

/**
 * Print meta tags to disable caching at any plugin page.
 *
 * @return void
 */
function printDisableCacheMetaTags() {
    echo '<meta http-equiv="Expires" content="0">'
        . '<meta http-equiv="Last-Modified" content="0">'
        . '<meta http-equiv="Cache-Control" '
        . 'content="no-store, max-age=0, must-revalidate">';
}

add_action('wp_head', 'printDisableCacheMetaTags', 0);

/**
 * Add style to admin.
 *
 * @return void
 */
function ohbeAddAdminStyle() {
    wp_enqueue_style(
        'ohbe_admin_style', OHBE_URL .
        'assets/css/admin-style.css',
        '',
        OHBE_VERSION
    );
}

add_action('admin_enqueue_scripts', 'ohbeAddAdminStyle');

/**
 * Handle accommodations search form.
 * Redirect to booking page.
 *
 * @return object When errors are set
 */
function ohbeSearchFormHandle($id, $account) {
    OHBE_Main::checkOhbePage();
    $url_params = OHBE_Tools::setUrlParams(OHBE_BOOKING_PAGE);

    if ($id) {
        $url_params['acco'] = $id;
    }
    if ($account) {
        $url_params['account'] = $account;
    }
    $url = esc_url_raw(add_query_arg($url_params, OHBE_Tools::getBaseUrl()));
    wp_safe_redirect($url);
    exit;
}

function writeLog($arg='') {
    if (!OHBE_LOG) {
        return;
    }

    $log_file = date('Ymd') . '.log';
    $log_limit = date('Ymd', strtotime('-7 day')) . '.log';
    $log_dir = OHBE_PATH . 'logs/';

    if (!file_exists($log_dir)) {
        mkdir($log_dir);
    }

    foreach (scandir($log_dir) as $f) {
        if (preg_match('/\d{8}\.log/', $f) && $f < $log_limit) {
            unlink($log_dir . '/' . $f);
        }
    }

    $ip = array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)
        ? $_SERVER['HTTP_X_FORWARDED_FOR']
        : $_SERVER['REMOTE_ADDR'];
    $msg = date('c') . ' ' . $ip;

    if (is_array($arg) || is_object($arg)) {
        $msg .= ' ' . json_encode($arg);
    }
    else {
        $msg .= ' ' . $arg;
    }

    $msg .= "\n";


    $handle = fopen($log_dir . $log_file, 'a');
    fwrite($handle, $msg);
    fclose($handle);
}

function hasCookie() {
    return isset($_COOKIE[session_name()]);
}
