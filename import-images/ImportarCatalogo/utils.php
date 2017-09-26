<?php
function findAllProductReferences() {
    $rows = Db::getInstance()->executeS('
		SELECT `reference`, `id_product`
		FROM `'._DB_PREFIX_.'product` p', true, false);

    return $rows;
}

/**
 * @param $string
 * @return string
 */
function cleanString($string)
{
    return trim($string);
}

/**
 * @param $source
 * @param $needle
 */
function findIdProduct($source, $needle)
{
    $index = searchInMultidimensionalArray($source, $needle);
    return $source[$index]['id_product'];
}

/**
 * @param $existing_references
 * @param $needle
 * @return mixed
 */
function searchInMultidimensionalArray($source, $needle)
{
    $column_array = array_column($source, REFERENCE_KEY);
    $index = array_search($needle, $column_array);

    return $index;
}

/**
 * @param $reference
 * @param $product
 */
function populateProduct($reference, $description, &$product, $id_category)
{
    $product->reference = $reference;
    $product->price = (float)0;
    $product->active = (int)1;
    $product->weight = (float)0;
    $product->minimal_quantity = (int)0;
    $product->id_category = $id_category;
    $product->id_category_default = $id_category;
    $product->name[1] = utf8_encode($reference);
    $product->description[1] = utf8_encode($description);
    $product->link_rewrite[1] = Tools::link_rewrite($reference);
    if (!isset($product->date_add) || empty($product->date_add)) {
        $product->date_add = date('Y-m-d H:i:s');
    }
    $product->date_upd = date('Y-m-d H:i:s');
    $product->save();
    $product->addToCategories(array($id_category));

    return $product;
}

function findProductIdByReference($reference) {
    $row = Db::getInstance()->getRow('
		SELECT `id_product`
		FROM `'._DB_PREFIX_.'product` p
		WHERE p.reference = "'.pSQL($reference).'"');

    return $row['id_product'];
}

/**
 * @return int|string
 */
function findCategoryId()
{
    $categories = CategoryCore::searchByName(Configuration::get('PS_LANG_DEFAULT'), 'pastillas');
    $id_category = 0;
    if (!empty($categories)) {
        $id_category = $categories[0]['id_category'];
    }
    return $id_category;
}

function findExistingReferences()
{
    $existing_references = findAllProductReferences();
    $references = array();
    foreach ($existing_references as $reference) {
        $references[$reference[REFERENCE_KEY]] = $reference['id_product'];
    }

    return $references;
}

/**
 * @param $reference
 * @return Created product
 */
function createProduct($reference, $description, &$reference_id_map, $id_category)
{
    $product = new Product();
    if(!$product->existsRefInDatabase($reference)) {
        $product = populateProduct($reference, $description, $product, $id_category);

        if($product->save()) {
            $reference_id_map[$reference] = $product->id;
        }
    } else {
        $product = findProductId($reference, $reference_id_map, $product);
    }
    return $product;
}

/**
 * @param $reference
 * @param $reference_id_map
 * @param $product
 * @return mixed
 */
function findProductId($reference, &$reference_id_map, &$product)
{
    if (array_key_exists($reference, $reference_id_map)) {
        $product->id = $reference_id_map[$reference];
    } else {
        $product_id = findProductIdByReference($reference);
        if ($product_id) {
            $reference_id_map[$reference] = $product_id;
            $product->id = $product_id;
        }
    }
    return $product;
}
