<?php

namespace WHMCS\Service;

class Service
{
    public static function find($id)
    {
        $query = mysql_query('SELECT * FROM tblhosting WHERE id = $id');

        $result = mysql_fetch_assoc($query);

        return $result['id'];
    }
}
