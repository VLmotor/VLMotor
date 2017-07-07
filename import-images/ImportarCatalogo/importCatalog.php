<?php
/**
 * User: afuentes
 * Date: 05/07/2017
 * Time: 18:42
 */
if (($handle = fopen("catalog.csv", "r")) !== FALSE) {
    $id = null;

    $products = array();
    // TODO: Search for product attributes

    // TODO: Search for combinations

    while (($data = fgetcsv($handle, 500, ",")) !== FALSE) {
//        $product = new Product();
//        $product->date_add = date('Y-m-d H:i:s');

        if(!empty($data[0])) {
            $brandOrId = $data[0];
        }
        if(!empty($brandOrId) && is_numeric($brandOrId[0])) {
            $compounds = array();

            $id = $brandOrId;
            $compoundsString = $data[1];
            $compounds = explode(' / ', $compoundsString);
        } else {
            $brand = $brandOrId;
            $model = $data[1];

            echo "Marca: " . $brand;
            echo " Modelo: " . $model . "\n";
            foreach($compounds as $compound) {
                echo "\t ";

                // Creates product combination for front spindle
                if(!empty($data[3])) {
                    $frontSpindle = $data[3] . $compound;

                    echo " Eje delantero: ". $frontSpindle;
                }

                // Creates product combination for back spindle
                if(!empty($data[5])) {
                    $backSpindle = $data[5] . $compound;

                    echo " Eje trasero: ". $backSpindle;
                }
                echo "\n";
//            $product->reference = $reference;
//            $product->name = $reference;
//
//            $products[] = $product;

                // Set combinations values

                // Set product combination

            }
        }
    }

//    DB::getInstance()->insert('p', );
    fclose($handle);
}