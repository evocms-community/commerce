<?php $this->extend('layout.tpl'); ?>

<?php $this->block('title'); ?>
    <?= !empty($currency['id']) ? sprintf($lang['module.edit_currency_caption'], $currency['title']) : $lang['module.new_currency_caption'] ?>
<?php $this->endBlock(); ?>

<?php $this->block('buttons'); ?>
    <a href="javascript:;" class="btn btn-success" onclick="document.getElementById('currency_form').submit();" title="<?= $lang['module.save_btn'] ?>">
        <i class="fa fa-floppy-o"></i>
        <span><?= $lang['module.save_btn'] ?></span>
    </a>
    <a href="<?= $this->module->makeUrl('currency') ?>" class="btn btn-secondary" title="<?= $lang['module.cancel_btn'] ?>">
        <i class="fa fa-times-circle"></i>
        <span><?= $lang['module.cancel_btn'] ?></span>
    </a>
<?php $this->endBlock(); ?>

<?php $this->block('content'); ?>
    <div class="tab-page" id="tab_main">
        <h2 class="tab"><?= $lang['module.currency_data'] ?></h2>

        <script type="text/javascript">
            tpCommerce.addTabPage(document.getElementById('tab_main'));
        </script>

        <div class="sectionHeader">
            <?= $lang['module.currency_data'] ?>
        </div>

        <div class="sectionBody">
            <form action="<?= $module->makeUrl('currency/save') ?>" method="post" id="currency_form">
                <?= csrf_field() ?>
                <table class="table">
                    <tr>
                        <td width="25%"><?= $lang['module.name_field'] ?></td>
                        <td>
                            <input type="text" name="title" value="<?= htmlentities($module->getFormAttr($currency, 'title')) ?>">
                        </td>

                    <tr>
                        <td><?= $lang['module.code_title'] ?></td>
                        <td>
                            <input type="text" name="code" value="<?= htmlentities($module->getFormAttr($currency, 'code')) ?>">
                        </td>

                    <tr>
                        <td><?= $lang['module.value_title'] ?></td>
                        <td>
                            <input type="text" name="value" value="<?= htmlentities($module->getFormAttr($currency, 'value')) ?>">
                        </td>

                    <tr>
                        <td><?= $lang['module.left_symbol'] ?></td>
                        <td>
                            <input type="text" name="left" value="<?= htmlentities($module->getFormAttr($currency, 'left')) ?>">
                        </td>

                    <tr>
                        <td><?= $lang['module.right_symbol'] ?></td>
                        <td>
                            <input type="text" name="right" value="<?= htmlentities($module->getFormAttr($currency, 'right')) ?>">
                        </td>

                    <tr>
                        <td><?= $lang['module.decimals_title'] ?></td>
                        <td>
                            <input type="text" name="decimals" value="<?= htmlentities($module->getFormAttr($currency, 'decimals')) ?>">
                        </td>

                    <tr>
                        <td><?= $lang['module.decimals_separator'] ?></td>
                        <td>
                            <input type="text" name="decsep" value="<?= htmlentities($module->getFormAttr($currency, 'decsep')) ?>">
                        </td>

                    <tr>
                        <td><?= $lang['module.thousands_separator'] ?></td>
                        <td>
                            <input type="text" name="thsep" value="<?= htmlentities($module->getFormAttr($currency, 'thsep')) ?>">
                        </td>

                    <tr>
                        <td><?= $lang['module.related_language'] ?></td>
                        <td>
                            <input type="text" name="lang" value="<?= htmlentities($module->getFormAttr($currency, 'lang')) ?>">
                        </td>

                    <tr>
                        <td></td>
                        <td>
                            <label>
                                <input type="hidden" name="active" value="0">
                                <input type="checkbox" name="active" value="1"<?= !empty($module->getFormAttr($currency, 'active')) ? ' checked' : '' ?>>
                                <?= $lang['module.active_title'] ?>
                            </label>
                        </td>

                    <tr>
                        <td></td>
                        <td>
                            <label>
                                <input type="hidden" name="default" value="0">
                                <input type="checkbox" name="default" value="1"<?= !empty($module->getFormAttr($currency, 'default')) ? ' checked' : '' ?>>
                                <?= $lang['module.default_currency_field'] ?>
                            </label>
                        </td>
                </table>

                <?php if (!empty($currency['id'])): ?>
                    <input type="hidden" name="currency_id" value="<?= $currency['id'] ?>">
                <?php endif; ?>

                <button type="submit" class="btn btn-secondary"><?= $lang['module.save_btn'] ?></button>
            </form>
        </div>
    </div>
<?php $this->endBlock(); ?>
