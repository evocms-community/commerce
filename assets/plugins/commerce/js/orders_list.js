(function($) {
    $(function() {
        var $dateInput = $('input.date-range');

        var ranges = {};
        ranges[_dpl.today]      = [moment(), moment()];
        ranges[_dpl.yesterday]  = [moment().subtract(1, 'days'), moment().subtract(1, 'days')];
        ranges[_dpl.lastWeek]   = [moment().subtract(6, 'days'), moment()];
        ranges[_dpl.lastMonth]  = [moment().subtract(29, 'days'), moment()];
        ranges[_dpl.thisMonth]  = [moment().startOf('month'), moment().endOf('month')];
        ranges[_dpl.prevMonth]  = [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')];

        $dateInput.daterangepicker({
            autoUpdateInput: false,
            alwaysShowCalendars: true,
            applyClass: 'btn btn-primary',
            cancelClass: 'btn btn-secondary',
            locale: {
                format:           'YYYY-MM-DD',
                applyLabel:       _dpl.applyLabel,
                cancelLabel:      _dpl.cancelLabel,
                customRangeLabel: _dpl.customRangeLabel,
                daysOfWeek:       _dpl.daysOfWeek,
                monthNames:       _dpl.monthNames
            },
            ranges: ranges
        });

        $dateInput.on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
        });

        $dateInput.on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('');
        });
        
        $('.order-delete').on('click', function(e) {
            if (!confirm(_oll.confirmDelete)) {
                e.preventDefault();
            }
        });
    });
})(jQuery);
