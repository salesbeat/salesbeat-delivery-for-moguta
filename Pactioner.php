<?php

/**
 * Класс Pactioner предназначен для выполнения действий, AJAX запросов плагина
 */

use \Salesbeat\Storage;
use \Salesbeat\Order;
use \Salesbeat\SbOrder;
use \Salesbeat\Tools;

class Pactioner extends Actioner
{
    public function saveBaseOption()
    {
        // Доступно только админам и модераторам.
        USER::AccessOnly('1,4', 'exit()');

        // Сообщения для нотификаций успеха или ошибки
        $this->messageSucces = $this->lang['sb_save_setting'];
        $this->messageError = $this->lang['sb_not_save_setting'];

        // Получаем параметры запроса
        $request = $_POST;

        // Проверяем наличие данных для опций
        if (!empty($request['data'])) {
            $data = $request['data'];

            // Массив корректных ключей
            $issetKey = [
                'api_token',
                'secret_token',
                'pay_systems_cash',
                'pay_systems_card',
                'pay_systems_online',
                'property_weight',
                'property_width',
                'property_height',
                'property_depth',
                'unit_weight',
                'unit_dimensions',
            ];

            if (is_array($data)) {
                // Удаляем не нужные значения
                foreach ($data as $key => $value) {
                    if (!in_array($key, $issetKey))
                        unset($data[$key]);
                }
            }

            // Устанавливаем новые опции
            MG::setOption([
                'option' => Salesbeat::$pluginName . '-option',
                'value' => addslashes(serialize($data))
            ]);
        }

        // Возвращаем статус "успешно"
        return true;
    }

    public function saveCallbackDelivery()
    {
        // Получаем параметры запроса
        $request = $_POST;

        if (!isset($request['sessionName']))
            return false;

        // Проверям данные расчета и сохраняем в хранилище
        if (!empty($request['result']) && !empty($request['deliveryId'])) {
            Storage::main($request['sessionName'])->save($request['deliveryId'], $request['result']);
            return true;
        }

        return false;
    }

    /**
     * Обязательная функция плагина, для получения стоимости доставки из админки, по параметрам заказа
     */
    public function getPriceForParams()
    {
        Salesbeat::$sessionName = 'deliveryAdmin'; // Присваеваем название сессии
        Salesbeat::$deliveryId = $_POST['deliveryId']; // Присваеваем Id доставки

        $result = Salesbeat::calcDeliveryPrice();

        if (!isset($result['error'])) {
            $this->data['deliverySum'] = $result['delivery_price'];
        } else {
            $this->data['deliverySum'] = -1;
            $this->data['error'] = isset($result['error']) ?
                $result['error'] : $this->lang['sb_error_type_4'];
        }

        return true;
    }

    /**
     * Обязательная функция плагина, для возможности пересчета стоимости, или изменения пункта достаки из админки
     * Возвращает дополнительную верстку для выборпа парметров доставки
     */
    public function getAdminDeliveryForm()
    {
        $pluginName = Salesbeat::$pluginName; // Название плагина
        $lang = $this->lang; // Локали
        $options = Salesbeat::$options; // Опции плагина
        $deliveryId = $_POST['deliveryId']; // Id доставки
        $products = Salesbeat::getCartItemsParams(); // Получаем параметры товаров
        $editMode = (isset($_POST['recalc']) && $_POST['recalc'] == 'true'); // True / false

        if ($editMode) {
            $arDeliveryInfo = Storage::main('deliveryAdmin')->getByID($deliveryId);
        } else {
            $arDeliveryInfo = Salesbeat::getDeliveryOptions((int)$_POST['orderId']);
            Storage::main('deliveryAdmin')->save($deliveryId, $arDeliveryInfo);
        }

        $info = Salesbeat::formattingDeliveryInfo($arDeliveryInfo); // Получаем информацию о дсотавке

        // Буфферизируем данные
        ob_start();
        Salesbeat::prepareAdminDeliveryForm($editMode); // Перед выводом страницы подключаем необходимые файлы css и js файлы
        include 'view/admin_order.php'; // Подключаем view для страницы плагина
        $html = ob_get_clean(); // Записываем буфер

        $this->data['form'] = $editMode ? $html : '';
        $this->data['delivery_price'] = $arDeliveryInfo['delivery_price'];
        $this->data['address'] = Salesbeat::formattingAddress($arDeliveryInfo);

        return true;
    }

    /**
     * Метод обновляет данные виджета
     * @return string
     */
    public function updatePublicWidget()
    {
        $options = Salesbeat::$options; // Опции плагина
        $products = Salesbeat::getCartItemsParams(); // Получаем параметры товаров
        $storage = Storage::main(Salesbeat::$sessionName)->getByID(Salesbeat::$deliveryId);

        $this->data['html'] = Salesbeat::updateOrderWidget();
        $this->data['token'] = isset($options['api_token']) ? $options['api_token'] : '';
        $this->data['city_code'] = '';
        $this->data['products'] = json_encode($products);
        $this->data['is_select'] = !empty($storage);

        return true;
    }

    public function sendOrder()
    {
        $pluginName = Salesbeat::$pluginName; // Название плагина
        $lang = $this->lang; // Локали
        $options = Salesbeat::$options; // Опции плагина
        $orderId = (int)$_POST['orderId']; // Id заказа

        $rsOrder = Order::getById($orderId);
        $order = DB::fetchAssoc($rsOrder);

        // Информация о доставке
        $arDeliveryInfo = $order['delivery_options'] ?
            unserialize($order['delivery_options']) : [];

        // Товары в заказе
        $orderContent = $order['order_content'] ?
            unserialize(stripcslashes($order['order_content'])) : [];

        $arFields = [];
        if (!empty($order) && !empty($arDeliveryInfo)) {
            $arFields = [];
            $arFields['secret_token'] = isset($options['secret_token']) ? $options['secret_token'] : '';
            $arFields['test_mode'] = false;

            $arFields['order'] = [
                'delivery_method_code' => $arDeliveryInfo['delivery_method_id'],
                'id' => $orderId,
                'delivery_price' => !empty($order['paided']) ? 0 : $arDeliveryInfo['delivery_price'],
                'delivery_from_shop' => false
            ];

            // Товары
            $products = [];
            $arProductsId = [];

            foreach ($orderContent as $product) {
                $arProductsId[] = $product['id'];

                $products[] = [
                    'id' => $product['id'],
                    'name' => $product['name'],
                    'price_to_pay' => $product['price'],
                    'price_insurance' => $product['price'],
                    'weight' => $product['weight'],
                    'x' => 0,
                    'y' => 0,
                    'z' => 0,
                    'quantity' => ceil($product['count']),
                ];
            }
            unset($orderContent, $product);

            $arFields['products'] = Salesbeat::setParamsWXYZ($products, $arProductsId);
            unset($products, $arProductsId);

            $recipient = [];
            $recipient['city_id'] = $arDeliveryInfo['city_code'];
            $recipient['full_name'] = trim($order['name_buyer']);
            $recipient['phone'] = Tools::phoneToTel($order['phone']);
            $recipient['email'] = $order['contact_email'];

            if (isset($arDeliveryInfo['pvz_id'])) {
                $recipient['pvz']['id'] = $arDeliveryInfo['pvz_id'];
            } else {
                $dateCourier = new DateTime();
                $dateCourier->add(new DateInterval('P1D'));

                $recipientCourier = [];
                $recipientCourier['street'] = $arDeliveryInfo['street'];
                $recipientCourier['house'] = $arDeliveryInfo['house'];
                $recipientCourier['flat'] = $arDeliveryInfo['flat'];
                $recipientCourier['date'] = $dateCourier->format('Y-m-d');
                $recipient['courier'] = $recipientCourier;
                unset($recipientCourier);
            }

            $arFields['recipient'] = $recipient;
            unset($recipient);
        }

        $api = new \Salesbeat\Api();
        $resultApi = $api->createOrder($arFields);

        $arDeliveryInfo['track_code'] = $resultApi['track_code'];
        Order::update($orderId, ['delivery_options' => serialize($arDeliveryInfo)]);

        if (isset($resultApi['success']) && $resultApi['success'] === true) {
            SbOrder::add([
                'order_id' => $resultApi['order_id'],
                'sb_order_id' => $resultApi['salesbeat_order_id'],
                'track_code' => $resultApi['track_code'],
                'date_order' => date('Y-m-d H:i:s'),
                'sent_courier' => 0
            ]);

            $this->data['status'] = 'success';
            $this->data['message'] = 'Заказ #' . $orderId . ' успешно выгружен';

            $editMode = true;
            $info = Salesbeat::formattingDeliveryInfo($arDeliveryInfo);

            ob_start();
            echo '<div class="add-delivery-info row">';
            include 'view/admin_order.php'; // Подключаем view для страницы плагина
            echo '</div>';
            $this->data['form'] = ob_get_clean();
        } else {
            $this->data['status'] = 'error';
            $this->data['message'] = $resultApi['error_message'] ?: '';
            $this->data['info'] = $resultApi;
        }

        return true;
    }
}