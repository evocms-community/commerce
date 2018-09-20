<?php include MODX_MANAGER_PATH . 'includes/header.inc.php'; ?>
<link rel="stylesheet" href="../assets/plugins/commerce/css/module.css">

<h1>
    <i class="fa fa-cog"></i><?= $this->block('title', $lang['module.orders_caption']) ?> 
</h1>

<?php if ($module->flash->has('error')): ?>
    <div class="container">
        <div class="alert alert-danger">
            <?= $module->flash->get('error') ?>
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

<form name="module" method="post" id="mutate">
    <?php if (!empty($this->block('buttons'))): ?>
        <div id="actions">
            <div class="btn-group">
                <?= $this->block('buttons') ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="sectionBody">
        <div class="tab-pane" id="commercePane">
            <script type="text/javascript">
                var tpCommerce = new WebFXTabPane(document.getElementById('commercePane'), <?= ($modx->getConfig('remember_last_tab') == 1 ? 'true' : 'false') ?> );
            </script> 

            <?= $this->block('content') ?>
        </div>
    </div>
</form>

<?php include MODX_MANAGER_PATH . 'includes/footer.inc.php'; ?>
