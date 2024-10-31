<?php
defined('ABSPATH') or exit();

/**
 * Handles parameters POST & GET params when
 * search form has been submitted.
 *
 * @return void
 */
function ohbeSearchForm () {
    if (
        $_SERVER['REQUEST_METHOD'] == 'POST'
        && isset($_POST['ohbe_search_submit'])
    ) {
        if (isset($_POST['acco'])) {
            $id = $_POST['acco'];
        }
        elseif (isset($_GET['acco'])) {
            $id = $_GET['acco'];
        }
        else {
            $id = false;
        }

        if (isset($_POST['ohbe_account'])) {
            $account = $_POST['ohbe_account'];
        }
        elseif (isset($_GET['account'])) {
            $account = $_GET['account'];
        }
        else {
            $account = null;
        }
        $errors = ohbeSearchFormHandle($id, $account);
    }
}

add_action('init', 'ohbeSearchForm');

/**
 * Create search form.
 *
 * @param null $atts
 * @param bool $is_widget
 *
 * @return string Search form's html structure.
 */
function ohbeSearchFormOutput ($atts=null, $is_widget=false) {
    $group_id = uniqid();
    $html = '<form class=ohbe-search-form method=POST>'
        . '<div class=input-group>';
    if (
        isset($atts['accounts_selector'])
        && count(OHBE_Main::getSettings('accounts')) > 0
    ) {
        $html .= '<select name=ohbe_account'
            . ' class=ohbe-accounts-selector'
            . " data-group-id={$group_id}>";
        foreach (OHBE_Main::getSettings('accounts') as $account) {
            $html .= "<option value={$account['label']}>"
                . "{$account['title']}</option>";
        }
        $html .= '</select>';
    }
    $html .= '<input name=ohbe_arrival class=ohbe-datepicker-arrival'
        . ' placeholder="' . __("Arrival", 'ohbe') . '" required readonly'
        . " data-group-id={$group_id}>"
        . '<input name=ohbe_departure class=ohbe-datepicker-departure'
        . ' placeholder="' . __("Departure", 'ohbe') . '" required readonly'
        . " data-group-id={$group_id}>"
        . '<input type=hidden name=ohbe_datepicker_arrival>'
        . '<input type=hidden name=ohbe_datepicker_departure>';
    if (isset($atts['account']) && !isset($atts['accounts_selector'])) {
        $html .= '<input type=hidden name=ohbe_account'
            . " value={$atts['account']}>";
    }
    if (isset($atts['ispr'])) {
        $html .= '<input name=ohbe_promo class=ohbe-promo'
            . ' placeholder="' . __("Promo code", 'ohbe') . '">'
            . ' <input type=hidden name=ispr value=1>';
    }
    $html .= '<button type=submit name=ohbe_search_submit '
        . 'class="btn-ohbe ohbe-search-submit">'
        . __("Search", 'ohbe') . '</button></div></form>';

    return $html;
}

add_shortcode('ohbe_main_search', 'ohbeSearchFormOutput');

/**
 * Handles search form for shortcode
 *
 * @param array $atts Shortcode attributes.
 *
 * @return string Search form's html structure.
 */
function ohbeSearchShortcode($atts) {
    $a = shortcode_atts(
        array(
            'acco_id' => null,
            'account' => null,
            'promo_field' => false,
            'accounts_selector' => false
        ),
        $atts
    );
    // Check parameter
    $parameterValue = array('on', '1', 'true');
    if (
        (
            !empty($a['accounts_selector'])
            && !in_array($a['accounts_selector'], $parameterValue)
        )
        || (
            !empty($a['promo_field'])
            && !in_array($a['promo_field'], $parameterValue)
        )
    ) {
        return sprintf(
            '<div class="alert alert-danger">%s</div>',
            __("Wrong shortcode parameter.", 'ohbe')
        );
    }
    // Check accounts
    if (
            !$a['account']
            && !$a['accounts_selector']
            && !get_option('ohbe_inventory')
        || (
            $a['account']
            && is_null(OHBE_Tools::getAccountKeyByLabel($a['account']))
        )
    ) {
        return sprintf(
            '<div class="alert alert-danger">%s</div>',
            __("This connection code doesn't exist.", 'ohbe')
        );
    }
    elseif (
        $a['accounts_selector']
        && (
            !OHBE_Main::getSettings('accounts')
            || count(OHBE_Main::getSettings('accounts')) === 0
        )
    ) {
        return sprintf(
            '<div class="alert alert-danger">%s</div>',
            __("There are no multiple accounts.", 'ohbe')
        );
    }
    $group_id = uniqid();
    $html = '<form class=ohbe-search-form method=POST>'
        . '<div class=input-group>';
    if (
        $a['accounts_selector']
        && count(OHBE_Main::getSettings('accounts')) > 0
    ) {
        $html .= '<select name=ohbe_account'
            . ' class=ohbe-accounts-selector'
            . " data-group-id={$group_id}>";
        foreach (OHBE_Main::getSettings('accounts') as $account) {
            $html .= "<option value={$account['label']}>"
                . "{$account['title']}</option>";
        }
        $html .= '</select>';
    }
    $html .= '<input name=ohbe_arrival class=ohbe-datepicker-arrival'
        . ' placeholder="' . __("Arrival", 'ohbe') . '" required readonly'
        . " data-group-id={$group_id}>"
        . '<input name=ohbe_departure class=ohbe-datepicker-departure'
        . ' placeholder="' . __("Departure", 'ohbe') . '" required readonly'
        . " data-group-id={$group_id}>"
        . '<input type=hidden name=ohbe_datepicker_arrival>'
        . '<input type=hidden name=ohbe_datepicker_departure>';
    if ($a['promo_field']) {
        $html .= '<input name=ohbe_promo class=ohbe-promo'
            . ' placeholder="' . __("Promo code", 'ohbe') . '">'
            . ' <input type=hidden name=ispr value=1>';
    }
    if ($a['acco_id']) {
        $html .= '<input type=hidden name=ohbe_acco'
            . ' value="' . intval($a['acco_id']) . '">';
    }
    if ($a['account'] && !$a['accounts_selector']) {
        $html .= '<input type=hidden name=ohbe_account'
            . " value='{$a['account']}'>";
    }
    $html .= '<button type=submit name=ohbe_search_submit '
        . 'class="btn-ohbe ohbe-search-submit">'
        . __("Search", 'ohbe') . '</button></div></form>';

    return $html;
}

add_shortcode('ohbe_search', 'ohbeSearchShortcode');

/**
 * Set calendar data just once for all api codes available,
 * depending on whether a search form (shortcode/widget)
 * has been displayed
 *
 * @return void
 */
function ohbeSetCalendarData() {
    if (!OHBE_Main::getIsSearchFormDisplayed() && !is_admin()) {
        $responses = array();
        // Api code
        $responses['api_code'] = OscarAPI::getCalendarData();
        if (isset($responses['api_code']['body']['inventory_version'])) {
            $updated_oscar_data = OHBE_Tools::checkVersion(
                $responses['api_code']['body']['inventory_version']
            );
        }
        if (isset($updated_oscar_data)) {
            OscarAPI::getInventoryData();
        }
        // Accounts
        if (OHBE_Main::getSettings('accounts')) {
            $responses['accounts'] = array();
            foreach (OHBE_Main::getSettings('accounts') as $k => $a) {
                $responses['accounts'][$a['label']] =
                    OscarAPI::getCalendarData($k);
                $updated_oscar_data = OHBE_Tools::checkVersion(
                    $responses['accounts'][$a['label']]['body']['inventory_version']
                );
                if (isset($updated_oscar_data)) {
                    OscarAPI::getInventoryAccounts($k);
                }
            }
        }
        wp_localize_script(
            'ohbe_common',
            'data',
            $responses
        );
        wp_localize_script(
            'ohbe_common',
            'lang',
            array('locale' => OscarAPI::getLanguage())
        );
        OHBE_Main::setIsSearchFormDisplayed(true);
    }
}
