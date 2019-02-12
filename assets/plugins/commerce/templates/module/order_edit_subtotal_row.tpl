<tr>
    <?php foreach ($row['cells'] as $cell): ?>
        <td><?= $cell ?></td>
    <?php endforeach; ?>
    <td>
        <a href="#" class="btn btn-sm btn-danger remove-row"><?= $lang['module.remove_product'] ?></a>
    </td>
</tr>
