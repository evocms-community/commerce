<?php $this->extend('layout.tpl'); ?>

<?php $this->block('title'); ?>
    <?= $lang['module.currency_caption'] ?>
<?php $this->endBlock(); ?>

<?php $this->block('buttons'); ?>
    <a href="<?= $module->makeUrl('currency/edit') ?>" class="btn btn-success" title="<?= $lang['module.add_currency'] ?>">
        <i class="fa fa-plus-circle"></i>
        <span><?= $lang['module.add_currency'] ?></span>
    </a>
<?php $this->endBlock(); ?>

<?php $this->block('content'); ?>
    <div class="tab-page" id="tab_main">
        <h2 class="tab">
            <?= $lang['module.currency_caption'] ?>
        </h2>

        <script type="text/javascript">
            tpCommerce.addTabPage(document.getElementById('tab_main'));
        </script>

        <div class="row">
            <div class="table-responsive">
                <table class="table data">
                    <thead>
                        <tr>
                            <td style="width: 1%; text-align: center;">#</td>
                            <td><?= $lang['module.name_field'] ?></td>
                            <td><?= $lang['module.code_title'] ?></td>
                            <td><?= $lang['module.value_title'] ?></td>
                            <td><?= $lang['module.active_title'] ?></td>
                            <td style="white-space: nowrap;"><?= $lang['module.default_currency_field'] ?></td>
                            <td style="white-space: nowrap;"><?= $lang['module.updated_at_field'] ?></td>
                            <td style="width: 1%;"></td>
                        </tr>
                    </thead>
                
                    <tbody>
                        <?php foreach ($list as $row): ?>
                            <tr>
                                <td style="width: 1%; text-align: center;"><?= $row['id'] ?></td>
                                <td><?= $row['title'] ?></td>
                                <td><?= $row['code'] ?></td>
                                <td><?= $row['value'] ?></td>
                                <td><?= !empty($row['active']) ? $lang['module.active_title'] : '' ?></td>
                                <td style="white-space: nowrap;"><strong><?= !empty($row['default']) ? $lang['module.default_currency_field'] : '' ?></strong></td>
                                <td style="white-space: nowrap;"><?= (new \DateTime())->setTimestamp(strtotime($row['updated_at']) + $modx->getConfig('server_offset_time'))->format('d.m.Y H:i:s') ?></td>
                
                                <td style="white-space: nowrap;">
                                    <a href="<?= $this->module->makeUrl('currency/edit', 'currency_id=' . $row['id']) ?>" class="btn btn-primary btn-sm">
                                        <?= $lang['module.edit_currency_btn'] ?>
                                    </a>

                                    <a href="<?= $this->module->makeUrl('currency/delete', 'currency_id=' . $row['id']) ?>" class="btn btn-danger btn-sm">
                                        <?= $lang['module.delete_currency_btn'] ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php $this->endBlock(); ?>
