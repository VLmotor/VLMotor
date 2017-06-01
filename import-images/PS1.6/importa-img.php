<?php
ini_set('memory_limit', '256M');

set_time_limit(0);
define('_PS_ADMIN_DIR_', getcwd());

require_once(_PS_ADMIN_DIR_ . '/../config/config.inc.php');

require_once(_PS_ADMIN_DIR_.'/../controllers/admin/AdminImportController.php');

//include_once './tabs/AdminImport.php'; 

require_once(_PS_ADMIN_DIR_ . '/functions.php');

/*Funciones*/
function writeLog($message, $value = NULL) {
	if($value !== NULL) {
		if(is_array($value)) {
			$message .= ' ' . print_r($value, true);
		} else {
			$message .= ' ' . $value;
		}
	}
	
	echo '<br />' . $message . '<br/>';
	file_put_contents('php://stderr', $message);
}

function leer_archivos_y_directorios($ruta) {
    $data_img = array();
    // comprobamos si lo que nos pasan es un directorio
    if (is_dir($ruta)) {
        // Abrimos el directorio y comprobamos que
        if ($aux = opendir($ruta)) {
            $num = 0 ;
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
						writeLog('Leyendo la imagen: ' . $archivo);
                        $data_img[$num++] =  $archivo;
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
// $data_imag es: $data_img[num] => nombre_archivo

function loadProductsPost() {
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

//Fin funciones

// START Configuration 
$pathToWriteFile = _PS_ADMIN_DIR_ . "/import/";
$csvname =  "PRODUCTSIMG-1.csv";
$file = $pathToWriteFile . $csvname;
$delimiter = ";";
$new_csv_file = $file;
// Ruta de las imágenes para la importación
$ruta_img = _PS_ADMIN_DIR_ . "/../import/img";
$array_img = array();
// Ruta de las imágenes que se añadirá al csv debe ser una url ejemplo http://dominio.com/imagenes/ ( es la url de la carpeta).
$url_img = "http://rsxracingsolutions.com/import/img/";
//variables de inicio.
$n = 0;
$prod = array();
$data = array();
//Variables para la base de datos

$localhost = "localhost";
$usuario = "rsx2";
$contraseña = "vlm0t0rsp0rt";
$base_de_datos ="ps_rsx";


// END Configuration 

//Conexión a la base de datos.
$conexion = mysql_connect($localhost,$usuario,$contraseña) or die("Problemas en la conexion");
mysql_select_db($base_de_datos, $conexion) or die("Problemas en la selección de la base de datos");

//Buscamos todos los productos con imágenes
$sqlx = mysql_query("SELECT *  from ps_image where 1 = 1", $conexion);
//Lo guardamos en un array
$total = mysql_num_rows($sqlx);
if($total == 0){
	writeLog('No hay imágenes');
} else {
	writeLog('Hay un total de ' . $total . ' imágenes en la tienda<br><br>');
	while ($row = mysql_fetch_array($sqlx)) {
		$data[] = $row;
	}
}

// Guardamos el directorio de nuevas imágenes en un array;
$array_nuevas_img = leer_archivos_y_directorios($ruta_img);
for ($i = 0; $i < count($array_nuevas_img); $i++) {   
	$img = $array_nuevas_img[$i];

	writeLog('Procesando la imagen: ' . $img);
	$id_image = explode(".", $img);
	$extension = $id_image[1];

	if($extension == "jpg" or $extension == "jpeg" or $extension == "JPG" or $extension == "JPEG"){
	   $id_image = $id_image[0];
	   $array_ids[$i] = $id_image;

		if($i == 0) {
			$n = 0;
			$array_images[$id_image][$n] = $img;
	    } else if($i > 0) {
	        if(!empty($array_images[$id_image])) {
	            $n = count($array_images[$id_image]);

	            $array_images[$id_image][$n] = $img;
	        } else {
				$n = 0;
				$array_images[$id_image][$n] = $img;
	        }
	    }
	}
}
if(empty($data)):
	foreach ($array_images as $codigo => $images_prod) {
		$img = "";  

		for ($i=0; $i < count($images_prod); $i++) { 
			$image_name = $images_prod[$i];

			if (strlen($img) > 0) {
				$img .= ",";
			}
			$img .= $url_img . $image_name;
		}

		$prod[$n] = array(
			$codigo,
			"$img"
		);
		$n++;
	}
else:
	//Creamos arrays de ids de producto para comparar
	for ($i = 0; $i < count($data); $i++):
		$id_image = $data[$i]["id_image"];
		$image = new Image($id_image);
		$image_url = _PS_BASE_URL_ . _THEME_PROD_DIR_ . $image->getExistingImgPath() . ".jpg";
		$array_img[$data[$i]["id_product"]] = $image_url;
		$array_prod_img[] = $data[$i]["id_product"];
	endfor;

	$resultado = array_diff($array_ids, $array_prod_img);
	foreach ($array_images as $codigo => $images_prod) {
		$img = "";  

		for ($i=0; $i <count($images_prod) ; $i++) { 
			$image_name =$images_prod[$i];
		
			if (strlen($img) > 0) {
			 $img .= ",";
			}
			$img .= $url_img.$image_name;
		}
		if (in_array($codigo, $resultado)) {
			$prod[$n] = array(
				$codigo,
				"$img"
			);
			$n++;
	    } 		 
	}   
endif;
// creamos archivo csv en admin/import
$output = fopen($new_csv_file, 'w');

writeLog('Se escribirá la siguiente información en el csv: ', $prod);
foreach ($prod as $product) {
    fputcsv($output, $product, $delimiter);
}

fclose($output) or die("Can't close php://output");
//chmod($new_csv_file, 0777);  //changed to add the zero

loadProductsPost();
$import = New AdminImportControllerCore();
$import->productImport();

writeLog('Añadido ' . count($prod) . ' imágenes a la base de datos');
?>