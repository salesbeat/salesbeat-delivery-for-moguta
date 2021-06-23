<?php

namespace Salesbeat;

class Storage
{
    private static $main = null; // Объект хранилища
    private $storage = []; // Хранилище
    private $storageName = '';

    /**
     * Storage constructor.
     * @param $storageName
     */
    private function __construct($storageName)
    {
        if (empty($this->storageName))
            $this->storageName = $storageName;

        if (empty($this->storage) && isset($_SESSION[$this->storageName]))
            $this->storage = $_SESSION[$this->storageName];
    }

    /**
     * @param string $storageName
     * @return Storage|null
     */
    public static function main($storageName = 'delivery')
    {
        if (is_null(self::$main))
            self::$main = new self($storageName);

        return self::$main;
    }

    /**
     * @return array
     */
    public function getList()
    {
        return $this->storage;
    }

    /**
     * @param int $id
     * @return array
     */
    public function getByID($id)
    {
        $result = isset($this->storage[$id]) ? $this->storage[$id] : [];
        return $result;
    }

    /**
     * @param int $id
     * @param array $data
     */
    public function save($id, array $data)
    {
        if ($id > 0 && is_array($data)) {
            $this->removeById($id);

            if (!isset($this->storage[$id]))
                $this->storage[$id] = [];

            $this->storage[$id] = $this->transform($data);
            $this->updateSession();
        }
    }

    /**
     * @param int $id
     * @param array $data
     */
    public function append($id, array $data)
    {
        if (!isset($this->storage[$id]))
            $this->storage[$id] = [];

        $this->storage[$id] = array_merge($this->storage[$id], $this->transform($data));
        $this->updateSession();
    }

    public function remove()
    {
        $this->storage = [];
        $this->updateSession();
    }

    /**
     * @param int $id
     */
    public function removeById($id)
    {
        if (isset($this->storage[$id])) {
            $this->storage[$id] = [];
            $this->updateSession();
        }
    }

    /**
     * @param array $data
     * @return array
     */
    private function transform(array $data)
    {
        $arResult = [];
        foreach ($data as $key => $value)
            $arResult[mb_strtolower($key)] = $value;
        return $arResult;
    }

    private function updateSession()
    {
        if (!empty($this->storage)) {
            $_SESSION[$this->storageName] = $this->storage;
        } else {
            unset($_SESSION[$this->storageName]);
        }
    }
}