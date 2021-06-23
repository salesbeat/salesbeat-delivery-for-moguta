SalesbeatOrder = {
    pluginName: 'salesbeat', // Название плагина

    init: function (params = []) {
        this.params = params;
        this.widgetModal = $('#widget-' + this.pluginName + '-modal');

        let me = this,
            button = document.querySelector('.button.primary.medium-12'),
            closeModal = document.querySelector('.closeModalSalesbeat');

        button.addEventListener('click', function (e) {
            e.preventDefault();

            admin.openModal(me.widgetModal);
            me.loadWidget();
        });

        closeModal.addEventListener('click', function (e) {
            e.preventDefault();

            admin.closeModal(me.widgetModal);
        });
    },
    loadWidget: function () {
        let me = this;

        SB.init_cart({
            token: this.params.token,
            city_code: this.params.city_code,
            products: this.params.products,
            callback: function (result) {
                $.ajax({
                    type: 'POST',
                    url: mgBaseDir + '/ajaxrequest',
                    dataType: 'json',
                    data: {
                        mguniqueurl: 'action/saveCallbackDelivery', // Действия для выполнения на сервере
                        pluginHandler: me.pluginName, // Название директории плагина
                        deliveryId: me.params.delivery_id,
                        sessionName: 'deliveryAdmin',
                        result: result
                    },
                    success: function (response) {
                        me.updateInfo();
                        admin.closeModal(me.widgetModal);
                    }
                });
            }
        });
    },
    disabledAddress: function () {
        const block = document.querySelector('#order-address');
        block.disabled = true;
    },
    updateInfo: function () {
        let id = $("#add-order-wrapper .save-button").attr('id'),
            deliveryId = $("#delivery :selected").attr('name'),
            plugin = $("#delivery :selected").data('plugin'),
            me = this;

        if (plugin && plugin.length > 0) {
            clearTimeout(window.timerChangeInfo);
            window.timerChangeInfo = setTimeout(function() {
                $.ajax({
                    type: "POST",
                    url: mgBaseDir + "/ajaxrequest",
                    data: {
                        mguniqueurl: 'action/getAdminDeliveryForm', // Действия для выполнения на сервере
                        pluginHandler: me.pluginName, // Название директории плагина
                        deliveryId: deliveryId,
                        recalc: true,
                        orderItems: order.orderItems,
                        orderId: id
                    },
                    cache: false,
                    dataType: 'json',
                    success: function (response) {
                        $('#delivery').parents('.js-insert-delivery-options').find('.delivery-' + me.pluginName).html(response.data.form);
                        $('input#deliveryCost').prop("disabled", true);
                        $('input#deliveryCost').val(response.data.delivery_price || '');
                        $('input#order-address').val(response.data.address || '');
                    }
                });
            }, 600);
        }
    },
    sendOrder: function () {
        let id = $("#add-order-wrapper .save-button").attr('id'),
            me = this;

        $.ajax({
            type: "POST",
            url: mgBaseDir + "/ajaxrequest",
            data: {
                mguniqueurl: 'action/sendOrder', // Действия для выполнения на сервере
                pluginHandler: this.pluginName, // Название директории плагина
                orderId: id
            },
            cache: false,
            dataType: 'json',
            success: function (response) {
                console.log(response);
                alert(response.data['message']);
                $('#delivery').parents('.js-insert-delivery-options')
                    .find('.delivery-' + me.pluginName).html(response.data.form);
            },
            error: function (response) {
                console.log(response);
            }
        });
    }
};