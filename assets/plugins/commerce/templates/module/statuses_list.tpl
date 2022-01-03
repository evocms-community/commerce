<?php $this->extend('layout.tpl'); ?>

<?php $this->block('title'); ?>
    <?= $lang['module.statuses_caption'] ?>
<?php $this->endBlock(); ?>

<?php $this->block('buttons'); ?>
    <a href="<?= $module->makeUrl('statuses/edit') ?>" class="btn btn-success" title="<?= $lang['module.add_status'] ?>">
        <i class="fa fa-plus-circle"></i>
        <span><?= $lang['module.add_status'] ?></span>
    </a>
<?php $this->endBlock(); ?>

<?php $this->block('content'); ?>
    <div class="tab-page" id="tab_main">
        <h2 class="tab">
            <?= $lang['module.statuses_caption'] ?>
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
                            <td><?= $lang['module.status_alias'] ?></td>
                            <td style="white-space: pre-wrap; text-align: center;"><?= $lang['module.default_field'] ?></td>
                            <td style="white-space: pre-wrap; text-align: center;"><?= $lang['module.notify_field'] ?></td>
                            <td style="white-space: pre-wrap; text-align: center;"><?= $lang['module.canbepaid_field'] ?></td>
                            <td style="width: 1%;"></td>
                        </tr>
                    </thead>
                
                    <tbody>
                        <?php foreach ($list as $row): ?>
                            <tr>
                                <td style="width: 1%; text-align: center;"><?= $row['id'] ?></td>
                                <td style="white-space: nowrap;"><i class="status-color fa fa-circle" style="color:#<?= !empty($row['color']) ? $row['color'] : 'FFFFFF' ?>"></i> <?= $row['title'] ?></td>
                                <td><?= $row['alias'] ?></td>
                                <td style="text-align: center;"><strong><?= !empty($row['default']) ? '<i class="fa fa-check-circle"></i>' : '' ?></strong></td>
                                <td style="text-align: center;"><?= !empty($row['notify']) ? '<i class="fa fa-check"></i>' : '' ?></td>
                                <td style="text-align: center;"><?= !empty($row['canbepaid']) ? '<i class="fa fa-check"></i>' : '' ?></td>
                                <td style="white-space: nowrap;">
                                    <a href="<?= $this->module->makeUrl('statuses/edit', 'status_id=' . $row['id']) ?>" class="btn btn-primary btn-sm">
                                        <?= $lang['module.edit_status_btn'] ?>
                                    </a>

                                    <a href="<?= $this->module->makeUrl('statuses/delete', 'status_id=' . $row['id']) ?>" class="btn btn-danger btn-sm">
                                        <?= $lang['module.delete_status_btn'] ?>
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
