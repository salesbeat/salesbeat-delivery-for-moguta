<?php

namespace Salesbeat;

new Property;

class Property
{
    private static $tableName = ''; // Название таблицы с свойств

    /**
     * Property constructor.
     */
    public function __construct()
    {
        self::$tableName = PREFIX . 'property';
    }

    /**
     * Получение списка свойств
     * @param array $sort
     * @param array $filter
     * @param array $select
     * @param int $limit
     * @return mixed
     */
    public static function getList($sort = [], array $filter = [], array $select = [], $limit = 0)
    {
        if (!empty($sort)) {
            $sortKey = key($sort);
            $sortValue = $sort[$sortKey];

            $strSort = ' ORDER BY ' . $sortKey . ' ' . $sortValue . ' ';
        } else {
            $strSort = '';
        }

        if (!empty($filter)) {
            $strFilter = ' WHERE ';

            foreach ($filter as $key => $value)
                $strFilter .= '`' . $key . '` = ' . \DB::quote($value) . ' AND';

            $strFilter = substr($strFilter, 0, -3);
        } else {
            $strFilter = '';
        }

        $strSelect = !empty($select) ?
            implode(', ', $select) :
            '*';

        $strLimit = !empty($limit) && $limit > 0 ? 'LIMIT ' . \DB::quoteInt($limit) : '';

        $sql = 'SELECT ' . $strSelect . ' 
                FROM `' . self::$tableName . '`  
                ' . $strFilter . ' 
                ' . $strSort . '
                ' . $strLimit;

        return \DB::query($sql);
    }

    /**
     * Получение свойства
     * @param $id
     * @param array $select
     * @return mixed
     */
    public static function getById($id, array $select = [])
    {
        $strSelect = !empty($select) ?
            implode(', ', $select) :
            '*';

        $sql = 'SELECT ' . $strSelect . ' FROM `' . self::$tableName . '`  WHERE `id` = ' . \DB::quoteInt($id);
        return \DB::query($sql);
    }

    /**
     * Создание свойства
     * @param array $fields
     */
    public static function add(array $fields)
    {
        $strSet = \DB::buildPartQuery($fields);

        if (strlen($strSet) > 0) {
            $sql = 'INSERT INTO `' . self::$tableName . '` SET ' . $strSet;
            \DB::query($sql);
        }
    }

    /**
     * Обновление свойства
     * @param $id
     * @param array $fields
     */
    public static function update($id, array $fields)
    {
        $strSet = \DB::buildPartQuery($fields);

        if (strlen($strSet) > 0) {
            $sql = 'UPDATE `' . self::$tableName . '` SET ' . $strSet . ' WHERE `id` = ' . \DB::quoteInt($id);
            \DB::query($sql);
        }
    }

    /**
     * Удаление свойства
     * @param $id
     */
    public static function delete($id)
    {
        $sql = 'DELETE FROM `' . self::$tableName . '` WHERE `id` = ' . \DB::quoteInt($id);
        \DB::query($sql);
    }
}