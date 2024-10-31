<?php
defined('ABSPATH') or exit();

/**
 * Redforts Hotel Booking Engine
 * Functions for different tasks
 *
 * @author Redforts Software SL
 * @link https://redforts.com
 * @since 0.1.1
 */
class OHBE_Tools
{
    /**
     * Check whether it is needed to update the inventory.
     *
     * @param int $version Number given by the Redforts inventory call
     * to know if inventory updated is needed.
     * @return bool
     */
    public static function checkVersion($version)
    {
        if (isset($version) && !OHBE_Main::getInventory('version')) {
            return true;
        }
        if (
            isset($version) && OHBE_Main::getInventory('version')
            && ($version > (int) OHBE_Main::getInventory('version'))
        ) {
            return true;
        }

        return false;
    }

    /**
     * Get home URL with language slug.
     *
     * @return string URL
     */
    public static function getBaseUrl() {
        if (
            function_exists('pll_home_url')
            && pll_home_url(pll_current_language('slug'))
        ) {
            return self::getPolylangOhbePagePermalink('ohbe');
        }

        return self::getOhbePagePermalink();
    }

    /**
     * Get the key position in the accounts array by its label.
     *
     * @return int|null Account key
     */
    public static function getAccountKeyByLabel($account_label) {
        $accounts = OHBE_Main::getSettings('accounts')
        ? OHBE_Main::getSettings('accounts')
        : array();
        $account_key = array_search(
            $account_label,
            array_column($accounts, 'label')
        );

        return $account_key;
    }

    /**
     * Get inventory BE code.
     *
     * @return string Inventory BE code
     */
    public static function getInvBeCode($account_label) {
        if ($account_label) {
            $account_key = self::getAccountKeyByLabel($account_label);
            if (is_int($account_key)) {
                $inv_be_code = OHBE_Main::getInventoryAccount(
                    $account_key,
                    'be_code'
                );
            } else {
                OHBE_Tools::mustSetInventory(0);
                $inv_be_code = '';
            }
        }
        else {
            if (!get_option('ohbe_inventory')) {
                OHBE_Tools::mustSetInventory('api_code');
            }
            $inv_be_code = OHBE_Main::getInventory('be_code');
        }

        return $inv_be_code;
    }

    /**
     * Get OHBE page permalink for Polylang.
     *
     * @param $page_slug string Page slug.
     * @return string Page permalink or home URL if the page is not found.
     */
    public static function getPolylangOhbePagePermalink($page_slug) {
        $page = get_page_by_path($page_slug);

        if (empty($page_slug) || empty($page) || is_null($page)) {
            return home_url('/');
        }

        $page_ID_current_lang = pll_get_post($page->ID);

        return empty($page_ID_current_lang)
            ? get_permalink($page->ID)
            : get_permalink($page_ID_current_lang);
    }

    /**
     * Get OHBE page permalink without languages.
     *
     * @return string URL
     */
    public static function getOhbePagePermalink() {
        return ($page = get_page_by_path('ohbe'))
            ? get_page_link($page)
            : home_url('/');
    }

    /**
     * Check whether WP has Polylang plugin.
     * pll_get_post_translations function is created when this plugin exists.
     *
     * @return boolean
     */
    public static function hasPolylang() {
        return function_exists('pll_get_post_translations');
    }

    /**
     * Check whether WP has WPML plugin.
     * SitePress class is created when this plugin exists.
     *
     * @return boolean
     */
    public static function hasWPML() {
        return class_exists('SitePress');
    }

    /**
     * Set an alert when there is no inventory.
     *
     * @param string $key Key to set the flash message's position.
     * @return void
     */
    public static function mustSetInventory($key) {
        OHBE_Tools::setFlashMessage(
            $key,
            __(
                "The connection code doesn't exists. Please, go to the plugin "
                    . "configuration page enter a new one",
                'ohbe'
            ),
            'danger'
        );
        OHBE_Tools::printFlashMessage($key);
    }

    /**
     * Print flash message.
     *
     * @param string $key Key to get the flash message's position.
     * @param string $is_unset
     * @return void
     */
    public static function printFlashMessage($key, $is_unset = true) {
        if (isset($_SESSION['ohbe']['flash_message'][$key])) {
            printf(
                '<div class="alert alert-%s">%s</div>',
                $_SESSION['ohbe']['flash_message'][$key]['type'],
                $_SESSION['ohbe']['flash_message'][$key]['message']
            );
            if ($is_unset) {
                unset($_SESSION['ohbe']['flash_message'][$key]);
            }
        }
    }

    /**
     * Set flash message.
     *
     * @param string $message Message to be displayed.
     * @param string $type Type of alert [success, danger].
     * @param string $key Key to position the flash message.
     * @return void
     */
    public static function setFlashMessage(
        $key,
        $message,
        $type = 'success'
    ) {
        $_SESSION['ohbe']['flash_message'][$key] = array(
            'message' => $message,
            'type' => $type
        );
    }

    /**
     * Set the URL params
     *
     * @param string $page The page's name.
     * @param bool $must_keep_parameters If the current URL params must be used.
     * @param array $drop_params_list Params to be removed.
     * @return array All the params used for the new URL.
     */
    public static function setUrlParams(
        $page,
        $must_keep_parameters=false,
        $drop_params_list=array()
    ) {
        $url_params = array();
        if (!empty($_GET) && $must_keep_parameters) {
            foreach ($_GET as $key => $value) {
                if (!in_array($key, $drop_params_list)) {
                    $url_params[$key] = urlencode($value);
                }
            }
        }
        elseif (isset($_GET['lang'])) {
            $url_params['lang'] = urlencode($_GET['lang']);
        }
        $url_params['ohbe'] = $page;

        // SEARCH FORM
        // Arrival date
        if (isset($_POST['ohbe_datepicker_arrival'])) {
            $arrival_date = DateTime::createFromFormat(
                '!Y-m-d',
                $_POST['ohbe_datepicker_arrival']
            );
            if ($arrival_date) {
                $url_params['arrival'] = urlencode(
                    $arrival_date->format('Y-m-d')
                );
            }
        }
        // Acco
        if (isset($_POST['ohbe_acco'])) {
            $url_params['acco'] = urlencode($_POST['ohbe_acco']);
        }
        // Departure date
        if (isset($_POST['ohbe_datepicker_departure'])) {
            $departure_date = DateTime::createFromFormat(
                '!Y-m-d',
                $_POST['ohbe_datepicker_departure']
            );
            if ($departure_date) {
                $url_params['departure'] = urlencode(
                    $departure_date->format('Y-m-d')
                );
            }
        }
        // Promo code
        if (isset($_POST['ohbe_promo'])) {
            $url_params['promo'] = urlencode($_POST['ohbe_promo']);
        }
        // Account label
        if (isset($_POST['ohbe_account'])) {
            $url_params['account'] = urlencode($_POST['ohbe_account']);
        }

        return $url_params;
    }

    /**
     * Remove "string" indexes from array.
     *
     * @return array Array without "string" indexes
     */
    public static function stripArrayIndexes($array) {
        foreach ($array as $item) {
            $array_stripped[] = $item;
        }

        return $array_stripped;
    }
}
