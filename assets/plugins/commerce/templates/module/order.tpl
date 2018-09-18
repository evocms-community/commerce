<?php $this->extend('layout.tpl'); ?>

<?php $this->block('title'); ?>
    <?= sprintf($lang['module.order_caption'], $order['id']) ?>
<?php $this->endBlock(); ?>

<?php $this->block('buttons'); ?>
    <a href="<?= $this->module->makeUrl('orders') ?>" class="btn btn-secondary"><?= $_lang['cancel'] ?></a>
<?php $this->endBlock(); ?>

<?php $this->block('content'); ?>
    <div class="tab-page" id="tab_main">
        <h2 class="tab"><?= $lang['module.order_contents'] ?></h2>

        <script type="text/javascript">
            tpCommerce.addTabPage(document.getElementById('tab_main'));
        </script>

        <table border="0" cellspacing="0" cellpadding="3" style="font-size: inherit; line-height: inherit;">
            <?php print_r($order); ?>
        </table>
    </div>
<?php $this->endBlock(); ?>
