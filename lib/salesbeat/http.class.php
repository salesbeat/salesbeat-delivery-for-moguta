<?php

namespace Salesbeat;

class Http
{
    /**
     * Http constructor
     * @param object $registry
     */
    public function __construct($registry = null)
    {

    }

    /**
     * @param $url
     * @param $data
     * @return array
     */
    public function get($url, $data)
    {
        if (!$url)
            return ['type' => 'error', 'message' => 'Empty url'];

        if (!is_array($data))
            return ['status' => 'error', 'message' => 'Data not array'];

        return $this->send('get', $url, $data);
    }

    /**
     * @param $url
     * @param $data
     * @return array
     */
    public function post($url, $data)
    {
        if (!$url)
            return ['type' => 'error', 'message' => 'Empty url'];

        if (!is_array($data))
            return ['status' => 'error', 'message' => 'Data not array'];

        return $this->send('post', $url, $data);
    }

    /**
     * @param string $method
     * @param string $url
     * @param array $data
     * @return array
     */
    private function send($method, $url, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        if ($method == 'get') {
            $query = '?' . http_build_query($data);

            curl_setopt($ch, CURLOPT_URL, $url . $query);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HEADER, false);
        } elseif ($method == 'post') {
            $query = json_encode($data);

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HEADER, 'Content-Type: application/json');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
        }

        $result = curl_exec($ch);
        curl_close($ch);

        $arResult = !empty($result) ? json_decode($result, true) : [];

        return $arResult;
    }
}