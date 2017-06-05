<?php
ini_set('memory_limit', '256M');

set_time_limit(0);

define('_PS_ADMIN_DIR_', getcwd());
require_once(_PS_ADMIN_DIR_ . '/../config/config.inc.php');
require_once(_PS_ADMIN_DIR_ . '/../controllers/admin/AdminImportController.php');
require_once(_PS_ADMIN_DIR_ . '/functions.php');
require_once(_PS_ADMIN_DIR_ . '/importa-img-configuracion.php');

/*Funciones*/

/*
* Escribe por pantalla y en stderr el mensaje proprocionado concatenando el valor si existe
*/
function writeLog($message, $value = NULL)
{
    if ($value !== NULL) {
        if (is_array($value)) {
            $message .= ' ' . print_r($value, true);
        } else {
            $message .= ' ' . $value;
        }
    }

    echo $message . '<br/>';
    file_put_contents('php://stderr', $message);
}

/**
 * Borra los ficheros con extensión jpg y JPG del directorio especificado
 *
 * @param $path
 */
function cleanDirectory($path)
{
    $path = realpath($path);
    writeLog('Borrando imágenes en: ', $path);
    $mask = "/*.jpg";
    array_map("unlink", glob($path . $mask));
    $mask = "/*.JPG";
    array_map("unlink", glob($path . $mask));
}

/**
 * Obtiene el listado de imágenes existentes en $ruta
 *
 * @param $ruta
 * @return array
 */
function retrieveFTPImages($ruta)
{
    $data_img = array();
    // comprobamos si lo que nos pasan es un directorio
    if (is_dir($ruta)) {
        // Abrimos el directorio y comprobamos que
        if ($aux = opendir($ruta)) {
            $num = 0;
            while (($archivo = readdir($aux)) !== false) {
                // Si quisieramos mostrar todo el contenido del directorio pondríamos lo siguiente:
                // echo '<br />' . $file . '<br />';
                // Pero como lo que queremos es mostrar todos los archivos excepto "." y ".."
                if ($archivo != "." && $archivo != "..") {
                    $ruta_completa = $ruta . '/' . $archivo;

                    // Comprobamos si la ruta más file es un directorio (es decir, que file es
                    // un directorio), y si lo es, decimos que es un directorio y volvemos a
                    // llamar a la función de manera recursiva.
                    if (is_dir($ruta_completa)) {
                        writeLog('<strong>Directorio:</strong> ' . $ruta_completa);
                        // TODO: Posible bug, en el caso de que la ruta contenga un directorio, qué se debe hacer
                        // con las imágenes que tenga ese directorio?, ahora mismo se están ignorando
                        leer_archivos_y_directorios($ruta_completa . "/");
                    } else {
//                        writeLog('Leyendo la imagen: ' . $archivo);
                        $data_img[$num++] = $archivo;
                    }
                }
            }

            closedir($aux);
            return $data_img;
            // Tiene que ser ruta y no ruta_completa por la recursividad
            // echo "<strong>Fin Directorio:</strong>" . $ruta . "<br /><br />";
        }
    } else {
        writeLog($ruta . 'No es ruta válida');
    }
}

function loadProductsPost()
{
    $_POST = array(
        'tab' => 'AdminImport',
        'skip' => '0',
        'csv' => "PRODUCTSIMG-1.csv",
        'convert' => '',
        'regenerate' => '',
        'entity' => '1',
        'iso_lang' => 'es',
        'separator' => ';',
        'multiple_value_separator' => ',',
        'forceIDs' => '1',
//				'truncate' => '1',
        'import' => 'Importar datos CSV',
        'type_value' =>
            array(
                0 => 'id',
                1 => 'image'
            )
    );
}

/**
 * Obtiene todas las imágenes existentes en PS
 *
 * @param $host
 * @param $user
 * @param $password
 * @param $database
 * @return array
 */
function retrieveExistingImages($host, $user, $password, $database)
{
    $images = array();

    //Conexión a la base de datos.
    $connection = mysql_connect($host, $user, $password) or die("Problemas en la conexion");
    mysql_select_db($database, $connection) or die("Problemas en la selección de la base de datos");

    //Buscamos todos los productos con imágenes
    $sqlx = mysql_query("SELECT id_image, id_product, position, cover from ps_image where 1 = 1", $connection);

    //Lo guardamos en un array
    $rowCount = mysql_num_rows($sqlx);
    if ($rowCount == 0) {
        writeLog('No hay imágenes');
    } else {
        writeLog('Hay un total de ' . $rowCount . ' imágenes en la tienda');
        while ($row = mysql_fetch_array($sqlx)) {
            $images[] = $row;
        }
    }

    mysql_close($connection);
    return $images;
}

/**
 * Agrupa por producto las imágenes en el FTP
 *
 * @param $ftpImages
 * @return array
 */
function calculateImages2Import($ftpImages)
{
    $imagesByProduct = [];

// Crea un listado de imágenes nuevas por producto
// $images => [id producto][image number]
    for ($i = 0; $i < count($ftpImages); $i++) {
        $ftpImage = $ftpImages[$i];

//        writeLog('Procesando la imagen: ' . $newImage);
        $newImageExploded = explode(".", $ftpImage);
        $extension = $newImageExploded[1];
        $newImageExploded = explode('-', $newImageExploded[0]);
        $newImageId = $newImageExploded[0];

        if ($extension == "jpg"
        || $extension == "jpeg"
        || $extension == "JPG"
        || $extension == "JPEG") {
//            if ($i == 0) {
//                $n = 0;
//                $newImagesList[$newImageId][$n] = $ftpImage;
//            } else if ($i > 0) {
            if (empty($imagesByProduct[$newImageId])) {
                $n = 0;
                $imagesByProduct[$newImageId][$n] = $ftpImage;
            } else {
                $n = count($imagesByProduct[$newImageId]);
                $imagesByProduct[$newImageId][$n] = $ftpImage;
            }
//            }
        }
    }
//    writeLog('newImagesList: ', $newImagesList);
    return $imagesByProduct;
}

/**
 * Borra todas las imágenes del producto con el id: productId
 *
 * @param $productId
 * @return Product
 */
function removeProductImages($productId)
{
    $product = new Product($productId);
    $product->deleteImages();
    return $product;
}

/**
 * Crea el fichero csv e invoca al backoffice para realizar la importación
 *
 * @param $new_csv_file
 * @param $productImages
 * @param $delimiter
 */
function importImages($new_csv_file, $productImages, $delimiter = ";")
{
    // creamos archivo csv en admin/import
    $output = fopen($new_csv_file, 'w');

    $addedImages = 0;
//    writeLog('Se escribirá la siguiente información en el csv: ', $productImages);
    $i = 0;
    foreach ($productImages as $product) {
        fputcsv($output, $product, $delimiter);
        $addedImages += (substr_count($productImages[$i++][1], ',') + 1);
    }
    fclose($output) or die("Can't close php://output");

    loadProductsPost();
    $import = New AdminImportControllerCore();
    $import->productImport();

    writeLog('Se han añadido ' . $addedImages . ' imágenes a la base de datos');
}

//Fin funciones

// START Configuration 
$pathToWriteFile = _PS_ADMIN_DIR_ . '/import/';
$csvname = "PRODUCTSIMG-1.csv";
$csvFileFullPath = $pathToWriteFile . $csvname;
$new_csv_file = $csvFileFullPath;
// Ruta de las imágenes para la importación
$newImagesDirectory = _PS_ADMIN_DIR_ . "/../import/img";
$array_img = array();
// Ruta de las imágenes que se añadirá al csv debe ser una url ejemplo http://dominio.com/imagenes/ ( es la url de la carpeta).
$url_img = 'http://' . $domain . '/import/img/';

//variables de inicio.
// END Configuration

// Obtiene la lista de imágenes existentes en PS
$existingImages = retrieveExistingImages($host, $user, $password, $database);
// Obtiene la lista de imágenes del ftp
$ftpImages = retrieveFTPImages($newImagesDirectory);
$imagesByProduct = calculateImages2Import($ftpImages);

$n = 0;
$productsImages = array();

if (empty($existingImages)) {
    // No hay imágenes en la base de datos
    foreach ($imagesByProduct as $productId => $productImages) {
        $imageUrl = "";

        for ($i = 0; $i < count($productImages); $i++) {
            $imageName = $productImages[$i];

            if (strlen($imageUrl) > 0) {
                $imageUrl .= ",";
            }
            $imageUrl .= $url_img . $imageName;
            writeLog('Imagen nueva: ', $imageUrl);
        }

        $productsImages[$n] = array($productId, "$imageUrl");
//        writeLog('ProducImages: ', $productImages);
        $n++;
    }
} else {
    //Creamos arrays de ids de producto para comparar
    $existingIds = array_column($existingImages, 'id_product');
    // Borra las imágenes de todos los productos
//    array_map('removeProductImages', $existingIds);

//    writeLog('existingIds: ', $existingIds);
    $imagesProductIds = array_keys($imagesByProduct);
    $unStoredImagesIds = array_diff($imagesProductIds, $existingIds);
    //Conexión a la base de datos.
    $connection = mysql_connect($host, $user, $password) or die("Problemas en la conexion");
    mysql_select_db($database, $connection) or die("Problemas en la selección de la base de datos");

    foreach ($imagesByProduct as $productId => $productImages) {
        if (!in_array($productId, $unStoredImagesIds)) {
            $images = array();
            $sqlx = mysql_query("SELECT id_image, position from ps_image where id_product = $productId", $connection);
            while($row = mysql_fetch_array($sqlx)) {
                $images[] = $row;
            }

//            writeLog('Images: ', $images);
            foreach($productImages as $imageName) {
                $imageName = explode('-', $imageName);
                $imageName = explode('.', $imageName[1]);
                $position = $imageName[0];
                if($position && is_numeric($position)) {
                    $position = (int) $position;
                    $imageId = null;
                    for($i = 0; $i < count($images); $i++) {
                        if($images[$i]['position'] == $position) {
                            $imageId = $images[$i]['id_image'];
                            break;
                        }
                    }

                    $image = new Image($imageId);
                    $image->delete();
                    writeLog('Se sobreescribirá la imagen ' . $productId . '-' . $position . ' con id: ' . $imageId);
                }
            }
        }
        $imageUrl = "";
        for ($i = 0; $i < count($productImages); $i++) {
            $imageName = $productImages[$i];

            if (strlen($imageUrl) > 0) {
                $imageUrl .= ",";
            }
            $imageUrl .= $url_img . $imageName;
        }
        $productsImages[$n] = array($productId, "$imageUrl");
        writeLog('Añadiendo: ', $productsImages[$n]);
        $n++;
    }
    mysql_close($connection);
}

importImages($new_csv_file, $productsImages);
cleanDirectory($newImagesDirectory);
unlink($new_csv_file);