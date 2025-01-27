<?php
define("ROOT_PATH", "./opencart");			//Raíz de la carpeta de trabajo que contiene una copia de OpenCart
define("SOURCE_ROOT_PATH", "../opencart");	//Raíz de la carpeta real de OpenCart

//Nombre del archivo zip. Se agrega .ocmod.zip al crear el archivo si no termina así
$zipFileName = 'coupon_gift_products';

//Archivos con cambios detectados
$changedFiles = [
    "admin/controller/marketing/coupon.php",
    "admin/language/en-gb/marketing/coupon.php",
    "admin/model/catalog/product.php",
    "admin/model/marketing/coupon.php",
    "admin/view/template/marketing/coupon_form.twig",
    "catalog/controller/extension/total/coupon.php",
    "catalog/model/extension/total/coupon.php",
    "system/library/cart/cart.php"
];

//Archivos nuevos a subir (carpeta upload). Se agregan/eliminan automáticamente cuando se agregan/eliminan archivos
$upload = [
    "catalog/model/extension/total/test.php"
];

//install.sql
$sql = '';

define("NAME", "Gift Products");
define("CODE", "gift_products");
define("VERSION", "1.0");
define("AUTHOR", "Amaury Herrera Brito");
define("LINK", "");
define("ENCODING", "utf-8");

define("TAG_OPERATION_BEGIN", "<OCMOD>");
define("TAG_OPERATION_END", "</OCMOD>");

$commentsBegin = ['//', '/*', '<!--', '{#'];
$commentsEnd = ['*/', '-->', '#}'];

$exclude = ['/cache/', '/cache-/', '/system/', '/image/', '/.idea'];

$force_include_dirs = [
	'system\library\cart'
];

/*
<add [LTRIM | RTRIM | TRIM] | [APPEND="xxx"] | [PREPEND="xxx"]>

Ejemplo en PHP
//<OCMOD>
//<search trim="false">public function addCoupon($data) {</search>
//<add position="before">
public function aFunction($data) {
    //This is my code
}
//</add>
//</OCMOD>

Ejemplo en Twig
{# <OCMOD> #}
{# <search trim="false">public function addCoupon($data) {</search> #}
{# <add position="before"> #}
public function aFunction($data) {
    //This is my code
}
{# </add> #}
{# </OCMOD> #}
*/