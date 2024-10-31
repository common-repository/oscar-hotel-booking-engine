<?php

get_header();

if (isset($_GET['ohbe']) && $_GET['ohbe'] == OHBE_BOOKING_PAGE) {
    echo '<div class=ohbe-page>';

    $account_label = isset($_GET['account']) && !empty($_GET['account'])
        ? $_GET['account']
        : null;

    if ($inv_be_code = OHBE_Tools::getInvBeCode($account_label)) {
        $account_key = OHBE_Tools::getAccountKeyByLabel($account_label);
        $lang = OscarAPI::getLanguage($account_key);
        $params = array(
            'arrival' => isset($_GET['arrival']) && isset($_GET['departure'])
                ? $_GET['arrival']
                : null,
            'departure' => isset($_GET['arrival']) && isset($_GET['departure'])
                ? $_GET['departure']
                : null,
            'account' => $account_label,
            'promo' => isset($_GET['promo']) && !empty($_GET['promo'])
                ? $_GET['promo']
                : null,
            'acco' => isset($_GET['acco']) && !empty($_GET['acco'])
                ? $_GET['acco']
                : null,
            
        );
        $src = 'https://' . OHBE_HOST . "/{$lang}/iframe/{$inv_be_code}/?"
            . http_build_query($params);
        echo "<iframe allowfullscreen frameborder=0 id=ohbe_iframe src={$src} ";

        if (!OHBE_Main::getSettings('is_adapt_size_automatically')) {
            $adapt_size_vh = OHBE_Main::getSettings('adapt_size_vh') ?: 100;
            echo "style='height: {$adapt_size_vh}vh' ";
        }

        echo 'width=100%></iframe>';
    }
    echo '</p>';

    // Javascript
    wp_enqueue_script('ohbe_booking');
}

get_footer();
