<?php
$conexion=mysql_connect("localhost","prueba","Prueba123") or die("Problemas en la conexion");
mysql_select_db("desarrollo",$conexion) or die("Problemas en la selección de la base de datos");

$delete=mysql_query("delete from ps_stock_available");

if(($handle = fopen("/var/www/vhosts/desarrollo.vlmotorsport.com/httpdocs/import/00112EXIST.NOR", "r")) !== FALSE){
	while(($fileop=fgetcsv($handle,8192,";")) !== false) {
			$param1=$fileop[0]; //codigo articulo 17
			$param2=$fileop[1]; //almacen 3
			$param3=$fileop[2]; //publicar en web 1 NO USADO 
			$param4=$fileop[3]; //existencia 19
			
			
//			introducir aqui el codigo de almacen que queramos importar stock al B2B
			
			$almacenes = array( 1,2,3 );
			
			if (!in_array("$param2", $almacenes)) {
			  $param4 = 0;
			}
		
			if($param4<0){
			$param4 = 0;
			}
			

			///////PS_STOCK_AVAILABLE///////////////
			$sqlx=mysql_query("SELECT id_product as pro from ps_stock_available where id_product = $param1", $conexion);

			if(mysql_fetch_assoc($sqlx)){
			  $upda=mysql_query("UPDATE ps_stock_available set quantity = quantity + $param4 where id_product = $param1", $conexion);
			} 
			else {

			  $inscarga=mysql_query("INSERT INTO ps_stock_available (id_product, id_product_attribute, id_shop, id_shop_group, quantity, depends_on_stock, out_of_stock) values ($param1, 0, 1, 0, $param4, 0, 0)", $conexion);
			}
		}
  		fclose($handle);
	}

$sql=mysql_query("UPDATE ps_product p INNER JOIN ps_stock_available s ON (p.id_product = s.id_product) SET p.quantity = s.quantity",$conexion);


// afuentes 08/07/2014 desactiva productos cuya cantidad <=0
// ghoose - 24/5/17 - lo desactivo para que muestre articulos sin stock.
 
 //$sqql=mysql_query("update ps_product_shop set ps_product_shop.active = 1 where ps_product_shop.id_product IN(select ps_product.id_product from ps_product where quantity = 0)",$conexion);

 //$sqql=mysql_query("update ps_product set active = 1 where quantity >= 0",$conexion);

// afuentes 08/07/2014 activa y muestra productos cuyo precio sea 0
//$sqql=mysql_query("update ps_product_shop set 
//                    ps_product_shop.active = 1, 
//                    ps_product_shop.show_price=0, 
//                    ps_product_shop.available_for_order=0  
//                    where ps_product_shop.id_product IN(select ps_product.id_product 
//                                                        from ps_product where price = 0 AND quantity > 0)",$conexion);
//$sqql=mysql_query("update ps_product set 
//                    ps_product.active = 1, 
//                    ps_product.show_price=0, 
//                    ps_product.available_for_order=0 
//                    where price = 0 AND quantity > 0",$conexion);
                    
                    
// afuentes: desactiva los productos que no tengan stock
//$sqql22=mysql_query("update ps_product_shop set ps_product_shop.active = 0 
//where ps_product_shop.id_product not in(select ps_stock_available.id_product from ps_stock_available)",$conexion);
//
//$sqql23=mysql_query("update ps_product set ps_product.active = 0 
//where ps_product.id_product not in(select ps_stock_available.id_product from ps_stock_available)",$conexion);

?>