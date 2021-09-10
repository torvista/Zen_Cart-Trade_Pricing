<?php
declare(strict_types=1);

/**
 * Class zcObserverPluginTradePricingAdmin
 */
class zcObserverPluginTradePricingAdmin extends base {
    public function __construct() {
       
               // $this->install();
               // $this->install_test_data();
               // $this->install_real_data();
 
        if (defined('PLUGIN_TRADE_PRICING') && PLUGIN_TRADE_PRICING==='true') {//admin configuration gID=6
            include '../' . DIR_WS_FUNCTIONS . 'extra_functions/plugin_trade_pricing_functions.php';
            $this->attach($this, [
                    'NOTIFY_ADMIN_CUSTOMERS_LISTING_HEADER',     // customers listing page: Trade Discount column header
                    'NOTIFY_ADMIN_CUSTOMERS_LISTING_NEW_FIELDS', // customers listing page: add Trade Discount field to SQL query, table sorting
                    'NOTIFY_ADMIN_CUSTOMERS_LISTING_ELEMENT',    // customers listing page: Trade Discount column data
                    'NOTIFY_ADMIN_CUSTOMERS_CUSTOMER_EDIT',      // customers edit page: html for Trade Discount field
                    'NOTIFY_ADMIN_CUSTOMERS_CUSTOMER_UPDATE',    // customers edit page: insert customer data to table customers

                    'NOTIFY_ADMIN_MANUFACTURERS_LISTING_HEADER',     // manufacturers listing page: Trade Discount column header
                    'NOTIFY_ADMIN_MANUFACTURERS_LISTING_NEW_FIELDS', // manufacturers listing page: add Trade Discount field to SQL query
                    'NOTIFY_ADMIN_MANUFACTURERS_LISTING_ELEMENT',    // manufacturers listing page: Trade Discount column data
                    'NOTIFY_ADMIN_MANUFACTURERS_MENU_BUTTONS_END',   // manufacturers edit infoBox: add Trade Discount field
                    'NOTIFY_ADMIN_MANUFACTURERS_INSERT_SAVE',        // manufacturers insert/save: add Trade Discount field to sql

                    'NOTIFY_ADMIN_ORDERS_LIST_EXTRA_COLUMN_HEADING', // orders page: add table heading Trade Order
                    'NOTIFY_ADMIN_ORDERS_LIST_EXTRA_COLUMN_DATA',    // orders page: add column Trade Order

                    'NOTIFY_ADMIN_PRODUCT_COLLECT_INFO_EXTRA_INPUTS', // product page: add field for discount
                    'NOTIFY_MODULES_UPDATE_PRODUCT_END'               // update_product: handle extra POST vars to insert in product table
            ]);
        }
    }

    /**
     * @param $class
     * @param $eventID
     * @param $p1
     * @param $p2
     * customers listing page: Trade Discount column header
     */
    protected function notify_admin_customers_listing_header(&$class, $eventID, $p1, &$p2): void {
        //$zco_notifier->notify('NOTIFY_ADMIN_CUSTOMERS_LISTING_HEADER', array(), $additional_headings);

        //these constants added in ZC158
        if (!defined('TEXT_ASC')) {
            define('TEXT_ASC', 'Asc');
        }
        if (!defined('TEXT_DESC')) {
            define('TEXT_DESC', 'Desc');
        }

        $content = (($_GET['list_order']==='tp-asc' || $_GET['list_order']==='tp-desc') ? '<span class="SortOrderHeader">' . TEXT_TP_TRADE_DISCOUNT . '</span>'
                        :TEXT_TP_TRADE_DISCOUNT) . '<br>' .
                '<a href="' . zen_href_link(FILENAME_CUSTOMERS, zen_get_all_get_params(['list_order', 'page']) . 'list_order=tp-asc', 'NONSSL') . '">' . ($_GET['list_order']==='tp-asc'
                        ? '<span class="SortOrderHeader">' . TEXT_ASC . '</span>':'<span class="SortOrderHeaderLink">' . TEXT_ASC . '</span>') . '</a>&nbsp;' .
                '<a href="' . zen_href_link(FILENAME_CUSTOMERS, zen_get_all_get_params(['list_order', 'page']) . 'list_order=tp-desc', 'NONSSL') . '">' . ($_GET['list_order']==='tp-desc'
                        ? '<span class="SortOrderHeader">' . TEXT_DESC . '</span>':'<span class="SortOrderHeaderLink">' . TEXT_DESC . '</span>') . '</a>';
        $class = 'center';
        $parms = null;
        $p2[] = compact('content', 'class', 'parms');
    }

    /**
     * @param $class
     * @param $eventID
     * @param $p1
     * @param $p2
     * @param $p3
     * customers listing page: add Trade Discount field to SQL query, table sorting
     */
    protected function notify_admin_customers_listing_new_fields(&$class, $eventID, $p1, &$p2, &$p3): void {
        //$zco_notifier->notify('NOTIFY_ADMIN_CUSTOMERS_LISTING_NEW_FIELDS', array(), $new_fields, $disp_order);
        //add column to query
        $p2 = ', c.customers_tp';

        //allow sorting by header, on search results
        if (isset($_GET['list_order'])) {
            $disp_order = '';
            switch ($_GET['list_order']) {
                case 'tp_asc':
                    $disp_order = "c.customers_tp";
                    break;
                case 'tp-desc':
                    $disp_order = "c.customers_tp DESC";
                    break;
            }
            if ($disp_order!=='') {
                $p3 = $disp_order;
            }
        }
    }

    /**
     * @param $class
     * @param $eventID
     * @param $p1
     * @param $p2
     * customers listing page: Trade Discount column data
     */
    protected function notify_admin_customers_listing_element(&$class, $eventID, $p1, &$p2): void {
        //$zco_notifier->notify('NOTIFY_ADMIN_CUSTOMERS_LISTING_ELEMENT', $customer, $additional_columns);
        // $additional_columns = array(
        //      array(
        //          'content' => 'The content for the column',
        //          'class' => 'Any additional class for the display',
        //          'parms' => 'Any additional parameters for the display',
        //      ),
        $content = $p1['customers_tp']==='0' ? '-':'<span>' . $p1['customers_tp'] . '</span>';
        $class = 'center';
        $parms = null;
        $p2[] = compact('content', 'class', 'parms');
    }

    /**
     * @param $class
     * @param $eventID
     * @param $p1
     * @param $p2
     * customers edit page: html for Trade Discount field
     */
    protected function notify_admin_customers_customer_edit(&$class, $eventID, $p1, &$p2): void {
        //$zco_notifier->notify('NOTIFY_ADMIN_CUSTOMERS_CUSTOMER_EDIT', $cInfo, $additional_fields);
        // $additional_fields = [
        //      [
        //          'label' => 'The text to include for the field label',
        //          'input' => 'The form-related portion of the field',
        //      ],
        //      ...
        // ];
        $label = TEXT_TP_CUSTOMER_EDIT_DISCOUNTS_LABEL;
        $fieldname = 'customers_tp';
        $input = zen_draw_input_field($fieldname, ($p1->customers_tp==='0' ? '':htmlspecialchars($p1->customers_tp, ENT_COMPAT, CHARSET)),
                'class="form-control" id="' . $fieldname . '" title="' . TEXT_TP_CUSTOMER_EDIT_DISCOUNTS_INFO . '" min="0" step="1"' .
                ($p1->customers_tp==='0' ? ' placeholder="0 (' . TEXT_TP_CUSTOMER_EDIT_DISCOUNTS_INFO . ')"':''), false, 'number');
        $input .= '<br>' . TEXT_TP_CUSTOMER_EDIT_DISCOUNTS_INFO;

        $p2[] = compact('label', 'fieldname', 'input');
    }

    /**
     * @param $class
     * @param $eventID
     * @param $p1
     * @param $p2
     * customers edit page: insert customer data to table customers
     */
    protected function notify_admin_customers_customer_update(&$class, $eventID, $p1, &$p2): void {
        //$zco_notifier->notify('NOTIFY_ADMIN_CUSTOMERS_CUSTOMER_UPDATE', $customers_id, $sql_data_array);
        $customers_tp = !empty($_POST['customers_tp']) ? (int) $_POST['customers_tp']:0;
        $p2[] = ['fieldName' => 'customers_tp', 'value' => $customers_tp, 'type' => 'integer'];
    }

    /**
     * @param $class
     * @param $eventID
     * @param $p1
     * @param $p2
     * customers listing page: Trade Discount column header
     */
    protected function notify_admin_manufacturers_listing_header(&$class, $eventID, $p1, &$p2): void {
        //$zco_notifier->notify('NOTIFY_ADMIN_MANUFACTURERS_LISTING_HEADER', array(), $additional_headings);

        $content = TEXT_TP_TRADE_DISCOUNT;
        $class = 'center';
        $parms = null;
        $p2[] = compact('content', 'class', 'parms');
    }

    /**
     * @param $class
     * @param $eventID
     * @param $p1
     * @param $p2
     * customers listing page: add Trade Discount field to SQL query, table sorting
     */
    protected function notify_admin_manufacturers_listing_new_fields(&$class, $eventID, $p1, &$p2): void {
        //$zco_notifier->notify('NOTIFY_ADMIN_MANUFACTURERS_LISTING_NEW_FIELDS', array(), $new_fields);
        //add column to query
        $p2 = ', manufacturers_tp';
    }

    /**
     * @param $class
     * @param $eventID
     * @param $p1
     * @param $p2
     * customers listing page: Trade Discount column data
     */
    protected function notify_admin_manufacturers_listing_element(&$class, $eventID, $p1, &$p2): void {
        //$zco_notifier->notify('NOTIFY_ADMIN_MANUFACTURERS_LISTING_ELEMENT', $customer, $additional_columns);
        // $additional_columns = array(
        //      array(
        //          'content' => 'The content for the column',
        //          'class' => 'Any additional class for the display',
        //          'parms' => 'Any additional parameters for the display',
        //      ),
        $content = $p1['manufacturers_tp']==='0' ? '-':'<span>' . $p1['manufacturers_tp'] . '</span>';
        $class = 'center';
        $parms = null;
        $p2[] = compact('content', 'class', 'parms');
    }

    /**
     * @param $class
     * @param $eventID
     * @param $p1
     * @param $p2
     * customers listing page: Trade Discount column data
     */
    protected function notify_admin_manufacturers_menu_buttons_end(&$class, $eventID, $p1, &$p2): void {
        //$zco_notifier->notify('NOTIFY_ADMIN_MANUFACTURERS_MENU_BUTTONS_END', ($mInfo ?? new stdClass()), $contents);
        /* $p1 (object):
 objectInfo Object
     (
         [manufacturers_id] => 33
     [manufacturers_tp] => 0
     [manufacturers_name] => Cymarc
     [manufacturers_image] => cymarc/logo_cymarc.gif
     [date_added] => 2020-11-24 09:37:53
     [last_modified] => 2021-07-22 12:11:28
     [featured] => 1
     [weighted] => 1
     [products_count] => 1
 )
        $p2
      [8] => Array
     (
         [align] => text-center
             [text] =>

  */
        $action = $_GET['action'] ?? '';
        $mInfo = $p1;
        $contents = $p2;
        switch ($action) {
            case 'new':
            case 'edit':
                array_splice($contents, -1, 0, [
                        [
                                'text' => zen_draw_label(TEXT_TP_TRADE_DISCOUNT, 'manufacturers_tp', 'class="control-label"') .
                                        zen_draw_input_field('manufacturers_tp', ($mInfo->manufacturers_tp==='0' ? '':$mInfo->manufacturers_tp),
                                                zen_set_field_length(TABLE_MANUFACTURERS, 'manufacturers_tp') . ' class="form-control" id="manufacturers_tp"' . ($mInfo->manufacturers_tp==='0' ? ' placeholder="'
                                                        . TEXT_TP_PRODUCT_DISCOUNTS_UNDEFINED . '"':'')) . '<span>' . TEXT_TP_MANUFACTURER_DISCOUNTS_INFO . '</span>'
                        ]
                ]);
                break;
            case 'delete':
                break;
            default:
                if (isset($mInfo) && is_object($mInfo)) {
                    $contents[] = ['text' => TEXT_TP_TRADE_DISCOUNT . ': ' . $mInfo->manufacturers_tp];
                }
                break;
        }
        $p2 = $contents;
    }

    /**
     * @param $class
     * @param $eventID
     * @param $p1
     * @param $p2
     */
    protected function notify_admin_manufacturers_insert_save(&$class, $eventID, $p1, &$p2): void {
        //      $zco_notifier->notify('NOTIFY_ADMIN_MANUFACTURERS_INSERT_SAVE', compact('action', 'manufacturers_id'), $sql_data_array);
        /*      $p1 (array):
      Array
      (
          [action] => save
          [manufacturers_id] => 33
      )
      $p2 (array):
      Array
      (
          [manufacturers_name] => Cymarc
          [featured] => 1
      )
      */
        global $messageStack;
        $_POST['manufacturers_tp'] = empty($_POST['manufacturers_tp']) ? '0':$_POST['manufacturers_tp'];
        if (tp_check_discount($_POST['manufacturers_tp'])) {
            $p2 = ['manufacturers_tp' => $_POST['manufacturers_tp']];
        } else {
            $messageStack->add_session(sprintf(TEXT_TP_INVALID_DISCOUNT, htmlentities($_POST['manufacturers_tp'], ENT_QUOTES, 'UTF-8')));
        }
    }

    /**
     * @param $class
     * @param $eventID
     * @param $p1
     * @param $p2
     */
    protected function notify_admin_orders_list_extra_column_heading(&$class, $eventID, $p1, &$p2): void {
        /*$zco_notifier->notify('NOTIFY_ADMIN_ORDERS_LIST_EXTRA_COLUMN_HEADING', array(), $extra_headings);
        // $extra_headings = array(
        //     array(
        //       'align' => $alignment,    // One of 'center', 'right', or 'left' (optional)
        //       'text' => $value
        //     ),
        // );
        // Observer note:  Be sure to check that the $p2/$extra_headings value is specifically (bool)false before initializing, since
        // multiple observers might be injecting content!
        */
        $p2[] = ['align' => 'center', 'text' => TEXT_TP_TRADE_DISCOUNT];
    }

    /**
     * @param $class
     * @param $eventID
     * @param $p1
     * @param $p2
     * @param $p3
     */
    protected function notify_admin_orders_list_extra_column_data(&$class, $eventID, $p1, &$p2, &$p3): void {
        //$zco_notifier->notify('NOTIFY_ADMIN_ORDERS_LIST_EXTRA_COLUMN_DATA', (isset($oInfo) ? $oInfo : array()), $orders->fields, $extra_data);
        // $extra_data = array(
        //     array(
        //       'align' => $alignment,    // One of 'center', 'right', or 'left' (optional)
        //       'text' => $value
        //     ),
        // );
        //
        // Observer note:  Be sure to check that the $p3/$extra_data value is specifically (bool)false before initializing, since
        // multiple observers might be injecting content!
        //

        global $db;
        //mv_printVar($p1);// for SELECTED order (infobox): never changes in the loop
        //mv_printVar($p2);// for order in loop
        $check_customer_query = "SELECT customers_tp
                           FROM " . TABLE_CUSTOMERS . "
                           WHERE customers_id = :customersId";
        $check_customer_query = $db->bindVars($check_customer_query, ':customersId', $p2['customers_id'], 'integer');
        $check_customer = $db->Execute($check_customer_query);
        $text = $check_customer->fields['customers_tp']!=='0' ? '<span>' . $check_customer->fields['customers_tp'] . '</span>':'-';
        $p3[] = [
                'align' => 'center',
                'text' => $text
        ];
    }

    /**
     * @param $class
     * @param $eventID
     * @param $p1 $pInfo
     * @param $p2 $extra_product_inputs
     *            html input field for discount bands
     */
    protected function notify_admin_product_collect_info_extra_inputs(&$class, $eventID, $p1, &$p2): void {
        /*$zco_notifier->notify('NOTIFY_ADMIN_PRODUCT_COLLECT_INFO_EXTRA_INPUTS', $pInfo, $extra_product_inputs);
         if (!empty($extra_product_inputs)) {
                        foreach ($extra_product_inputs as $extra_input) {
                            $addl_class = (isset($extra_input['label']['addl_class'])) ? (' ' . $extra_input['label']['addl_class']) : '';
                            $parms = (isset($extra_input['label']['parms'])) ? (' ' . $extra_input['label']['parms']) : '';
                            <div class="form-group">
                                <?php echo zen_draw_label($extra_input['label']['text'], $extra_input['label']['field_name'], 'class="col-sm-3 control-label' . $addl_class . '"' . $parms); ?>
                                <div class="col-sm-9 col-md-6"><?php echo $extra_input['input']; ?></div>
                            </div>

                array fields:
                $p2['label']['text']
                $p2['label']['addl_class']
                $p2['label']['parms']
                $p2['label']['field_name']
                $p2['input];
                */
        global $db;
        $sql = "SELECT manufacturers_name, manufacturers_tp FROM " . TABLE_MANUFACTURERS . " WHERE manufacturers_id = :manufacturersId";
        $sql = $db->bindVars($sql, ':manufacturersId', $p1->manufacturers_id, 'integer');
        $result = $db->Execute($sql);
        $manufacturers_name = $result->fields['manufacturers_name'];
        $manufacturers_tp = $result->fields['manufacturers_tp'];
        $manufacturers_tp_text = $result->fields['manufacturers_tp']==='0' ? sprintf(TEXT_TP_PRODUCT_MANUFACTURER_DISCOUNT_UNDEFINED, $result->fields['manufacturers_name']):sprintf(TEXT_TP_PRODUCT_MANUFACTURER_DISCOUNT, $manufacturers_name, $manufacturers_tp);

        if ($p1->products_tp==='0') {
            if ($result->fields['manufacturers_tp']==='0') {
                $placeholder = TEXT_TP_PRODUCT_DISCOUNTS_UNDEFINED;
            } else {
                $placeholder = $manufacturers_tp_text;
            }
        } else {
            $placeholder = '';
        }
        $checkbox_popup = "\n<script>function myFunction() {
  // Get the checkbox
  var checkBox = document.getElementById('tp_discounts_manufacturer');
  // If the checkbox is checked, display the output text
  if (checkBox.checked == true){
    alert('" . TEXT_TP_PRODUCT_DISCOUNT_MANUFACTURER_LABEL_POPUP . "');
  }
  }
</script>";

        $text = TEXT_TP_PRODUCT_DISCOUNTS_LABEL . '<br><span><a href="' . zen_href_link(FILENAME_MANUFACTURERS, 'mID=' . $p1->manufacturers_id . '&action=edit') . '" title="' . htmlspecialchars(stripslashes(sprintf(TEXT_TP_PRODUCT_MANUFACTURER_EDIT_TITLE, $manufacturers_name)), ENT_COMPAT, CHARSET) . '" target="_blank">' . $manufacturers_tp_text . '</a></span>';
        $addl_class = null; //as test is isset, not empty
        $parms = null;
        $field_name = 'products_tp';
        $input = zen_draw_input_field($field_name, ($p1->products_tp==='0' ? '':$p1->products_tp),
                ' class="form-control" id="' . $field_name . '"' . ($placeholder==='' ? '':' placeholder="' . htmlspecialchars(stripslashes($placeholder), ENT_COMPAT, CHARSET) . '"'));
        $input .= '<span>' . TEXT_TP_PRODUCT_DISCOUNTS_INFO . '</span>';
        $input .= $checkbox_popup .
                '<label class="checkboxLabel" for="tp_discounts_manufacturer">' . zen_draw_checkbox_field('tp_discounts_manufacturer', '1', false, '',
                        'id="tp_discounts_manufacturer" onclick="myFunction()"') . TEXT_TP_PRODUCT_DISCOUNT_MANUFACTURER_LABEL . '</label>';
        $p2[] = ['label' => compact('text', 'addl_class', 'parms', 'field_name'), 'input' => $input];
    }

    /**
     * @param $class
     * @param $eventID
     * @param $p1
     *           update_product: handle extra POST vars to insert in product table
     */
    protected function notify_modules_update_product_end(&$class, $eventID, $p1): void {
        //$zco_notifier->notify('NOTIFY_MODULES_UPDATE_PRODUCT_END', array('action' => $action, 'products_id' => $products_id));
        global $db, $messageStack;
        $products_tp = empty($_POST['products_tp']) ? '0':$_POST['products_tp'];
        if (tp_check_discount($products_tp)) {
            if (isset($_POST['tp_discounts_manufacturer']) && ($_POST['tp_discounts_manufacturer']==='1' && (int) $_POST['manufacturers_id']!==0)) {
                $sql = "UPDATE " . TABLE_PRODUCTS . " SET products_tp = '" . $products_tp . "' WHERE manufacturers_id = " . (int) $_POST['manufacturers_id'];
                $message = 'Updated ALL products for manufacturer_id #' . (int) $_POST['manufacturers_id'] . ' with discount products_tp=' . $products_tp;
                $messageStack->add_session($message, 'caution');
            } else {
                $sql = "UPDATE " . TABLE_PRODUCTS . " SET products_tp = '" . $products_tp . "' WHERE products_id = " . (int) $p1['products_id'];
                $message = 'Updated product #' . $p1['products_id'] . ' with products_tp=' . $products_tp;
            }
            $db->Execute($sql);
            zen_record_admin_activity($message, 'notice');
        } else {
            $messageStack->add_session(sprintf(TEXT_TP_INVALID_DISCOUNT, htmlentities($_POST['products_tp'], ENT_QUOTES, 'UTF-8')));
        }
    }

    private function install(): void {
        global $db, $messageStack, $sniffer;
        /*uninstall sql
        ALTER TABLE `customers` DROP `customers_tp`;
        ALTER TABLE `manufacturers` DROP `manufacturers_tp`;
        ALTER TABLE `products` DROP `products_tp`;
        ALTER TABLE `orders_products` DROP `products_rrp_tp`;
        DELETE FROM `configuration` WHERE configuration_key = "PLUGIN_TRADE_PRICING";
        */

        if (defined('PLUGIN_TRADE_PRICING')) {
            $messageStack->add('Plugin Trade Pricing: constant "PLUGIN_TRADE_PRICING" already defined');
        } else {
            $sql = "INSERT IGNORE INTO " . TABLE_CONFIGURATION . "
        ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function, set_function ) VALUES      
        ('Plugin Trade Pricing - enabled', 'PLUGIN_TRADE_PRICING', 'true', 'enable Trade Pricing observers', 6, 100, now(), NULL, NULL)";
            $db->Execute($sql);
            $messageStack->add('Plugin Trade Pricing: field "PLUGIN_TRADE_PRICING" added to table "' . TABLE_CONFIGURATION . '".', 'success');
        }

        if ($sniffer->field_exists(TABLE_CUSTOMERS, 'customers_tp')) {
            $messageStack->add('Plugin Trade Pricing: field "customers_tp" exists in table "' . TABLE_CUSTOMERS . '"');
        } else {
            $db->Execute("ALTER TABLE " . TABLE_CUSTOMERS . " ADD `customers_tp` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0'");
            $messageStack->add('Plugin Trade Pricing: field "customers_tp" added to table "' . TABLE_CUSTOMERS . '".', 'success');
        }
        if ($sniffer->field_exists(TABLE_MANUFACTURERS, 'manufacturers_tp')) {
            $messageStack->add('Plugin Trade Pricing: field "manufacturers_tp" exists in table "' . TABLE_MANUFACTURERS . '"');
        } else {
            $db->Execute("ALTER TABLE " . TABLE_MANUFACTURERS . " ADD `manufacturers_tp` VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NULL DEFAULT '0'");
            $messageStack->add('Plugin Trade Pricing: field "manufacturers_tp" added to table "' . TABLE_MANUFACTURERS . '".', 'success');
        }
        if ($sniffer->field_exists(TABLE_PRODUCTS, 'products_tp')) {
            $messageStack->add('Plugin Trade Pricing: field "products_tp" exists in table "' . TABLE_PRODUCTS . '"');
        } else {
            $db->Execute("ALTER TABLE " . TABLE_PRODUCTS . " ADD `products_tp` VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NULL DEFAULT '0'");
            $messageStack->add('Plugin Trade Pricing: field "products_tp" added to table "' . TABLE_PRODUCTS . '".', 'success');
        }
        if ($sniffer->field_exists(TABLE_ORDERS_PRODUCTS, 'products_rrp_tp')) {
            $messageStack->add('Plugin Trade Pricing: field "products_rrp_tp" exists in table "' . TABLE_ORDERS_PRODUCTS . '"');
        } else {
            $db->Execute("ALTER TABLE " . TABLE_ORDERS_PRODUCTS . " ADD `products_rrp_tp` DECIMAL(15,4) NULL DEFAULT '0'");
            $messageStack->add('Plugin Trade Pricing: field "products_rrp_tp" added to table "' . TABLE_ORDERS_PRODUCTS . '".', 'success');
        }
        $messageStack->add('Plugin Trade Pricing: install complete', 'success');
    }

    private function install_test_data(): void {
        global $db, $messageStack;
        $messageStack->add('Plugin Trade Pricing: install_test_data START', 'success');
        $sql = "UPDATE " . TABLE_CUSTOMERS . " SET customers_tp = 1 WHERE customers_id = 1";
        $db->Execute($sql);

        $messageStack->add($sql, 'success');
        $sql = "UPDATE " . TABLE_CUSTOMERS . " SET customers_tp = 1 WHERE customers_id = 738";
        $db->Execute($sql);
        $messageStack->add($sql, 'success');

        $sql = "UPDATE " . TABLE_MANUFACTURERS . " SET manufacturers_tp = '25%'";
        $db->Execute($sql);
        $messageStack->add($sql, 'success');

        $sql = "UPDATE " . TABLE_PRODUCTS . " SET products_tp = '10%-20%' WHERE products_id = 2889"; // RScan supports
        $db->Execute($sql);
        $messageStack->add($sql . ': RScan supports', 'success');

        $sql = "UPDATE " . TABLE_PRODUCTS . " SET products_tp = '10%-20%' WHERE products_id = 2690"; //Grip P, base price 0, attribute pricing
        $db->Execute($sql);
        $messageStack->add($sql . ': Grip Puppies', 'success');
        $messageStack->add('Plugin Trade Pricing: install_test_data END', 'success');
    }

    private function install_real_data(): void {
        global $db, $messageStack;
        $messageStack->add('Plugin Trade Pricing: install_real_data START', 'success');

        $sql_array = ["UPDATE manufacturers SET manufacturers_tp ='20%' WHERE manufacturers_id = 9",
                      "UPDATE products SET products_tp ='0%' WHERE products_id = 4631"];

        foreach ($sql_array as $sql) {
            $db->Execute($sql);
            $messageStack->add($sql, 'success');
        }
        $messageStack->add('Plugin Trade Pricing: install_real_data END', 'success');
    }
}
