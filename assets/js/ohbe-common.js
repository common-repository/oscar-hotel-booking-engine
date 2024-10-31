(function($) {
    'use strict';

    $('.ohbe-datepicker-arrival').each(function() {
      ohbeGetSearchFormData($(this), false);
    });

    $('.ohbe-accounts-selector').change(function() {
        ohbeGetSearchFormData($(this).siblings('.ohbe-datepicker-arrival'), true);
    });

    /**
     * Get the calendar data for the selected datepicker.
     *
     * @param {object} Arrival datepicker.
     * @returns {object}
     */
    function ohbeGetCalendarData(arrivalDatepicker) {
        var calendar_data;
        var account_label = arrivalDatepicker
            .siblings('[name=ohbe_account]')
            .val();
        if (account_label) {
          calendar_data = data?.accounts?.[account_label]?.body;
        }
        if (
            !calendar_data
            && arrivalDatepicker.siblings('.ohbe-accounts-selector').length > 0
        ) {
            account_label = arrivalDatepicker
              .siblings('.ohbe-accounts-selector')
              .val();
            calendar_data = data?.accounts?.[account_label]?.body;
        }
        if (!calendar_data) {
            calendar_data = data?.api_code?.body;
        }

        return calendar_data;
    }

    /**
     * Add days to a given date.
     *
     * @param {object} d Date object.
     * @param {int} numdays Number of days to add.
     * @returns {object} The date that is numdays after d.
     */
    function ohbeDateAdd(d, numdays) {
        return moment(d).add(numdays, 'days').toDate();
    }

    /**
     * Return a Date object for the given YYYY-MM-DD string.
     *
     * @param {string} s Date string.
     * @returns {object}
     */
    function ohbeGetDateFromIso(s) {
        return moment(s, "YYYY-MM-DD").toDate();
    }

    /**
     * Load datepickers data for the selected the search form.
     *
     * @param {object} Arrival datepicker.
     * @param {object} Calendar data.
     */
    function ohbeLoadDatepickers(arrivalDatepicker, calendar_data, is_reload) {
        var datepickers = arrivalDatepicker
            .parent()
            .find('.ohbe-datepicker-arrival, .ohbe-datepicker-departure');

        datepickers.each(function() {
            $(this).ohbe_datepicker('destroy');
            $(this).val('');
        });

        arrivalDatepicker.siblings(
            'input[name=ohbe_datepicker_arrival],'
            + 'input[name=ohbe_datepicker_departure]'
        ).val('');

        var d = ohbeGetDateFromIso(calendar_data?.first_date);
        var dates_info = calendar_data?.dates_info;
        var dates_range = [];
        var disabled_arrivals = [];
        var disabled_departures = [];
        var len = calendar_data?.dates_info?.length;
        var locale = lang?.locale ?? 'en';
        var value;

        for (var i = 0; i < len; i++) {
            value = dates_info[i];
            if (value != '1') {
                disabled_arrivals.push(moment(d).format('D-M-Y'));
            }
            d = ohbeDateAdd(d, 1);
            if (value == '-') {
                disabled_departures.push(moment(d).format('D-M-Y'));
            }
        }

        datepickers.each(function() {
            var group_id = $(this).attr('data-group-id');

            if (!is_reload) {
                $(this)
                    .ohbe_datepicker({
                        autoclose: true,
                        beforeShowDay: function(date) {
                            // Add style to dates in selected range.
                            if (
                            dates_range[group_id]
                            && dates_range[group_id][0]
                            && dates_range[group_id][1]
                            && date >= dates_range[group_id][0]
                            && date <= dates_range[group_id][1]
                            ) {
                            return {classes: 'range-selected'};
                            }
                            return;
                        },
                        datesDisabled: this.classList.contains(
                            'ohbe-datepicker-arrival'
                        )
                            ? disabled_arrivals
                            : disabled_departures,
                        endDate: ohbeGetDateFromIso(calendar_data?.last_date),
                        format: 'D d M yyyy',
                        language: locale,
                        maxViewMode: 2,
                        startDate: ohbeGetDateFromIso(calendar_data?.first_date),
                        title: `${this.placeholder}:`,
                        weekStart: 1,
                    })
                    .on('changeDate', function(ev) {
                        var date = ev.date;
                        var startdate;
                        var enddate;

                        if (this.classList.value == 'ohbe-datepicker-arrival') {
                            $(this)
                                .siblings('input[name=ohbe_datepicker_arrival]')
                                .val(moment(date).format('YYYY-MM-DD'));

                            startdate = new Date(moment(date).format('YYYY-MM-DD'));
                            // Set departure to arrival date plus one day.
                            date.setDate(date.getDate() + 1);
                            $(this).next().ohbe_datepicker('update', date);
                            $(this)
                                .siblings('input[name=ohbe_datepicker_departure]')
                                .val(moment(date).format('YYYY-MM-DD'));
                            enddate = new Date(moment(date).format('YYYY-MM-DD'));
                        }
                        else {
                            $(this)
                                .siblings('input[name=ohbe_datepicker_departure]')
                                .val(moment(date).format('YYYY-MM-DD'));
                            enddate = new Date(moment(date).format('YYYY-MM-DD'));

                            // Check whether an enddate is less than startdate
                            var sdate = $(this)
                                .siblings('.ohbe-datepicker-arrival')
                                .ohbe_datepicker('getDate');

                            if (sdate && moment(sdate).isSameOrAfter(moment(date))) {
                                date.setDate(date.getDate() - 1);
                                $(this)
                                    .siblings('.ohbe-datepicker-arrival')
                                    .ohbe_datepicker('update', date);
                                $(this)
                                    .siblings('input[name=ohbe_datepicker_arrival]')
                                    .val(moment(date).format('YYYY-MM-DD'));
                            }
                        }
                        startdate =
                            this.classList.value == 'ohbe-datepicker-arrival'
                                ? $(this).ohbe_datepicker('getDate')
                                : $(this)
                                    .siblings('.ohbe-datepicker-arrival')
                                    .ohbe_datepicker('getDate');
                        enddate =
                            this.classList.value == 'ohbe-datepicker-arrival'
                                ? $(this)
                                    .siblings('.ohbe-datepicker-departure')
                                    .ohbe_datepicker('getDate')
                                : $(this).ohbe_datepicker('getDate');
                        // Datepickers check hours matching with 00:00 time.
                        if (startdate) {
                            startdate.setHours(0, 0, 0, 0);
                        }
                        if (enddate) {
                            enddate.setHours(0, 0, 0, 0);
                        }
                        dates_range[$(this).data('group-id')] = [
                            startdate,
                            enddate,
                        ];
                        // Update both datepickers. Pair could have updated
                        // input values.
                        datepickers.ohbe_datepicker('update');

                        if (this.classList.value == 'ohbe-datepicker-arrival') {
                            $(this).next().ohbe_datepicker('show');
                        }
                    })
                    .on('show', function() {
                        $('.datepicker').addClass('ohbe-datepicker');
                    });
            }
            else {
                $(this).ohbe_datepicker({
                    autoclose: true,
                    beforeShowDay: function(date) {
                        // Add style to dates in selected range.
                        if (
                            dates_range[group_id] &&
                            dates_range[group_id][0] &&
                            dates_range[group_id][1] &&
                            date >= dates_range[group_id][0] &&
                            date <= dates_range[group_id][1]
                        ) {
                            return {classes: 'range-selected'};
                        }
                            return;
                    },
                    datesDisabled: this.classList.contains(
                        'ohbe-datepicker-arrival'
                    )
                        ? disabled_arrivals
                        : disabled_departures,
                    endDate: ohbeGetDateFromIso(calendar_data?.last_date),
                    format: 'D d M yyyy',
                    language: locale,
                    maxViewMode: 2,
                    startDate: ohbeGetDateFromIso(calendar_data?.first_date),
                    title: `${this.placeholder}:`,
                    weekStart: 1,
                });
            }
        });
    }

    /**
     * Get all the needed data for the search form.
     *
     * @param {object} Arrival datepicker.
     * @returns {object}
     */
    function ohbeGetSearchFormData(arrivalDatepicker, is_reload) {
        var calendar_data = ohbeGetCalendarData(arrivalDatepicker);
        ohbeLoadDatepickers(arrivalDatepicker, calendar_data, is_reload);
    }
})(jQuery);
