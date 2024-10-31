<?php
defined('ABSPATH') or exit();

/**
 * Redforts Hotel Booking Engine settings class
 * Administrative contents
 *
 * @author Redforts Software SL
 * @link https://redforts.com
 * @since 0.1.1
 */
class OHBE_Admin
{
    /**
     * The single instance of the class.
     */
    public static $instance;

    /**
     * Create & return an instance of the class.
     *
     * @return object OHBE_Admin instance.
     */
    public static function getInstance() {
        if (null == self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Add hooks to initialize the class.
     *
     * @return void
     */
    public function __construct() {
        // Add Redforts admin menu
        add_action('admin_menu', array($this, 'registerAdminMenu'));
        // Register options
        add_action('admin_init', array($this, 'registerSettings'));
        // Save options
        add_action('admin_init', array($this, 'saveSettings'));
        // CSS & JS for Color Picker
        add_action('admin_enqueue_scripts', array($this, 'loadAssets'), 21);
    }

    /**
     * Add the settings page to the wp admin menu.
     *
     * @return void
     */
    public function registerAdminMenu() {
        $p = add_menu_page(
            'Redforts',
            'Redforts Hotel',
            'manage_options',
            'ohbe',
            array($this, 'adminPage'),
            OHBE_URL . 'assets/images/redforts-icon.png',
            90
        );
        add_action('load-' . $p, array(__CLASS__, 'addHelpTabs'));
    }

    /**
     * Display the options page for the plugin.
     *
     * @return void
     */
    public function adminPage() {
        if (!current_user_can('manage_options')) {
            return;
        }
        printf(
            '<div class=wrap><h1>%s</h1>',
            __("Redforts Hotel Settings", 'ohbe')
        );
        if (isset($_GET['settings-updated'])) {
            add_settings_error(
                'wporg_messages',
                'wporg_message',
                __("Settings saved.", 'ohbe'),
                'updated'
            );
        }
        if (!get_option('ohbe_inventory')) {
            OscarAPI::getInventoryData();
        }
        settings_errors('wporg_messages');
        print '<form method=POST action=options.php>';
        print '<input type=hidden name=action>';
        settings_fields('oscar-hotel-settings');
        do_settings_sections('oscar-hotel-settings');
        submit_button();
        wp_nonce_field('update_ohbe_settings', 'update_ohbe_settings');
        print '</form>';
    }

    /**
     * Register plugin settings.
     *
     * @return void
     */
    public function registerSettings() {
        // Register settings array name
        register_setting(
            'oscar-hotel-settings',
            'ohbe',
            array($this, 'sanitizeInput')
        );
        // Settings section: Main Settings
        add_settings_section(
            'main-settings',
            '',
            array($this, 'mainSettingsCallback'),
            'oscar-hotel-settings'
        );
        // Settings section: Account connection
        add_settings_section(
            'account-settings',
            __("Account connection", 'ohbe'),
            null,
            'oscar-hotel-settings'
        );
        // Connetion_code
        add_settings_field(
            'connection_code',
            __("Connection code", 'ohbe'),
            array($this, 'clientCodeCallback'),
            'oscar-hotel-settings',
            'account-settings'
        );
        add_settings_field(
            'connect_multiple_accounts',
            __("Connect multiple accounts", 'ohbe'),
            array($this, 'connectMultipleAccountsCallback'),
            'oscar-hotel-settings',
            'account-settings'
        );
        // Settings section: Colors
        add_settings_section(
            'color-settings',
            __("Colors", 'ohbe'),
            null,
            'oscar-hotel-settings'
        );
        // Btn font color
        add_settings_field(
            'btn-font-color',
            __("Buttons font", 'ohbe'),
            array($this, 'btnFontColorCallback'),
            'oscar-hotel-settings',
            'color-settings'
        );
        // Btn background color
        add_settings_field(
            'btn-bg-color',
            __("Buttons background", 'ohbe'),
            array($this, 'btnBgColorCallback'),
            'oscar-hotel-settings',
            'color-settings'
        );
        // Other settings section
        add_settings_section(
            'other-settings',
            __("Other settings", 'ohbe'),
            null,
            'oscar-hotel-settings'
        );
        // Adapt size automatically
        add_settings_field(
            'adapt-size-automatically',
            __("Adapt size automatically (recommended)", 'ohbe'),
            array($this, 'adaptSizeAutomaticallyCallback'),
            'oscar-hotel-settings',
            'other-settings'
        );
    }

    /**
     * Define settings section: Main Settings.
     *
     * @return void
     */
    public function mainSettingsCallback() {
        printf(
            '<p>%s</p>',
            __("Check out the Help section for "
            . "more information about the plugin.",
            'ohbe'
        ));
    }

    /**
     * Display client_code field in settings page.
     *
     * @return void
     */
    public function clientCodeCallback() {
        $html = sprintf(
            '<input type=text name=api_code id=ohbe_api_code value="%s">',
            OHBE_Main::getSettings('api_code')
        );
        $html .= sprintf(
            '<p>'
            . __("You can find your connection code on the "
            . "<a %s>booking engine integration page</a> in Redforts.", 'ohbe')
            . '</p>',
            'href=https://oscar.redforts.com/setup,be,integration,wordpress '
            . 'target=_blank'
        );
        echo $html;
        OHBE_Tools::printFlashMessage('api_code', false);
    }

    /**
     * Display multiple accounts table in settings page.
     *
     * @return void
     */
    public function connectMultipleAccountsCallback() {
        $is_multi = OHBE_Main::getSettings('is_multiple_accounts');
        echo '<!-- ' . $is_multi . ' -->';

        echo '<p><input '
            . 'id=show_accounts '
            . 'type=checkbox '
            . 'name=is_multiple_accounts';
        if ($is_multi) {
            echo ' checked';
        }
        echo '></p>'
            . '<div id=accounts';
        if (!$is_multi) {
            echo ' style="display:none"';
        }
        echo '><table><thead><tr>'
            . '<td><b>' . __("Selector title", 'ohbe') . '</b></td>'
            . '<td><b>' . __("Connection code", 'ohbe') . '</b></td>'
            . '<td><b>' . __("Account label", 'ohbe') . '</b></td>'
            . '<td></td></tr></thead>'
            . '<tbody>';
        if (isset(get_option('ohbe_settings')['accounts'])) {
            foreach (get_option('ohbe_settings')['accounts'] as $account) {
                echo '<tr><td><input '
                        . 'name=accounts_selector_title[] '
                        . 'pattern="\S.*" '
                        . 'required '
                        . 'type=text '
                        . 'value="' . $account['title'] . '"'
                        . '></td>'
                    . '<td><input '
                        . 'maxlength=20 '
                        . 'name=accounts_connection_code[] '
                        . 'pattern="[A-Za-z0-9]{20}" '
                        . 'required '
                        . 'type=text '
                        . 'value="' . $account['code'] .'"'
                        . '></td>'
                    . '<td><input '
                        . 'name=accounts_account_label[] '
                        . 'pattern="[A-Za-z0-9_]+" '
                        . 'title="' . __(
                            "Only letters, digits and underscores.",
                            'ohbe'
                            ) . '" '
                        . 'type=text '
                        . 'required '
                        . 'value="' . $account['label'] . '"></td>'
                    . '<td><input '
                        . 'class="button remove-account-btn" '
                        . 'type=button '
                        . 'value="' . __("Remove", 'ohbe') . '"'
                        . '></td>'
                    . '</tr>';
            }
        }
        echo '<tr class=add><td colspan=4><input '
                . 'class=button '
                . 'id=add_account_btn '
                . 'type=button '
                . 'value="' . __("Add", 'ohbe') . '">'
            . '</table>'
            . '<p>'
            . __(
                "The account selector can be enabled by adding "
                . "«accounts_selector=\"true\"» to the shortcode, "
                . "for example: [ohbe_search accounts_selector=\"true\"]",
                'ohbe')
            . '</p>'
            . '<p>'
            . __(
                "The label can be used in the shortcode to select the account, "
                . "for example: [ohbe_search account=\"default\"]",
                'ohbe')
            . '</p>'
            . '</div>';
    }

    /**
     * Define help tabs.
     *
     * @return void
     */
    public static function addHelpTabs() {
        $screen = get_current_screen();
        $screen->add_help_tab(array(
            'id' => 'main_help',
            'title' => __("Overview", 'ohbe'),
            'content' => '',
            'callback' => array(__CLASS__, 'helpCallback'),
        ));
        if (OHBE_LOG) {
            $screen->add_help_tab(array(
                'id' => 'logs',
                'title' => __("Plugin logs", 'ohbe'),
                'content' => '',
                'callback' => array(__CLASS__, 'getLogs'),
            ));
        }
    }

    /**
     * Define help callback.
     *
     * @return void
     */
    public static function helpCallback() {
        $base = OHBE_PATH . "includes/admin/help";
        $lang = self::getCurrentLocale();
        if (!file_exists("$base-$lang.php")) {
            $lang = 'en';
        }
        include "$base-$lang.php";
    }

    public static function getLogs() {
        $log_dir = OHBE_PATH . 'logs/';

        $html = '<ul>';
        $content = '';
        foreach (scandir($log_dir) as $f) {
            if (preg_match('/\d{8}\.log/', $f)) {
                $html .= '<li>' . $f . '</li>';
                $content .= file_get_contents($log_dir . $f);
            }
        }
        $html .= '</ul><pre>' . $content . '</pre>';
        echo $html;
    }

    /**
     * Display "button" font color field in settings page.
     *
     * @return void
     */
    public static function btnFontColorCallback() {
        printf(
            '<input class=color-field name=btn_font_color value="%s">',
            OHBE_Main::getSettings('btn_font_color')
        );
    }

    /**
     * Display "button" background color field in settings page.
     *
     * @return void
     */
    public static function btnBgColorCallback() {
        printf(
            '<input class=color-field name=btn_bg_color value="%s">',
            OHBE_Main::getSettings('btn_bg_color')
        );
    }

    /**
     * Adapt iframe size automatically.
     *
     * @return void
     */
    public function adaptSizeAutomaticallyCallback() {
        $is_adapt_size_automatically = OHBE_Main::getSettings(
            'is_adapt_size_automatically'
        );
        $adapt_size_vh = OHBE_Main::getSettings('adapt_size_vh') ?: 100;

        echo '<p><input '
            . 'id=adapt_size_automatically '
            . 'type=checkbox '
            . 'name=is_adapt_size_automatically ';
        if ($is_adapt_size_automatically) {
            echo 'checked ';
        }
        echo '></p>'
            . '<div id=adapt_size_vh';
            if ($is_adapt_size_automatically) {
                echo ' style="display:none" ';
            }
        echo '><p><input '
            . 'type=number '
            . 'name=adapt_size_vh '
            . "value='{$adapt_size_vh}' "
            . '></p>'
            . '<p>' . __("Insert viewport's percentage", 'ohbe') . '</p>'
            . '</div>';
    }

    /**
     * Handle fields when form is submitted.
     *
     * @return void
     */
    public function saveSettings() {
        if (
            $_SERVER['REQUEST_METHOD'] == 'POST'
            && isset($_POST['update_ohbe_settings'])
            && check_admin_referer('update_ohbe_settings', 'update_ohbe_settings')
            && current_user_can('administrator')
        ) {
            OHBE_Main::setSettings(
                'api_code',
                isset($_POST['api_code']) ? $_POST['api_code'] : ''
            );
            delete_option('ohbe_inventory_accounts');
            $accounts = array();
            if (isset($_POST['accounts_connection_code'])) {
                foreach ($_POST['accounts_connection_code'] as $key => $code) {
                    $label = $_POST['accounts_account_label'][$key];
                    if (
                        $code !== ''
                        && preg_match('/^\w+$/', $label)
                        && $_POST['accounts_selector_title'][$key] !== ''
                    ) {
                        $accounts[] = [
                            'code' => $code,
                            'label' => $label,
                            'title' => $_POST['accounts_selector_title'][$key],
                        ];
                    }
                }
            }
            OHBE_Main::setSettings('accounts', $accounts);
            OHBE_Main::setSettings(
                'is_multiple_accounts',
                isset($_POST['is_multiple_accounts']) && $accounts
            );
            foreach ($accounts as $key => $account) {
                OscarAPI::getInventoryAccounts($key);
            }
            OHBE_Main::setSettings(
                'btn_font_color',
                isset($_POST['btn_font_color'])
                    ? $_POST['btn_font_color']
                    : '#fff'
            );
            OHBE_Main::setSettings(
                'btn_bg_color',
                isset($_POST['btn_bg_color'])
                    ? $_POST['btn_bg_color']
                    : '#000'
            );
            OHBE_Main::setSettings(
                'is_adapt_size_automatically',
                isset($_POST['is_adapt_size_automatically'])
            );
            OHBE_Main::setSettings(
                'adapt_size_vh',
                isset($_POST['adapt_size_vh']) ? $_POST['adapt_size_vh'] : ''
            );
            OscarAPI::getInventoryData();
        }
    }

    /**
     * Enqueue CSS and JS.
     *
     * @return void
     */
    public function loadAssets() {
        // Javascript
        wp_enqueue_script(
            'ohbe-admin', OHBE_URL . 'assets/js/ohbe-admin.js',
            array('jquery', 'wp-color-picker' ), OHBE_VERSION, 1
        );
        wp_localize_script(
            'ohbe-admin',
            'trans_strings',
            array(
                'default_title' => __("Hotel", 'ohbe'),
                'label_format' => __(
                    "Only letters, digits and underscores.",
                    'ohbe'
                ),
                'remove' => __("Remove", 'ohbe')
            )
        );
        // CSS
        wp_enqueue_style('wp-color-picker');
    }

    /**
     * Sanitize user input for options.
     *
     * @return void
     */
    public function sanitizeInput($input) {
        $valid_input = array();
        // Connection_code
        $connection_code = trim($input['api_code']);
        if (preg_match('/^[0-9a-zA-Z]{8,}+$/', $connection_code)) {
            $valid_input['connection_code'] = strip_tags(
                stripslashes($connection_code)
            );
        }
        elseif (empty($connection_code)) {
            $valid_input['connection_code'] = '';
        }
        else {
            add_settings_error(
                'oscar-hotel-settings',
                'ohbe-connection-code-error',
                __("Not a correct connection code", 'ohbe'),
                'error'
            );
            $valid_input['connection_code'] = $this->settings['connection_code'];
        }
        // Adapt size
        $adapt_size_vh = trim($input['adapt_size_vh']);
        $valid_input['adapt_size_vh'] = is_numeric($adapt_size_vh)
            ? $adapt_size_vh
            : 100;

        apply_filters('sanitize_options', $valid_input, $input);
    }

    /**
     * Get current locale
     *
     * @return string Current locale. Example: 'en'
     */
    public static function getCurrentLocale() {
        return substr(get_locale(), 0, 2);
    }
}
