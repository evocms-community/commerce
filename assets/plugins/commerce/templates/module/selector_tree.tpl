<ul>
    <?php foreach ($rows as $row): ?>
        <li data-id="<?= $row['id'] ?>" data-values="<?= htmlentities(json_encode($row, JSON_UNESCAPED_UNICODE)) ?>">
            <?php if ($row['isfolder']): ?>
                <span class="expand"></span>
            <?php endif; ?>

            <span class="title"><?= $row['pagetitle'] ?></span>
            <div class="children"></div>
        </li>
    <?php endforeach; ?>
</ul>
