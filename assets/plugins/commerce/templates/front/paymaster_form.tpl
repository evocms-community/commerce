<form action="https://paymaster.ru/Payment/Init" method="get" id="paymaster_request" style="display: none;">
    <?php foreach ($data as $key => $value): ?>
        <input type="hidden" name="<?= $key ?>" value="<?= htmlentities($value) ?>">
    <?php endforeach; ?>
</form>

<script type="text/javascript">
    document.getElementById('paymaster_request').submit();
</script>
