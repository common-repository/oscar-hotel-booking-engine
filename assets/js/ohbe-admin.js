(function($) {
  'use strict';

    var accounts_table = $('#accounts');
    var adapt_size_vh = $('#adapt_size_vh');

    function add_row(copy_from_main) {
        var key = '';
        var label = '';
        var title = '';
        if (copy_from_main) {
            key = $('#ohbe_api_code').val();
            label = 'default';
            title = trans_strings.default_title;
        }
        var last_row = accounts_table.find('tr.add');
        last_row.before(
            '<tr>'
                + '<td><input '
                    + 'name=accounts_selector_title[] '
                    + 'pattern="^\\S.*" '
                    + 'required '
                    + 'type=text '
                    + `value="${title}"></td>`
                + '<td><input '
                    + 'maxlength=20 '
                    + 'name=accounts_connection_code[] '
                    + 'pattern="[A-Za-z0-9]{20}" '
                    + 'required '
                    + 'type=text '
                    + `value="${key}"></td>`
                + '<td><input '
                    + 'name=accounts_account_label[] '
                    + 'pattern="[A-Za-z0-9_]+" '
                    + 'required '
                    + `title="${trans_strings.label_format}" `
                    + 'type=text '
                    + `value="${label}"></td>`
                + '<td><input '
                    + 'class="button remove-account-btn" '
                    + 'type=button '
                    + `value="${trans_strings.remove}"></td>`
            + '</tr>'
        );
        last_row.prev().find('.remove-account-btn').click(function() {
            $(this).closest('tr').remove();
        });
    }

    function hide_multi_account() {
        accounts_table.find('tr').not('.add').remove();
        accounts_table.hide();
    }

    function show_multi_account() {
        add_row(true); // Copy details from the main account.
        accounts_table.show();
    }

    $('#add_account_btn').click(function() {
        add_row(false);
    });

    $('.remove-account-btn').click(function() {
        $(this).closest('tr').remove();
    });

    $('#show_accounts').click(function() {
        if ($(this).is(':checked')) {
            show_multi_account();
        }
        else {
            hide_multi_account();
        }
    });

    $('#adapt_size_automatically').click(function() {
        if ($(this).is(':checked')) {
            adapt_size_vh.hide();
        }
        else {
            adapt_size_vh.show();
        }
    });

    $('.color-field').wpColorPicker();
})(jQuery);
