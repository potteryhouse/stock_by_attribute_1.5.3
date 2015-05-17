<?php
/**
 * checkout_success header_php.php
 *
 * @package page
 * @copyright Copyright 2003-2012 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version GIT: $Id: Author: Ian Wilson  Tue Aug 14 14:56:11 2012 +0100 Modified in v1.5.1 $
 * Updated for Stock by Attributes 1.5.3.1
 */

// This should be first line of the script:
$zco_notifier->notify('NOTIFY_HEADER_START_SBA_CHECKOUT_SUCCESS');


if (!isset($_GET['action']) || (isset($_GET['action']) && $_GET['action'] != 'confirm')) {
/*
$notify_string='';
if (isset($_GET['action']) && ($_GET['action'] == 'update')) {
  $notify_string = 'action=notify&';
  $notify = $_POST['notify'];

  if (is_array($notify)) {
    for ($i=0, $n=sizeof($notify); $i<$n; $i++) {
      $notify_string .= 'notify[]=' . $notify[$i] . '&';
    }
    if (strlen($notify_string) > 0) $notify_string = substr($notify_string, 0, -1);
  }
  if ($notify_string == 'action=notify&') {
      zen_redirect(zen_href_link(FILENAME_CHECKOUT_SUCCESS, '', 'SSL'));
  } else {
    zen_redirect(zen_href_link(FILENAME_DEFAULT, $notify_string));
  }
}

require(DIR_WS_MODULES . zen_get_module_directory('require_languages.php'));
$breadcrumb->add(NAVBAR_TITLE_1);
$breadcrumb->add(NAVBAR_TITLE_2);

// find out the last order number generated for this customer account
$orders_query = "SELECT * FROM " . TABLE_ORDERS . "
                 WHERE customers_id = :customersID
                 ORDER BY date_purchased DESC LIMIT 1";
$orders_query = $db->bindVars($orders_query, ':customersID', $_SESSION['customer_id'], 'integer');
$orders = $db->Execute($orders_query);
$orders_id = $orders->fields['orders_id'];

// use order-id generated by the actual order process
// this uses the SESSION orders_id, or if doesn't exist, grabs most recent order # for this cust (needed for paypal et al).
// Needs reworking in v1.4 for checkout-rewrite
$zv_orders_id = (isset($_SESSION['order_number_created']) && $_SESSION['order_number_created'] >= 1) ? $_SESSION['order_number_created'] : $orders_id;
$orders_id = $zv_orders_id;
$order_summary = $_SESSION['order_summary'];
unset($_SESSION['order_summary']);
unset($_SESSION['order_number_created']);

// prepare list of product-notifications for this customer
$global_query = "SELECT global_product_notifications
                 FROM " . TABLE_CUSTOMERS_INFO . "
                 WHERE customers_info_id = :customersID";

$global_query = $db->bindVars($global_query, ':customersID', $_SESSION['customer_id'], 'integer');
$global = $db->Execute($global_query);
$flag_global_notifications = $global->fields['global_product_notifications'];*/

$notificationsArray = array();

if ($flag_global_notifications != '1') {

  $products_array = array();
  $counter = 0;
  //mc12345678 2014-09-16 modified to prevent duplicate SBA tracked items on checkout_success
  $products_query = "SELECT DISTINCT products_id, products_name
                     FROM " . TABLE_ORDERS_PRODUCTS . "
                     WHERE orders_id = :ordersID
                     ORDER BY products_name";

  $products_query = $db->bindVars($products_query, ':ordersID', $orders_id, 'integer');
  $products = $db->Execute($products_query);

  while (!$products->EOF) {
    $notificationsArray[] = array('counter'=>$counter,
                                  'products_id'=>$products->fields['products_id'],
                                  'products_name'=>$products->fields['products_name']);
    $counter++;
    $products->MoveNext();
  }
}

}

// This should be last line of the script:
$zco_notifier->notify('NOTIFY_HEADER_END_SBA_CHECKOUT_SUCCESS');
