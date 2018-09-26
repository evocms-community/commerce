<?php $this->extend('layout.tpl'); ?>

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
                
                                <td>
                                    <a href="<?= $this->module->makeUrl('statuses/edit', 'status_id=' . $row['id']) ?>" class="btn btn-primary">
                                        <?= $lang['module.edit_status_btn'] ?>
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
