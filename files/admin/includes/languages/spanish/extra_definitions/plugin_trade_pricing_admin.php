<?php
declare(strict_types=1);
// plugin Trade Pricing admin texts
// spanish

define('TEXT_TP_TRADE_DISCOUNT', 'Desc. Prof.');
define('TEXT_TP_INVALID_DISCOUNT', 'Desc. Prof no cambiado. Formato inválido:"%s".');

//customers.php
define('TEXT_TP_CUSTOMER_EDIT_DISCOUNTS_LABEL', 'Nivel Descuento Profesional');
define('TEXT_TP_CUSTOMER_EDIT_DISCOUNTS_INFO', 'PARTICULARES=0 / PROFESIONALES=1 o más');

//manufacturers.php
define('TEXT_TP_MANUFACTURER_DISCOUNTS_INFO', '<p>eg: "10%-25%-30%" for a three-level default discount structure for this manufacturer. Customer is defined as level 1/2/3 etc. A <em>product</em> trade discount will override this global default discount.<br>Enter 0 to remove discounts.</p>');

//product/collect_info.php
define('TEXT_TP_PRODUCT_DISCOUNTS_LABEL', 'Descuentos Profesional');
define('TEXT_TP_PRODUCT_DISCOUNTS_INFO', '<p>p.ej: "10%-25%-30%" para tres nivels de descuento cuales sustituyen un descuento por fabricante. Puede ser una cantidad absoluta o %, separados por guiones. El cliente tiene su nivel de descuento 1/2/3 etc.<br>Introduzca 0 para borrar el descuento específico e apliqe el descuento por defecto del fabricante');
define('TEXT_TP_PRODUCT_DISCOUNTS_UNDEFINED', 'sin definir');
define('TEXT_TP_PRODUCT_MANUFACTURER_DISCOUNT_UNDEFINED', '"%s" no tiene un descuento global definido');
define('TEXT_TP_PRODUCT_MANUFACTURER_DISCOUNT', '"%1$s" descuento por defecto = "%2$s"');
define('TEXT_TP_PRODUCT_MANUFACTURER_EDIT_TITLE', 'editar descuento por defecto para "%s"');
define('TEXT_TP_PRODUCT_DISCOUNT_MANUFACTURER_LABEL', 'aplique este descuento a TODOS los products de este fabricante');
define('TEXT_TP_PRODUCT_DISCOUNT_MANUFACTURER_LABEL_POPUP', '¿Está seguro que quiere aplique este descuento a TODOS los products de este fabricante? ¡No hay marcha atrás!');
