<?php $this->extend('layout.tpl'); ?>

<?php $this->block('title'); ?>
    <?= !empty($status['id']) ? sprintf($lang['module.edit_status_caption'], $status['title']) : $lang['module.new_status_caption'] ?>
<?php $this->endBlock(); ?>

<?php $this->block('buttons'); ?>
    <a href="javascript:;" class="btn btn-success" onclick="document.getElementById('status_form').submit();" title="<?= $lang['module.save_btn'] ?>">
        <i class="fa fa-floppy-o"></i>
        <span><?= $lang['module.save_btn'] ?></span>
    </a>
    <a href="<?= $this->module->makeUrl('statuses') ?>" class="btn btn-secondary" title="<?= $lang['module.cancel_btn'] ?>">
        <i class="fa fa-times-circle"></i>
        <span><?= $lang['module.cancel_btn'] ?></span>
    </a>
<?php $this->endBlock(); ?>

<?php $this->block('content'); ?>
    <div class="tab-page" id="tab_main">
        <h2 class="tab"><?= $lang['module.status_data'] ?></h2>

        <script type="text/javascript">
            tpCommerce.addTabPage(document.getElementById('tab_main'));
        </script>

        <div class="sectionHeader">
            <?= $lang['module.status_data'] ?>
        </div>

        <div class="sectionBody">
            <form action="<?= $module->makeUrl('statuses/save') ?>" method="post" id="status_form">
                <?= csrf_field() ?>
                <table class="table">
                    <tr>
                        <td><?= $lang['module.status_title'] ?></td>
                        <td>
                            <input type="text" name="title" value="<?= htmlentities($module->getFormAttr($status, 'title')) ?>">
                        </td>
                    </tr>
                    <tr>
                        <td><?= $lang['module.status_alias'] ?></td>
                        <td>
                            <input type="text" name="alias" value="<?= htmlentities($module->getFormAttr($status, 'alias')) ?>">
                        </td>
                    </tr>
                    <tr>
                        <td><?= $lang['module.status_marker_color'] ?></td>
                        <td style="white-space: nowrap; vertical-align: baseline;">
                            <i class="status-color fa fa-circle" style="color:#<?= htmlentities($module->getFormAttr($status, 'color')) ?>"></i> <input type="text" name="color" value="<?= htmlentities($module->getFormAttr($status, 'color')) ?>">
                        </td>
                    </tr>
                    <tr>
                        <td></td>
                        <td>
                            <label>
                                <input type="hidden" name="notify" value="0">
                                <input type="checkbox" name="notify" value="1"<?= !empty($module->getFormAttr($status, 'notify')) ? ' checked' : '' ?>>
                                <?= $lang['module.status_change_notify'] ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td></td>
                        <td>
                            <label>
                                <input type="hidden" name="canbepaid" value="0">
                                <input type="checkbox" name="canbepaid" value="1"<?= !empty($module->getFormAttr($status, 'canbepaid')) ? ' checked' : '' ?>>
                                <?= $lang['module.canbepaid_field'] ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td></td>
                        <td>
                            <label>
                                <input type="hidden" name="default" value="0">
                                <input type="checkbox" name="default" value="1"<?= !empty($module->getFormAttr($status, 'default')) ? ' checked' : '' ?>>
                                <?= $lang['module.default_field'] ?>
                            </label>
                        </td>
                    </tr>
                </table>

                <?php if (!empty($status['id'])): ?>
                    <input type="hidden" name="status_id" value="<?= $status['id'] ?>">
                <?php endif; ?>

                <button type="submit" class="btn btn-secondary"><?= $lang['module.save_btn'] ?></button>
            </form>
        </div>
    </div>
<?php $this->endBlock(); ?>

<?php $this->block('footer'); ?>
<script src="../assets/plugins/commerce/js/vanilla-picker.min.js"></script>
<script>
    var color_input = document.getElementsByName('color')[0];
    var color_marker = document.getElementsByClassName('status-color')[0];
    var picker = new Picker({
        parent: color_input.parentNode,
        popup: 'bottom',
        alpha: false,
        color: '#' + color_input.value,
        onDone: function(color){
            color_input.value = color.hex.substring(1,7).toUpperCase();
            color_marker.style.color = color.hex;
        }
    });
</script>
<?php $this->endBlock(); ?>
