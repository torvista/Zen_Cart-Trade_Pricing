<?php // plugin Trade Pricing
declare(strict_types=1);
/** phpStorm
 * @var messageStack $messageStack
 * @var queryFactory $db
 * @var sniffer $sniffer
 */
global $debug_tpr;
$debug_tpr = false;

function tp_get_products_base_price($product_id)
{
    global $db;
    if ($GLOBALS['debug_tpr']) {
        echo "<em>fn:tp_get_products_base_price #$product_id</em><br>";
    }

    $products_price = zen_products_lookup($product_id, 'products_price'); //set default value, to be possibly modified
    
    if ($GLOBALS['debug_tpr']) {
        echo __LINE__ . ': $original product price =' . $products_price . '<br>';
    }
    $tp_modifier = tp_get_price_modifier($product_id);

    if ($tp_modifier === false) {
        return false;
    }

    if (zen_has_product_attributes($product_id)) {
        // do not select display only attributes and attributes_price_base_included is true
        $sql = "SELECT options_id, price_prefix, options_values_price,
                   attributes_display_only, attributes_price_base_included,
                   options_values_id,
            CONCAT(price_prefix, options_values_price) AS value
            FROM " . TABLE_PRODUCTS_ATTRIBUTES . "
            WHERE products_id = " . (int)$product_id . "
            AND attributes_display_only != 1
            AND attributes_price_base_included=1
            ORDER BY options_id, CAST(value AS SIGNED)";
        $results = $db->Execute($sql);
        if ($GLOBALS['debug_tpr']) {
            echo __LINE__;
            foreach ($results as $result) {
                mv_printVar($result);
            }
        }
        $the_options_id = 'x';
        $the_base_price = 0;

        // add attributes price to price

        foreach ($results as $result) {
            if ($the_options_id !== $result['options_id']) {
                $the_options_id = $result['options_id'];
                $factor = $result['price_prefix'] === '-' ? -1 : 1;
                $the_base_price += $factor * $result['options_values_price'];
                if ($GLOBALS['debug_tpr']) {
                    echo __LINE__ . ': $the_base_price (attributes) =' . $the_base_price . '<br>';
                }

            }
        }
        $products_price += $the_base_price;
        if ($GLOBALS['debug_tpr']) {
            echo __LINE__ . ' $products_price+cheapest att=' . $products_price . '<br>';
        }
    }

    if (substr($tp_modifier, -1) === '%') {
        if ($GLOBALS['debug_tpr']) {
            echo __LINE__ . ': $tp_modifier is % =' . $tp_modifier . '<br>';
        }
        $products_price = (float)$products_price - (((float)$tp_modifier / 100) * (float)$products_price);
    } else {
        if ($GLOBALS['debug_tpr']) {
            echo __LINE__ . ': $dp_modifier is absolute price=' . $tp_modifier . '<br>';
        }
        $products_price -= (float)$tp_modifier;
    }
    return $products_price;
}


function tp_get_price_modifier($product_id)
{
    if ($GLOBALS['debug_tpr']) {
        echo "<em>fn:tp_get_price_modifier for #$product_id</em><br>";
    }
    global $db;
    $tp_customer_level = $_SESSION['customers_tp'];
    if (!($tp_customer_level > 0)) return false;
    
    if ($GLOBALS['debug_tpr']) {
        echo __LINE__ . ':$tp_customer_level=' . $tp_customer_level . '<br>';
    }

        $tp_pricing = zen_products_lookup($product_id, 'products_tp');
        if ($tp_pricing === '0') {
            if ($GLOBALS['debug_tpr']) {
                echo __LINE__ . ': products_tp=0<br>';
            }
            $sql = "SELECT manufacturers_tp FROM " . TABLE_MANUFACTURERS . " WHERE manufacturers_id = " . (int)zen_products_lookup($product_id, 'manufacturers_id') . " LIMIT 1";
            $result = $db->Execute($sql);
            if ($result->fields['manufacturers_tp'] !== '0') {
                $tp_pricing = $result->fields['manufacturers_tp'];
                if ($GLOBALS['debug_tpr']) {
                    echo __LINE__ . ': using manufacturers_tp=' . $tp_pricing . '<br>';
                }
            } elseif ($GLOBALS['debug_tpr']) {
                echo __LINE__ . ': manufacturers_tp=0<br>';
            }
        }
        if ($tp_pricing === '0' || $tp_pricing === '' || $tp_pricing === null) {
            if ($GLOBALS['debug_tpr']) {
                echo __LINE__ . ':Abort TP pricing<br>';
            }
            return false;
        }

        if ($GLOBALS['debug_tpr']) {
            echo __LINE__ . ' $tp_pricing=' . $tp_pricing . '<br>';
        }

    $tp_pricing_array = explode("-", $tp_pricing);

    if ($tp_customer_level > count($tp_pricing_array)) { //customer has a pricing level that does not exist on the product...should never happen
        error_log("\n" . 'plugin Dual Pricing ERROR: customer has discount level "' . $tp_customer_level . '" but product id#' . $product_id . ': ' . zen_products_lookup($product_id,
                'products_model') . ' - "' . zen_get_products_model($product_id) . '" has only ' . count($tp_pricing_array) . ' price level(s) defined.');
        if ($GLOBALS['debug_tpr']) {
            echo __LINE__ . ':ERROR $tp_customer_level (' . $tp_customer_level . ') > product base defined price bands (' . count($tp_pricing_array) . ')<br>';
        }
        return false;
    }

    $tp_pricing_array = array_combine(range(1, count($tp_pricing_array)), array_values($tp_pricing_array)); //make array keys equate to customer level

    if ($GLOBALS['debug_tpr']) {
        mv_printVar($tp_pricing_array);
    }

    return $tp_pricing_array[$tp_customer_level];
}

/**
 * @param $product_id
 * @param array $selected_attributes
 *
 * @return array
 * $selected_attributes => Array
([3] => 9)
 */
function tp_get_final_prices($product_id, array $selected_attributes = []): array
{
    global $db;
    if ($GLOBALS['debug_tpr']) {
        echo "<em>fn:tp_get_final_prices for #$product_id</em><br>";
    }
    $price_tp = false;
    $price_atts_tp = false;
    $tp_modifier = tp_get_price_modifier($product_id);
    if ($tp_modifier === false) {
        return [$price_tp, $price_atts_tp];
    }

    $product_price = zen_products_lookup($product_id, 'products_price');
    /*if ($GLOBALS['debug_tpr']) {
        echo __LINE__ . ' #' . $product_id . ' $product_price=' . $product_price . '<br>';
    }*/
    $attributes_total = 0;
    if (count($selected_attributes) > 0) {
        foreach ($selected_attributes as $key_a => $value_a) {
            $sql = "SELECT options_values_price, price_prefix FROM " . TABLE_PRODUCTS_ATTRIBUTES . " WHERE products_id=" . (int)$product_id . " AND options_id=" . (int)$key_a
                . " AND options_values_id=" . (int)$value_a;
            $result = $db->Execute($sql, 1);
            if ($result->EOF || (int)$result->fields['options_values_price'] === 0) {
                break;
            }
            $factor = $result->fields['price_prefix'] === '-' ? -1 : 1;
            $attributes_total += $factor * $result->fields['options_values_price'];
        }
    }
    if (substr($tp_modifier, -1) === '%') {
        $price_tp = $product_price * (1 - (float)$tp_modifier / 100);
        $price_atts_tp = ($product_price + $attributes_total) * (1 - (float)$tp_modifier / 100);
        /*if ($GLOBALS['debug_tpr']) {
            echo __LINE__ . ' % $price_tp=' . $price_tp . ', $price_atts_tp=' . $price_atts_tp . '<br>';
        }*/
    } else {
        $price_tp = $product_price - (float)$tp_modifier;
        $price_atts_tp = ($product_price + $attributes_total) - (float)$tp_modifier;
        /*if ($GLOBALS['debug_tpr']) {
            echo __LINE__ . ' abs. disc.  $price_tp=' . $price_tp . ', $price_atts_tp=' . $price_atts_tp . '<br>';
        }*/
    }
    return [$price_tp, $price_atts_tp, $product_price + $attributes_total];
}

/**
 * @param $discounts
 *
 * @return bool
 */
function tp_check_discount($discounts): bool
{
    $tp_pricing_array = explode("-", $discounts);
    foreach ($tp_pricing_array as $value) {
        if (substr($value, -1) === '%') {
            if ((float)$value != rtrim($value, '%')) {//use a loose comparison
                return false;
            }
            if ((float)$value > 100) {
                return false;
            }
        } elseif ((float)$value != $value) {//use a loose comparison
            return false;
        }
    }
    return true;
}

//for debugging a variable
if (!function_exists('mv_printVar')) {
    /**
     * @param $a
     */
    function mv_printVar($a)
    {
        $backtrace = debug_backtrace()[0];
        $fh = fopen($backtrace['file'], 'rb');
        $line = 0;
        $code = '';
        while (++$line <= $backtrace['line']) {
            $code = fgets($fh);
        }
        fclose($fh);
        preg_match('/' . __FUNCTION__ . '\s*\((.*)\)\s*;/u', $code, $name);
        echo '<pre>';
        if (!empty($name[1])) {
            echo '<strong>' . trim($name[1]) . '</strong> ('.gettype($a)."):\n";
        }
        //var_export($a);
        print_r($a);
        echo '</pre><br>';
    }
}