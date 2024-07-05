<?php include MODX_MANAGER_PATH . 'includes/header.inc.php'; ?>
<link rel="stylesheet" href="../assets/plugins/commerce/css/module.css">
<?= $this->block('head') ?>

<h1>
    <i class="<?= $module->getControllerIcon() ?>"></i><?= $this->block('title', $lang['module.orders_caption']) ?>
</h1>

<?php if ($module->flash->has('error')): ?>
    <div class="container">
        <div class="alert alert-danger">
            <?= $module->flash->get('error') ?>
        </div>
    </div>
<?php endif; ?>

<?php if ($module->flash->has('validation_errors')): ?>
    <div class="container">
        <div class="alert alert-danger">
            <?php foreach ($module->flash->get('validation_errors') as $error): ?>
                <?= $error[2] ?><br>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<?php if ($module->flash->has('success')): ?>
    <div class="container">
        <div class="alert alert-success">
            <?= $module->flash->get('success') ?>
        </div>
    </div>
<?php endif; ?>

<div id="actions">
    <div class="btn-group">
        <?= $this->block('buttons') ?>
        <a class="btn btn-danger" href="index.php?a=2" title="<?= $lang['module.close_btn'] ?>">
            <i class="fa fa-sign-out"></i>
            <span><?= $lang['module.close_btn'] ?></span>
        </a>
    </div>
</div>

<div class="sectionBody">
    <div class="tab-pane" id="commercePane_<?= $module->getCurrentRouteName() ?>">
        <script type="text/javascript">
            var tpCommerce = new WebFXTabPane(document.getElementById('commercePane_<?= $module->getCurrentRouteName() ?>'), <?= ($modx->getConfig('remember_last_tab') == 1 ? 'true' : 'false') ?> );
        </script>

        <?= $this->block('content') ?>
    </div>
</div>

<?php if (!empty($custom)): ?>
    <?= $custom ?>
<?php endif; ?>

<?= $this->block('footer') ?>
<?php include MODX_MANAGER_PATH . 'includes/footer.inc.php'; ?>
