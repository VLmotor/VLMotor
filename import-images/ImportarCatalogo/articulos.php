<?php

$conexion=mysql_connect("localhost","prueba","Prueba123") or die("Problemas en la conexion");
mysql_select_db("desarrollo",$conexion) or die("Problemas en la selección de la base de datos");

$sql = mysql_query("DELETE FROM ps_product_shop") or die(mysql_error()); 
$sql = mysql_query("DELETE FROM ps_product_lang") or die(mysql_error()); 
$sql = mysql_query("DELETE FROM ps_product") or die(mysql_error()); 
$sql = mysql_query("DELETE FROM ps_category_product") or die(mysql_error());
 
if(($handle = fopen("/var/www/vhosts/desarrollo.vlmotorsport.com/httpdocs/import/00122ARTIC.NOR", "r")) !== FALSE) {
	// afuentes: 22/08/2014 - lee el contenido del fichero de artículos
	while(($fileop=fgetcsv($handle,8192,";")) !== false)
	{		
		$codigo=$fileop[0]; //codigo
		$referencia_original=$fileop[1];//ref_original
		$descripcion=$fileop[2]; //descripcion
		$descripcion_larga=$fileop[3]; //descripcion larga
		$publicar_web=$fileop[4]; //se publica o no
		$observaciones1=$fileop[5];
		$observaciones2=$fileop[6];
		$familia=$fileop[7]; //para categoria default
		$tipo_articulo=$fileop[8]; //se puede usar para categoria padre
		$pvp_a=$fileop[9];
		$pvp_b=$fileop[10];
		$pvp_c=$fileop[11];
		$igic=$fileop[12];		
		$recargo=$fileop[13];
		$aiem=$fileop[14];
		$igic_incluido=$fileop[15];
		$reciclaje=$fileop[16];
		$reciclaje_incluido=$fileop[17];
		$unidad=$fileop[18];
		$cantidad_unidad=$fileop[19];
		$unidades_envase=$fileop[20];
		$descuento1=$fileop[21];
		$descuento2=$fileop[22];
		$descuento3=$fileop[23];
		$proveedor=$fileop[24];
		
		//friendly url(SEO) => 240/45 R14
	  	$frien=str_replace(" ","-",strtolower($descripcion)); //240/45r14
	   	$friendlyurl= str_replace("/","-",$frien);//24045r14
	 	$friendly=str_replace(".","-",$friendlyurl);
	 		 	
		//$friendlyurl= str_replace("/","-",$frien);
		$metatitle= str_replace(" ","",substr($descripcion,0,10));
		$meta_description= str_replace(" ","",substr($descripcion,0,10));
		$meta_keywords= str_replace(" ","",strtolower(substr($descripcion,0,10)));
		$precio= str_replace(",","",$pvp_a);
				
		// Comprobar que el articulo exista
		$sql = mysql_query("SELECT id_product FROM ps_product WHERE id_product = $codigo", $conexion);
			
		if(mysql_num_rows($sql) > 0) {
		    // articulo existe
		    echo '<br>articulo : ' . $codigo . ' existe en la base de datos';
		  	    
		} 
		else {
		    // El articulo no existe
			 echo '<br>articulo : ' . $codigo . ' NO existe en la base de datos';
		
		//datos producto
	  $sql1 = mysql_query("INSERT INTO ps_product(id_product, id_supplier, id_manufacturer, id_category_default, id_tax_rules_group, price, reference, active, indexed, cache_default_attribute, date_add, date_upd) 
											VALUES($codigo, 1, 1, 13, 1, $precio, '$referencia_original', 1, 1, 0, NOW(), NOW())"); 
	  
		//datos product_lang 								
		$sql2 = mysql_query("INSERT INTO ps_product_lang(id_product, id_shop, id_lang, name) 
												VALUES ($codigo, 1, 1, '$descripcion')");
												
//		datos product_lang 								
//		$sql2 = mysql_query("INSERT INTO ps_product_lang(id_product, id_shop, id_lang, description, description_short, link_rewrite, meta_description, meta_keywords, meta_title, name) 
//												VALUES ($codigo, 1, 1, '$descripcion_larga', '$descripcion', '$friendly', '$meta_description', '$meta_keywords', '$metatitle', '$descripcion')");
		
												
		
			//datos product_lang 								
//			$sql2 = mysql_query("INSERT INTO ps_product_lang(id_product, id_shop, id_lang, description, description_short, link_rewrite, meta_description, meta_keywords, meta_title, name) 
//													VALUES ($codigo, 1, 1, '$descripcion_larga', '$descripcion', '$friendly', '$meta_description', '$meta_keywords', '$metatitle', '$descripcion'),($codigo, 1, 4, '$descripcion_larga', '$descripcion', '$friendly', '$meta_description', '$meta_keywords', '$metatitle', '$descripcion')");
													
												
	
	 	//datos product-shop
		$sql4 = mysql_query("INSERT INTO ps_product_shop(id_product, id_shop, id_category_default, id_tax_rules_group, price, active, indexed, date_add, date_upd) 
												VALUES ($codigo, 1, 13, 1, $precio, 1, 1, NOW(), NOW())"); 
												
		//datos category-product
//		$position=1;
//		$sql5 = mysql_query("INSERT INTO ps_category_product(id_category, id_product, position) 
//														VALUES ($tipo_articulo, $codigo, $position)");
	//datos category-product
	// $unidad es para declarar productos DESTACADOS
	if ($unidad==999) {
	
	$sql6 = mysql_query("INSERT INTO ps_category_product(id_category, id_product, position) 
											VALUES (2, $codigo, 0),(13, $codigo, $position)");

											
						echo '<br>unidades :'  . $codigo . $unidad ;
	}
	else {
//		datos category-product
				$position=1;
				$sql5 = mysql_query("INSERT INTO ps_category_product(id_category, id_product, position) 
																VALUES (13, $codigo, $position)");
		
	}$position=$position++;
	
	}
	
	
				
	}
	fclose($handle);
}	  
echo "fin";

?>