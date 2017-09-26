<?php
include_once('./config.php');

const PRODUCTS_CSV_PATH = ADMIN_IMPORT_DIR . DIRECTORY_SEPARATOR . 'products.csv';
// Combinations attributes
//Product ID*
//Attribute (Name:Type:Position)*
//Value (Value:Position)*
//Supplier reference
//Reference
//EAN13
//UPC
//Wholesale price
//Impact on price
//Ecotax
//Quantity
//Minimal quantity
//Impact on weight
//Default (0 = No, 1 = Yes)
//Combination available date
//Image position
//Image URL
//Delete existing images (0 = No, 1 = Yes)
//ID / Name of shop
//Advanced Stock Managment
//Depends on stock
//Warehouse

function readNorayProducts() {
    $products = array();

    if (($handle = fopen(SCRIPT_IMPORT_DIR . '00166ARTIC.NOR', "r")) !== FALSE) {
        while (($product = fgetcsv($handle, 500, ";")) !== FALSE/* && $i-- != 0*/) {
            if(strpos($product[1], 'RC') !== FALSE) {
                $code = $product[0]; //codigo

                $reference = cleanString($product[1]);
                $products[] = array(
                    'code' => cleanString($code),
                    'product_reference' => substr($reference, 0, strpos($reference, 'RC')),
                    'compound' => substr($reference, strpos($reference, 'RC')),
                    'description' => cleanString($product[2]),
                    'pvp_a' => cleanString($product[9]),
                    'pvp_b' => cleanString($product[10]),
                    'pvp_c' => cleanString($product[11]),
                    'igic' => cleanString($product[12])
                );
    //            $referencia_original=$product[1];//ref_original
    //            $descripcion=$product[2]; //descripcion
    //            $descripcion_larga=$product[3]; //descripcion larga
    //            $publicar_web=$product[4]; //se publica o no
    //            $observaciones1=$product[5];
    //            $observaciones2=$product[6];
    //            $familia=$product[7]; //para categoria default
    //            $tipo_articulo=$product[8]; //se puede usar para categoria padre
    //            $pvp_a=$product[9];
    //            $pvp_b=$product[10];
    //            $pvp_c=$product[11];
    //            $igic=$product[12];
    //            $recargo=$product[13];
    //            $aiem=$product[14];
    //            $igic_incluido=$product[15];
    //            $reciclaje=$product[16];
    //            $reciclaje_incluido=$product[17];
    //            $unidad=$product[18];
    //            $cantidad_unidad=$product[19];
    //            $unidades_envase=$product[20];
    //            $descuento1=$product[21];
    //            $descuento2=$product[22];
    //            $descuento3=$product[23];
    //            $proveedor=$product[24];
            }
        }
    }
    fclose($handle);

    return $products;
}

function createProductsCsv($products) {
    $existing_references = findAllProductReferences();
    $category_id = findCategoryId();

    $out = fopen(PRODUCTS_CSV_PATH, 'w');
    foreach ($products as $productMap) {
        $product = createProduct($productMap['product_reference'], ''/*$productMap['description']*/, $existing_references, $category_id);
        $productLine = $product->id . ';';
        $productLine .= $productMap['product_reference'] . ';';
        $productLine .= 'compuesto:select:0;' . $productMap['compound'] . ':0;';
        $productLine .= $productMap['product_reference'] . ';';
        $productLine .= $productMap['product_reference'] . $productMap['compound'] . ';';
        $productLine .= $productMap['pvp_a'] . ';';
        $productLine .= ($productMap['compound'] == 'RC5+') ? '1;' : '0;';
        $productLine .= ';;;;;;;;'. "\n";

        fputs($out, str_replace(',;', ';', $productLine));
    }

    fclose($out);
}

function updateProductsCombinations()
{
    $import = New AdminImportControllerCore();

    $_POST = array(
        'csv' => 'products.csv',
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
            6 => 'price',
            7 => 'default_on',
            8 => 'upc',
            9 => 'wholesale_price',
            10 => 'ecotax',
            11 => 'quantity',
            12 => 'minimal_quantity',
            13 => 'weight',
            14 => 'ean13',
            15 => 'available_date'
        ),
        'import' => 'Importing combinations'
    );
    $import->postProcess();
    gc_collect_cycles();

    if(!unlink(PRODUCTS_CSV_PATH)) {
        echo 'No se pudo borrar ' . PRODUCTS_CSV_PATH;
    }
}

$products = readNorayProducts();
createProductsCsv($products);
updateProductsCombinations();