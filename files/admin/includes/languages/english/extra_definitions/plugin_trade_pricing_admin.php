<?php
declare(strict_types=1);
// plugin Trade Pricing admin texts
// english

define('TEXT_TP_TRADE_DISCOUNT', 'Trade Discount');
define('TEXT_TP_INVALID_DISCOUNT', 'Trade Discount not updated. Invalid format:"%s".');

//customers.php
define('TEXT_TP_CUSTOMER_EDIT_DISCOUNTS_LABEL', 'Trade Discount Level');
define('TEXT_TP_CUSTOMER_EDIT_DISCOUNTS_INFO', 'RETAIL=0 / TRADE=1 or more');

//manufacturers.php
define('TEXT_TP_MANUFACTURER_DISCOUNTS_INFO', '<p>eg: "10%-25%-30%" for a three-level default discount structure for this manufacturer. Customer is defined as level 1/2/3 etc. A <em>product</em> trade discount will override this global default discount.<br>Enter 0 to remove discounts.</p>');

//product/collect_info.php
define('TEXT_TP_PRODUCT_DISCOUNTS_LABEL', 'Trade Discounts');
define('TEXT_TP_PRODUCT_DISCOUNTS_INFO', '<p>eg: "10%-25%-30%" for a three-level discount structure for this product which overrides a manufacturer discount. Each value can be an absolute discount or a %. Customer is defined as level 1/2/3 etc.<br>Enter 0 to remove <em>product-specific</em> discounts/apply any manufacturer global discount.</p>');
define('TEXT_TP_PRODUCT_DISCOUNTS_UNDEFINED', 'undefined');
define('TEXT_TP_PRODUCT_MANUFACTURER_DISCOUNT_UNDEFINED', 'No global discount defined for "%s"');
define('TEXT_TP_PRODUCT_MANUFACTURER_DISCOUNT', '"%1$s" global discount = "%2$s"');
define('TEXT_TP_PRODUCT_MANUFACTURER_EDIT_TITLE', 'edit global discount for "%s"');
define('TEXT_TP_PRODUCT_DISCOUNT_MANUFACTURER_LABEL', 'apply this discount structure to ALL products from this manufacturer');
define('TEXT_TP_PRODUCT_DISCOUNT_MANUFACTURER_LABEL_POPUP', 'Are you SURE you want to apply THIS discount structure to ALL products from THIS manufacturer? There is NO undo possible!!');
