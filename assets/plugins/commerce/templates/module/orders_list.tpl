<?php $this->extend('layout.tpl'); ?>

<?php $this->block('content'); ?>
    <div class="tab-page" id="tab_main">
        <h2 class="tab">
            <?= $lang['module.orders_caption'] ?>
        </h2>

        <script type="text/javascript">
            tpCommerce.addTabPage(document.getElementById('tab_main'));
        </script>

        <div class="row">
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
                
                                <td>
                                    <a href="<?= $this->module->makeUrl('orders/view', 'order_id=' . $order['id']) ?>" class="btn btn-primary">
                                        <?= $lang['module.show_order_btn'] ?>
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
<?php $this->endBlock(); ?>
