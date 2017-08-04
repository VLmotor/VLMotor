<?php
const REFERENCE_KEY = 'reference';
const BRAND_KEY = 'brand';

const MODEL_KEY = 'model';
const FAMILY_KEY = 'family';
const LINES_PER_FILE = 5000;
ini_set('memory_limit', '512M');
set_time_limit(24000);

$root = getcwd() . '/../';
if(empty($_REQUEST)) {
    $root = getcwd() . '/httpdocs/';
}

define('ROOT_DIR', $root);

define('_PS_ADMIN_DIR_',  ROOT_DIR . '/admin367nyjlfd/');
define('TARGET_DIR',  _PS_ADMIN_DIR_. '/import/');
define('SCRIPT_ROOT', ROOT_DIR  . '/import/');
define('CSV_FILE', SCRIPT_ROOT . 'combinations.csv');

require_once(ROOT_DIR . '/config/config.inc.php');
require_once(ROOT_DIR . '/controllers/admin/AdminImportController.php');

$key_attribute_map = array(
    REFERENCE_KEY => 'Referencia',
    BRAND_KEY => 'Marca',
    MODEL_KEY => 'Modelo',
    FAMILY_KEY => 'Familia'
);

function importCatalog($catalog) {
    if(is_array($catalog)) {
        $reference_compounds_map = array();
        $reference_id_map = findExistingReferences();
        foreach ($catalog as $product_info) {
            $reference_compounds_map = array_merge($reference_compounds_map, createCombinations($product_info, $reference_id_map));
        }
        $catalog = null;
//        createCsvFiles($reference_compounds_map);
        $reference_compounds_map = null;
        gc_collect_cycles();

//        importProducts($file_names);
    }
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
function createProduct($reference, &$reference_id_map)
{
    $product = new Product();
    if(!$product->existsRefInDatabase($reference)) {
        $product = populateProduct($reference, $product);

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

/**
 * @param $reference
 * @param $product
 */
function populateProduct($reference, &$product)
{
    $product->reference = $reference;
    $product->price = (float)0;
    $product->active = (int)1;
    $product->weight = (float)0;
    $product->minimal_quantity = (int)0;
    $product->id_category_default = 2;// TODO: which one?
    $product->name[1] = utf8_encode($reference);
    $product->link_rewrite[1] = Tools::link_rewrite($reference);
    if (!isset($product->date_add) || empty($product->date_add)) {
        $product->date_add = date('Y-m-d H:i:s');
    }
    $product->date_upd = date('Y-m-d H:i:s');
    $product->addToCategories(array(13));

    return $product;
}

function findProductIdByReference($reference) {
    $row = Db::getInstance()->getRow('
		SELECT `id_product`
		FROM `'._DB_PREFIX_.'product` p
		WHERE p.reference = "'.pSQL($reference).'"');

    return $row['id_product'];
}

function findAllProductReferences() {
    $rows = Db::getInstance()->executeS('
		SELECT `reference`, `id_product`
		FROM `'._DB_PREFIX_.'product` p', true, false);

    return $rows;
}

/**
 * @param $product
 * @param $product_info = array(
 *                            'attributes' => (
 *                                   'family' => $family,
 *                                   'brand' => $brand,
 *                                   'model' => $model
 *                              ),
 *                            'references' => array(
 *                                'frontSide' => $frontSide,
 *                                'backSide'  => $backSide
 *                            ),
 *  *                         'compounds' => $compounds
 *                        );
 */
function createCombinations($product_info, &$reference_id_map)
{
    global $key_attribute_map;

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

    return $reference_compounds_map;
}

/**
 * @param $reference_compounds_map
 * @param $out
 */
function createCsvFiles($reference_compounds_map)
{
    $out = null;
    $file_count = 0;
    $i = 0;

    $csvDirectoryName = date('Ymd');
    if(!is_dir(getCsvFilesPath($csvDirectoryName))) {
        mkdir(getCsvFilesPath($csvDirectoryName));
    }
    foreach ($reference_compounds_map as $combinationLine) {
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
    return TARGET_DIR . '/' . $csvDirectoryName;
}

/**
 * @param $product_info
 * @param $product_id
 * @param $key_attribute_map
 * @param $reference
 * @param $out
 */
function createProductCombination($product_info, $product_id, $key_attribute_map, $reference)
{
    $referenceCompoundMap = array();
    $combinations = array();
    // $referenceCompoundMap['reference] => array('compound1' => 'compound1', 'compound2' => 'compound2');
    $referenceCompoundMap = groupCompoundsByReferende($product_info, $reference, $referenceCompoundMap);
	return $referenceCompoundMap;
/*
    $combinationLines = array();
    foreach ($referenceCompoundMap as $groupReference => $groupCompounds) {
        foreach ($groupCompounds as $compound) {
            $i = 0;
            $sideReference = $groupReference . $compound;
            $attribute = 'compuesto:select:' . $i . ',';
            $values = $compound . ':' . $i++ . ',';
//        foreach ($product_info['attributes'] as $key => $value) {
//            $attribute .= $key_attribute_map[$key] . ':select:' . $i . ', ';
//            $values .= '"' . $value . '"' . ':' . $i++ . ', ';
//        }

            $combinations[$sideReference] = array($product_id,// Product ID*;
                $groupReference,
                $attribute,//$key_attribute_map[$key] . ':select:' . $i, // Attribute (Name:Type:Position)*;
                $values,
                $groupReference,// Supplier reference;
                $sideReference,// Reference;
                '',// EAN13;
                '', // UPC;
                '100',          // Wholesale price;
                '40',           // Impact on price;
                '0',            // Ecotax;
                '10',           // Quantity;
                '1',            // Minimal quantity;
                '0',            // Impact on weight;
                '0',            // Default (0 = No, 1 = Yes);
                '2014-01-01'// Combination available date;
            );
        }
    }

    foreach ($combinations as $sideReference => $combination) {
        $combinationLine = implode(';', $combination) . ";\n";
        $combinationLines[$sideReference] = str_replace(',;', ';', $combinationLine);
    }

    return $combinationLines;
	*/
}

/**
 * @param $product_info
 * @param $reference
 * @param $referenceCompoundMap
 */
function groupCompoundsByReferende($product_info, $reference, &$referenceCompoundMap)
{
    foreach ($product_info['compounds'] as $compound) {
        if (!array_key_exists($reference, $referenceCompoundMap)) {
            $referenceCompoundMap[$reference] = array();
        }
		if (!array_key_exists($compound, $referenceCompoundMap[$reference])) {
			$referenceCompoundMap[$reference][$compound] = $compound;
		}
    }

    return $referenceCompoundMap;
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

/**
 * @param $string
 * @return string
 */
function cleanString($string)
{
    return trim($string);
}

function createCombinationsCSV()
{
    if (($handle = fopen(SCRIPT_ROOT . "catalog.csv", "r")) !== FALSE) {
        $family = '';
        $compounds = array();

        $i = 20;
        $catalog = array();
        while (($data = fgetcsv($handle, 200, ",")) !== FALSE && $i-- != 0) {
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
