<?php
/*
 * Plugin Name: WooCommerce Close Payment Gateway
 * Plugin URI: https://rudrastyh.com/woocommerce/payment-gateway-plugin.html
 * Description: Enable Close Payment Gateway.
 * Author: Hemang Bhogayata
 * Author URI: http://theclosecompany.com
 * Version: 1.0.0
 */

add_filter( 'woocommerce_payment_gateways', 'close_add_gateway_class' );
function close_add_gateway_class( $gateways ) {
	$gateways[] = 'WC_Close_Gateway'; // your class name is here
	return $gateways;
}
 
/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action( 'plugins_loaded', 'close_init_gateway_class' );
add_action( 'woocommerce_after_cart_totals', 'woo_add_continue_shopping_button_to_cart' );
add_action('admin_post_close_checkout_click', 'close_checkout');

function woo_add_continue_shopping_button_to_cart() {
  $shop_page_url = get_permalink( woocommerce_get_page_id( 'shop' ) );
  
  echo "<form method='post' action=''>";
  echo " <button  class='input' type='submit' name='close-checkout' > Checkout With Close Payments </button>";
  echo "</form>";
 }

 add_action( 'init', 'process_my_form' );
function process_my_form() {
     if( isset( $_POST['close-checkout'] ) ) {
      return array(
        'result'   => 'success',
        'redirect' => 'http://localhost:3006/home/'.$response_body['transactionId']
    );
     }
}

function close_checkout() {


} 
 
function close_init_gateway_class() {
  class WC_Close_Gateway extends WC_Payment_Gateway {
    public function __construct() {
 
      $this->id = 'close'; // payment gateway plugin ID
      $this->icon = 'https://theclosecompany.com'; // URL of the icon that will be displayed on checkout page near your gateway name
      $this->has_fields = true; 
      $this->method_title = 'Close Payment Gateway';
      $this->method_description = 'Enable 1 tap payments on your store using the Close Payment Gateway'; // will be displayed on the options page
     
      // gateways can support subscriptions, refunds, saved payment methods,
      
      $this->supports = array(
        'products'
      );
     
      // Method with all the options fields
      $this->init_form_fields();
     
      // Load the settings.
      $this->init_settings();
      $this->title = $this->get_option( 'title' );
      $this->description = $this->get_option( 'description' );
      $this->enabled = $this->get_option( 'enabled' );
      $this->testmode = 'yes' === $this->get_option( 'testmode' );
      $this->private_key = $this->testmode ? $this->get_option( 'test_private_key' ) : $this->get_option( 'private_key' );
      $this->publishable_key = $this->testmode ? $this->get_option( 'test_publishable_key' ) : $this->get_option( 'publishable_key' );
     
      // This action hook saves the settings
      add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
      // We need custom JavaScript to obtain a token
      add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );

      // You can also register a webhook here
      add_action( 'woocommerce_api_close-payment-complete', array( $this, 'webhook' ) ); 
      // add_action( 'woocommerce_api_{webhook name}', array( $this, 'webhook' ) );
    }

    

    public function webhook() {
 
      $order = wc_get_order( $_GET['wcOrder'] );
      $order->payment_complete();
      $order->reduce_order_stock();
      update_option('webhook_debug', $_GET);
    }

    public function init_form_fields(){
 
      $this->form_fields = array(
        'enabled' => array(
          'title'       => 'Enable/Disable',
          'label'       => 'Enable Close Payment Gateway',
          'type'        => 'checkbox',
          'description' => '',
          'default'     => 'no'
        ),
        'title' => array(
          'title'       => 'Title',
          'type'        => 'text',
          'description' => 'This controls the title which the user sees during checkout.',
          'default'     => '1 Tap Close Payment',
          'desc_tip'    => true,
        ),
        'description' => array(
          'title'       => 'Description',
          'type'        => 'textarea',
          'description' => 'This controls the description which the user sees during checkout.',
          'default'     => 'Pay hassle free with our 1 Tap Payments.',
        ),
        'testmode' => array(
          'title'       => 'Test mode',
          'label'       => 'Enable Test Mode',
          'type'        => 'checkbox',
          'description' => 'Place the payment gateway in test mode using test API keys.',
          'default'     => 'yes',
          'desc_tip'    => true,
        ),
        'test_publishable_key' => array(
          'title'       => 'Test Publishable Key',
          'type'        => 'text'
        ),
        'test_private_key' => array(
          'title'       => 'Test Private Key',
          'type'        => 'password',
        ),
        'publishable_key' => array(
          'title'       => 'Live Publishable Key',
          'type'        => 'text'
        ),
        'private_key' => array(
          'title'       => 'Live Private Key',
          'type'        => 'password'
        )
      );
    }

    public function process_payment( $order_id ) {
      
      global $woocommerce;
     
      // we need it to get any order detailes
      $order = wc_get_order( $order_id );
     
      echo $_POST;
      /*
        * Array with parameters for API interaction
       */
      $args = array(
        'method' => 'POST',
        'headers' => array(
          'Accept' => 'application/json',
          'Content-Type' => 'application/json',
          
        ),
       'body' => json_encode(array(
        'phone' => $_POST['billing_phone'],
        'wcOrder' => $order_id,
        'price' => $order->get_total(),
        'redirectUrl' => 'http://localhost/wordpress/index.php/thank-you-for-shopping-with-us/',
        'merchantId' => '507f1f77bcf86cd799439011'
      
        ))

      );
       
      /*
      *   Your API interaction could be built with wp_remote_post()
      */
      $order->payment_complete();
      $response = wp_remote_post( 'http://localhost:5001/api/transaction', $args );
      echo $response;
      $response_body = json_decode($response['body'], true);
      $response_headers = wp_remote_retrieve_headers( $response );
      return array(
        'result'   => 'success',
        'redirect' => 'http://localhost:3006/home/'.$response_body['transactionId']
    );
    }

  
  }
}