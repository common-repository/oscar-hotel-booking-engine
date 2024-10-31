<?php
defined('ABSPATH') or exit();

/**
 * Redforts Hotel Booking Engine
 * API class
 *
 * @author Redforts Software SL
 * @link https://redforts.com
 * @since 0.1.1
 */

class OscarAPI
{
    const API_VERSION = 5;

    /**
     * Get the calendar dates availability in Redforts.
     *
     * @param string $account_key Account position in array.
     *
     * @return array Redforts calendar data call response.
     */
    public static function getCalendarData($account_key = null) {
        if (!is_null($account_key)) {
            $accounts = OHBE_Main::getSettings('accounts');
            $api_code = isset($accounts[$account_key]['code'])
                ? $accounts[$account_key]['code']
                : null;
        }
        else {
            $api_code = OHBE_Main::getSettings('api_code');
        }

        if ($api_code) {
            $body = array(
                'api_version' => self::API_VERSION,
                "system_info" => array(
                    "ohbe" => OHBE_VERSION,
                    "os" => php_uname('s') . ' ' . php_uname('r'),
                    "php" => phpversion(),
                    "wordpress" => get_bloginfo('version')
                ),
            );

            return OscarAPI::send('calendar_data', $api_code, $body);
        }
    }

    /**
     * Get the inventory in Redforts Hotel Software.
     *
     * @return array Redforts inventory call response.
     */
    public static function getInventory() {
        if (OHBE_Main::getSettings('api_code')) {
            return OscarAPI::send(
                'inventory',
                OHBE_Main::getSettings('api_code'),
                array('api_version' => self::API_VERSION)
            );
        }
    }

    /**
     * Update inventory data from Redforts Hotel Software.
     *
     * @return void
     */
    public static function getInventoryData() {
        if (!$inventory_response = OscarAPI::getInventory()) {
            unset($_SESSION['ohbe']['flash_message']['api_code']);
            delete_option('ohbe_inventory');
        }
        switch ($inventory_response['code']) {
            case 404:
                OHBE_Tools::setFlashMessage(
                    'api_code',
                    __("This connection code doesn't exist.", 'ohbe'),
                    'danger'
                );
                delete_option('ohbe_inventory');
                return;
            case 406:
                OHBE_Tools::setFlashMessage(
                    'api_code',
                    __("This connection code is not active.", 'ohbe'),
                    'danger'
                );
                delete_option('ohbe_inventory');
                return;
        }
        if (isset($inventory_response['body']['errors'])) {
            OHBE_Tools::setFlashMessage(
                'api_code',
                $inventory_response['body']['errors'][0]['text'],
                'error'
            );
            delete_option('ohbe_inventory');
            return;
        }
        else {
            unset($_SESSION['ohbe']['flash_message']['api_code']);
        }
        update_option('ohbe_inventory', $inventory_response['body']);
    }

    /**
     * Get the inventory account in Redforts Hotel Software.
     *
     * @return array Redforts inventory account call response.
     */
    public static function getInventoryAccount($key) {
        if (isset(OHBE_Main::getSettings('accounts')[$key])) {
            return OscarAPI::send(
                'inventory',
                OHBE_Main::getSettings('accounts')[$key]['code'],
                array('api_version' => self::API_VERSION)
            );
        }
    }

    /**
     * Update inventory accounts data from Redforts Hotel Software.
     *
     * @return void
     */
    public static function getInventoryAccounts($key) {
        $inventory_response = OscarAPI::getInventoryAccount($key);
        $ohbe_inventory_accounts = get_option('ohbe_inventory_accounts') ?: array();

        switch ($inventory_response['code']) {
            case 404:
                OHBE_Tools::setFlashMessage(
                    $key,
                    __("This connection code doesn't exist.", 'ohbe'),
                    'danger'
                );
                unset($ohbe_inventory_accounts[$key]);
                update_option('ohbe_inventory_accounts', $ohbe_inventory_accounts);
                return;
            case 406:
                OHBE_Tools::setFlashMessage(
                    $key,
                    __("This connection code is not active.", 'ohbe'),
                    'danger'
                );
                unset($ohbe_inventory_accounts[$key]);
                update_option('ohbe_inventory_accounts', $ohbe_inventory_accounts);
                return;
        }

        if (
            !isset($inventory_response)
            || isset($inventory_response['body']['errors'])
        ) {
            OHBE_Tools::setFlashMessage(
                $key,
                $inventory_response['body']['errors'][0]['text'],
                'error'
            );
            return;
        }
        else {
            $ohbe_inventory_accounts[$key] = $inventory_response['body'];
            if (isset($_SESSION['ohbe']['flash_message'][$key])) {
                unset($_SESSION['ohbe']['flash_message'][$key]);
            }
        }

        update_option('ohbe_inventory_accounts', $ohbe_inventory_accounts);
    }

    /**
     * Set a language for API calls to Redforts Hotel Software based on WP locale.
     *
     * @return string|null Locale slug. Example: 'en'
     */
    public static function getLanguage($key = null) {
        $inv_langs = $key
            ? OHBE_Main::getInventoryAccount($key, 'languages')
            : OHBE_Main::getInventory('languages');

        if ($inv_langs) {
            foreach ($inv_langs as $code => $data) {
                // Polylang
                if (
                    function_exists('pll_current_language')
                    && $code == pll_current_language()
                ) {
                    return pll_current_language();
                }
                // Locale
                if ($code == substr(get_locale(), 0, 2) || $code == get_locale()) {
                    return $code;
                }
            }
        }

        return OHBE_Main::getInventory('default_lang');
    }

    /**
     * Get the full URL for the API call.
     *
     * @param string $call API call.
     * @param string $api_code API code.
     * @return string|null Locale slug. Example: 'en'
     */
    public static function getUrl($call, $api_code) {
        return sprintf('https://%s/wpp/%s/%s',
            OHBE_HOST,
            $api_code,
            $call
        );
    }

    public static function send($call, $api_code, $body) {
        $post = wp_remote_post(
            OscarAPI::getUrl($call, $api_code),
            array(
                'headers' => array(
                    'Content-Type' => 'application/json; charset=UTF-8'
                ),
                'body' => json_encode($body),
                // Allow no SSL API calls when WP_DEBUG is set to true
                'sslverify' => !WP_DEBUG
            )
        );

        return array(
            'body' => json_decode(wp_remote_retrieve_body($post), true),
            'code' => wp_remote_retrieve_response_code($post)
        );
    }

}
