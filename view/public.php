<?php if ($isAdd): ?><div class="section-<?= $pluginName; ?>"><?php endif; ?>
<?php if (!empty($info)): ?>
    <div id="sb-cart-widget"></div>
    <div id="sb-cart-widget-result">
        <?php
        foreach ($info as $field):
            if (empty($field['value']) && !is_numeric($field['value'])) continue;
            ?>
            <p><span class="salesbeat-summary-label"><?= $field['name']; ?>:</span> <?= $field['value']; ?></p>
        <?php endforeach; ?>

        <p><a href="" class="sb-reshow-cart-widget"><?= $lang['sb_button_change_delivery']; ?></a></p>
    </div>

    <script type="text/javascript">
        $(function () {
            let button = document.querySelector('.sb-reshow-cart-widget');

            button.addEventListener('click', function (e) {
                e.preventDefault();

                SalesbeatPublic.init({
                    "token": "<?= isset($options['api_token']) ? $options['api_token'] : ''; ?>",
                    "city_code": "",
                    "products": <?= json_encode($products); ?>
                });
            });

            $(document).ajaxComplete(function (event, jqXHR, ajaxOptions) {
                if (ajaxOptions.url === mgBaseDir + '/cart')
                    SalesbeatPublic.reloadWidget(jqXHR.responseJSON.data.cart);
            });
        });
    </script>
<?php else: ?>
    <div id="sb-cart-widget"></div>
    <div id="sb-cart-widget-result"></div>

    <script type="text/javascript">
        $(function () {
            SalesbeatPublic.init({
                "token": "<?= isset($options['api_token']) ? $options['api_token'] : ''; ?>",
                "city_code": "",
                "products": <?= json_encode($products); ?>
            });

            $(document).ajaxComplete(function (event, jqXHR, ajaxOptions) {
                if (ajaxOptions.url === mgBaseDir + '/cart')
                    SalesbeatPublic.reloadWidget(jqXHR.responseJSON.data.cart);
            });
        });
    </script>
<?php endif; ?>
<?php if ($isAdd): ?></div><?php endif; ?>