<?php

namespace Salesbeat;

use \Salesbeat\Http;

class Api
{
    public $url = 'https://app.salesbeat.pro';
    private $httpd = null;

    /**
     * Api constructor
     * @param object $registry
     */
    public function __construct($registry = null)
    {
        if (!$this->httpd)
            $this->httpd = new Http();
    }

    /**
     * Проверка правильности токенов
     * @param string $apiToken
     * @param string $secretToken
     * @return array
     */
    public function postCheckTokens($apiToken = '', $secretToken = '')
    {
        $arFields = [
            'api_token' => $apiToken,
            'secret_token' => $secretToken,
        ];

        $result = $this->httpd->get($this->url . '/api/v1/check_tokens', $arFields);
        return $result;
    }

    /**
     * Поиск местоположения
     * @param string $token
     * @param array $arCity
     * @return array
     */
    public function getCities($token = '', $arCity = [])
    {
        $arFields = [];
        $arFields = array_merge($arFields, $this->validateToken($token));
        $arFields = array_merge($arFields, $this->validateCity($arCity));

        $result = $this->httpd->get($this->url . '/api/v1/get_cities', $arFields);
        return $arResult = $result ?: [];
    }

    /**
     * Список всех служб доставки
     * @param string $token
     * @return array
     */
    public function getListDeliveries($token = '')
    {
        $arFields = $this->validateToken($token);

        $result = $this->httpd->get($this->url . '/api/v1/get_all_delivery_methods', $arFields);
        return $arResult = $result ?: [];
    }

    /**
     * Список служб доставки в населённом пункте
     * @param string $token
     * @param array $arCity
     * @param array $arProfile
     * @param array $arPrice
     * @return array
     */
    public function getDeliveryByCity($token = '', $arCity = [], $arProfile = [], $arPrice = [])
    {
        $arFields = [];
        $arFields = array_merge($arFields, $this->validateToken($token));
        $arFields = array_merge($arFields, $this->validateCity($arCity));
        $arFields = array_merge($arFields, $this->validateProfile($arProfile));
        $arFields = array_merge($arFields, $this->validatePrice($arPrice));

        $result = $this->httpd->get($this->url . '/api/v1/get_delivery_methods_by_city', $arFields);
        return $arResult = $result ?: [];
    }

    /**
     * Расчёт стоимости доставки
     * @param string $token
     * @param array $arCity
     * @param array $arDelivery
     * @param array $arProfile
     * @param array $arPrice
     * @return array
     */
    public function getDeliveryPrice($token = '', $arCity = [], $arDelivery = [], $arProfile = [], $arPrice = [])
    {
        $arFields = [];
        $arFields = array_merge($arFields, $this->validateToken($token));
        $arFields = array_merge($arFields, $this->validateCity($arCity));
        $arFields = array_merge($arFields, $this->validateDelivery($arDelivery));
        $arFields = array_merge($arFields, $this->validateProfile($arProfile));
        $arFields = array_merge($arFields, $this->validatePrice($arPrice));

        $result = $this->httpd->get($this->url . '/api/v1/get_delivery_price', $arFields);
        return $arResult = $result ?: [];
    }

    /**
     * Синхронизация способов оплаты
     * @param string $token
     * @param array $arPaySystems
     * @param array $arExPaySystems
     * @return array
     */
    public function syncDeliveryPaymentTypes($token = '', $arPaySystems = [], $arExPaySystems = [])
    {
        $arPaySystemsCash = $arExPaySystems['cash'] ?: [];
        $arPaySystemsCard = $arExPaySystems['card'] ?: [];
        $arPaySystemsOnline = $arExPaySystems['online'] ?: [];

        $arFields = [];
        foreach ($arPaySystems as $arPaySystem) {
            $paySystemCode = $arPaySystem['code'];

            if (empty($arPaySystem['name'])) continue;
            if (in_array($paySystemCode, $arPaySystemsCash)) continue;
            if (in_array($paySystemCode, $arPaySystemsCard)) continue;
            if (in_array($paySystemCode, $arPaySystemsOnline)) continue;

            $arFields[] = [
                'name' => $arPaySystem['name'] ?: '',
                'code' => $arPaySystem['code'] ?: ''
            ];
        }

        $result = $this->httpd->post($this->url . '/api/v1/sync_delivery_payment_types?token=' . $token, $arFields);
        return $arResult = $result ?: [];
    }

    /**
     * Получение способов оплаты
     * @param string $token
     * @return array
     */
    public function getDeliveryPaymentTypes($token = '')
    {
        $arFields = $this->validateToken($token);

        $result = $this->httpd->get($this->url . '/api/v1/get_delivery_payment_types', $arFields);
        return $arResult = $result ?: [];
    }

    /**
     * Создать заказ на доставку
     * @param array $arFields
     * @return array
     */
    public function createOrder($arFields = [])
    {
        if (!$arFields) return [];

        $result = $this->httpd->post($this->url . '/delivery_order/create/', $arFields);
        return $arResult = $result ?: [];
    }

    /**
     * Вызвать курьера
     * @param int $orderId
     * @return array
     */
    public function callCourier($orderId = 0)
    {
        if ($orderId <= 0) return [];
        $arFields = [];

        $result = $this->httpd->post($this->url . '/delivery_order/create/', $arFields);
        return $arResult = $result ?: [];
    }

    /**
     * Валидация токена
     * @param string $string
     * @return array
     */
    private function validateToken($string = '')
    {
        $string = $string ?: '';
        return ['token' => $string];
    }

    /**
     * Валидация населенного пункта
     * @param array $array
     * @return array
     */
    private function validateCity($array = [])
    {
        $arResult = [];
        foreach ($array as $key => $value) {
            if (in_array($key, ['id', 'city', 'city_id', 'postalcode', 'ip']))
                $arResult[$key] = $value;
        }
        return $arResult;
    }

    /**
     * Валидация метода доставки
     * @param array $array
     * @return array
     */
    private function validateDelivery($array = [])
    {
        $arResult = [];
        foreach ($array as $key => $value) {
            if (in_array($key, ['delivery_method_id', 'pvz_id']))
                $arResult[$key] = $value;
        }
        return $arResult;
    }

    /**
     * Валидация габаритов
     * @param array $array
     * @return array
     */
    private function validateProfile($array = [])
    {
        return [
            'weight' => (int)$array['weight'],
            'x' => (int)$array['x'],
            'y' => (int)$array['y'],
            'z' => (int)$array['z']
        ];
    }

    /**
     * Валидация цены
     * @param array $array
     * @return array
     */
    private function validatePrice($array = [])
    {
        return [
            'price_to_pay' => (float)$array['price_to_pay'],
            'price_insurance' => (float)$array['price_insurance']
        ];
    }
}