<?php if (!empty($result)): ?>
    <style>
        .salesbeat-change-city,
        .salesbeat-pvz-map {
            border-bottom: 1px dotted;
        }
    </style>
    <div id="<?= $result['main_div_id'] ?>" class="salesbeat-deliveries"></div>
    <script type="text/javascript">
        SB.init({
            token: '<?= $result['token'] ?>',
            price_to_pay: '<?= $result['price_to_pay'] ?>',
            price_insurance: '<?= $result['price_insurance'] ?>',
            weight: '<?= $result['weight'] ?>',
            x: '<?= $result['x'] ?>',
            y: '<?= $result['y'] ?>',
            z: '<?= $result['z'] ?>',
            quantity: '<?= $result['quantity'] ?>',
            city_by: '<?= $result['city_by'] ?>',
            params_by: '<?= $result['params_by'] ?>',
            main_div_id: '<?= $result['main_div_id'] ?>',
            callback: function () {
                console.log('Salesbeat is ready!');
            }
        });
    </script>
<? endif; ?>