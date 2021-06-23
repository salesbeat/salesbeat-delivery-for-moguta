SalesbeatAdmin = {
    pluginName: 'salesbeat', // Название плагина

    // Функция инициализации, выполняемая, когда страница админки будет готова вывести настройки плагина
    init: function () {
        let me = this;

        // [Клик по кнопке "Сохранить" в панели настроек плагина]: сохраняет настроки плагина
        $(document).on('click', '.section-' + this.pluginName + ' .base-setting-save', function () {
            // Собираем все значения формы в один массив

            let obj = $('.section-' + me.pluginName + ' form'),
                paySystemsCash = obj.find('input[name=pay_systems_cash]'),
                paySystemsCard = obj.find('input[name=pay_systems_card]'),
                paySystemsOnline = obj.find('input[name=pay_systems_online]'),
                data = {};

            data['api_token'] = obj.find('input[name=api_token]').val();
            data['secret_token'] = obj.find('input[name=secret_token]').val();

            data['pay_systems_cash'] = [];
            data['pay_systems_card'] = [];
            data['pay_systems_online'] = [];

            data['property_weight'] = obj.find('select[name=property_weight] option:selected').val();
            data['property_width'] = obj.find('select[name=property_width] option:selected').val();
            data['property_height'] = obj.find('select[name=property_height] option:selected').val();
            data['property_depth'] = obj.find('select[name=property_depth] option:selected').val();
            data['unit_weight'] = obj.find('select[name=unit_weight] option:selected').val();
            data['unit_dimensions'] = obj.find('select[name=unit_dimensions] option:selected').val();

            paySystemsCash.each(function () {
                let id = $(this).data('id');

                if (id && $(this).val() === 'true')
                    data['pay_systems_cash'].push(id);
            });

            paySystemsCard.each(function () {
                let id = $(this).data('id');

                if (id && $(this).val() === 'true')
                    data['pay_systems_card'].push(id);
            });

            paySystemsOnline.each(function () {
                let id = $(this).data('id');

                if (id && $(this).val() === 'true')
                    data['pay_systems_online'].push(id);
            });

            // Выполняем запрос в Pactioner плагина к методу saveBaseOption()
            admin.ajaxRequest(
                {
                    mguniqueurl: 'action/saveBaseOption', // Действие для выполнения на сервере
                    pluginHandler: me.pluginName, // Плагин для обработки запроса
                    data: data // Входные данные:
                },

                function (response) {
                    // Выводим нотификацию с результатом (ошибка или успех)
                    admin.indication(response.status, response.msg);

                    // Если успех, обновляем страницу настроек плагина
                    if (response.status === 'success') admin.refreshPanel()
                }
            )
        });

        // Табы
        $(document).on('click', '[data-tab]', function(e) {
            e.preventDefault();

            let $this = $(this),
                tabsName = $this.data('tab'),
                id = $this.data('tab-id');

            $('[data-tab='+ tabsName +']').closest('li').removeClass('is-active');
            $this.closest('li').addClass('is-active');

            $('[data-tab-content='+ tabsName +']').removeClass('is-active');
            $('[data-tab-content-id=' + id +']').addClass('is-active');
        });
    },
};