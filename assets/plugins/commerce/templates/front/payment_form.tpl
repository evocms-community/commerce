<form action="<?= $url ?>" method="get" id="payment_request" style="display: none;">
    <?php foreach ($data as $key => $value): ?>
        <input type="hidden" name="<?= $key ?>" value="<?= htmlentities($value) ?>">
    <?php endforeach; ?>
</form>

<script type="text/javascript">
    <?php if (ci()->commerce->getSetting('instant_redirect_to_payment') == 1): ?>
        document.getElementById('payment_request').submit();
    <?php endif; ?>
</script>
