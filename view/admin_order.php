<?php if (!$editMode): ?>
    <style>
        .mg-admin-html .closeModalSalesbeat {
            position: absolute;
            background: 0 0;
            font-size: 28px;
            line-height: 1;
            padding: 0;
            border: 0;
            border-radius: 100%;
            top: 13px;
            right: 20px;
            -webkit-transition: opacity .2s ease-in-out;
            -o-transition: opacity .2s ease-in-out;
            transition: opacity .2s ease-in-out;
            opacity: .5;
        }

        .delivery-salesbeat {
            display: block;
            width: 100%;
        }

        .delivery-salesbeat .shadow-block {
            margin: 15px 15px 0 15px;
        }

        .wdel2-btn {
            -webkit-appearance: none !important;
            cursor: pointer !important;
            margin: 0 !important;
            font: inherit !important;
            color: #fff !important;
            text-decoration: none !important;
            font-weight: 600 !important;
            font-size: 18px !important;
            height: 50px !important;
            line-height: 50px !important;
            border: 0 !important;
            border-radius: 6px !important;
            background: #70bc63 !important;
            padding: 0 30px !important;
            -ms-flex: 0 0 auto;
            flex: 0 0 auto !important;
            text-align: center !important;
            white-space: nowrap !important;
        }

        .wdel2-tabs > *, .wdel2-tabs > * {
            font-size: 13px !important;
            line-height: 20px !important;
            cursor: pointer !important;
            padding: 8px 18px !important;
            border-radius: 4px !important;
            display: block !important;
        }
    </style>

    <!-- Тут начинается верстка модального окна -->
    <div class="reveal-overlay" style="display:none;">
        <div class="reveal xssmall" id="widget-<?= $pluginName; ?>-modal" style="display:block;">
            <button class="closeModalSalesbeat" title="<?= $lang['sb_button_close_modal']; ?>">
                <i class="fa fa-times-circle-o"></i>
            </button>

            <div class="reveal-header">
                <h2><i class="fa fa-pencil"></i> <span class="add-order-table-icon">Salesbeat</span></h2>
            </div>
            <div class="reveal-body">
                <div class="widget-body slide-editor">
                    <div id="sb-cart-widget"></div>
                </div>
            </div>
        </div>
    </div>
    <!-- Тут заканчивается Верстка модального окна -->

    <div class="delivery-<?= $pluginName; ?>">
        <?php endif; ?>
        <?php if (!empty($info)): ?>
            <div class="order-fieldset order-payment-sum__item order-payment-sum__item_wide shadow-block table-<?= $pluginName; ?>">
                <h2 class="order-fieldset__h2"><?= $lang['sb_order_delivery_info']; ?></h2>
                <div class="order-fieldset__inner">
                    <?php
                    foreach ($info as $field):
                        if (empty($field['value']) && !is_numeric($field['value'])) continue;
                        ?>
                        <div class="row sett-line dashed">
                            <div class="small-12 medium-6 columns">
                                <div class="with-help"><?= $field['name']; ?>:</div>
                            </div>
                            <div class="small-12 medium-6 columns">
                                <strong><?= $field['value']; ?></strong>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($arDeliveryInfo['track_code'])): ?>
            <div class="small-12 medium-12 columns">
                <?php if ($editMode): ?>
                    <a href="#" class="button primary medium-12" data-modal-open="widget"><?= $lang['sb_button_open_widget']; ?></a>
                <? else: ?>
                    <a href="#" class="button success medium-12" data-send-order><?= $lang['sb_button_send_order']; ?></a>
                <? endif; ?>
            </div>
        <? endif; ?>

        <script type="text/javascript">
            $(function () {
                SalesbeatOrder.disabledAddress();

                SalesbeatOrder.init({
                    "token": "<?= isset($options['api_token']) ? $options['api_token'] : ''; ?>",
                    "city_code": "",
                    "products": <?= json_encode($products); ?>,
                    "delivery_id": "<?= isset($deliveryId) ? (int)$deliveryId : 0; ?>",
                });
            });
        </script>

        <?php if (!$editMode): ?>
    </div>

    <script type="text/javascript">
        $('.js-insert-delivery-options').append($('.add-delivery-info'));

        $(document).ajaxComplete(function (event, jqXHR, ajaxOptions) {
            if (ajaxOptions.url === mgBaseDir + '/ajaxrequest') {
                let obj = ajaxOptions.data.split("&").reduce(function (ob, v) {
                        v = v.split("=");
                        ob[v[0]] = v[1];
                        return ob
                    }, {}
                );

                if (obj.action === 'getAdminDeliveryForm')
                    SalesbeatOrder.updateInfo();

                if (obj.action === 'getPriceForParams')
                    SalesbeatOrder.updateInfo();
            }
        });

        $(document).ready(() => {
            $('.js-insert-delivery-options').append($('.add-delivery-info'));

            const button = document.querySelector('[data-send-order]');
            button.addEventListener('click', (e) => {
                e.preventDefault();

                SalesbeatOrder.sendOrder();
            });
        });
    </script>
<? endif; ?>