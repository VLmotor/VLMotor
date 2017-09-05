<?php
$conexion=mysql_connect("localhost","ardiel","fuentes") or die("Problemas en la conexion");
mysql_select_db("tienda",$conexion) or die("Problemas en la selección de la base de datos");

//$sql = "SELECT ps_orders.id_order, ps_orders.id_shop, ps_orders.id_customer, ps_orders.date_add, ps_message.message FROM ps_orders, ps_message WHERE ps_orders.valid = 0";

$sql = "SELECT ps_orders.id_order, ps_orders.id_shop, ps_orders.id_customer, ps_orders.date_add FROM ps_orders WHERE ps_orders.valid = 0";

$result = mysql_query($sql,$conexion) or die("no hay xaxis");
$fp = fopen('00112WEBPC.WEB', 'w');

$order = null;
while($row=mysql_fetch_assoc($result)) {
	$order[] = $row;
}

if($order == null){
	echo "no hay nuevos pedidos";
}else{
	foreach($order as $ecabe):
		
		$pedido=str_pad($ecabe['id_order'],10);//// num_nota////
		
		$cliente=str_pad($ecabe['id_customer'],5);////cliente////
		
		//$centro=str_pad($ecabe['id_shop'],3);///centro/obra////
		$centro="   ";///centro/obra////
		
		
		
		$vendedor="3";////vendedor////
		
		$tipo="2";////tipo de documento////
		
		$fecha=date("d/m/Y", strtotime($ecabe['date_add'],10));////fecha pedido////   -----------------FORMATO-
		
		$hora=date("H:i:s", strtotime($ecabe['date_add'],10));////hora pedido////   -----------------FORMATO-
		
		$fecha=date("d/m/Y", strtotime($ecabe['date_add'],10));////fecha entrega////   -----------------FORMATO-
		
		$almacen="1";////almacen////
		
		$formapago="1";//forma de pago
		
		$desc="0";////porcentaje de descuento comercial descuento ////
		
		$desc2="0";////importe de descuento comercial descuento ////
		
		$desc3="0";////porcentaje de descuento forma pago ////
			
		$desc4="0";////importe de descuento forma pago ////
				
		//$observaciones=str_pad($ecabe['message'],90);////observaciones////
		$observaciones="aqui va el mensaje del pedido";////observaciones////
		
		$serie="20";////serie documento////		
						
		fwrite($fp,$pedido.';'.$cliente.';'.$centro.';'.$vendedor.';'.$tipo.';'.$fecha.';'.$hora.';'.$fecha.';'.$almacen.';'.$formapago.';'.$desc.';'.$desc2.';'.$desc3.';'.$desc4.';'.$observaciones.';'.$serie.PHP_EOL);
		
	endforeach;

fclose($fp);
echo "close fp";

$sql2 = "select ps_order_detail.id_order,ps_order_detail.product_id,ps_order_detail.product_price,ps_order_detail.product_quantity,ps_order_detail.ecotax
         from ps_orders inner join ps_order_detail on ps_orders.id_order=ps_order_detail.id_order where valid = 0";
         
$result2= mysql_query($sql2,$conexion) or die("no hay xaxis");

$fp = fopen('00112WEBPL.WEB', 'w');

while($row=mysql_fetch_assoc($result2)) {
	$orders[] = $row;
}
	
	$linea=1;
	$last_order=-1;
		
foreach($orders as $eline):

	$order = str_pad($eline['id_order'],10);//pedido original
	
	if ($order != $last_order) {
		$linea = 1;
		$last_order = $order;
	}
	
	$articulo = str_pad($eline['product_id'],17);//articulo
	
	
	
	
	$ecotax = ($eline['ecotax'] == null) ? 0 : $eline['ecotax'];
	$precio = str_pad(($eline['product_price'])-($ecotax),19);

	//$precio = str_pad(($eline['product_price'])-($eline['ecotax']),19);
	
	$bultos ="0";

	$unidades = str_pad($eline['product_quantity'],6);
	
	//$reciclaje = str_pad($eline['ecotax'],19);
	$reciclaje = str_pad($ecotax,19);

	$desto ="0";
	
	$desto2 ="0";
	
	$desto3 ="0";
	          
	$last_order = $order;
	
fwrite($fp,$order.';'.$linea.';'.$articulo.';'.$precio.';'.$bultos.';'.$unidades.';'.$reciclaje.';'.$desto.';'.$desto2.';'.$desto3.PHP_EOL);
$linea++;
endforeach;
fclose($fp);
$sql11=mysql_query("update ps_orders set valid=1");
}
?>