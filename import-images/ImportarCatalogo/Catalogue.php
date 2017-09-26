<?php
ini_set('memory_limit', '512M');
set_time_limit(24000);

include('./config.php');

createConstants();

require_once(PS_ROOT_DIR . '/config/config.inc.php');
require_once(PS_ROOT_DIR . '/controllers/admin/AdminImportController.php');

$key_attribute_map = array(
    REFERENCE_KEY => 'Referencia',
    BRAND_KEY => 'Marca',
    MODEL_KEY => 'Modelo',
    FAMILY_KEY => 'Familia'
);

function importCatalog($catalog) {
    if(is_array($catalog)) {
        $reference_id_map = findExistingReferences();
        $id_category = findCategoryId();

        $combinations = array();
        foreach ($catalog as $product_info) {
            $combinations = array_merge($combinations, createCombinations($product_info, $reference_id_map, $id_category));
        }
        $catalog = null;
        createCsvFiles($combinations);
        $reference_compounds_map = null;
        gc_collect_cycles();

        importProducts();
    }
}

///**
// * @return int|string
// */
//function findCategoryId()
//{
//    $categories = CategoryCore::searchByName(Configuration::get('PS_LANG_DEFAULT'), 'pastillas');
//    $id_category = 0;
//    if (!empty($categories)) {
//        $id_category = $categories[0]['id_category'];
//    }
//    return $id_category;
//}
//
//function findExistingReferences()
//{
//    $existing_references = findAllProductReferences();
//    $references = array();
//    foreach ($existing_references as $reference) {
//        $references[$reference[REFERENCE_KEY]] = $reference['id_product'];
//    }
//
//    return $references;
//}

///**
// * @param $reference
// * @return Created product
// */
//function createProduct($reference, &$reference_id_map, $id_category)
//{
//    $product = new Product();
//    if(!$product->existsRefInDatabase($reference)) {
//        $product = populateProduct($reference, $product, $id_category);
//
//        if($product->save()) {
//            $reference_id_map[$reference] = $product->id;
//        }
//    } else {
//        $product = findProductId($reference, $reference_id_map, $product);
//    }
//    return $product;
//}

//function findProductIdByReference($reference) {
//    $row = Db::getInstance()->getRow('
//		SELECT `id_product`
//		FROM `'._DB_PREFIX_.'product` p
//		WHERE p.reference = "'.pSQL($reference).'"');
//
//    return $row['id_product'];
//}

//function findAllProductReferences() {
//    $rows = Db::getInstance()->executeS('
//		SELECT `reference`, `id_product`
//		FROM `'._DB_PREFIX_.'product` p', true, false);
//
//    return $rows;
//}

/**
 * @param $product
 * @param $product_info = [4000]=>
array(3) {
["reference"]=>
string(4) "4000"
["attributes"]=>
array(3) {
["family"]=>
string(4) "4000"
["brand"]=>
string(7) "CITROEN"
["model"]=>
string(14) "ZX 2.0 Volcane"
}
["compounds"]=>
array(5) {
["RC5+"]=>
string(4) "RC5+"
["RC6"]=>
string(3) "RC6"
["RC6 E"]=>
string(5) "RC6 E"
["RC8"]=>
string(3) "RC8"
["RC8R"]=>
string(4) "RC8R"
}
}
 */
function createCombinations($product_info, &$reference_id_map, $id_category)
{
//    global $key_attribute_map;

    $product_reference = $product_info[REFERENCE_KEY];
    $combinations = array();

    if($product_reference) {// front_side or back_side
        $reference = $product_reference;
        $product = createProduct($reference, $reference, $reference_id_map, $id_category);
        $product_id = $product->id;

        $i = 0;
        foreach($product_info['compounds'] as $compound) {
            if($compound) {
                $sideReference = $product_reference . $compound;
                $attribute = 'compuesto:select:' . $i . ',';
                $values = $compound . ':' . $i++ . ',';

                $combinations[] = array($product_id,// Product ID*;
                    $product_reference,
                    $attribute,//$key_attribute_map[$key] . ':select:' . $i, // Attribute (Name:Type:Position)*;
                    $values,
                    $product_reference,// Supplier reference;
                    $sideReference,// Reference;
                    '',// EAN13;
                    '', // UPC;
                    '0',          // Wholesale price;
                    '00',           // Impact on price;
                    '0',            // Ecotax;
                    '0',           // Quantity;
                    '0',            // Minimal quantity;
                    '0',            // Impact on weight;
                    '0',            // Default (0 = No, 1 = Yes);
                    '2014-01-01'// Combination available date;
                );
            }
        }
    }
    /*
    $reference_compounds_map = array();
    foreach ($product_info['references'] as $product_reference) {
        if($product_reference) {// front_side or back_side
            $reference = $product_reference;
            $product = createProduct($reference, $reference_id_map);
            if ($product !== null) {
                // Creates combinations csv
                $product_id = $product->id;
                $product = null;
                $reference_compounds_map[$reference] = createProductCombination($product_info, $product_id, $key_attribute_map, $reference);
            }
        }
    }
    var_dump($reference_compounds_map);
*/
    return $combinations;
}

/**
 * @param $reference_compounds_map
 * @param $out
 */
function createCsvFiles($combinations)
{
    $out = null;
    $file_count = 0;
    $i = 0;

    $csvDirectoryName = date('Ymd');
    if(!is_dir(getCsvFilesPath($csvDirectoryName))) {
        mkdir(getCsvFilesPath($csvDirectoryName));
    }
    $combinationLines = array();
    foreach ($combinations as $combination) {
        $combinationLine = implode(';', $combination) . ";\n";
        $combinationLines[] = str_replace(',;', ';', $combinationLine);
    }

    var_dump($combinationLines);

    foreach ($combinationLines as $combinationLine) {
        if ($i % LINES_PER_FILE == 0) {
            if ($out) {
                fclose($out);
            }
            $file_name = getCsvFilesPath($csvDirectoryName) . '/combinations-' . ++$file_count . '.csv';
            $out = fopen($file_name, 'w');
        }
        fputs($out, $combinationLine);
        $i++;

        if($i % 1000 == 0) {
            gc_collect_cycles();
        }
    }
    fclose($out);
}

/**
 * @param $csvDirectoryName
 * @return string
 */
function getCsvFilesPath($csvDirectoryName)
{
    return ADMIN_IMPORT_DIR . '/' . $csvDirectoryName;
}

function importProducts()
{
    $import = New AdminImportControllerCore();
    $file_names = array_diff(scandir(getCsvFilesPath(date('Ymd'))), array('.', '..'));

    foreach ($file_names as $file_name) {
        if(strpos($file_name, '.csv') !== false) {
            $file_name = substr($file_name, strrpos($file_name, '/'));

            $_POST = array(
                'csv' => date('Ymd') . '/' . $file_name,
                'convert' => '',
                'regenerate' => '',
                'entity' => '2',
                'iso_lang' => 'es',
                'separator' => ';',
                'multiple_value_separator' => ',',
                'skip' => '0',
                'type_value' => array(
                    0 => 'id_product',
                    1 => 'product_reference',
                    2 => 'group',
                    3 => 'attribute',
                    4 => 'supplier_reference',
                    5 => REFERENCE_KEY,
                    6 => 'ean13',
                    7 => 'upc',
                    8 => 'wholesale_price',
                    9 => 'price',
                    10 => 'ecotax',
                    11 => 'quantity',
                    12 => 'minimal_quantity',
                    13 => 'weight',
                    14 => 'default_on',
                    15 => 'available_date'
                ),
                'import' => 'Importing combinations'
            );
            $import->postProcess();
            gc_collect_cycles();

            echo 'Borrando: ' . getCsvFilesPath(date('Ymd')) . '/' . $file_name;
            if(!unlink(getCsvFilesPath(date('Ymd')) . '/' . $file_name)) {
                echo 'No se pudo borrar ' . getCsvFilesPath(date('Ymd')) . '/' . $file_name;
            }
        }
    }
}

function createCombinationsCSV()
{
    if (($handle = fopen(SCRIPT_IMPORT_DIR . "catalog.csv", "r")) !== FALSE) {
        $family = '';
        $compounds = array();

        $i = 20;
        $catalog = array();
        while (($data = fgetcsv($handle, 200, ",")) !== FALSE/* && $i-- != 0*/) {
            // $data[0] => Brand or Family
            // $data[1] => Compounds separated by ' / ' or Model
            // $data[2] =>
            // $data[3] => Front spindle reference
            // $data[4] =>
            // $data[5] => Back spindle reference
            // $data[6] =>

            if (!empty($data[0])) {
                $brandOrFamily = $data[0];
            }

            if (!empty($brandOrFamily) && is_numeric($brandOrFamily[0])) {
                $family = cleanString($brandOrFamily);
                $compounds = explode(' / ', $data[1]);
            } else {
                $brand = cleanString($brandOrFamily);
                $model = cleanString($data[1]);
                $front_side = cleanString($data[3]);
                $back_side = cleanString($data[5]);

                if($front_side) {
                    if(!array_key_exists($front_side, $catalog)) {
                        $catalog[$front_side] = array();
                        $catalog[$front_side][REFERENCE_KEY] = $front_side;
                        $catalog[$front_side]['attributes'][FAMILY_KEY] = $family;
                        $catalog[$front_side]['attributes'][BRAND_KEY] = $brand;
                        $catalog[$front_side]['attributes'][MODEL_KEY] = $model;
                    }

                    foreach($compounds as $compound) {
                        $catalog[$front_side]['compounds'][$compound] = $compound;
                    }
                }

                if($back_side) {
                    if(!array_key_exists($back_side, $catalog)) {
                        $catalog[$back_side] = array();
                        $catalog[$back_side][REFERENCE_KEY] = $back_side;
                        $catalog[$back_side]['attributes'][FAMILY_KEY] = $family;
                        $catalog[$back_side]['attributes'][BRAND_KEY] = $brand;
                        $catalog[$back_side]['attributes'][MODEL_KEY] = $model;
                    }
                    foreach($compounds as $compound) {
                        $catalog[$back_side]['compounds'][$compound] = $compound;
                    }
                }
                /*
                $product_info = array(
                    'attributes' => array(
                        FAMILY_KEY => $family,
                        BRAND_KEY => $brand,
                        MODEL_KEY => $model
                    ),
                    'references' => array(
                        'frontSide' => $front_side,
                        'backSide' => $back_side
                    ),
                    'compounds' => $compounds
                );

                $catalog[] = $product_info;
                */
            }
        }
        fclose($handle);

        importCatalog($catalog);
    }
}

switch($_GET['script']) {
    case 'create':
        createCombinationsCSV();
        break;
    case 'import':
        importProducts();
        break;
}