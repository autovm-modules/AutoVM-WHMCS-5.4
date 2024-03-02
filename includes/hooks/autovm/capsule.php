<?php

namespace WHMCS\Database;

class Capsule
{
    public $id;
    public $table;

    public static function table($table)
    {
        return new Capsule($table);
    }

    public function __construct($table)
    {
        $this->table = $table;
    }

    public function whereId($id)
    {
        $this->id = $id;

        return $this;
    }

    public static function getClient($id)
    {
        $id = intval($id);

        $query = mysql_query("SELECT * FROM tblclients WHERE id = $id");

        $row = mysql_fetch_object($query);

        return $row;
    }

    public static function getService($id)
    {
        $id = intval($id);

        $query = mysql_query("SELECT * FROM tblhosting WHERE id = $id");

        $row = mysql_fetch_object($query);

        return $row;
    }

    public static function getClientService($clientId, $serviceId)
    {
        $clientId = intval($clientId);
        $serviceId = intval($serviceId);

        $query = mysql_query("SELECT * FROM tblhosting WHERE id = $serviceId AND userid = $clientId");

        $row = mysql_fetch_object($query);

        return $row;
    }

    public static function escape($query, $params)
    {
        foreach ($params as $name => $value) {

            $name = mysql_real_escape_string($name);
            $value = mysql_real_escape_string($value);

            $query = str_replace(":$name", $value, $query);
        }

        return $query;
    }

    public static function selectOne($query, $params)
    {
        $query = self::escape($query, $params);

        $query = mysql_query($query);

        $row = mysql_fetch_object($query);

        return $row;
    }

    public function get()
    {
        $query = mysql_query("SELECT * FROM $this->table");

        $output = array();

        while( $row = mysql_fetch_object($query) ) {
            $output[] = $row;
        }

        return $output;
    }

    public function insert($params)
    {
        return insert_query($this->table, $params);
    }

    public function update($params)
    {
        $conditions = array('id' => $this->id);

        return update_query($this->table, $params, $conditions);
    }

    public function getAutoVMOrder($orderId)
    {
        $params = ['order_id' => $orderId];

        $order = Capsule::selectOne('SELECT * FROM autovm_order WHERE order_id = :order_id', $params);

        return $order;
    }
}
