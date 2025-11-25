<?php
// Include the order class
require_once(__DIR__ . '/../classes/order_class.php');

/**
 * Create a new order
 * @param int $customer_id - Customer ID
 * @param string $invoice_no - Unique invoice number
 * @param string $order_date - Order date (YYYY-MM-DD)
 * @param string $order_status - Order status
 * @return int|false - Returns order_id if successful, false if failed
 */
function create_order_ctr($customer_id, $invoice_no, $order_date, $order_status) {
    $order = new order_class();
    return $order->create_order($customer_id, $invoice_no, $order_date, $order_status);
}

/**
 * Add order details (products) to an order
 * @param int $order_id - Order ID
 * @param int $product_id - Product ID
 * @param int $qty - Quantity ordered
 * @return bool - Returns true if successful, false if failed
 */
function add_order_details_ctr($order_id, $product_id, $qty) {
    $order = new order_class();
    return $order->add_order_details($order_id, $product_id, $qty);
}

/**
 * Record a payment for an order
 * @param float $amount - Payment amount
 * @param int $customer_id - Customer ID
 * @param int $order_id - Order ID
 * @param string $currency - Currency code
 * @param string $payment_date - Payment date (YYYY-MM-DD)
 * @param string $payment_method - Payment method (default: 'direct')
 * @param string $transaction_ref - Transaction reference
 * @param string $authorization_code - Authorization code
 * @param string $payment_channel - Payment channel
 * @return int|false - Returns payment_id if successful, false if failed
 */
function record_payment_ctr($amount, $customer_id, $order_id, $currency, $payment_date, $payment_method = 'direct', $transaction_ref = null, $authorization_code = null, $payment_channel = null) {
    $order = new order_class();
    return $order->record_payment($amount, $customer_id, $order_id, $currency, $payment_date, $payment_method, $transaction_ref, $authorization_code, $payment_channel);
}

/**
 * Get all orders for a user
 * @param int $customer_id - Customer ID
 * @return array|false - Returns array of orders or false if failed
 */
function get_user_orders_ctr($customer_id) {
    $order = new order_class();
    return $order->get_user_orders($customer_id);
}

/**
 * Get details of a specific order
 * @param int $order_id - Order ID
 * @param int $customer_id - Customer ID (for security check)
 * @return array|false - Returns order details or false if not found
 */
function get_order_details_ctr($order_id, $customer_id) {
    $order = new order_class();
    return $order->get_order_details($order_id, $customer_id);
}

/**
 * Get all products in a specific order
 * @param int $order_id - Order ID
 * @return array|false - Returns array of products in the order or false if failed
 */
function get_order_products_ctr($order_id) {
    $order = new order_class();
    return $order->get_order_products($order_id);
}

/**
 * Update order status
 * @param int $order_id - Order ID
 * @param string $order_status - New order status
 * @return bool - Returns true if successful, false if failed
 */
function update_order_status_ctr($order_id, $order_status) {
    $order = new order_class();
    return $order->update_order_status($order_id, $order_status);
}
?>