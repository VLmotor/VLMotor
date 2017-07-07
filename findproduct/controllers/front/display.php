<?php
/**
 * Created by PhpStorm.
 * User: afuentes
 * Date: 03/07/2017
 * Time: 18:41
 */
class FindProductDisplayModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $marca = 'Seat';
        $modelo = 'Toledo';
        $id_lang = 1;

        $query = 'SELECT pa.id_product' .
            ' FROM ' . _DB_PREFIX_ . 'product_attribute pa' .
            ' LEFT JOIN ' . _DB_PREFIX_ . 'product_attribute_combination pac ON pac.id_product_attribute = pa.id_product_attribute' .
            ' LEFT JOIN ' . _DB_PREFIX_ . 'attribute a ON a.id_attribute = pac.id_attribute' .
            ' LEFT JOIN ' . _DB_PREFIX_ . 'attribute_group ag ON ag.id_attribute_group = a.id_attribute_group' .
            ' LEFT JOIN ' . _DB_PREFIX_ . 'attribute_lang al ON (a.id_attribute = al.id_attribute AND al.id_lang = ' . (int) $id_lang . ')' .
            ' WHERE (ag.id_attribute_group = 4 AND al.name = \'' . pSQL($marca) . '\')' .
            ' OR (ag.id_attribute_group = 5 AND al.name = \'' . pSQL($modelo) . '\') GROUP BY pa.id_product';

        var_dump((int)Db::getInstance()->executeS($query));

//        $this->setTemplate('display.tpl');
    }
}