(function($) {
    'use strict';

    var ohbe_iframe;
    var ohbe_is_mobile = (
        typeof window.orientation !== 'undefined'
        || navigator.userAgent.indexOf('IEMobile') !== -1
    );
    window.addEventListener('message', function(ev) {
        if (
            !ev.data
            || typeof ev.data !== 'string'
            || !ev.data.startsWith('ohbe_')
        ) {
            return;
        }
        if (!ohbe_iframe) {
            ohbe_iframe = document.getElementById('ohbe_iframe');
            if (ohbe_is_mobile) {
                ohbe_iframe.style.maxHeight = (
                    'max(70vh, calc(100vh - ' + (ohbe_iframe.offsetTop + 60) + 'px))'
                );
            }
        }
        if (ohbe_is_mobile && ev.data.startsWith('ohbe_book')) {
            ohbe_iframe.scrollIntoView({'behavior': 'smooth', 'block': 'end'});
        }
        ohbe_iframe.style.height = ev.data.split('_')[1];
    }, false);
})(jQuery);
