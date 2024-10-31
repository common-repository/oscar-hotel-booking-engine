<?php
defined('ABSPATH') or exit();

/**
 * Redforts Hotel Booking Engine
 * Set up and initialize the plugin
 *
 * @author Redforts Software SL
 * @link https://redforts.com
 * @since 0.1.1
 */
class OHBE_Main
{
    /**
     * The single instance of the class.
     */
    public static $instance;

    /**
     * Define whether a search form has been display at least once.
     */
    public static $isSearchFormDisplayed;

    /**
     * Set up needed actions/filters for the plugin to initialize.
     *
     * @return void
     */
    public function __construct() {
        add_action('plugins_loaded', array($this, 'setConstants'), 1);
        add_action('plugins_loaded', array($this, 'loadLanguages'), 6);
        add_action('plugins_loaded', array($this, 'loadFiles'), 3);
        add_action('wp_enqueue_scripts', array($this, 'loadAssets'), 20);
        add_action(
            'upgrader_process_complete',
            array('OscarAPI', 'getInventoryData'),
            10,
            2
        );
        add_action('wp_head', array($this, 'setDynamicStyles'), 21);
        add_action('init', array($this, 'sessionStart'));
        if (is_admin()) {
            OHBE_Admin::getInstance();
        }
    }

    /**
     * Add hook on plugin activation.
     *
     * @return void
     */
    public static function activation() {
        self::initializeSettings();
        self::checkOhbePage();
        delete_option('ohbe_inventory');
        delete_option('ohbe_inventory_accounts');
        flush_rewrite_rules();
    }

    public static function checkOhbePage() {
        $ohbe_pages = self::getOhbePages();

        if (empty($ohbe_pages) || (
            function_exists('pll_home_url')
            && count($ohbe_pages) !== pll_languages_list()
        )) {
            self::removeOhbePages($ohbe_pages);
            self::createOhbePage();
        }
    }

    /**
     * Create OHBE page.
     *
     * @return void
     */
    public static function createOhbePage() {
        $current_user = wp_get_current_user();
        $page = array(
            'post_author' => $current_user->ID,
            'post_name' => 'ohbe',
            'post_status' => 'publish',
            'post_title'  => 'Redforts Hotel Booking Engine',
            'post_type'   => 'page',
        );

        if (
            function_exists('pll_home_url')
            && $page_id = wp_insert_post($page, true)
        ) {
            // Create pages for every language in Polylang
            $language_slugs = pll_languages_list();
            $translated_pages = array();

            foreach ($language_slugs as $slug) {
                if ($slug !== pll_default_language()) {
                    $translated_pages[$slug] = wp_insert_post($page, true);
                }
            }

            if (
                function_exists('pll_set_post_language')
                && function_exists('pll_save_post_translations')
            ) {

                pll_set_post_language($page_id, pll_default_language());
                foreach ($translated_pages as $slug => $value) {
                    pll_set_post_language($value, $slug);
                }

                pll_save_post_translations(array_merge(
                    array($page_id),
                    $translated_pages
                ));
            }
        }
        elseif (
            class_exists('SitePress')
            && $page_id = wp_insert_post($page, true)
        ) {
            // Create pages for every language in WPML
            do_action( 'wpml_admin_make_post_duplicates', $page_id);
        }
        else {
            // Create a single page without languages
            wp_insert_post($page);
        }
    }

    /**
     * Add hook on plugin deactivation.
     *
     * @return void
     */
    public static function deactivation() {
        self::removeOhbePages(self::getOhbePages());
    }

    /**
     * Create an instance of the class.
     *
     * @return object OHBE_Main instance.
     */
    public static function getInstance() {
        if (is_null(self::$instance)) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * Get inventory data.
     *
     * @return mixed Value from inventory.
     */
    public static function getInventory($field, $subfield = null) {
        $ohbe_settings = get_option('ohbe_inventory');
        if (isset($ohbe_settings[$field])) {
            $ohbe_settings_field = $ohbe_settings[$field];

            if (isset($subfield)) {
                return $ohbe_settings_field[$subfield];
            }
        }

        return isset($ohbe_settings_field) ? $ohbe_settings_field : null;
    }

    /**
     * Get inventory accounts data.
     *
     * @return mixed Value from inventory accounts.
     */
    public static function getInventoryAccount($key, $field, $subfield = null) {
        $ohbe_settings = isset(get_option('ohbe_inventory_accounts')[$key])
            ? get_option('ohbe_inventory_accounts')[$key]
            : null;
        if (isset($ohbe_settings[$field])) {
            $ohbe_settings_field = $ohbe_settings[$field];

            if (isset($subfield)) {
                return $ohbe_settings_field[$subfield];
            }
        }

        return isset($ohbe_settings_field) ? $ohbe_settings_field : null;
    }

    /**
     * Get OHBE pages.
     *
     * @return array OHBE pages list
     */
    public static function getOhbePages() {
        global $wpdb;
        return $wpdb->get_results("
            select * from $wpdb->posts
            where post_name like 'ohbe%'
            and post_type = 'page'
            and post_status = 'publish';
        ");
    }

    /**
     * Get isSearchFormDisplayed value.
     *
     * @return bool
     */
    public static function getIsSearchFormDisplayed() {
        return self::$isSearchFormDisplayed;
    }

    /**
     * Get a single field from settings.
     *
     * @return mixed Value from settings.
     */
    public static function getSettings($field) {
        $ohbe_settings = get_option('ohbe_settings');
        return isset($ohbe_settings[$field]) ? $ohbe_settings[$field] : '';
    }

    /**
     * Initialize settings data.
     *
     * @return void
     */
    private static function initializeSettings() {
        $ohbe_settings = array(
            'api_code' => '',
            'accounts' => array(),
            'btn_bg_color' => '#000',
            'btn_font_color' => '#fff',
            'is_adapt_size_automatically' => true
        );
        add_option('ohbe_settings', $ohbe_settings);
    }

    /**
     * Check if current WP version is compatible with the Booking API of
     * Redforts Hotel Software
     *
     * @return boolean
     */
    public function isCompatibleWpVersion() {
        global $wp_version;
        return version_compare($wp_version, '4.2.2') >= 0;
    }

    /**
     * Load assets.
     *
     * @return void
     */
    public function loadAssets() {
        if (!is_admin()) {
            // Javascript
            wp_enqueue_script(
                'ohbe_moment',
                '//cdnjs.cloudflare.com/ajax/libs/moment.js/2.22.2/'
                . 'moment-with-locales.min.js',
                array('jquery')
            );
            wp_enqueue_script(
                'ohbe_bootstrap',
                '//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js',
                array('jquery')
            );
            wp_enqueue_script(
                'ohbe_bootstrap_datepicker',
                OHBE_URL . 'assets/js/bootstrap_ohbe_datepicker.min.js',
                array('jquery', 'ohbe_bootstrap')
            );
            wp_enqueue_script(
                'ohbe_common',
                OHBE_URL . 'assets/js/ohbe-common.js',
                array('jquery'),
                OHBE_VERSION,
                1
            );
            if (OHBE_Main::getSettings('is_adapt_size_automatically')) {
                wp_register_script(
                    'ohbe_booking',
                    OHBE_URL . 'assets/js/ohbe-booking.js',
                    array('jquery'),
                    OHBE_VERSION,
                    1
                );
            }
            // CSS
            wp_enqueue_style(
                'bootstrap-datepicker-css',
                OHBE_URL . 'assets/css/bootstrap-datepicker.standalone.min.css'
            );
            wp_enqueue_style(
                'ohbe_style', OHBE_URL .
                'assets/css/style.css',
                '',
                OHBE_VERSION
            );
            ohbeSetCalendarData();
        }
    }

    /**
     * Load files.
     *
     * @return void
     */
    public function loadFiles() {
        require_once(OHBE_PATH . 'includes/api/OscarAPI.php');
        require_once(OHBE_PATH . 'includes/class/ohbe-tools.php');
        require_once(OHBE_PATH . 'includes/functions.php');
        require_once(OHBE_PATH . 'includes/templates/OHBE-virtual-page-creator.php');
        require_once(OHBE_PATH . 'includes/templates/shortcode/ohbe-search.php');
        require_once(OHBE_PATH . 'includes/admin/widgets/ohbe-search-widget.php');
    }

    /**
     * Load language files.
     *
     * @return void
     */
    public function loadLanguages() {
        load_plugin_textdomain(
            'ohbe',
            false,
            plugin_basename(OHBE_PATH . 'languages')
        );
    }

    /**
     * Remove OHBE pages.
     *
     * @param array $ohbe_pages OHBE pages list.
     * @return void
     */
    public static function removeOhbePages($ohbe_pages) {
        if (!empty($ohbe_pages)) {
            if (OHBE_Tools::hasPolylang() || OHBE_Tools::hasWPML()) {
                // Delete pages for every language in Polylang / WPML
                foreach ($ohbe_pages as $ohbe_page) {
                    wp_delete_post($ohbe_page->ID, true);
                }
            }
            else {
                // Delete a single page without languages
                wp_delete_post($ohbe_pages[0]->ID, true);
            }
        }
    }

    /**
     * Define constants for the plugin.
     *
     * @return void
     */
    public function setConstants() {
        // Set constant path to booking page
        define('OHBE_BOOKING_PAGE', 'booking');
    }

    /**
     * Add dynamic styles to <head>.
     *
     * @return void
     */
    public static function setDynamicStyles() {
        // Btn
        printf('<style>
            button.btn-ohbe {
                border-color: %1$s;
            }
            button.btn-ohbe,
            .ohbe-datepicker.datepicker-dropdown .datepicker-days table tr td.active,
            .ohbe-datepicker.datepicker-dropdown .range-selected,
            .ohbe-datepicker.datepicker-dropdown table.table-condensed tr td span.active {
                background-color: %1$s;
                color: %2$s;
            }
            </style>',
            OHBE_Main::getSettings('btn_bg_color'),
            OHBE_Main::getSettings('btn_font_color')
        );
    }

    /**
     * Start session.
     *
     * @return void
     */
    public function sessionStart() {
        if (!session_id()) {
            session_start();
        }

        if (!isset($_SESSION['ohbe'])) {
            $_SESSION['ohbe'] = array();
        }
    }

    /**
     * Set isSearchFormDisplayed value.
     *
     * @return void
     */
    public static function setIsSearchFormDisplayed($value) {
        self::$isSearchFormDisplayed = $value;
    }

    /**
     * Set settings data.
     *
     * @return void
     */
    public static function setSettings($field, $value, $subkey = null) {
        $ohbe_settings = get_option('ohbe_settings');

        if ($subkey) {
            $ohbe_settings[$field][$subkey] = $value;
        }
        else {
            $ohbe_settings[$field] = $value;
        }

        update_option('ohbe_settings', $ohbe_settings);
    }

    /**
     * Add hook on plugin uninstall.
     *
     * @return void
     */
    public static function uninstall() {
        delete_option('ohbe_settings');
        delete_option('ohbe_inventory');
        delete_option('ohbe_inventory_accounts');
        flush_rewrite_rules();
    }
}
