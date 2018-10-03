<?php $this->extend('layout.tpl'); ?>

<?php $this->block('title'); ?>
    <?= $lang['module.statuses_caption'] ?>
<?php $this->endBlock(); ?>

<?php $this->block('buttons'); ?>
    <a href="<?= $module->makeUrl('statuses/edit') ?>" class="btn btn-success"><?= $lang['module.add_status'] ?></a>
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
                            <td style="white-space: nowrap;"><?= $lang['module.default_field'] ?></td>
                            <td style="white-space: nowrap;"><?= $lang['module.notify_field'] ?></td>
                            <td style="width: 1%;"></td>
                        </tr>
                    </thead>
                
                    <tbody>
                        <?php foreach ($list as $row): ?>
                            <tr>
                                <td style="width: 1%; text-align: center;"><?= $row['id'] ?></td>
                                <td><?= $row['title'] ?></td>
                                <td style="white-space: nowrap;"><strong><?= !empty($row['default']) ? $lang['module.default_field'] : '' ?></strong></td>
                                <td style="white-space: nowrap;"><?= !empty($row['notify']) ? $lang['module.notify'] : '' ?></td>
                
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
