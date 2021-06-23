SalesbeatPublic = {
    pluginName: 'salesbeat', // Название плагина
    deliveryId: 0, // Id доставки

    init: function (params) {
        this.params = params;

        this.elementBlock = document.querySelector('#sb-cart-widget');
        this.elementResultBlock = document.querySelector('#sb-cart-widget-result');

        this.shippingBlock = this.elementBlock.closest('li');

        if (this.shippingBlock !== null) {
            this.shippingMethodInput = this.shippingBlock.querySelector('input[name="delivery"]');
            this.deliveryId = this.shippingBlock.querySelector('input[name="delivery"]').value;
        }

        if (this.elementBlock !== null)
            this.loadWidget();
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
                        deliveryId: me.deliveryId,
                        sessionName: 'delivery',
                        result: result
                    },
                    success: function (response) {
                        if (me.shippingMethodInput !== null)
                            me.checkedMethodDelivery();
                    }
                });

                me.callbackWidget(result);
            }
        });

        this.clearResultBlock();
    },
    callbackWidget: function (data) {
        let me = this,
            methodName = data['delivery_method_name'] || 'Не известно';

        let address = '';
        if (data['pvz_address']) {
            address = 'Самовывоз: ' + data['pvz_address']
        } else {
            address = 'Адрес: ';

            if (data['street']) address += 'ул. ' + data['street'];
            if (data['house']) address += ', д. ' + data['house'];
            if (data['house_block']) address += ' корпус ' + data['house_block'];
            if (data['flat']) address += ', кв. ' + data['flat'];
        }

        let deliveryDays = '';
        if (data['delivery_days']) {
            if (data['delivery_days'] === 0) {
                deliveryDays = 'сегодня';
            } else if (data['delivery_days'] === 1) {
                deliveryDays = 'завтра';
            } else {
                deliveryDays = this.suffixToNumber(data['delivery_days'], ['день', 'дня', 'дней']);
            }
        } else {
            deliveryDays = 'Не известно';
        }

        let deliveryPrice = '';
        if (data['delivery_price']) {
            deliveryPrice = data['delivery_price'] === 0 ?
                'бесплатно' :
                this.numberWithCommas(data['delivery_price']) + ' руб';
        } else {
            deliveryPrice = 'Не известно';
        }

        let comment = data['comment'] ? '<p> Комментарий: ' + data['comment'] + '</p>' : '';
        this.elementResultBlock.innerHTML += ('<p><span class="salesbeat-summary-label">Способ доставки:</span> ' + methodName + '</p>'
            + '<p><span class="salesbeat-summary-label">Стоимость доставки:</span> ' + deliveryPrice + '</p>'
            + '<p><span class="salesbeat-summary-label">Срок доставки:</span> ' + deliveryDays + '</p>'
            + '<p>' + address + '</p>' + comment
            + '<p><a href="" class="sb-reshow-cart-widget">Изменить данные доставки</a></p>');

        let button = this.elementResultBlock.querySelector('.sb-reshow-cart-widget');
        button.addEventListener('click', function (e) {
            e.preventDefault();
            me.reshowCardWidget();
        });
    },
    reshowCardWidget: function () {
        SB.reinit_cart(true);
        this.clearResultBlock();
    },
    clearResultBlock: function () {
        this.elementResultBlock.innerHTML = '';
    },
    checkedMethodDelivery: function () {
        this.shippingMethodInput.checked = false;
        $(this.shippingMethodInput).trigger('click');
    },
    suffixToNumber: function (number, suffix) {
        let cases = [2, 0, 1, 1, 1, 2];
        return number + ' ' + suffix[(number % 100 > 4 && number % 100 < 20) ? 2 : cases[(number % 10 < 5) ? number % 10 : 5]];
    },
    numberWithCommas: function (string) {
        return string.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
    },

    reloadWidget: function (cart = {}) {
        let me = this;

        $.ajax({
            type: 'POST',
            url: mgBaseDir + '/ajaxrequest',
            dataType: 'json',
            data: {
                mguniqueurl: 'action/updatePublicWidget', // Действия для выполнения на сервере
                pluginHandler: me.pluginName, // Название директории плагина
                cart: cart
            },
            success: function (response) {
                me.changeInfo(response);
            }
        });
    },

    changeInfo: function (response) {
        let me = this;

        if (response.data.html.length) {
            this.elementBlock = document.querySelector('.section-salesbeat');
            this.elementBlock.innerHTML = response.data.html;
        }

        if (response.data.is_select) {
            let button = document.querySelector('.sb-reshow-cart-widget');
            button.addEventListener('click', function (e) {
                e.preventDefault();

                me.init({
                    "token": response.data.token || '',
                    "city_code": response.data.city_code || '',
                    "products": response.data.products || '',
                });
            });
        } else {
            this.init({
                "token": response.data.token || '',
                "city_code": response.data.city_code || '',
                "products": response.data.products || '',
            });
        }
    }
};