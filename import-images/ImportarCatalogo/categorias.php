<?php
ini_set('memory_limit', '128M');
$conexion=mysql_connect("localhost","ardiel","fuentes") or die("Problemas en la conexion");
mysql_select_db("tienda",$conexion) or die("Problemas en la selección de la base de datos");



if(($handle = fopen("/var/www/vhosts/shop.vlmotorsport.com/httpdocs/import/00112TIPAR.NOR", "r")) !== FALSE) {
	while(($fileop=fgetcsv($handle,8192,";")) !== false) {		
		$codigo=$fileop[0]; //codigo
		$tipo=$fileop[1];//FAMILIA
		$web=$fileop[2];//publicado en web S/N - ¿usar para discriminar familias a importar?
	
	
	//creamos un array con los codigos de tipo de articulo que queremos convertir en categoria en esta tienda
	$categorias = array(101,102,103,104,105);
	
//	$subcategorias = array(302,303,304);
	
		
	// Comprobar que la categoria exista
	$sql = mysql_query("SELECT id_category,id_parent FROM ps_category WHERE id_category = $codigo", $conexion);
		
		if(mysql_num_rows($sql) > 0) {
	    // categoria existe
	    echo '<br>categoria: ' . $codigo . ' existe en la base de datos';
	  	    
	} 
	else {
	    // categoria no existe
		 echo '<br>categoria: ' . $codigo . ' NO existe en la base de datos';
	
													
			if ($web == 'S' && (in_array($codigo, $categorias))) {		
			$position=1;
							
							
						$ins = mysql_query("INSERT INTO ps_category(id_category, id_parent, id_shop_default, level_depth, active, date_add, date_upd, position, is_root_category) 
						VALUES ($codigo, 2, 1, 3, 1, NOW(), NOW(), $position, 0)"); 
						
																			
					$sqlcat = mysql_query("INSERT INTO ps_category_group(id_category, id_group) 
																VALUES ($codigo, 1), ($codigo, 2), ($codigo, 3)");
																
					//duplicamos la entrada en esta tabla, para crear la categoria en ingles, auqnue se traducirá a manos desde el BE
					$link= str_replace(" ","-",strtolower($tipo));												
					$sqlcat = mysql_query("INSERT INTO ps_category_lang(id_category, id_shop, id_lang, name, link_rewrite, meta_title) 
																VALUES ($codigo, 1, 1, '$tipo', '$link', '$link'),($codigo, 1, 4, '$tipo', '$link', '$link')");
					
								  
				    $sqlcat = mysql_query("INSERT INTO ps_category_shop(id_category, id_shop, position)
				    VALUES ($codigo,1, $position)");									
		}
		++$position; 
	 
//	 if ($web == 'S' && (in_array($codigo, $subcategorias))) {
//	 	$position=1;
//	 			$ins = mysql_query("INSERT INTO ps_category(id_category, id_parent, id_shop_default, level_depth, active, date_add, date_upd, position, is_root_category) 
//	 				VALUES ($codigo, 410, 1, 4, 1, NOW(), NOW(), $position, 0)"); 
//	 				
//	 																	
//	 			$sqlcat = mysql_query("INSERT INTO ps_category_group(id_category, id_group) 
//	 														VALUES ($codigo, 1), ($codigo, 2), ($codigo, 3)");
//	 														
//	 			//duplicamos la entrada en esta tabla, para crear la categoria en ingles, auqnue se traducirá a manos desde el BE
//	 			$link= str_replace(" ","-",strtolower($tipo));												
//	 			$sqlcat = mysql_query("INSERT INTO ps_category_lang(id_category, id_shop, id_lang, name, link_rewrite, meta_title) 
//	 														VALUES ($codigo, 1, 1, '$tipo', '$link', '$link'),($codigo, 1, 4, '$tipo', '$link', '$link')");
//	 			
//	 						  
//	 			$sqlcat = mysql_query("INSERT INTO ps_category_shop(id_category, id_shop, position)
//	 			VALUES ($codigo,1, $position)");			
//	 }  
//	 ++$position; 
}

}	

fclose($handle);

}

//include_once(dirname(dirname(__FILE__)) . '/config/config.inc.php');
//include_once(dirname(dirname(__FILE__)) . '/init.php');

include_once('/var/www/vhosts/shop.vlmotorsport.com/httpdocs/config/config.inc.php');
include_once('/var/www/vhosts/shop.vlmotorsport.com/httpdocs/init.php');


Category::regenerateEntireNtree();
?>