<?php

/*
  Plugin Name: Salesbeat - Интегратор способов доставки
  Description: Данный плагин позволяет выводить виджет расчета доставок на странице оформления заказа.
  Author: Salesbeat
  Version: 1.2.1
 */

include __DIR__ . '/lib/loader.class.php';
$loader = new \Salesbeat\Loader;
$loader->register();

use \Salesbeat\Delivery,
    \Salesbeat\Payment,
    \Salesbeat\Storage,
    \Salesbeat\Order,
    \Salesbeat\Property;

new Salesbeat;

class Salesbeat
{
    public static $pluginName = ''; // Название плагина (соответствует названию папки)
    public static $lang = []; // Массив с переводом плагина
    public static $path = ''; // Путь до файлов плагина
    public static $deliveryId = null; // Id доставки
    public static $options = []; // Настройки
    public static $sessionName = 'delivery'; // Название сессии

    /**
     * Salesbeat constructor.
     */
    public function __construct()
    {
        // Записываем информацию в глобальные переменные класса
        self::$pluginName = PM::getFolderPlugin(__FILE__); // Поазвание плагина
        self::$lang = PM::plugLocales(self::$pluginName); // Локали
        self::$path = PLUGIN_DIR . self::$pluginName; // Путь до файлов плагина

        if (URL::isSection('mg-admin'))
            self::$sessionName = 'deliveryAdmin'; // Присваеваем название сессии

        // Активация и деактивация плагина
        mgActivateThisPlugin(__FILE__, [__CLASS__, 'activatePlugin']); // Инициализация метода выполняющегося при активации
        mgDeactivateThisPlugin(__FILE__, [__CLASS__, 'deActivatePlugin']); // Инициализация метода выполняющегося при деактивации

        // Действия
        mgAddAction(__FILE__, [__CLASS__, 'pageSettingsPlugin']); // Инициализация метода выполняющегося при нажатии на кнопку настроек плагина
        mgAddAction('Controllers_Order_getPaymentByDeliveryId', [__CLASS__, 'getPaymentByDeliveryId'], 1);
        mgAddAction('Models_Order_isValidData', [__CLASS__, 'isValidData'], 1);
        mgAddAction('Models_Order_addOrder', [__CLASS__, 'setOrderDeliveryInfo'], 1);
        mgAddAction('Models_Order_updateOrder', [__CLASS__, 'setOrderDeliveryInfo'], 1);

        mgAddAction('getAdminOrderForm', [__CLASS__, 'getAdminOrderForm'], 1);

        // Шорткоды
        mgAddShortcode('salesbeat', [__CLASS__, 'addOrderWidget']); // Шорткод для дополнительной информации по способу доставки
        mgAddShortcode('salesbeat_product', [__CLASS__, 'addProductWidget']); // Шорткод для дополнительной информации по способу доставки

        if (is_null(self::$deliveryId))
            self::$deliveryId = self::getDeliveryId(); // Получаем ID доставки

        if (empty(self::$options))
            self::$options = self::getOptions(); // Получаем настройки плагина
    }

    /**
     * Метод выполняющийся при активации палагина
     */
    public static function activatePlugin()
    {
        if (self::$deliveryId > 0) {
            Delivery::active(self::$deliveryId);
        } else {
            $arFields = [
                'name' => self::$lang['plugin_name'],
                'description' => self::$lang['plugin_description'],
                'plugin' => 'salesbeat',
            ];
            Delivery::add($arFields);
        }

        self::createTableSbOrder();
    }

    /**
     * Метод выполняющийся при деактивации палагина
     */
    public static function deActivatePlugin()
    {
        if (self::$deliveryId > 0)
            Delivery::deActive(self::$deliveryId);
    }

    /**
     * Метод создания таблицы с заказами Salesbeat
     */
    private static function createTableSbOrder()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . PREFIX . 'salesbeat_order` (
                    `id` int(11) not null auto_increment,
                    `order_id` varchar(255) not null,
                    `sb_order_id` varchar(255) not null,
                    `track_code` varchar(255) not null,
                    `date_order` DATETIME,
                    `sent_courier` tinyint(1) not null,
                    `date_courier` DATETIME,
                    `tracking_status` varchar(255) not null,
                    `date_tracking` DATETIME,
                    PRIMARY KEY(`id`)
                )
                ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;';

        DB::query($sql);
    }

    /**
     * Метод получения ID доставки
     * @return int
     */
    public static function getDeliveryId()
    {
        $deliveryId = 0;

        $rsDelivery = Delivery::getList([], ['plugin' => self::$pluginName], ['id']);
        while ($arDelivery = DB::fetchAssoc($rsDelivery)) {
            if (!empty($arDelivery['id']))
                $deliveryId = $arDelivery['id'];
        }

        return $deliveryId;
    }

    /**
     * Метод с настройками плагина
     */
    public static function pageSettingsPlugin()
    {
        $pluginName = self::$pluginName; // Название плагина
        $lang = self::$lang; // Локали
        $options = self::$options; // Опции плагина

        // Список платежных систем
        $arPayments = [];
        $rsPayments = Payment::getList(['name' => 'asc']);
        while ($arPayment = DB::fetchAssoc($rsPayments))
            $arPayments[$arPayment['id']] = $arPayment;

        // Список свойств
        $arProperties = [];
        $rsProperties = Property::getList(['name' => 'asc'], ['type' => 'string', 'activity' => 1]);
        while ($arProperty = DB::fetchAssoc($rsProperties))
            $arProperties[$arProperty['id']] = $arProperty;

        // Перед выводом страницы подключаем необходимые файлы css и js файлы
        self::preparePageSettings();

        // Подключаем view для страницы плагина
        include 'view/admin.php';
    }

    /**
     * Метод для получения опций плагина
     * @return array
     */
    public static function getOptions()
    {
        // Получаем опции плагина в специальном формате
        $option = MG::getSetting(self::$pluginName . '-option');

        // Преобразуем в массив
        $option = stripslashes($option);
        $options = unserialize($option);

        // Возвращаем опции в виде получившегося массива
        return $options;
    }

    /**
     * Метод выполняющийся перед генераццией страницы настроек плагина
     * Подключает css и js файлы плагина
     */
    private static function preparePageSettings()
    {
        echo '
            <link rel="stylesheet" href="' . SITE . '/' . self::$path . '/css/admin/plugin.css" type="text/css" />
            <script type="text/javascript">
                includeJS("' . SITE . '/' . self::$path . '/js/admin/plugin.js");
                SalesbeatAdmin.init();
            </script> 
        ';
    }

    /**
     * Метод отображения информации о доставке вместо шорткода
     * @return string
     */
    public static function addOrderWidget()
    {
        $pluginName = self::$pluginName; // Название плагина
        $lang = self::$lang; // Локали
        $options = self::$options; // Опции плагина
        $info = self::getShippingInfo(); // Получаем информацию о доставке
        $products = self::getCartItemsParams(); // Получаем параметры товаров
        $isAdd = true;

        // Буфферизируем данные
        ob_start();
        // Перед выводом страницы подключаем необходимые файлы css и js файлы
        self::prepareAddOrderWidget();

        // Подключаем view для страницы плагина
        include 'view/public.php';

        return ob_get_clean(); // Возвращаем буфер
    }

    /**
     * Метод выполняющийся перед генераццией доставки плагина
     * Подключает css и js файлы плагина
     */
    private static function prepareAddOrderWidget()
    {
        echo '
            <link rel="stylesheet" href="' . SITE . '/' . self::$path . '/css/public/plugin.css" type="text/css" />
            <script src="https://app.salesbeat.pro/static/widget/js/widget.js"></script>
            <script src="https://app.salesbeat.pro/static/widget/js/cart_widget.js"></script>
            <script src="' . SITE . '/' . self::$path . '/js/public/plugin.js"></script>
        ';
    }

    /**
     * Метод обновления виджета на странице заказа (Public)
     * @return string
     */
    public static function updateOrderWidget()
    {
        Salesbeat::calcDeliveryPrice();

        $pluginName = self::$pluginName; // Название плагина
        $lang = self::$lang; // Локали
        $options = self::$options; // Опции плагина
        $info = self::getShippingInfo(); // Получаем информацию о доставке
        $products = self::getCartItemsParams(); // Получаем параметры товаров
        $isAdd = false;

        // Буфферизируем данные
        ob_start();

        // Подключаем view для страницы плагина
        include 'view/public.php';
        return ob_get_clean(); // возвращаем буфер
    }

    /**
     * Метод формирующий информацию расчета доставки из хранилища
     * @return array
     */
    private static function getShippingInfo()
    {
        $storage = Storage::main()->getByID(self::$deliveryId);
        $result = [];

        if (isset($storage['delivery_method_name'])) {
            $result['method_name'] = [
                'name' => self::$lang['sb_delivery_method_name'],
                'value' => $storage['delivery_method_name']
            ];
        }

        if (isset($storage['delivery_price'])) {
            $result['price'] = [
                'name' => self::$lang['sb_delivery_price'],
                'value' => $storage['delivery_price']
            ];
        }

        if (isset($storage['delivery_days'])) {
            $result['days'] = [
                'name' => self::$lang['sb_delivery_days'],
                'value' => $storage['delivery_days']
            ];
        }

        if (isset($storage['pvz_address'])) {
            $result['pvz_address'] = [
                'name' => self::$lang['sb_delivery_pvz_address'],
                'value' => self::formattingAddress($storage)
            ];
        }

        if (isset($storage['street']) || isset($storage['house']) || isset($storage['house_block']) || isset($storage['flat'])) {
            $result['address'] = [
                'name' => self::$lang['sb_delivery_address'],
                'value' => self::formattingAddress($storage)
            ];
        }

        if (isset($storage['comment'])) {
            $result['comment'] = [
                'name' =>  self::$lang['sb_delivery_comment'],
                'value' => $storage['comment']
            ];
        }

        return $result;
    }

    /**
     * Хук: Выполняется после формирования списка способов оплаты для
     * выбранного способа доставки, на странице оформления заказа
     * @param array $args
     * @return array
     */
    public static function getPaymentByDeliveryId($args)
    {
        $deliveryArgs = [];

        if (is_array($args['args'][0])) {
            $deliveryArgs = $args['args'][0];
            $selectedDeliveryId = $deliveryArgs['delivery'];
        } else {
            $selectedDeliveryId = $args['args'][0];
        }

        // Поверяем наша ли выбрана доставка, если нет возвращаем входящие аргументы
        if ($selectedDeliveryId !== self::$deliveryId)
            return $args['result'];

        $storage = Storage::main()->getByID(self::$deliveryId); // Получаем расчеты доставки из хранилища

        // Устанавливаем стоимость заказа
        if (!empty($deliveryArgs)) {
            $price = isset($storage['delivery_price']) ? $storage['delivery_price'] : 0;
            $args['args']['this']->delivery_cost = $price;
            unset($price);
        } else if (isset($storage['delivery_price'])) {
            $settings = MG::get('settings');
            $args['result']['summDelivery'] = MG::numberFormat($storage['delivery_price']) . ' ' . $settings['currency'];
        } else {
            $args['result']['summDelivery'] = -1;
            $args['result']['error'][] = '<div class="c-alert c-alert--red">' . self::$lang['sb_error_type_2'] . '</div>';
        }

        // Работаем с платежными системами
        $payments = [];
        if (!empty($args['result']['paymentTable'])) {
            preg_match_all('/<li(.*?)value="(.*?)"(.*?)\/li>/s', $args['result']['paymentTable'], $matches);

            foreach ($matches[2] as $key => $matche)
                $payments[$matche] = [
                    'id' => $matche,
                    'html' => $matches[0][$key]
                ];

            unset($matches);
        }

        $arPayments = self::filterPaymentSystem($payments); // Фильтруем доступные платежные системы
        unset($payments);

        if (!empty($arPayments)) {
            $paymentTable = '';

            foreach ($arPayments as $payment)
                $paymentTable .= $payment['html'];

            $args['result']['paymentTable'] = $paymentTable;
        } else {
            $args['result']['error'][] = '<div class="c-alert c-alert--blue">' . self::$lang['sb_error_type_1'] . '</div>';
        }

        return $args['result'];
    }

    /**
     * Метод для проверки правильности введенных данных перед отправкой заказа
     * @param $args
     * @return string
     */
    public static function isValidData($args)
    {
        if (is_array($args['args'][0])) {
            $deliveryArgs = $args['args'][0];
            $selectedDeliveryId = $deliveryArgs['delivery'];
        } else {
            $selectedDeliveryId = $args['args'][0];
        }

        // Поверяем наша ли выбрана доставка, если нет возвращаем входящие аргументы
        if ($selectedDeliveryId !== self::$deliveryId)
            return $args['result'];

        $storage = Storage::main()->getByID(self::$deliveryId); // Получаем расчеты доставки из хранилища

        if (empty($storage))
            $args['result'] = self::$lang['sb_error_type_3'];

        return $args['result'];
    }

    /**
     * Метод фильтрует платежные системы
     * @param array $payments
     * @return array
     */
    public static function filterPaymentSystem($payments)
    {
        $options = self::$options; // Опции плагина
        $storage = Storage::main()->getByID(self::$deliveryId); // Получаем расчеты доставки из хранилища

        // Если не получены платежные системы
        if (empty($payments)) return [];

        // Если не получены методы оплаты при расчете
        if (empty($storage['payments'])) return [];

        // Получаем данные из настроек плагина
        $arPaySystemsCash = $options['pay_systems_cash'] ?: [];
        $arPaySystemsCard = $options['pay_systems_card'] ?: [];
        $arPaySystemsOnline = $options['pay_systems_online'] ?: [];

        // Фильтруем платежные системы из системы на доступность
        $arPayments = [];
        foreach ($payments as $key => &$payment) {
            $paymentCode = $payment['id'];

            if (in_array($key, $arPaySystemsCash))
                $paymentCode = 'cash';

            if (in_array($key, $arPaySystemsCard))
                $paymentCode = 'card';

            if (in_array($key, $arPaySystemsOnline))
                $paymentCode = 'online';

            if (in_array($paymentCode, $storage['payments']))
                $arPayments[$key] = $payment;
        }

        return $arPayments;
    }

    /**
     * Хук: Выполняется после добавления/обновления заказа
     * @param array $args
     * @return array
     */
    public static function setOrderDeliveryInfo($args)
    {
        $isAdmin = URL::isSection('mg-admin'); // Проверяем в админке ли
        $deliveryId = 0;

        // Пролучаем ID закаказа
        if ($isAdmin) {
            $orderId = (int)$args['args'][0]['id'];

            if (empty($orderId))
                $orderId = (int)$args['result']['id'];
        } else {
            $orderId = (int)$args['result']['id'];
        }

        // Проверяем получен ли ID заказа, если нет возвращаем входящие аргументы
        if ($orderId <= 0)
            return $args['result'];

        // Получаем ID доставки из заказа
        $rsOrder = Order::getById($orderId, ['id', 'delivery_id']);
        if ($arOrder = DB::fetchAssoc($rsOrder))
            $deliveryId = (int)$arOrder['delivery_id'];

        // Проверяем получен ли ID доставки, если нет возвращаем входящие аргументы
        if ($deliveryId != self::$deliveryId)
            return $args['result'];

        $storage = Storage::main(self::$sessionName)->getByID(self::$deliveryId); // Получаем расчеты доставки из хранилища
        Storage::main(self::$sessionName)->removeById(self::$deliveryId); // Очищаем данные в хранилище

        // Проверяем было ли что в хранилище
        if (!empty($storage)) {

            // Обновляем в заказе информацию о доставке
            Order::update(
                $orderId,
                [
                    'address' => self::formattingAddress($storage),
                    'delivery_cost' => isset($storage['delivery_price']) ? $storage['delivery_price'] : 0,
                    'delivery_options' => serialize($storage)
                ]
            );
        }

        return $args['result'];
    }

    /**
     * Метод форматирует адрес
     * @param array $data
     * @return string
     */
    public static function formattingAddress(array $data)
    {
        $address = '';
        if (isset($data['pvz_address'])) {
            $address .= $data['pvz_address'];
        } elseif (isset($data['street']) || isset($data['house']) || isset($data['house_block']) || isset($data['flat'])) {
            $address .= !empty($data['street']) ? self::$lang['sb_delivery_address_street'] . ' ' . $data['street'] : '';
            $address .= !empty($data['street']) && !empty($data['house']) ? ', ' : '';
            $address .= !empty($data['house']) ? self::$lang['sb_delivery_address_house'] . ' ' . $data['house'] : '';
            $address .= !empty($data['house_block']) ? ' ' . self::$lang['sb_delivery_address_house_block'] . ' ' . $data['house_block'] : '';
            $address .= !empty($data['flat']) ? ', ' . self::$lang['sb_delivery_address_flat'] . ' ' . $data['flat'] : '';
        }

        return $address;
    }

    /**
     * Метод возвращает информацию о доставке из заказа
     * @param $orderId
     * @return array
     */
    public static function getDeliveryOptions($orderId)
    {
        $deliveryOptions = [];

        $rsOrder = Order::getById($orderId);
        if ($arOrder = DB::fetchAssoc($rsOrder)) {
            if (!empty($arOrder['delivery_options']))
                $deliveryOptions = unserialize($arOrder['delivery_options']);
        }

        return $deliveryOptions;
    }

    /**
     * Метод получения параметров продуктов из корзины
     * @return array
     */
    public static function getCartItemsParams()
    {
        $arCart = $arCartProdIds = [];
        if (!empty($_POST['orderItems'])) { // При пересчете в админке
            foreach ($_POST['orderItems'] as $arPostItem) {
				$arCartProdIds[] = $arPostItem['id'];
                $arCart[$arPostItem['id']] = [
                    'price_to_pay' => ceil($arPostItem['price']),
                    'price_insurance' => ceil($arPostItem['price']),
                    'weight' => $arPostItem['weight'],
                    'x' => 0,
                    'y' => 0,
                    'z' => 0,
                    'quantity' => ceil($arPostItem['count']),
                ];
            }
        } elseif (!empty($_POST['cart'])) { // При пересчете в паблике
            $cart = new Models_Cart();
            $itemsCart = $cart->getItemsCart();

            foreach ($_POST['cart'] as $arPostItem) {
                foreach ($itemsCart['items'] as $item) {
                    if ($item['id'] != $arPostItem['id'])
                        continue;

                    $arCartProdIds[] = $item['id'];
                    $arCart[$item['id']] = [
                        'price_to_pay' => ceil($item['price']),
                        'price_insurance' => ceil($item['price']),
                        'weight' => $item['weight'],
                        'x' => 0,
                        'y' => 0,
                        'z' => 0,
                        'quantity' => ceil($arPostItem['count']),
                    ];
                }
            }
        } else { // При первой загрузке паблика
            $cart = new Models_Cart();
            $itemsCart = $cart->getItemsCart();

            if (!empty($itemsCart['items'])) {
                foreach ($itemsCart['items'] as $item) {
                    $arCartProdIds[] = $item['id'];
                    $arCart[$item['id']] = [
                        'price_to_pay' => ceil($item['price']),
                        'price_insurance' => ceil($item['price']),
                        'weight' => $item['weight'],
                        'x' => 0,
                        'y' => 0,
                        'z' => 0,
                        'quantity' => ceil($item['countInCart']),
                    ];
                }
            }
        }

        $arCart = self::setParamsWXYZ($arCart, $arCartProdIds);
        return array_values($arCart);
    }

    /**
     * Метод на расчет стоимости доставки по Api
     * @return array
     */
    public static function calcDeliveryPrice()
    {
        $options = self::$options;
        $sumCart = self::sumCartItemsParams(self::getCartItemsParams());
        $storage = Storage::main(self::$sessionName)->getByID(self::$deliveryId);

        $arCity = [];
        if (isset($storage['city_code']))
            $arCity['city_id'] = $storage['city_code'];

        $arDelivery = [];
        if (isset($storage['delivery_method_id']))
            $arDelivery['delivery_method_id'] = $storage['delivery_method_id'];
        if (isset($storage['pvz_id']))
            $arDelivery['pvz_id'] = $storage['pvz_id'];

        $arProfile = [];
        if (isset($sumCart['weight']))
            $arProfile['weight'] = $sumCart['weight'];
        if (isset($sumCart['x']))
            $arProfile['x'] = $sumCart['x'];
        if (isset($sumCart['y']))
            $arProfile['y'] = $sumCart['y'];
        if (isset($sumCart['z']))
            $arProfile['z'] = $sumCart['z'];

        $arPrice = [];
        if (isset($sumCart['price_to_pay']))
            $arPrice['price_to_pay'] = $sumCart['price_to_pay'];
        if (isset($sumCart['price_insurance']))
            $arPrice['price_insurance'] = $sumCart['price_insurance'];

        $api = new \Salesbeat\Api();
        $result = $api->getDeliveryPrice(
            $options['api_token'],
            $arCity,
            $arDelivery,
            $arProfile,
            $arPrice
        );

        if ($result['success']) {
            $data = [
                'delivery_price' => $result['delivery_price'],
                'delivery_days' => $result['delivery_days'],
            ];

            Storage::main(self::$sessionName)->append(self::$deliveryId, $data);
        } else {
            $data['error'] = $result['error_message'];
        }

        return $data;
    }

    /**
     * Метод подсчитывает склывает параметры товаров из корзины
     * @param array $arCart
     * @return array
     */
    public static function sumCartItemsParams(array $arCart)
    {
        $result = [
            'price_to_pay' => 0,
            'price_insurance' => 0,
            'weight' => 0,
            'x' => 0,
            'y' => 0,
            'z' => 0,
            'quantity' => 0,
        ];

        if (!empty($arCart)) {
            foreach ($arCart as $arProduct) {
                $price = $arProduct['price_to_pay'] * $arProduct['quantity'];

                $result['price_to_pay'] += $price;
                $result['price_insurance'] += $price;
                $result['weight'] += $arProduct['weight'];
                $result['x'] += $arProduct['x'];
                $result['y'] += $arProduct['y'];
                $result['z'] += $arProduct['z'];
                $result['quantity'] += $arProduct['quantity'];
            }
        }

        return $result;
    }

    /**
     * Метод отображения информации о доставке вместо шорткода
     * @param array $arg
     * @return string
     */
    public static function addProductWidget(array $arg)
    {
        $pluginName = self::$pluginName; // Название плагина
        $lang = self::$lang; // Локали
        $options = self::$options; // Опции плагина

        $arUnitWeight = ['gr' => 1, 'kg' => 1000];
        $arUnitDimensions = ['mm' => 0.1, 'sm' => 1, 'm' => 100];

        // Устанавливаем кофициент
        $unitWeight = !empty($options['unit_weight']) ? $arUnitWeight[$options['unit_weight']] : 1;
        $unitDimensions = !empty($options['unit_dimensions']) ? $arUnitDimensions[$options['unit_dimensions']] : 1;

        // Проверяем передан ли ID
        if (isset($arg['id'])) {
            $product = new Models_Product();
            $arProduct = $product->getProduct($arg['id']); // Получаем даные о товаре

            $result = [
                'token' => isset($options['api_token']) ? $options['api_token'] : '',
                'price_to_pay' => ceil($arProduct['price']),
                'price_insurance' => ceil($arProduct['price']),
                'weight' => ceil($arProduct['weight'] * $unitWeight),
                'x' => ceil(0 * $unitDimensions),
                'y' => ceil(0 * $unitDimensions),
                'z' => ceil(0 * $unitDimensions),
                'quantity' => 1,
                'city_by' => 'ip',
                'params_by' => 'params',
                'main_div_id' => 'salesbeat-deliveries-' . $arg['id'],
            ];

            if (!empty($options['property_width']) && !empty($options['property_height']) && !empty($options['property_depth'])) {
                $strParamIds = implode(',', [$options['property_width'], $options['property_height'], $options['property_depth']]);
                $sql = 'SELECT * FROM `' . PREFIX . 'product_user_property_data` WHERE `product_id` = ' . DB::quoteInt($arProduct['id']) . ' AND `prop_id` IN (' . DB::quoteIN($strParamIds) . ')';
                if ($dbResult = DB::query($sql)) {
                    while ($arResult = DB::fetchAssoc($dbResult)) {
                        switch ($arResult['prop_id']) {
                            case $options['property_width']:
                                $result['x'] = ceil($arResult['name'] * $unitDimensions);
                                break;
                            case $options['property_height']:
                                $result['y'] = ceil($arResult['name'] * $unitDimensions);
                                break;
                            case $options['property_depth']:
                                $result['z'] = ceil($arResult['name'] * $unitDimensions);
                                break;
                        }
                    }
                }
            }
        } else {
            $result = [];
        }

        // Буфферизируем данные
        ob_start();
        // Перед выводом страницы подключаем необходимые файлы css и js файлы
        self::prepareAddProductWidget();

        // Подключаем view для страницы плагина
        include 'view/public_product.php';
        return ob_get_clean(); // Возвращаем буфер
    }

    /**
     * Метод выполняющийся перед генераццией виджета в карточке товара
     * Подключает css и js файлы плагина
     */
    private static function prepareAddProductWidget()
    {
        echo '
            <script src="https://app.salesbeat.pro/static/widget/js/widget.js"></script>
        ';
    }

    public static function getAdminOrderForm($args) {
        $pluginName = self::$pluginName; // Название плагина
        $lang = self::$lang; // Локали
        $options = self::$options; // Опции плагина
        $products = self::getCartItemsParams(); // Получаем параметры товаров
        $editMode  = false;

        $order = $args['args'];

        $arDeliveryInfo = unserialize($order['delivery_options']);
        $info = self::formattingDeliveryInfo($arDeliveryInfo); // Получаем информацию о дсотавке

        // Буфферизируем данные
        ob_start();
        echo '<div class="add-delivery-info row">';
        self::prepareAdminDeliveryForm($editMode); // Перед выводом страницы подключаем необходимые файлы css и js файлы
        include 'view/admin_order.php'; // Подключаем view для страницы плагина
        echo '</div>';

        return ob_get_clean(); // Возвращаем буфер
    }

    /**
     * Метод выполняющийся перед генераццией в заказе способа доставки
     * Подключает css и js файлы плагина
     * @param boolean $editMode
     */
    public static function prepareAdminDeliveryForm($editMode = false)
    {
        if (!$editMode) {
            echo '
                <script type="text/javascript">
                    includeJS("https://app.salesbeat.pro/static/widget/js/widget.js");
                    includeJS("https://app.salesbeat.pro/static/widget/js/cart_widget.js");
                    includeJS("' . SITE . '/' . Salesbeat::$path . '/js/admin/order.js");
                </script> 
            ';
        }
    }

    /**
     * Метод формирующий информацию расчета доставки
     * @param array $info
     * @return array
     */
    public static function formattingDeliveryInfo(array $info)
    {
        $lang = self::$lang; // Локали

        $result = [];

        if (!empty($info)) {
            if (isset($info['city_code'])) {
                $result['city_code'] = [
                    'name' => $lang['sb_delivery_city_code'],
                    'value' => $info['city_code']
                ];
            }

            if (isset($info['region_name']) || isset($info['city_name'])) {
                $location = '';
                $location .= !empty($info['region_name']) ? $info['region_name'] : '';
                $location .= !empty($info['region_name']) && !empty($info['short_name']) ? ', ' : '';
                $location .= !empty($info['short_name']) ? $info['short_name'] . '. ' : '';
                $location .= !empty($info['city_name']) ? $info['city_name'] : '';

                $result['location'] = [
                    'name' => $lang['sb_delivery_location'],
                    'value' => $location
                ];
            }

            if (isset($info['delivery_method_id'])) {
                $result['method_id'] = [
                    'name' => $lang['sb_delivery_method_id'],
                    'value' => $info['delivery_method_id']
                ];
            }

            if (isset($info['delivery_method_name'])) {
                $result['method_name'] = [
                    'name' => $lang['sb_delivery_method_name'],
                    'value' => $info['delivery_method_name']
                ];
            }

            if (isset($info['delivery_price'])) {
                $result['price'] = [
                    'name' => $lang['sb_delivery_price'],
                    'value' => $info['delivery_price']
                ];
            }

            if (isset($info['delivery_days'])) {
                $result['days'] = [
                    'name' => $lang['sb_delivery_days'],
                    'value' => $info['delivery_days']
                ];
            }

            if (isset($info['index'])) {
                $result['index'] = [
                    'name' => $lang['sb_delivery_index'],
                    'value' => $info['index']
                ];
            }

            if (isset($info['pvz_address'])) {
                $result['pvz_address'] = [
                    'name' => $lang['sb_delivery_pvz_address'],
                    'value' => Salesbeat::formattingAddress($info)
                ];
            }

            if (isset($info['street']) || isset($info['house']) || isset($info['house_block']) || isset($info['flat'])) {
                $result['address'] = [
                    'name' => $lang['sb_delivery_address'],
                    'value' => Salesbeat::formattingAddress($info)
                ];
            }

            if (isset($info['comment'])) {
                $result['comment'] = [
                    'name' => $lang['sb_delivery_comment'],
                    'value' => $info['comment']
                ];
            }

            if (isset($info['track_code'])) {
                $result['track_code'] = [
                    'name' => $lang['sb_delivery_track_code'],
                    'value' => $info['track_code']
                ];
            }
        }

        return $result;
    }

    public static function setParamsWXYZ($arProducts, $arCartProdIds) {
        $options = self::$options; // Опции плагина

        // Устанавливаем кофициент
        $arUnitWeight = ['gr' => 1, 'kg' => 1000]; // Справочники соотношений
        $unitWeight = !empty($options['unit_weight']) ? $arUnitWeight[$options['unit_weight']] : 1;

        $arUnitDimensions = ['mm' => 0.1, 'sm' => 1, 'm' => 100]; // Справочники соотношений
        $unitDimensions = !empty($options['unit_dimensions']) ? $arUnitDimensions[$options['unit_dimensions']] : 1;

        // Устанавливаем параметры
        foreach ($arProducts as &$arProduct)
            $arProduct['weight'] = ceil($arProduct['weight'] * $unitDimensions);

        if (!empty($options['property_width']) && !empty($options['property_height']) && !empty($options['property_depth'])) {
            $strParamIds = implode(',', [$options['property_width'], $options['property_height'], $options['property_depth']]);
            $strProdIds = implode(',', $arCartProdIds);
            $sql = 'SELECT * FROM `' . PREFIX . 'product_user_property_data` WHERE `product_id` IN (' . DB::quoteIN($strProdIds) . ') AND `prop_id` IN (' . DB::quoteIN($strParamIds) . ')';
            if ($dbResult = DB::query($sql)) {
                while ($arResult = DB::fetchAssoc($dbResult)) {
                    switch ($arResult['prop_id']) {
                        case $options['property_width']:
                            $arProducts[$arResult['product_id']]['x'] = ceil($arResult['name'] * $unitDimensions);
                            break;
                        case $options['property_height']:
                            $arProducts[$arResult['product_id']]['y'] = ceil($arResult['name'] * $unitDimensions);
                            break;
                        case $options['property_depth']:
                            $arProducts[$arResult['product_id']]['z'] = ceil($arResult['name'] * $unitDimensions);
                            break;
                    }
                }
            }
        }

        return $arProducts;
    }
}