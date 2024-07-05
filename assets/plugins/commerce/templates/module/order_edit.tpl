<?php $this->extend('layout.tpl'); ?>

<?php $this->block('title'); ?>
    <?= sprintf($lang['module.order_edit_caption'], $order['id']) ?>
<?php $this->endBlock(); ?>

<?php $this->block('buttons'); ?>
    <a href="javascript:;" class="btn btn-success" onclick="document.getElementById('order_form').submit();">
        <i class="fa fa-save"></i>
        <span><?= $lang['module.save_btn'] ?></span>
    </a>
    <a href="<?= $this->module->makeUrl('orders/view&order_id=' . $order['id']) ?>" class="btn btn-secondary">
        <i class="fa fa-times-circle"></i>
        <span><?= $lang['module.cancel_btn'] ?></span>
    </a>
<?php $this->endBlock(); ?>

<?php $this->block('content'); ?>
    <div class="tab-page" id="tab_main">
        <h2 class="tab"><?= sprintf($lang['module.order_edit_caption'], $order['id']) ?></h2>

        <script type="text/javascript">
            tpCommerce.addTabPage(document.getElementById('tab_main'));
        </script>

        <form action="<?= $module->makeUrl('orders/save') ?>" method="post" id="order_form">
            <?= csrf_field() ?>
            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">

            <div class="editable-order">
                <div class="sectionHeader">
                    <?= $lang['order.order_info'] ?>
                </div>

                <div class="sectionBody">
                    <div class="table-responsive">
                        <table class="table data">
                            <?php foreach ($fields as $field): ?>
                                <tr>
                                    <td style="width: 20%;"><?= $field['title'] ?>:</td>
                                    <td><?= $field['content'] ?></td>
                                </tr>
                            <?php endforeach; ?>

                            <tr>
                                <td style="width: 20%;"><?= $lang['module.order_change_notify'] ?></td>
                                <td>
                                    <label>
                                        <input type="checkbox" name="notify" value="1" checked>
                                        <?= $lang['module.yes_btn'] ?>
                                    </label>
                                </td>
                            </tr>

                            <tr>
                                <td style="width: 20%; vertical-align: top;"><?= $lang['module.status_change_description'] ?></td>
                                <td>
                                    <textarea name="history_description"></textarea>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="editable-cart">
                <div class="sectionHeader">
                    <?= $lang['module.cart_contents_title'] ?>
                </div>

                <div class="sectionBody">
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

                            <tbody id="products">
                                <?php foreach ($products as $row): ?>
                                    <?= $this->render('order_edit_product_row.tpl', [
                                        'row' => $row,
                                    ]); ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div style="margin-top: 10px; margin-bottom: 20px; text-align: right;">
                        <a href="#" class="btn btn-sm btn-primary" id="add-product"><?= $lang['module.add_product'] ?></a>
                    </div>

                    <div class="table-responsive">
                        <table class="table data">
                            <thead>
                                <tr>
                                    <?php foreach ($subcolumns as $column): ?>
                                        <td<?= !empty($column['style']) ? ' style="' . $column['style'] . '"' : '' ?>><?= $column['title'] ?></td>
                                    <?php endforeach; ?>
                                    <td style="width: 1%;"></td>
                                </tr>
                            </thead>

                            <tbody id="subtotals">
                                <?php foreach ($subtotals as $row): ?>
                                    <?= $this->render('order_edit_subtotal_row.tpl', [
                                        'row' => $row,
                                    ]); ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div style="margin-top: 10px; text-align: right;">
                        <a href="#" class="btn btn-sm btn-primary" id="add-subtotal"><?= $lang['module.add_subtotal'] ?></a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div id="product-select" style="display: none;">
        <div class="evo-popup alert alert-default">
            <div class="evo-popup-close close">&times;</div>

            <div class="evo-popup-header">
                <?= $lang['module.products_selector_title'] ?>
            </div>

            <div class="evo-popup-body">
                <?= $this->render('selector_tree.tpl', [
                    'rows' => $documents,
                ]); ?>
            </div>
        </div>
    </div>

    <script type="text/template" id="productRowTpl">
        <?= $this->render('order_edit_product_row.tpl', [
            'row' => $productBlank,
        ]); ?>
    </script>

    <script type="text/template" id="subtotalRowTpl">
        <?= $this->render('order_edit_subtotal_row.tpl', [
            'row' => $subtotalBlank,
        ]); ?>
    </script>

    <script src="../assets/plugins/commerce/js/order_edit.js"></script>
<?php $this->endBlock(); ?>
