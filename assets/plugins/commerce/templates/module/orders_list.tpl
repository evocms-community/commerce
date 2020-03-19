<?php $this->extend('layout.tpl'); ?>

<?php if (!$modx->getConfig('commerce_ordersfilters_active')): ?>
    <?php $this->block('buttons'); ?>
        <a href="<?= $this->module->makeUrl('orders/toggle-filters') ?>" class="btn btn-secondary">
            <i class="fa fa-filter"></i>
            <span><?= $lang['module.show_filters'] ?></span>
        </a>
    <?php $this->endBlock(); ?>
<?php endif; ?>

<?php $this->block('content'); ?>
    <div class="tab-page" id="tab_main">
        <h2 class="tab">
            <?= $lang['module.orders_caption'] ?>
        </h2>

        <script type="text/javascript">
            tpCommerce.addTabPage(document.getElementById('tab_main'));
        </script>

        <div class="row">
            <?php if ($modx->getConfig('commerce_ordersfilters_active')): ?>
                <div class="list-filters">
                    <a href="<?= $this->module->makeUrl('orders/toggle-filters') ?>" class="fa fa-times" title="<?= $lang['module.hide_filters'] ?>"></a>

                    <form action="<?= $module->makeUrl('orders') ?>" method="get">
                        <?php foreach ($filters as $filter): ?>
                            <div class="filters-item">
                                <label><?= $filter['title'] ?></label>
                                <?= $filter['content'] ?>
                            </div>
                        <?php endforeach; ?>

                        <div class="submit-btn">
                            <input type="hidden" name="a" value="<?= htmlentities($_GET['a']) ?>">
                            <input type="hidden" name="id" value="<?= htmlentities($_GET['id']) ?>">
                            <input type="hidden" name="type" value="orders">
                            <button type="submit" class="btn btn-primary"><?= $lang['module.filter_btn'] ?></button>
                            <a href="<?= $module->makeUrl('orders') ?>" class="btn btn-secondary"><?= $lang['module.reset_filters_btn'] ?></a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table data">
                    <thead>
                        <tr>
                            <?php foreach ($columns as $column): ?>
                                <td<?= !empty($column['style']) ? ' style="' . $column['style'] . '"' : '' ?>><?= $column['title'] ?></td>
                            <?php endforeach; ?>
                            <td style="width: 1%;"></td>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <?php foreach ($order['cells'] as $name => $cell): ?>
                                    <td<?= !empty($columns[$name]['style']) ? ' style="' . $columns[$name]['style'] . '"' : '' ?>><?= $cell ?></td>
                                <?php endforeach; ?>

                                <td style="white-space: nowrap;">
                                    <a href="<?= $this->module->makeUrl('orders/view', 'order_id=' . $order['id']) ?>" class="btn btn-primary" title="<?= $lang['module.show_order_btn'] ?>">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                    <a href="<?= $this->module->makeUrl('orders/delete', 'order_id=' . $order['id']) . '&hash=' . md5(MODX_MANAGER_PATH . $order['hash']) ?>" class="btn btn-danger order-delete" title="<?= $lang['module.delete_order_btn'] ?>">
                                        <i class="fa fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?= $modx->getPlaceholder('list.pages') ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script>
        var _dpl = {
            applyLabel:       '<?= htmlentities($lang['dp.apply_label']) ?>',
            cancelLabel:      '<?= htmlentities($lang['dp.cancel_label']) ?>',
            customRangeLabel: '<?= htmlentities($lang['dp.custom_range_label']) ?>',
            today:            '<?= htmlentities($lang['dp.today']) ?>',
            yesterday:        '<?= htmlentities($lang['dp.yesterday']) ?>',
            lastWeek:         '<?= htmlentities($lang['dp.last_week']) ?>',
            lastMonth:        '<?= htmlentities($lang['dp.last_month']) ?>',
            thisMonth:        '<?= htmlentities($lang['dp.this_month']) ?>',
            prevMonth:        '<?= htmlentities($lang['dp.prev_month']) ?>',
            daysOfWeek:       <?= json_encode($lang['dp.days_of_week'], JSON_UNESCAPED_UNICODE) ?>,
            monthNames:       <?= json_encode($lang['dp.month_names'], JSON_UNESCAPED_UNICODE) ?>
        };
        var _oll = {
            confirmDelete: '<?= htmlentities($lang['module.confirm_delete']) ?>'
        };
    </script>
    <script src="../assets/plugins/commerce/js/orders_list.js?v=<?= $modx->commerce->getVersion() ?>"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
<?php $this->endBlock(); ?>
