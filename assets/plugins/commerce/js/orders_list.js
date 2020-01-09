(function($) {
    $(function() {
        $('input.date-range')
            .daterangepicker({
                autoUpdateInput: false,
                alwaysShowCalendars: true,
                applyClass: 'btn btn-primary',
                cancelClass: 'btn btn-secondary',
                locale: {
                    format: 'DD.MM.YYYY',
                    applyLabel: 'Выбрать',
                    cancelLabel: 'Очистить',
                    fromLabel: 'С',
                    toLabel: 'По',
                    customRangeLabel: 'Свой интервал',
                    weekLabel: 'Н',
                    daysOfWeek: [ 'Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб' ],
                    monthNames: [ 'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь' ]
                },
                ranges: {
                    'Сегодня':           [ moment(), moment() ],
                    'Вчера':             [ moment().subtract(1, 'days'), moment().subtract(1, 'days') ],
                    'Последние 7 дней':  [ moment().subtract(6, 'days'), moment() ],
                    'Последние 30 дней': [ moment().subtract(29, 'days'), moment() ],
                    'Этот месяц':        [ moment().startOf('month'), moment().endOf('month') ],
                    'Предыдущий месяц':  [ moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month') ]
                }/*/*
            /*
                startDate: moment().subtract(29, 'days'),
                endDate: moment(),
                
                
            */})
            .on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('DD.MM.YYYY') + ' - ' + picker.endDate.format('DD.MM.YYYY'));
            })
            /*
            .on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
            })*/
            ;//.val(moment().subtract(29, 'days').format('DD.MM.YYYY') + ' - ' + moment().format('DD.MM.YYYY'));
    });
})(jQuery);
