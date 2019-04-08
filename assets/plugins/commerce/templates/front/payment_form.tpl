<form action="<?= $url ?>" method="get" id="payment_request" style="display: none;">
    <?php foreach ($data as $key => $value): ?>
        <input type="hidden" name="<?= $key ?>" value="<?= htmlentities($value) ?>">
    <?php endforeach; ?>
</form>

<script type="text/javascript">
    document.getElementById('payment_request').submit();
</script>
