<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Product;

class Products
{
    public function getProducts($groupId = NULL)
    {
        $where = array();
        if ($groupId) {
            $where["tblproducts.gid"] = (int) $groupId;
        }
        $products = array();
        $result = select_query("tblproducts", "tblproducts.id,tblproducts.gid,tblproducts.retired,tblproducts.name,tblproductgroups.name AS groupname", $where, "tblproductgroups`.`order` ASC, `tblproducts`.`order` ASC, `name", "ASC", "", "tblproductgroups ON tblproducts.gid=tblproductgroups.id");
        while ($data = mysql_fetch_assoc($result)) {
            $products[] = $data;
        }
        return $products;
    }
}

?>