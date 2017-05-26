<?php

set_time_limit(0);
# FUNCIONES FTP
# CONSTANTES
# Cambie estos datos por los de su Servidor FTP
/*
  ftp.vlmotorsport.com/prueba
  u: vlmotor
  c: VLm0t0rsp0rt14
 *  */
define("SERVER", "ftp.rsxracingsolutions.com"); //IP o Nombre del Servidor
define("PORT", 21); //Puerto
define("USER", "vlrsx"); //Nombre de Usuario
define("PASSWORD", "VLm0t0rsp0rt14"); //Contraseña de acceso
define("PASV", true); //Activa modo pasivo
define("FICHERO_ID", 'E:\\CCI\\script_TiendaOnline\\imageids.txt');
# FUNCIONES

function carga_lista_imagenes() {
    $fichero = file_get_contents(FICHERO_ID);
    $array_ids = explode(",", $fichero);
    $lista = array();
    $num = 0;
    for ($i = 0; $i < count($array_ids); $i++) {
        $rango = explode("-", $array_ids[$i]);

        if (count($rango) > 1) {
            $inicio = $rango[0];
            $id_rango = $inicio;
            $fin = $rango[1];

            while ($id_rango <= $fin) {
                $lista[$num] = $id_rango;
                $num++;
                $id_rango++;
            }
        } else {
            $lista[$num] = $array_ids[$i];
            $num++;
        }
    }
    return $lista;
}

function ConectarFTP() {
    //Permite conectarse al Servidor FTP
    $id_ftp = ftp_connect(SERVER, PORT); //Obtiene un manejador del Servidor FTP
    ftp_login($id_ftp, USER, PASSWORD); //Se loguea al Servidor FTP
    ftp_pasv($id_ftp, PASV); //Establece el modo de conexi�n
    return $id_ftp; //Devuelve el manejador a la funci�n
}

function validar_imagen($img,$lista_imagenes) {
    //se busca tu extensión
    $info = pathinfo($img);
    $extension = $info['extension'];
    //se le quita la extensión
    $img = explode("." . $extension, $img);

    //Se busca si tiene texto exlicativo
    $nombre_img = explode("-", $img[0]);

    //compruebo que el tamaño del string sea válido. 
    if (strlen($nombre_img[0]) > 10 || strlen($nombre_img[0]) < 1) {
        echo $nombre_img[0] . " no es válido<br> " . strlen($nombre_img[0]);
        return FALSE;
    }
    //compruebo que los caracteres sean solo numéricos 
    // $permitidos = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_"; 
    $permitidos = "0123456789";
    for ($i = 0; $i < strlen($nombre_img[0]); $i++) {
        if (strpos($permitidos, substr($nombre_img[0], $i, 1)) === false) {
            echo $nombre_img[0] . " no es numérico<br>";
            return FALSE;
        }
    }
    //Miramos si es está en la lista para este almacen
    if(!in_array($nombre_img[0], $lista_imagenes)){
        echo $nombre_img[0] . " no está en la lista<br>";
        return FALSE;
    }
    echo $nombre_img[0] . " es válido<br>";
    return TRUE;
}

$id_ftp = ConectarFTP(); //Obtiene un manejador y se conecta al Servidor FTP

$img_ruta_ftp = "httpdocs/import/img/"; //ruta donde se suben las imágenes
$img_ruta_local = 'E:\\CCI\\almacen7\\FOTOARTI.001\\'; //ruta donde se mira si hay imágenes nuevas para subir.
chmod('E:\\CCI\\almacen7\\FOTOARTI.001\\', 0755);
$ficheros_local = scandir($img_ruta_local);
$lista_imagenes = carga_lista_imagenes();


if ($gestor = opendir($img_ruta_local)) { //cargamos todos los datos de la  carpeta
    while (false !== ($entrada = readdir($gestor))) { //bucle de todos los archivos de la carpeta 
        //Validamos el archivo
        $imagen_valida = validar_imagen($entrada,$lista_imagenes);

        if ($imagen_valida == TRUE) {
           
            $ruta_img = $img_ruta_local . $entrada;
            $fecha = date("Y-m-d", filectime($ruta_img));  //fecha de creación/modificación

            $today = strtotime(date("Y-m-d"));
            $fecha_img = strtotime($fecha);


           //if ($today == $fecha_img) { //si la imagen tiene fecha de hoy se sube
                $remote_file_path = $img_ruta_ftp . $entrada;
                ftp_put($id_ftp, $remote_file_path, $img_ruta_local . $entrada, FTP_ASCII);
                echo $remote_file_path;
           //}
        }
    }


    closedir($gestor);
}
ftp_close($id_ftp);
