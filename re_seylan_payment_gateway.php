<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/*
Plugin Name: Seylan Bank Payment Gateway
Plugin URI: http://www.redevoke.com/services/payment-gateways/seylan-bank-payment-gateway-integration
Description: Seylan Bank Payment Gateway from RedEvoke Solutions.
Version: 1.0
Author: RedEvoke Solutions
Author URI: www.redevoke.com
*/

add_action('plugins_loaded', 'woocommerce_re_seylan_payment_gateway', 0);

function woocommerce_re_seylan_payment_gateway(){

	if(!class_exists('WC_Payment_Gateway')) return;

	class WC_ReSeylan extends WC_Payment_Gateway{

		public function __construct(){

			$plugin_dir = plugin_dir_url(__FILE__);
			$this->id = 'reseylan';	  
			$this->icon = $plugin_dir . 'seylan_payment_gateway_logo_redevoke_solutions.png';
			$this->method_title = __( "Seylan IPG", 'reseylan' );
			$this->has_fields = false;

			$this->init_form_fields();
			$this->init_settings();

			$this->title 				= $this -> settings['title'];
			$this->description 			= $this -> settings['description'];	  
			$this->pg_instance_id 		= $this -> settings['pg_instance_id'];
			$this->merchant_id 			= $this -> settings['merchant_id'];
			$this->currency_code 		= $this -> settings['currency_code'];
			$this->hash_key 			= $this -> settings['hash_key'];	  	  
			$this->checkout_msg			= $this-> settings['checkout_msg'];	 

		  	$this->msg['message'] 		= "";
			$this->msg['class'] 		= "";

			add_action('init', array(&$this, 're_check_seylan_response')); 

			if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
				add_action( 'woocommerce_update_options_payment_gateways_'.$this->id, array( &$this, 'process_admin_options' ) );
			} else {
				add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
			}
			add_action('woocommerce_receipt_'.$this->id, array(&$this, 'receipt_page'));

		}

	    function init_form_fields(){
		
	       $this -> form_fields = array(
	                'enabled' 	=> array(
	                    'title' 		=> __('Enable/Disable', 'reseylanipg'),
	                    'type' 			=> 'checkbox',
	                    'label' 		=> __('Enable Seylan Payment Gateway Module.', 'reseylanipg'),
	                    'default' 		=> 'no'),
						
	                'title' 	=> array(
	                    'title' 		=> __('Title:', 'reseylanipg'),
	                    'type'			=> 'text',
	                    'description' 	=> __('This controls the title which the user sees during checkout.', 'reseylanipg'),
	                    'default' 		=> __('Visa / Master ( Seylan IPG )', 'reseylanipg')),
					
					'description'=> array(
	                    'title' 		=> __('Description:', 'reseylanipg'),
	                    'type'			=> 'textarea',
	                    'description' 	=> __('This controls the description which the user sees during checkout.', 'reseylanipg'),
	                    'default' 		=> __('Pay using Visa or Master card ( Seylan IPG )', 'reseylanipg')),
						
					'pg_instance_id' => array(
	                    'title' 		=> __('Instance ID:', 'reseylanipg'),
	                    'type'			=> 'text',
	                    'description' 	=> __('Instance ID given by Seylan bank.', 'reseylanipg'),
	                    'default' 		=> __('', 'reseylanipg')),
					
					'merchant_id' => array(
	                    'title' 		=> __('Merchant ID:', 'reseylanipg'),
	                    'type'			=> 'text',
	                    'description' 	=> __('Merchant ID given by Seylan bank.', 'reseylanipg'),
	                    'default' 		=> __('', 'reseylanipg')),
							
						
					'currency_code' => array(
	                    'title' 		=> __('Currency code:', 'reseylan'),
	                    'type'			=> 'text',
	                    'description' 	=> __('Three Number ISO code of the currency. Eg :- 144 for LKR', 'reseylan'),
	                    'default' 		=> __('144', 'reseylan')),
					
					
					'hash_key' => array(
	                    'title' 		=> __('Hash Key:', 'reseylanipg'),
	                    'type'			=> 'text',
	                    'description' 	=> __('Hash Key given by Seylan bank ', 'reseylanipg'),
	                    'default' 		=> __('', 'reseylanipg')),
								
									
					'checkout_msg' => array(
	                    'title' 		=> __('Checkout Message:', 'reseylanipg'),
	                    'type'			=> 'textarea',
	                    'description' 	=> __('Message display when checkout'),
	                    'default' 		=> __('Thank you for your order, Please click the button below to pay with the secured Seylan Bank payment gateway.', 'reseylanipg'))
	            );
	    }

		public function admin_options(){

	    	$plugin_dir 		= plugin_dir_url(__FILE__);
			echo '<h3>'.__('Seylan Bank Payment Gateway', 'reseylanipg').'</h3>';
			echo '<p>'.__('Seylan bank payment gateway allows you to accept payments from customers using Visa / MasterCard').'</p>';
			echo '<a href="'.$this->contact_link.'" ><img src="'.$plugin_dir.'/images/cover_admin.jpg" style="max-width:100%;min-width:100%" ></a>';
			echo '<table class="form-table">';        
					$this->generate_settings_html();
			echo '</table>'; 
			echo '<h4 style="text-align:center;"> Payment gateway developed by <a href="http://www.redevoke.com/">RedEvoke Solutions</a></h4>';
		}

		function payment_fields(){	
			if($this -> description) echo wpautop(wptexturize($this -> description));
		}

		function receipt_page($order){      

			global $woocommerce;
			$order_details = new WC_Order($order);
			echo '<br>'.$this->checkout_msg.'</b>';
			echo $this->generate_payment_gateway_form($order);			
		}

		public function generate_payment_gateway_form($order_id){
			
			$plugin_dir 		= plugin_dir_url(__FILE__);
			wc_add_notice('Please contact <a href="http://www.redevoke.com/contact-us">RedEvoke Solutions</a> to get the complete version of the plugin', 'success');
			echo '<a href="'.$this->contact_link.'" ><img src="'.$plugin_dir.'images/cover_checkout.jpg" style="max-width:100%;min-width:100%" ></a> ';
	        return;
		}

		function process_payment($order_id){
		
			$order = new WC_Order($order_id);
			return array('result' => 'success', 'redirect' => add_query_arg('order',           
			   $order->id, add_query_arg('key', $order->order_key, get_permalink(woocommerce_get_page_id('pay' ))))
			);
		}

		function get_pages($title = false, $indent = true) {
	        $wp_pages = get_pages('sort_column=menu_order');
	        $page_list = array();
	        if ($title) $page_list[] = $title;
	        foreach ($wp_pages as $page) {
	            $prefix = '';            
	            if ($indent) {
	                $has_parent = $page->post_parent;
	                while($has_parent) {
	                    $prefix .=  ' - ';
	                    $next_page = get_page($has_parent);
	                    $has_parent = $next_page->post_parent;
	                }
	            }            
	            $page_list[$page->ID] = $prefix . $page->post_title;
	        }
	        return $page_list;
	    }

	}

	function woocommerce_add_re_seylan_payment_gateway($methods) {
		$methods[] = 'WC_ReSeylan';
		return $methods;
	}

	add_filter('woocommerce_payment_gateways', 'woocommerce_add_re_seylan_payment_gateway' );
}