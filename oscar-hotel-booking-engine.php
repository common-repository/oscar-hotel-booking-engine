<?php
/*
Plugin Name: Redforts Hotel Booking Engine
Plugin URI:  https://wordpress.org/plugins/oscar-hotel-booking-engine/
Description: This plugin integrates with Redforts Hotel Software, an all-in-one solution for hotels, hostels, apartments, villas, campings, and more.
Version:     4.7
Author:      Redforts Software SL
Author URI:  https://redforts.com
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: ohbe
Domain Path: /languages
*/

define('OHBE_VERSION', '4.7');
define('OHBE_BASE', __FILE__);
define('OHBE_PATH', plugin_dir_path(OHBE_BASE));
define('OHBE_URL', plugins_url('/', OHBE_BASE));
define('OHBE_LOG', false);

if (file_exists(OHBE_PATH . '/config.php')) {
    require_once('config.php');
}
else {
    define('OHBE_HOST', 'booking.redforts.com');
}

require_once('includes/admin/OHBE-admin.php');
require_once('includes/OHBE-main.php');

register_activation_hook(__FILE__, array('OHBE_Main', 'activation'));
register_deactivation_hook(__FILE__, array('OHBE_Main', 'deactivation'));
register_uninstall_hook(__FILE__, array('OHBE_Main', 'uninstall'));

OHBE_Main::getInstance();
