<?php include MODX_MANAGER_PATH . 'includes/header.inc.php'; ?>

<h1>
    <i class="fa fa-cog"></i><?= $this->block('title', $lang['module.orders_caption']) ?> 
</h1>

<?php if (!empty($flash['error'])): ?>
    <div class="alert alert-danger">
        <?= $flash['error'] ?>
    </div>
<?php endif; ?>

<?php if (!empty($flash['success'])): ?>
    <div class="alert alert-success">
        <?= $flash['success'] ?>
    </div>
<?php endif; ?>

<form name="module" method="post" id="mutate">
    <div id="actions">
        <div class="btn-group">
            <?= $this->block('buttons') ?>
        </div>
    </div>

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
