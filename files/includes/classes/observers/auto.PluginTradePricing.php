<?php
declare(strict_types=1);

/* Lots of trial end errors here.,,
 * There is a debugging switch in functions_plugin_trade_pricing
 * How it works.
 * NOTIFY_FOOTER_END checks customer id and sets the customer session value to enable the trade discount
 * NOTIFY_ZEN_GET_PRODUCTS_DISPLAY_PRICE_NORMAL', 'NOTIFY_ZEN_GET_PRODUCTS_DISPLAY_PRICE_SALE' use same functionality to display price on product listing and info page with a strikeout of the normal price
 * NOTIFIER_CART_GET_PRODUCTS_END: modifies class shopping cart->function get_products array with trade price and trade final_price.
 * NOTIFIER_CART_SHOW_TOTAL_END: after shopping->calculate, to change cart total
 * The cart object in the SESSION is then read by class order->function cart into the order class.  Copies only price, but recalculates final_price, so final_price needs to be overridden again by
 * NOTIFY_ORDER_CART_ADD_PRODUCT_LIST
*/

/**
 * Class zcObserverPluginTradePricing
 */
class zcObserverPluginTradePricing extends base {
    public $tp_cart_total = false;
    public $original_price = 0;
    public function __construct() {

        if (defined('PLUGIN_TRADE_PRICING') && PLUGIN_TRADE_PRICING==='true') {//admin configuration gID=6

            $this->attach($this, [
                // check and set SESSION['customers_tp'] on each page load
                    'NOTIFY_FOOTER_END',
////product info and listing page               
                // (using common function update) From function zen_get_products_display_price, to handle "normal" price display, when no special_price
                    'NOTIFY_ZEN_GET_PRODUCTS_DISPLAY_PRICE_NORMAL',
                // (using common function update) From function zen_get_products_display_price, to handle sale price display e.g. "75% discount"
                    'NOTIFY_ZEN_GET_PRODUCTS_DISPLAY_PRICE_SALE',
                //custom notifier added to plugin Dynamic Price Updater (\includes\classes\ajax\zcDPU_Ajax.php at the close of protected function prepareOutput())
                    'NOTIFY_DYNAMIC_PRICE_UPDATER_PREPARE_OUTPUT_END', //Dynamic Price Updater: replacement of text shown BEFORE span normalprice (strikeout price)
                // module attributes: prior to creation of html for attributes selection/display, to modify prices
                    'NOTIFY_ATTRIBUTES_MODULE_ORIGINAL_PRICE',
////shopping cart
// class shopping cart: function get_products returns individual product details..can modify the products_price and options_price at source and make a running total
                    'NOTIFIER_CART_GET_PRODUCTS_END',
                // class shopping cart: function show_total. Override whatever was calculated by core code: Show the SESSION total                
                    'NOTIFIER_CART_SHOW_TOTAL_END',

                // shopping_cart/header_php.php. To add an extra column RRP to the cart table
                    'NOTIFY_HEADER_SHOPPING_CART_IN_PRODUCTS_LOOP',
                //class orders->function cart. Override final_price. Add to original_price_tp into the array for display in the cart and for the order confirmation email.
                    'NOTIFY_ORDER_CART_ADD_PRODUCT_LIST',

////order confirmation

                //add RRP price at the time, into the order_products table
                    'NOTIFY_ORDER_DURING_CREATE_ADDED_PRODUCT_LINE_ITEM',

// custom notifier added to class order:create_add_products. Pre creation of product list for email
                 'NOTIFY_ORDER_PROCESSING_PRODUCTS_ORDERED_EMAIL',

                //MC123 email handling
                 //'NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_INIT',   // Start of product parse loop
               //  'NOTIFY_ORDER_PROCESSING_ONE_TIME_CHARGES_BEGIN', // Prior to construction of email
              //  'NOTIFY_ORDER_AFTER_ORDER_CREATE_ADD_PRODUCTS',   // After completion of loop

            ]);
        }
    }

    // check and set SESSION['customers_tp'] on each page load
    protected function notify_footer_end(&$class, $eventID, $p1): void {
        //$zco_notifier->notify('NOTIFY_FOOTER_END', $current_page);
        global $db;
        if ($GLOBALS['debug_tpr']) {
            echo 'observer: notify_footer_end<br>';
        }

        if (empty($_SESSION['customer_id'])) {
            $_SESSION['customers_tp'] = 0;
        } else {
            $check_customer_query = "SELECT customers_tp
                           FROM " . TABLE_CUSTOMERS . "
                           WHERE customers_id = :customerId";

            $check_customer_query = $db->bindVars($check_customer_query, ':customerId', $_SESSION['customer_id'], 'integer');
            $check_customer = $db->Execute($check_customer_query);
            if ($check_customer->RecordCount()) {
                $_SESSION['customers_tp'] = (int) $check_customer->fields['customers_tp'];
            }
        }
        if ($GLOBALS['debug_tpr']) {
            echo '$_SESSION[\'customers_tp\']=' . $_SESSION['customers_tp'] . '<br>';
        }
    }

    public function update(&$class, $eventID, $p1, &$p2, &$p3, &$p4, &$p5) {
        switch ($eventID) {
            // -----
            // Triggered during the orders-listing sidebar generation, after the upper button-list has been created.
            //
            // $p1 ... Contains the current $oInfo object, which contains the orders-id.
            // $p2 ... A reference to the current $contents array; the NEXT-TO-LAST element has been updated
            //         with the built-in button list.
            //
            case 'NOTIFY_ZEN_GET_PRODUCTS_DISPLAY_PRICE_NORMAL':
                /*        $zco_notifier->notify(
            'NOTIFY_ZEN_GET_PRODUCTS_DISPLAY_PRICE_NORMAL',
            [
                'products_id' => $product_id,
                'display_sale_price' => $display_sale_price,
                'display_special_price' => $display_special_price,
                'display_normal_price' => $display_normal_price,
                'products_tax_class_id' => $product_check->fields['products_tax_class_id'],
                'product_is_free' => $product_check->fields['product_is_free']
            ],
            $pricing_handled,
            $show_normal_price,
            $show_special_price,
            $show_sale_price
        );
                 * */
            case 'NOTIFY_ZEN_GET_PRODUCTS_DISPLAY_PRICE_SPECIAL':
                /*
                 * $zco_notifier->notify(
            'NOTIFY_ZEN_GET_PRODUCTS_DISPLAY_PRICE_SPECIAL',
            [
                'products_id' => $product_id,
                'display_sale_price' => $display_sale_price,
                'display_special_price' => $display_special_price,
                'display_normal_price' => $display_normal_price,
                'products_tax_class_id' => $product_check->fields['products_tax_class_id'],
                'product_is_free' => $product_check->fields['product_is_free']
            ],
            $pricing_handled,
            $show_normal_price,
            $show_special_price,
            $show_sale_price
        );*/
                global $currencies, $flag_show_product_info_starting_at;
            if ($GLOBALS['debug_tpr']) {
                echo 'observers (2): notify_zen_get_products_display_price_normal/special<br>';
            }
                //isset needed here
                if (isset($_SESSION['customers_tp']) && $_SESSION['customers_tp'] > 0) {
                    $product_price_tp = tp_get_products_base_price($p1['products_id']);
                    if ($GLOBALS['debug_tpr']) {
                        echo __LINE__ . ': $product_price_trade=' . $product_price_tp . '<br>';
                    }
                    if ($product_price_tp!==false) {
                        $starting_from_text = zen_has_product_attributes_values($p1['products_id'] && $flag_show_product_info_starting_at==='1') ? ' ' . TEXT_TP_BASE_PRICE . ' ':'';
                        $products_base_price = zen_get_products_base_price($p1['products_id']);
                        
                        if (DISPLAY_PRICE_WITH_TAX==='true') {
                            $show_normal_price = TEXT_TP_RETAIL_PRICE_INCL . ' ' .
                                    $starting_from_text . '<span class="normalprice">'; // TEXT_BASE_PRICE "Starting from:" is added by the template
                            //copied from $show_normal_price .= $currencies->display_price($display_normal_price, zen_get_tax_rate($product_check->fields['products_tax_class_id']));
                            $show_normal_price .= $currencies->display_price($products_base_price, zen_get_tax_rate($p1['products_tax_class_id']));
                        } else {
                            $show_normal_price = TEXT_TP_RETAIL_PRICE_EXCL . ' ' .
                                    $starting_from_text . '<span class="normalprice">'; // TEXT_BASE_PRICE "Starting from:" is added by the template
                            $show_normal_price .= $currencies->display_price($products_base_price, 1);
                        }
                        $show_normal_price .= '</span>';

                        $show_special_price = '<br>';
                        $show_special_price .= TEXT_TP_TRADE_PRICE . $starting_from_text . ' <span class="productSpecialPrice">';
                        $show_special_price .= $currencies->display_price($product_price_tp, zen_get_tax_rate($p1['products_tax_class_id']));
                        $show_special_price .= '</span>';

                        if (SHOW_SALE_DISCOUNT==='1') {// 1 = shows percentage, 2 = shows discount amount
                            if ($p1['display_normal_price']!==0 && $product_price_tp != 0) {//loose comparison as double
                                $show_discount_amount = number_format(100 - (($product_price_tp / $p1['display_normal_price']) * 100), (int) SHOW_SALE_DISCOUNT_DECIMALS);
                            } else {
                                $show_discount_amount = '';//was empty
                            }
                            //hide discount if there is none
                            if ($show_discount_amount !== '') {
                                $show_sale_discount = '<span class="productPriceDiscount">';
                                $show_sale_discount .= '<br>';
                                //$show_sale_discount .= PRODUCT_PRICE_DISCOUNT_PREFIX; // this is "Save"...but trade is for resale so not appropriate
                                $show_sale_discount .= $show_discount_amount;
                                $show_sale_discount .= PRODUCT_PRICE_DISCOUNT_PERCENTAGE;
                                $show_sale_discount .= '</span>';
                            }
                        } else { //do not show the calculated discount
                            $show_sale_discount = '<span class="productPriceDiscount">';
                            $show_sale_discount .= '<br>';
                            //$show_sale_discount .= PRODUCT_PRICE_DISCOUNT_PREFIX;
                            //$show_sale_discount .= $currencies->display_price(($display_normal_price - $display_sale_price), zen_get_tax_rate($product_check->fields['products_tax_class_id']));
                            $show_sale_discount .= $currencies->display_price(($p1['display_normal_price'] - $product_price_tp), zen_get_tax_rate($p1['products_tax_class_id']));
                            $show_sale_discount .= PRODUCT_PRICE_DISCOUNT_AMOUNT;
                            $show_sale_discount .= '</span>';
                        }
                        $p2 = true; // $pricing_handled
                        $p3 = $show_normal_price;   // $show_normal_price
                        $p4 = $show_special_price;  // $show_special_price
                        $p5 = $show_sale_discount;  // $show_sale_price
                    }
                }
        }
    }

    //custom notifier added to plugin Dynamic Price Updater (\includes\classes\ajax\zcDPU_Ajax.php at the close of protected function prepareOutput())
    protected function notify_dynamic_price_updater_prepare_output_end(&$class, $eventID): void {

        //$this->notify('NOTIFY_DYNAMIC_PRICE_UPDATER_PREPARE_OUTPUT_END');
        global $currencies;
        if ($GLOBALS['debug_tpr']) {
            echo 'observer: tp_notify_dynamic_price_updater_prepare_output_end<br>';
        }
        //needs isset
     if (!isset($_SESSION['customers_tp']) || !($_SESSION['customers_tp'] > 0)) return;

        $pid = (int) $_POST['products_id'];

        //$class->responseText['preDiscPriceTotal']; old/strikeout price

        $class->responseText['preDiscPriceTotalText'] = DISPLAY_PRICE_WITH_TAX==='true' ? TEXT_TP_RETAIL_PRICE_INCL . ' ':TEXT_TP_RETAIL_PRICE_EXCL . ' ';
        $tax_class_id = zen_products_lookup($pid, 'products_tax_class_id');
        if (empty($_POST['attributes'])) {
            [$price_tp, $price_atts_tp, $original_price] = tp_get_final_prices((int) $_POST['products_id']);
        } else {
            $temp = array_filter(explode('|', $_POST['attributes'])); // e.g. radio [0] => id[1]~29, dropdown $_POST['attributes']
            /*  
          if ($this->DPUdebug) {
                      $tmp = __LINE__ . ': $temp=' . print_r($temp, true);
                      $this->logDPU($tmp);
                  }
          */
            foreach ($temp as $item) {
                $tempArray = explode('~', $item);
                /*
                            if ($this->DPUdebug) {
                                $tmp = __LINE__ . ': $tempArray=' . print_r($tempArray, true);
                                $this->logDPU($tmp);
                            }
                */
                if ($tempArray!==false && is_array($tempArray)) {
                    $temp1 = str_replace('id[', '', $tempArray[0]); //remove "[id"
                    $temp2 = str_replace(']', '', $temp1); //remove "]", leaving id_number (and prefix txt_ for text/file)
                    $attributes[$temp2] = $tempArray[1]; //index may be integer
                }
            }
            /*
                    if ($this->DPUdebug) {
                        $tmp = __LINE__ . ': $attributes=' . print_r($attributes, true);
                        $this->logDPU($tmp);
                    }
                            */
            [$price_tp, $price_atts_tp, $original_price] = tp_get_final_prices((int) $_POST['products_id'], $attributes);
        }
        $total_price_tp = (int) $_POST['cart_quantity'] * $price_atts_tp;
        $class->responseText['priceTotal'] = (DISPLAY_PRICE_WITH_TAX==='true' ? $currencies->display_price($total_price_tp, zen_get_tax_rate($tax_class_id)):$currencies->display_price($total_price_tp, 0));

        //[$price, $final_price] = tp_get_final_prices($product_id, $selected_attributes): array

        //$class->responseText['priceTotal']; final price
        //$class->responseText['quantity']; product chosen quantity
        //$class->responseText['stock_quantity']; stock info
        //$class->responseText['weight']; total weight
        //$class->preDiscPrefix = DISPLAY_PRICE_WITH_TAX==='true' ? TEXT_TP_RETAIL_PRICE_INCL . ' ' : TEXT_TP_RETAIL_PRICE_EXCL . ' ';
    }

    /**
     * @param        $class
     * @param        $eventID
     * @param array $p1
     * @param array $p2
     * @param string $p3
     * @param string $p4
     *
     * @return void
     */
    protected function notify_attributes_module_original_price(&$class, $eventID, array $p1, array &$p2, string &$p3, string &$p4): void {
        /*$zco_notifier->notify('NOTIFY_ATTRIBUTES_MODULE_ORIGINAL_PRICE', $products_options->fields, $products_options_array, $products_options_display_price, $data_properties);
                $p1 (array):
        Array
        (
            [products_options_values_id] => 8
            [products_options_values_name] => PS/2
            [products_attributes_id] => 10
            [products_id] => 26
            [options_id] => 3
            [options_values_id] => 8
            [options_values_price] => 0.0000
            [options_values_price_w] => 0
            [price_prefix] => +
            [products_options_sort_order] => 20
            [product_attribute_is_free] => 0
            [products_attributes_weight] => 0
            [products_attributes_weight_prefix] =>
            [attributes_display_only] => 0
            [attributes_default] => 0
            [attributes_discounted] => 1
            [attributes_image] =>
            [attributes_price_base_included] => 1
            [attributes_price_onetime] => 0.0000
            [attributes_price_factor] => 0.0000
            [attributes_price_factor_offset] => 0.0000
            [attributes_price_factor_onetime] => 0.0000
            [attributes_price_factor_onetime_offset] => 0.0000
            [attributes_qty_prices] =>
            [attributes_qty_prices_onetime] =>
            [attributes_price_words] => 0.0000
            [attributes_price_words_free] => 0
            [attributes_price_letters] => 0.0000
            [attributes_price_letters_free] => 0
            [attributes_required] => 0
        )
        $p2 (array):
        Array
        (
            [0] => Array
                (
                    [id] => 9
                    [text] => USB ( +€128.26 )
                )

            [1] => Array
                (
                    [id] => 8
                    [text] => PS/2
                )
        )
         $p3 (string):
         ( +€121.00 )
        $p4 (string):
         data-key="attrib-3"
                */
        global $currencies, $db;
        if ($GLOBALS['debug_tpr']) {
            echo 'observer: notify_attributes_module_original_price<br>';
            /*mv_printVar($p1);
            mv_printVar($p2);
            mv_printVar($p3);
            mv_printVar($p4);*/

        }
        if (isset($_SESSION['customers_tp']) && $_SESSION['customers_tp'] > 0) {
            //get base products modified price
            //find products_id from attribute_id
            $sql = "SELECT products_id FROM " . TABLE_PRODUCTS_ATTRIBUTES . " WHERE products_attributes_id=" . (int) $p1['products_attributes_id'] . " LIMIT 1";
            $result = $db->Execute($sql);
            $products_id = $result->fields['products_id'];
            $products_price = (float) zen_products_lookup($products_id, 'products_price');
            $tp_modifier = tp_get_price_modifier($products_id);
            if ($tp_modifier===false) {
                return;
            }
            $attribute_price = (float) $p1['options_values_price'];
            if (substr($tp_modifier, -1)==='%') {
                $products_tp = $products_price * (1 - (float) $tp_modifier / 100);
                $attribute_price_tp = $attribute_price * (1 - (float) $tp_modifier / 100);
            } else {
                //default, take absolute discount from base price
                $products_tp = $products_price - (float) $tp_modifier;
                if ($products_tp < 0) { //maybe base price is zero
                    if ((float) $p1['options_values_price'] < (float) $tp_modifier) {
                        $products_tp = 0;
                        $attribute_price_tp = 0;
                    } else { //take absolute discount from the attribute only
                        $products_tp = $products_price; //reset base price which may be non-zero but still less than discount
                        $attribute_price_tp = $attribute_price - (float) $tp_modifier;
                    }
                } else {
                    $attribute_price_tp = $attribute_price;//maintain as original: absolute discount is taken from main price
                }
            }
            if ($GLOBALS['debug_tpr']) {
                echo __LINE__ . ': product #' . $products_id . ' base price ' . $products_price . '=>' . $products_tp . ', option price ' . $attribute_price . '=>' . $attribute_price_tp
                        . '<br>';
            }

            $product_attribute_price_tp = $products_tp + $attribute_price_tp;
//todo clarify about the plus sign
            $p3_unmodified = $p3;
            $p3 = ' ( ' . $currencies->display_price($product_attribute_price_tp, zen_get_tax_rate(zen_products_lookup($p1['products_id'], 'products_tax_class_id'))) . ' )';

            if ($GLOBALS['debug_tpr']) {
                echo __LINE__ . ': att ' . $p1['options_values_id'] . ', $new_attributes_price change ' . $p3_unmodified . ' => ' . $p3 . '<br>';
            }
            /*$price_display = $currencies->display_price($new_attributes_price, zen_get_tax_rate(zen_products_lookup($p1['products_id'], 'products_tax_class_id')));
            $price_display_new = $currencies->display_price($new_attributes_price_dp, zen_get_tax_rate(zen_products_lookup($p1['products_id'], 'products_tax_class_id')));
            $p3 = str_replace($price_display, $price_display_new, $p3);*/

        }
    }

    /**
     * @param       $class
     * @param       $eventID
     * @param       $p1
     * @param array $p2
     */
    protected function notifier_cart_get_products_end(&$class, $eventID, $p1, array &$p2): void {
//  $this->notify('NOTIFIER_CART_GET_PRODUCTS_END', null, $products_array);
        //called from class shopping_cart
        /* $p2 (array):
        Array
        (
            [0] => Array
                (
                    [id] => 25
                    [category] => 8
                    [name] => Microsoft Internet Keyboard PS/2
                    [model] => MSINTKB
                    [image] => microsoft/intkeyboardps2.gif
                    [price] => 100.0000
                    [quantity] => 1
                    [weight] => 8
                    [final_price] => 100
                    [onetime_charges] => 0
                    [tax_class_id] => 1
                    [attributes] =>
                    [attributes_values] =>
                    [products_priced_by_attribute] => 1
                    [product_is_free] => 0
                    [products_discount_type] => 0
                    [products_discount_type_from] => 0
                    [products_virtual] => 0
                    [product_is_always_free_shipping] => 0
                    [products_quantity_order_min] => 1
                    [products_quantity_order_units] => 1
                    [products_quantity_order_max] => 0
                    [products_quantity_mixed] => 0
                    [products_mixed_discount_quantity] => 1
                )

            [1] => Array
                (
                    [id] => 26:4614766d78b26fb71bfd21d09f9eb98c
                    [category] => 9
                    [name] => Microsoft IntelliMouse Explorer
                    [model] => MSIMEXP
                    [image] => microsoft/imexplorer.gif
                    [price] => 100.0000
                    [quantity] => 1
                    [weight] => 8
                    [final_price] => 104.13
                    [onetime_charges] => 0
                    [tax_class_id] => 1
                    [attributes] => Array
                        (
                            [3] => 9
                        )
                    [attributes_values] =>
                    [products_priced_by_attribute] => 1
                    [product_is_free] => 0
                    [products_discount_type] => 0
                    [products_discount_type_from] => 0
                    [products_virtual] => 0
                    [product_is_always_free_shipping] => 0
                    [products_quantity_order_min] => 1
                    [products_quantity_order_units] => 1
                    [products_quantity_order_max] => 0
                    [products_quantity_mixed] => 0
                    [products_mixed_discount_quantity] => 1
               */

        if ($GLOBALS['debug_tpr']) {
            echo 'observer: notifier_cart_get_products_end<br>';
        }
        //isset needed here
        if (isset($_SESSION['customers_tp']) && $_SESSION['customers_tp'] > 0) {
            if ($GLOBALS['debug_tpr']) {
                echo __LINE__ . ' $p2 PRE modification';
                mv_printVar($p2);
            }

            $tp_cart_total = false;
            $selected_attributes = [];
            foreach ($p2 as $key_p => $value_p) {
                if (is_array($value_p['attributes'])) {
                    $selected_attributes = $value_p['attributes'];
                }

                [$price, $final_price, $original_price] = tp_get_final_prices($value_p['id'], $selected_attributes);

                if ($price!==false) {
                    $p2[$key_p]['price'] = $price;
                    $p2[$key_p]['final_price'] = $final_price;

//steve added to shopping cart class $_SESSION['cart'] for PVP column in cart
                    $products_tax = zen_get_tax_rate($p2[$key_p]['tax_class_id']);
                    $class->original_price_array_tp[$key_p] = zen_round(zen_add_tax($original_price, $products_tax), 2); //steve extra property added to class for original price

                    if ($tp_cart_total===false) {
                        $tp_cart_total = 0; //start totaliser
                    }
                    if ($tp_cart_total!==false) {
                        //$products_tax = zen_get_tax_rate($p2[$key_p]['tax_class_id']);//already calculated above
                        $tp_cart_total += $p2[$key_p]['quantity'] * zen_round(zen_add_tax($p2[$key_p]['final_price'], $products_tax), 2);
                    }
                }
            }

            $this->tp_cart_total = $tp_cart_total;//not used here but created for subsequent use by NOTIFIER_CART_SHOW_TOTAL_END to override result of function calculate

            if ($GLOBALS['debug_tpr']) {
                echo __LINE__ . ' $p2 POST modification';
                // mv_printVar($p2);
                echo __LINE__ . ' $tp_cart_total=' . $tp_cart_total . '<br>';
                //mv_printVar($class);
            }
        }
    }

    /**
     * @param $class
     * @param $eventID
     */
    protected function notifier_cart_show_total_end(&$class, $eventID): void {
        //$this->notify('NOTIFIER_CART_SHOW_TOTAL_END');
        global $currencies;
        if ($GLOBALS['debug_tpr']) {
            echo 'observer: notifier_cart_show_total_end<br>';
        }
//needs isset
        if (isset($_SESSION['customers_tp']) && $_SESSION['customers_tp'] > 0) {
            if ($this->tp_cart_total===false) { //no price modifiers used
                return;
            }
            $class->total = zen_round($this->tp_cart_total, $currencies->get_decimal_places($_SESSION['currency']));
        }
    }

    protected function notify_header_shopping_cart_in_products_loop(&$class, $eventID, $p1, &$p2): void {
        //$zco_notifier->notify('NOTIFY_HEADER_SHOPPING_CART_IN_PRODUCTS_LOOP', $i, $productArray);
        /*
         * $p1 (integer):
0
$p2 (array):
Array
(
    [0] => Array
        (
            [attributeHiddenField] => 
            [flagStockCheck] => ***
            [flagShowFixedQuantity] => 
            [linkProductsImage] => https://www.motorvista.es.local/tienda/index.php?main_page=product_info&products_id=6034
            [linkProductsName] => https://www.motorvista.es.local/tienda/index.php?main_page=product_info&products_id=6034
            [productsImage] => Engine Case Cover (RHS Clutch) Race version - Aprilia RS660, Tuono 660 2021 onwards
            [productsName] => Engine Case Cover (RHS Clutch) Race version - Aprilia RS660, Tuono 660 2021 onwards
            [productsModel] => RG-ECC0321R
            [showFixedQuantity] => 
            [showFixedQuantityAmount] => 2
            [showMinUnits] => 
            [quantityField] => 
            [buttonUpdate] => 


            [productsPrice] => 118,92€
            [productsPriceEach] => 59,46€
            [rowClass] => rowEven
            [buttonDelete] => 1
            [checkBoxDelete] => 
            [id] => 6034
            [attributes] => 
        )*/
        global $currencies;
        if ($GLOBALS['debug_tpr']) {
            echo 'observer: notify_header_shopping_cart_in_products_loop<br>';
        }
        //mv_printVar($p1);
        // mv_printVar($p2);
        //mv_printVar($_SESSION['cart']->original_price_array_tp);
        if ($_SESSION['customers_tp'] > 0) {
            $p2[$p1]['productsPriceRRP_tp'] = $currencies->format(zen_round($_SESSION['cart']->original_price_array_tp[$p1], $currencies->get_decimal_places($_SESSION['currency'])));
        }
    }
    
    /**
     * @param $class
     * @param $eventID
     * @param $p1
     */
    protected function notify_order_cart_add_product_list(&$class, $eventID, $p1): void {
//$this->notify('NOTIFY_ORDER_CART_ADD_PRODUCT_LIST', ['index' => $index, 'products' => $products[$i]]);
        /*      $p1 (array):
      Array
      (
          [index] => 0
          [products] => Array
          (
              [id] => 25
                  [category] => 8
                  [name] => Microsoft Internet Keyboard PS/2
                  [model] => MSINTKB
          [image] => microsoft/intkeyboardps2.gif
          [price] => 75
                  [quantity] => 1
                  [weight] => 8
                  [final_price] => 75
                  [onetime_charges] => 0
                  [tax_class_id] => 1
                  [attributes] =>
                  [attributes_values] =>
                  [products_priced_by_attribute] => 1
                  [product_is_free] => 0
                  [products_discount_type] => 0
                  [products_discount_type_from] => 0
                  [products_virtual] => 0
                  [product_is_always_free_shipping] => 0
                  [products_quantity_order_min] => 1
                  [products_quantity_order_units] => 1
                  [products_quantity_order_max] => 0
                  [products_quantity_mixed] => 0
                  [products_mixed_discount_quantity] => 1
        [original_price_tp] => 70.2066 ***this added by notifier_cart_get_products_end
              )
      )
        $p1 (array):
      Array
      (
          [index] => 1
          [products] => Array
              (
                  [id] => 26:4614766d78b26fb71bfd21d09f9eb98c
                  [category] => 9
                  [name] => Microsoft IntelliMouse Explorer
                  [model] => MSIMEXP
                  [image] => microsoft/imexplorer.gif
                  [price] => 150
                  [quantity] => 1
                  [weight] => 8
                  [final_price] => 153.09915
                  [onetime_charges] => 0
                  [tax_class_id] => 1
                  [attributes] => Array
                      (
                          [3] => 9
                      )

                  [attributes_values] =>
                  [products_priced_by_attribute] => 1
                  [product_is_free] => 0
                  [products_discount_type] => 0
                  [products_discount_type_from] => 0
                  [products_virtual] => 0
                  [product_is_always_free_shipping] => 0
                  [products_quantity_order_min] => 1
                  [products_quantity_order_units] => 1
                  [products_quantity_order_max] => 0
                  [products_quantity_mixed] => 0
                  [products_mixed_discount_quantity] => 1
              )

      )*/
        /// global $products;
        if ($GLOBALS['debug_tpr']) {
            echo 'observer notify_order_cart_add_product_list<br>';
        }

        if ($_SESSION['customers_tp'] > 0) {
            if (is_array($p1['products']['attributes'])) {
                $selected_attributes = $p1['products']['attributes'];
            } else {
                $selected_attributes = [];
            }
            [$price, $final_price, $original_price] = tp_get_final_prices((int) $p1['products']['id'], $selected_attributes);
            $class->products[$p1['index']]['price'] = $price; // not needed as copied from value created by class shopping cart
            $class->products[$p1['index']]['final_price'] = $final_price;
            $class->products[$p1['index']]['original_price'] = $original_price;//extra element for use in cart display and email
$this->original_price = $original_price;
            //echo __LINE__ . ' POST mv_printVar($class->products);';
//mv_printVar($class->products);
        }
    }

    function notify_order_during_create_added_product_line_item(&$class, $eventID, $p1) {
//$this->notify('NOTIFY_ORDER_DURING_CREATE_ADDED_PRODUCT_LINE_ITEM', array_merge(['orders_products_id' => $order_products_id, 'i' => $i], $sql_data_array), $order_products_id);
//Array
//(
//    [orders_products_id] => 16536
//    [i] => 0
//    [orders_id] => 20210669
//    [products_id] => 1262
//    [products_model] => LC-10013
//    [products_name] => Anti-oxidante ACF-50 - spray 13oz [en stock]
//    [products_price] => 12.3657
//    [final_price] => 12.3657
//    [onetime_charges] => 0
//    [products_tax] => 21
//    [products_quantity] => 1
//    [products_priced_by_attribute] => 0
//    [product_is_free] => 0
//    [products_discount_type] => 0
//    [products_discount_type_from] => 0
//    [products_prid] => 1262
//    [products_weight] => 0.5
//    [products_virtual] => 0
//    [product_is_always_free_shipping] => 0
//    [products_quantity_order_min] => 1
//    [products_quantity_order_units] => 1
//    [products_quantity_order_max] => 0
//    [products_quantity_mixed] => 0
//    [products_mixed_discount_quantity] => 1
//)
        global $db;
        if (!($_SESSION['customers_tp'] > 0)) {
            return;
        }

        if ($this->original_price > 0) {
            $sql = "UPDATE " . TABLE_ORDERS_PRODUCTS . " SET products_rrp_tp='" . $this->original_price . "' WHERE orders_products_id='" . $p1['orders_products_id'] . "'";
            $db->Execute($sql);
        }
    }
    protected function notify_order_processing_products_ordered_email(&$class, $eventID, $p1, &$p2): void {
        // $this->notify('NOTIFY_ORDER_PROCESSING_PRODUCTS_ORDERED_EMAIL', $i, $products_ordered);//steve custom modifier

        global $currencies;

        if (!($_SESSION['customers_tp'] > 0)) {
            return;
        }
        $p2 = true;

        $class->products_ordered .=
                ($class->products[$p1]['model']!=='' ? $class->products[$p1]['model'] . ' - ':'') .
                $class->products[$p1]['name'] .
                ' (' . $currencies->display_price($class->products[$p1]['original_price'], $class->products[$p1]['tax']) . ' PVP) ' .
                $currencies->display_price($class->products[$p1]['final_price'], $class->products[$p1]['tax']) .
                ' x ' . $class->products[$p1]['qty'] . ' = ' .
                $currencies->display_price($class->products[$p1]['final_price'], $class->products[$p1]['tax'], $class->products[$p1]['qty']) . "\n";

        $class->products_ordered_html .=
                '<tr>' . "\n" .
                '<td class="product-details" valign="top" align="left">' .
                ($class->products[$p1]['model']!=='' ? nl2br($class->products[$p1]['model']) . ' - ':'') .
                nl2br($class->products[$p1]['name']) . ' <small>(' . $currencies->display_price($class->products[$p1]['original_price'], $class->products[$p1]['tax']) . ' PVP)' . '</small>' .
                ($class->products_ordered_attributes!=='' ? "\n" . '<nobr>' . '<small><em>' . nl2br($class->products_ordered_attributes) . '</em></small>' . '</nobr>':'') .
                '</td>' . "\n" .
                '<td class="product-details" valign="top" align="center">' . $currencies->display_price($class->products[$p1]['final_price'], $class->products[$p1]['tax']) . '</td>' . "\n" .
                '<td class="product-details" valign="top" align="center">' . $class->products[$p1]['qty'] . '</td>' . "\n" .
                '<td class="product-details-num" valign="top" align="right">' . $currencies->display_price($class->products[$p1]['final_price'], $class->products[$p1]['tax'], $class->products[$p1]['qty']) .
                '</td>' . "\n" .
                "</tr>\n";

        $table_header = "\n" . '<th align="left">' . TABLE_HEADING_PRODUCTS . '</th><th align="center">' . TABLE_HEADING_PRICE . '</th><th align="center">' . TABLE_HEADING_QUANTITY . '</th><th align="right">' . TABLE_HEADING_TOTAL . '</th>' . "\n";
        $class->products_ordered_html = str_replace($table_header, '', $class->products_ordered_html); //remove all instances of table heading
        $class->products_ordered_html = $table_header . $class->products_ordered_html; // add one table heading
    }

    //////////////////////////////////////////////////////////////////////////
    /// https://github.com/zencart/zencart/pull/4440#issuecomment-897273908
    /**
     * This observer is where the content is added to the email with the idea that
     *   $order_total_modules->apply_credit();//ICW ADDED FOR CREDIT CLASS SYSTEM
     *   doesn't affect the data being modified.
     * This is also called by earlier processes that want to add/modify the text.
     *
     * May need to do something specific if there are no product though would think
     *   there would be nothing generated.
     **/
/*                    'NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_INIT',   // Start of product parse loop
                    'NOTIFY_ORDER_PROCESSING_ONE_TIME_CHARGES_BEGIN', // Prior to construction of email
                    'NOTIFY_ORDER_AFTER_ORDER_CREATE_ADD_PRODUCTS',   // After completion of loop
*/

// Start of product parse loop
    //$this->notify('NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_INIT', array('i'=>$i), $this->products[$i], $i);
    function notify_order_processing_stock_decrement_init(&$callingClass, $notifier, $i_array, &$productsI, &$i) {
        $this->i = $i_array['i']; //used for text generation later
        if (empty($this->i)) {
            $this->text_action = [];
            $this->text_add = '';
            $this->text_add_html = '';
            $this->text_replace = '';
            $this->text_replace_html = '';
        }

        // Cause action associated with the text and internal variables at the beginning of each loop or
        //   as otherwise thought upon restarting the loop.
        $this->notify_order_after_order_create_add_products($callingClass, $notifier);

        // reset tracking variables
        $this->text_action = [];
        $this->text_add = '';
        $this->text_add_html = '';
        $this->text_replace = '';
        $this->text_replace_html = '';
    }
 
//Prior to construction of email    
    //     $this->notify('NOTIFY_ORDER_AFTER_ORDER_CREATE_ADD_PRODUCTS');
    function notify_order_after_order_create_add_products(&$callingClass, $notifier) { //Prior to construction of email
        // IF want something special to happen if this is called from inside the loop, then can evaluate the
        //   value of $notifier to see what caused this code to be executed.

        // Early escape if there is nothing to be done with the content.
        if (empty($this->text_action)) {
            return;
        }
        // select case is not used here, because the options are considered to not be mutually exclusive.
        if (in_array('replace', $this->text_action)) {
            $callingClass->products_ordered = $this->text_before . $this->text_replace;
            $callingClass->products_ordered_html = $this->text_before_html . $this->text_replace_html;
        }
        if (in_array('add', $this->text_action)) {
            $callingClass->products_ordered .= $this->text_add;
            $callingClass->products_ordered_html .= $this->text_add_html;
        }
    }

//After completion of product parse loop
    // $this->notify('NOTIFY_ORDER_PROCESSING_ONE_TIME_CHARGES_BEGIN', $i);
    function notify_order_processing_one_time_charges_begin(&$callingClass, $notifier, $i) {
        $criteria_to_modify_output = true;
        $criteria_to_cause_replace = true;
        if (!$criteria_to_modify_output) {
            return;
        }
        $this->i = $i; // Make the looped value of $i available throughout the class for data assembly.

        $this->text_before = $callingClass->products_ordered;
        $this->text_before_html = $callingClass->products_ordered_html;
        if ($criteria_to_cause_replace) {
            $this->text_action[] = 'replace';
            $this->text_replace = NEW_TEXT;
            $this->text_replace_html = NEW_TEXT_HTML;
        }
        if ($criteria_to_add) {
            $this->text_action[] = 'add';
            $this->text_add = ADDED_TEXT;
            $this->text_add_html = ADDED_TEXT_HTML;
        }
    }
//////////////////////////////////////////////////////////////////////

}
