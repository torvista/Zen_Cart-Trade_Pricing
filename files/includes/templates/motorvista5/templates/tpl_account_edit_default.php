<?php // steve, company name in address, NIF, lower edge,extra stuff added by Steve taxid line 49, extra email field 66
/**
 * Page Template
 *
 * Loaded automatically by index.php?main_page=account_edit.
 * View or change Customer Account Information
 *
 * @copyright Copyright 2003-2020 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id:  Modified in v1.5.8 $
 */
?>
<div class="centerColumn" id="accountEditDefault">
<!-- steve bof-->
<p><?php echo EDIT_COMPANY_NAME_IN_ADDRESS; ?></p>
<!-- steve eof -->
<?php echo zen_draw_form('account_edit', zen_href_link(FILENAME_ACCOUNT_EDIT, '', 'SSL'), 'post', 'onsubmit="return check_form(account_edit);"') . zen_draw_hidden_field('action', 'process'); ?>

<?php if ($messageStack->size('account_edit') > 0) echo $messageStack->output('account_edit'); ?>

<fieldset>
<legend><?php echo HEADING_TITLE; ?></legend>
<div class="alert forward"><?php echo FORM_REQUIRED_INFORMATION; ?></div>
<br class="clearBoth">
    <?php //steve trade pricing
    if ($_SESSION['customers_tp'] > 0) { ?>
    <h3><?php echo TEXT_TP_EDIT_ADDRESS_INFO; ?></h3>
    <?php }

  if (ACCOUNT_GENDER == 'true') {
?>
<?php echo zen_draw_radio_field('gender', 'm', $male, 'id="gender-male"') . '<label class="radioButtonLabel" for="gender-male">' . MALE . '</label>' . zen_draw_radio_field('gender', 'f', $female, 'id="gender-female"') . '<label class="radioButtonLabel" for="gender-female">' . FEMALE . '</label>' . (!empty(ENTRY_GENDER_TEXT) ? '<span class="alert">' . ENTRY_GENDER_TEXT . '</span>': ''); ?>
<br class="clearBoth">
<?php
  }?>
<label class="inputLabel" for="firstname"><?php echo ENTRY_FIRST_NAME; ?></label>
<?php echo zen_draw_input_field('firstname', $account->fields['customers_firstname'], 'id="firstname" placeholder="' . ENTRY_FIRST_NAME_TEXT . '"' . ((int)ENTRY_FIRST_NAME_MIN_LENGTH > 0 ? ' required' : '') . ($_SESSION['customers_tp'] === 0 ? '' : ' disabled')); ?>
<br class="clearBoth">

<label class="inputLabel" for="lastname"><?php echo ENTRY_LAST_NAME; ?></label>
<?php echo zen_draw_input_field('lastname', $account->fields['customers_lastname'], 'id="lastname" placeholder="' . ENTRY_LAST_NAME_TEXT . '"' . ((int)ENTRY_LAST_NAME_MIN_LENGTH > 0 ? ' required' : '') . ($_SESSION['customers_tp'] === 0 ? '' : ' disabled')); ?>
<br class="clearBoth">
<?php //steve for second email address, tax id
/*
if (!empty($_POST['taxid'])) {//pageload is from form submit
    $taxid = zen_db_prepare_input(zen_sanitize_string(strtoupper($_POST['taxid'])));
} else {
    $taxid = $account->fields['customers_taxid'];
    }
if (!empty($_POST['email_address_confirm'])) {//pageload is from form submit
    $email_address_confirm = zen_db_prepare_input($_POST['email_address_confirm']);
} else {
    $email_address_confirm = $account->fields['customers_email_address'];
}
 $notaxid = isset($_POST['noTaxID']) ? 1 : 0;//steve, checkbox to exempt from NIF checks
*/
if (isset($_POST['action']) && ($_POST['action'] === 'process')) {
    //pageload is from a form submission
    } else {//normal load
            $taxid = $account->fields['customers_taxid'];
            $email_address_confirm = $account->fields['customers_email_address'];
    }
    $notaxid = isset($_POST['noTaxID']) ? 1 : 0;//steve, checkbox to exempt from NIF checks
?>
    <label class="inputLabel" for="taxId" id="taxidLabel"><?php echo ENTRY_TAXID_LABEL_DEFAULT; ?></label>
    <?php echo zen_draw_input_field('taxid', $taxid, zen_set_field_length(TABLE_CUSTOMERS, 'customers_taxid', ENTRY_TAXID_LENGTH_DEFAULT)
        . ' id="taxId"
        title="' . ENTRY_TAXID_TITLE_DEFAULT. '"
        placeholder="' . ENTRY_TAXID_PLACEHOLDER . '"' .
        (ENTRY_TAXID_MIN_LENGTH_DEFAULT > 0 ? ' pattern=".{' . ENTRY_TAXID_MIN_LENGTH_DEFAULT . ',}" required' : '')
         . ($_SESSION['customers_tp'] === 0 ? '' : ' disabled')); ?>
    &nbsp;<span id="taxidPostField"><?php echo ENTRY_TAXID_SUFFIX_DEFAULT; ?></span>
    <div id="noNationalTaxID"><label class="checkboxLabel" for="noTaxIdCheckbox" id="noTaxIdCheckboxLabel"><?php echo ENTRY_NO_TAXID_CHECKBOX_DEFAULT; ?></label><?php echo zen_draw_checkbox_field('noTaxID', '1', $notaxid, 'id="noTaxIdCheckbox"'); ?></div>
    <br class="clearBoth">
    <!--steve eof TAXID-->
<?php
  if (ACCOUNT_DOB == 'true') {
?>
<label class="inputLabel" for="dob"><?php echo ENTRY_DATE_OF_BIRTH; ?></label>
<?php echo zen_draw_input_field('dob', zen_date_short($account->fields['customers_dob']), 'id="dob" placeholder="' . ENTRY_DATE_OF_BIRTH_TEXT . '"' . (ACCOUNT_DOB == 'true' && (int)ENTRY_DOB_MIN_LENGTH != 0 ? ' required' : '')); ?>
<br class="clearBoth">
<?php
  }
?>

<label class="inputLabel" for="email-address"><?php echo ENTRY_EMAIL_ADDRESS; ?></label>
<?php echo zen_draw_input_field('email_address', $account->fields['customers_email_address'], 'id="email-address" placeholder="' . ENTRY_EMAIL_ADDRESS_TEXT . '"'. ((int)ENTRY_EMAIL_ADDRESS_MIN_LENGTH > 0 ? ' required' : '') . ($_SESSION['customers_tp'] === 0 ? '' : ' disabled'), 'email'); ?>
<br class="clearBoth">

<?php //steve bof FOR DUPLICATE CHECK OF EMAIL ADDRESS ?>
<label class="inputLabel" for="email-address-confirm"><?php echo ENTRY_EMAIL_ADDRESS_CONFIRM; ?></label>
<?php echo zen_draw_input_field('email_address_confirm', $email_address_confirm, 'id="email-address-confirm" placeholder="' . ENTRY_EMAIL_ADDRESS_TEXT . '"'. ((int)ENTRY_EMAIL_ADDRESS_MIN_LENGTH > 0 ? ' required' : '') . ($_SESSION['customers_tp'] === 0 ? '' : ' disabled'), 'email'); ?>
<br class="clearBoth">
<?php //steve eof FOR DUPLICATE CHECK OF EMAIL ADDRESS ?>
<label class="inputLabel" for="telephone"><?php echo ENTRY_TELEPHONE_NUMBER; ?></label>
<?php echo zen_draw_input_field('telephone', $account->fields['customers_telephone'], 'id="telephone" placeholder="' . ENTRY_TELEPHONE_NUMBER_TEXT . '"' . ((int)ENTRY_TELEPHONE_MIN_LENGTH > 0 ? ' required' : '') . ($_SESSION['customers_tp'] === 0 ? '' : ' disabled'), 'tel'); ?>
<br class="clearBoth">

<?php
if (ACCOUNT_FAX_NUMBER == 'true' ) {
?>
<label class="inputLabel" for="fax"><?php echo ENTRY_FAX_NUMBER; ?></label>
<?php echo zen_draw_input_field('fax', $account->fields['customers_fax'], 'id="fax" placeholder="' . ENTRY_FAX_NUMBER_TEXT . '"', 'tel'); ?>
<br class="clearBoth">
<?php
  }
?>

<?php
  if (CUSTOMERS_REFERRAL_STATUS == 2 and $customers_referral == '') {
?>
<label class="inputLabel" for="customers-referral"><?php echo ENTRY_CUSTOMERS_REFERRAL; ?></label>
<?php echo zen_draw_input_field('customers_referral', '', zen_set_field_length(TABLE_CUSTOMERS, 'customers_referral', 15) . ' id="customers-referral"'); ?>
<br class="clearBoth">
<?php } ?>

<?php
  if (CUSTOMERS_REFERRAL_STATUS == 2 and $customers_referral != '') {
?>
<label for="customers-referral-readonly"><?php echo ENTRY_CUSTOMERS_REFERRAL; ?></label>
<?php echo $customers_referral; zen_draw_hidden_field('customers_referral', $customers_referral,'id="customers-referral-readonly"'); ?>
<br class="clearBoth">
<?php } ?>
</fieldset>

<fieldset>
<legend><?php echo ENTRY_EMAIL_PREFERENCE; ?></legend>
<?php echo zen_draw_radio_field('email_format', 'HTML', $email_pref_html,'id="email-format-html"') . '<label class="radioButtonLabel" for="email-format-html">' . ENTRY_EMAIL_HTML_DISPLAY . '</label>' . zen_draw_radio_field('email_format', 'TEXT', $email_pref_text, 'id="email-format-text"') . '<label  class="radioButtonLabel" for="email-format-text">' . ENTRY_EMAIL_TEXT_DISPLAY . '</label>'; ?>
<br class="clearBoth">
</fieldset>

<div class="buttonRow back"><?php echo '<a href="' . zen_href_link(FILENAME_ACCOUNT, '', 'SSL') . '">' . zen_image_button(BUTTON_IMAGE_BACK , BUTTON_BACK_ALT) . '</a>'; ?></div>
<div class="buttonRow forward"><?php echo zen_image_submit(BUTTON_IMAGE_UPDATE , BUTTON_UPDATE_ALT); ?></div>
<br class="clearBoth">

<?php echo '</form>'; //steve ?>
</div>
<div class="lower_edge"></div>
