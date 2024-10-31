<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/*
Plugin Name: NTB Bank IPG
Plugin URI: www.oganro.com/plugins
Description: NTB Bank Payment Gateway from Oganro (Pvt)Ltd.
Version: 1.1
Author: Oganro
Author URI: www.oganro.com
*/

add_action('plugins_loaded', 'woocommerce_ntb_gateway', 0);

function woocommerce_ntb_gateway(){
  if(!class_exists('WC_Payment_Gateway')) return;

  class WC_NTB extends WC_Payment_Gateway{
    public function __construct(){
	  $plugin_dir = plugin_dir_url(__FILE__);
      $this->id = 'NTBIPG';	  
	  $this->icon = apply_filters('woocommerce_Paysecure_icon', ''.$plugin_dir.'ntb.jpg');
      $this->medthod_title = 'NTBIPG';
      $this->has_fields = false;
 
      $this->init_form_fields();
      $this->init_settings(); 
	  
      $this->title 				= $this -> settings['title'];
      $this->description 		= $this -> settings['description'];
      $this->merchant_id 		= $this -> settings['merchant_id'];
      $this->action 		    = $this -> settings['action'];      	  
	  $this->currency_code 		= $this -> settings['currency_code'];
      $this->return_url 		= $this -> settings['return_url'];
      $this->ipg_server_url 		= $this -> settings['ipg_server_url'];                  
	  $this->sucess_responce_code	= $this-> settings['sucess_responce_code'];
      $this->checkout_msg			= $this-> settings['checkout_msg'];	  
	  $this->responce_url_sucess	= $this-> settings['responce_url_sucess'];
	  $this->responce_url_fail		= $this-> settings['responce_url_fail'];	  	  
	   
      $this->msg['message'] 	= "";
      $this->msg['class'] 		= "";
 
      add_action('init', array(&$this, 'check_NTBIPG_response'));	  
	  	  
		if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
        	add_action( 'woocommerce_update_options_payment_gateways_'.$this->id, array( &$this, 'process_admin_options' ) );
		} else {
            add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
        }
      add_action('woocommerce_receipt_NTBIPG', array(&$this, 'receipt_page'));	 
   }
	
    function init_form_fields(){
 
       $this-> form_fields = array(
                'enabled' => array(
                    'title' => __('Enable/Disable', 'ogn'),
                    'type' => 'checkbox',
                    'label' => __('Enable NTB IPG Module.', 'ognro'),
                    'default' => 'no'),
					
                'title' => array(
                    'title' => __('Title:', 'ognro'),
                    'type'=> 'text',
                    'description' => __('This controls the title which the user sees during checkout.', 'ognro'),
                    'default' => __('NTB IPG', 'ognro')),
				
				'description' => array(
                    'title' => __('Description:', 'ognro'),
                    'type'=> 'textarea',
                    'description' => __('This controls the description which the user sees during checkout.', 'ognro'),
                    'default' => __('Pay with american express', 'ognro')),	
								
				'merchant_id' => array(
                    'title' => __('PG Merchant Id:', 'ognro'),
                    'type'=> 'text',
                    'description' => __('Unique ID for the merchant acc, given by bank.', 'ognro'),
                    'default' => __('', 'ognro')),
				
				'action' => array(
                    'title' => __('Action:', 'ognro'),
                    'type'=> 'text',
                    'description' => __('Type of action, default value is SaleTxn', 'ognro'),
                    'default' => __('SaleTxn', 'ognro')),
                
                'currency_code' => array(
                    'title' => __('PG Currency Code LKR:', 'ognro'),
                    'type'=> 'text',
                    'description' => __('You\'r currency type of the account. LKR', 'ognro'),
                    'default' => __('LKR', 'ognro')),
            		
				'return_url' => array(
                    'title' => __('Return url:', 'ognro'),
                    'type'=> 'text',
                    'description' => __('This is where bank send payment responce', 'ognro'),
                    'default' => __('http://localhost/merchant/decrypt.php', 'ognro')),
                    
                'ipg_server_url' => array(
                    'title' => __('IPG Server URL:', 'ognro'),
                    'type'=> 'text',
                    'description' => __('This is where we submit our from data', 'ognro'),
                    'default' => __('https://www.ipayamex.lk/ipg/servlet_pay', 'ognro')),    
					
				'sucess_responce_code' => array(
                    'title' => __('Sucess responce code :', 'ognro'),
                    'type'=> 'text',
                    'description' => __('ACCEPTED - Transaction Passed', 'ognro'),
                    'default' => __('ACCEPTED', 'ognro')),	  
								
				'checkout_msg' => array(
                    'title' => __('Checkout Message:', 'ognro'),
                    'type'=> 'textarea',
                    'description' => __('Message display when checkout'),
                    'default' => __('Thank you for your order, please click the button below to pay with the secured NTB Bank payment gateway.', 'ognro')),		
					
				'responce_url_sucess' => array(
                    'title' => __('Sucess redirect URL :', 'ognro'),
                    'type'=> 'text',
                    'description' => __('After payment is sucess redirecting to this page.'),
                    'default' => __('http://your-site.com/thank-you-page/', 'ognro')),
				
				'responce_url_fail' => array(
                    'title' => __('Fail redirect URL :', 'ognro'),
                    'type'=> 'text',
                    'description' => __('After payment if there is an error redirecting to this page.', 'ognro'),
                    'default' => __('http://your-site.com/error-page/', 'ognro'))	
            );
    }
	
    public function admin_options(){
		
		$plugin_path = plugin_dir_path( __FILE__ );
		$file = $plugin_path.'includes/auth.php';
		if(file_exists($file)){
			include 'includes/auth.php';
			$auth = new Auth();
			$auth->check_auth();
			if ( !$auth->get_status() ) {
				deactivate_plugins( plugin_basename( __FILE__ ) );
				if($auth->get_code() == 2){
					wp_die( "<h1>".ucfirst($auth->get_message())."</h1><br>Visit <a href='http://www.oganro.com/plugins/profile'>www.oganro.com/plugins/profile</a> and change the domain" ,"Activation Error","ltr" );
				}else{
					wp_die( "<h1>".ucfirst($auth->get_message())."</h1><br>Visit <a href='http://www.oganro.com/wordpress-plug-in-support'>www.oganro.com/wordpress-plug-in-support</a> for more info" ,"Activation Error","ltr" );
				}
			}
		}else{
			deactivate_plugins( plugin_basename( __FILE__ ) );
			$path =  plugin_basename( __FILE__ );
			$dir  = explode("/", $path);
			wp_die( "<h1>Buy serial key to activate this plugin</h1><br><a href='http://www.oganro.com/wordpress-plug-in-support'><img src=".site_url('wp-content/plugins/'.$dir[0].'/support.jpg')." style='width:700px;height:auto;' /></a><p>Visit <a href='http://www.oganro.com/plugins'>www.oganro.com/plugins</a> to buy this plugin<p>" ,"Activation Error","ltr" );
		}
    	echo '<h3>'.__('Nation Trust Bank online payment', 'ognro').'</h3>';
        echo '<p>'.__('<a target="_blank" href="http://www.oganro.com/">Oganro</a> is a fresh and dynamic web design and custom software development company with offices based in East London, Essex, Brisbane (Queensland, Australia) and in Colombo (Sri Lanka).').'</p>';
        echo'<a href="http://www.oganro.com/wordpress-plug-in-support" target="_blank"><img class="wpimage" alt="payment gateway" src="../wp-content/plugins/sampath-bank-ipg/plug-inimg.jpg" width="100%"></a>';
        echo '<table class="form-table">';        
        $this->generate_settings_html();
        echo '</table>'; 
    }

    function payment_fields(){
        if($this -> description) echo wpautop(wptexturize($this -> description));
    }

    function receipt_page($order){
		global $woocommerce;
        $order_details = new WC_Order($order);
        
        echo $this->generate_ipg_form($order);		
		echo '<br>'.$this->checkout_msg.'</b>';        
    }
    
    public function generate_ipg_form($order_id){
 
        global $wpdb;
        global $woocommerce;
        
        $order          = new WC_Order($order_id);
		$productinfo    = "Order $order_id";		
        $currency_code  = $this -> currency_code;		
		$curr_symbole 	= get_woocommerce_currency();		
						
		$table_name = $wpdb->prefix . 'ntb_ipg';		
		$check_oder = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE merchant_transaction_id = '".$order_id."'" );
                            
		if($check_oder > 0){
			$wpdb->update( 
				$table_name, 
				array( 
					'bank_reference_id' => '',					
					'currency_code' => $this->currency_code,
                    'ipg_transaction_id' => $this->ipg_transaction_id,
                    'transaction_name' => '',                    
                    'transaction_amount' => ($order->order_total),
                    'transaction_status' => '',
                    'transaction_reason' => '',
                    'or_date' => date('Y-m-d')
				), 
				array( 'merchant_transaction_id' => $order_id ));								
		}else{			
			$wpdb->insert($table_name, array( 'merchant_transaction_id'=>$order_id,'bank_reference_id'=>'', 'currency_code'=>$this->currency_code, 'ipg_transaction_id'=>$order->ipg_transaction_id,'transaction_name'=>'','transaction_amount'=>$order->order_total,'transaction_status'=>'','transaction_reason'=>'', 'or_date' => date('Y-m-d')), array( '%s', '%d' ) );					
		}		
				        
        //============================
        $IPGClientIP = "127.0.0.1";
        $IPGClientPort = "10000";
        
        $ERRNO = "";
        $ERRSTR = "";
        $SOCKET_TIMEOUT = 2;
        $IPGSocket = "";
        
        $error_message = "";
        $invoice_sent_error = "";
        $encryption_ERR = "";
        
        $Invoice = "";
        $EncryptedInvoice = "";
        
        $IPGServerURL   = $this->ipg_server_url;        
        
        $currencyCode   = $this->currency_code;
        $MerchantID     = $this->merchant_id;
        $MerchantRefID  = $order_id;
        $TxnAmount      = $order->order_total;
        $ReturnURL      = $this->return_url;
        $action         = $this->action;
        $MerchantVar1 = "";
        $MerchantVar2 = "";
        $MerchantVar3 = "";
        $MerchantVar4 = "";
    
        $Invoice = "";
        $Invoice .= "<req>".
                        "<mer_id>" . $MerchantID . "</mer_id>".
                        "<mer_txn_id>" .$MerchantRefID. "</mer_txn_id>".
                        "<action>" . $action . "</action>".
    		    "<txn_amt>" . $TxnAmount . "</txn_amt>".
    		    "<cur>" . $currencyCode . "</cur>" .
    		    "<lang>en</lang>";
    
        if($ReturnURL != "") {
           $Invoice .= "<ret_url>" . $ReturnURL . "</ret_url>"; 
        }
    
        if($MerchantVar1 != "") {
            $Invoice .= "<mer_var1>" .$MerchantVar1. "</mer_var1>";
        }
    
        if($MerchantVar2 != "") {
            $Invoice .= "<mer_var2>" .$MerchantVar2. "</mer_var2>";
        }
    
        if($MerchantVar3 != "") {
            $Invoice .= "<mer_var3>" .$MerchantVar3. "</mer_var3>";
        }
    
        if($MerchantVar4 != "") {
            $Invoice .= "<mer_var4>" .$MerchantVar4. "</mer_var4>";
        }
    
        $Invoice .= "</req>";
        
               
            if ($IPGClientIP != "" && $IPGClientPort != "") 
            {
                $IPGSocket = fsockopen($IPGClientIP, $IPGClientPort, $ERRNO, $ERRSTR, $SOCKET_TIMEOUT);
            }
            else 
            {
                $error_message = "Could not establish a socket connection for given IPGClientIP = ". $IPGClientIP . "and IPGClientPort = ".$IPGClientPort; 
                $socket_creation_err = true;
            }
        
                
            if(!$socket_creation_err) 
            {
                socket_set_timeout($IPGSocket, $SOCKET_TIMEOUT);
        
                // Write the invoice to socket connection
                if(fwrite($IPGSocket,$Invoice) === false) 
                {
                    $error_message .= "Invoice could not be written to socket connection";
                    $invoice_sent_error = true;
                }
            }
            
        
            if(!$socket_creation_err && !$invoice_sent_error)
            {
                while (!feof($IPGSocket)) 
                {
                    $EncryptedInvoice .= fread($IPGSocket, 8192);
                }    
             }
        
        
            if(!$socket_creation_err) 
            {
                fclose($IPGSocket);
            }
        
        
            if (!(strpos($EncryptedInvoice, '<error_code>') === false && strpos($EncryptedInvoice, '</error_code>') === false && strpos($EncryptedInvoice, '<error_msg>') === false && strpos($EncryptedInvoice, '</error_msg>') === false)) 
            {
                $encryption_ERR = true;
                
                $Error_code = substr($EncryptedInvoice, (strpos($EncryptedInvoice, '<error_code>')+12), (strpos($EncryptedInvoice, '</error_code>') - (strpos($EncryptedInvoice, '<error_code>')+12)));
            
                $Error_msg = substr($EncryptedInvoice, (strpos($EncryptedInvoice, '<error_msg>')+11), (strpos($EncryptedInvoice, '</error_msg>') - (strpos($EncryptedInvoice, '<error_msg>')+11)));
            
            }
        
        if(!$socket_creation_err && !$invoice_sent_error && !$encryption_ERR){
            
        }
         $form_args = array(
		  'encryptedInvoicePay' => $EncryptedInvoice
		  );   
        //==============================
        
        $form_args_array = array();
        foreach($form_args as $key => $value){
          $form_args_array[] = "<input type='hidden' name='$key' value='$value'/>";
        }
        return '<p>'.$percentage_msg.'</p>
		<p>Total amount will be <b>'.$curr_symbole.' '.number_format(($order->order_total)).'</b></p>
		<form action="'.$this->ipg_server_url.'" method="post" id="send_form" name="send_form">
            ' . implode('', $form_args_array) . '
            <input type="submit" class="button-alt" id="submit_ipg_payment_form" value="'.__('Pay via Credit Card', 'ognro').'" /> 
			<a class="button cancel" href="'.$order->get_cancel_order_url().'">'.__('Cancel order &amp; restore cart', 'ognro').'</a>            
            </form>
            <script type="text/javascript">
                document.getElementById("send_form").submit();
            </script>'; 
    }
    	
    function process_payment($order_id){
        $order = new WC_Order($order_id);
        return array('result' => 'success', 'redirect' => add_query_arg('order',           
		   $order->id, add_query_arg('key', $order->order_key, get_permalink(woocommerce_get_page_id('pay' ))))
        );
    }
 
   	 
    function check_NTBIPG_response(){        
        global $wpdb;
        global $woocommerce;
        
        $IPGClientIP = "127.0.0.1";
        $IPGClientPort = "10000";
        
        $ERRNO = "";
        $ERRSTR = "";
        $SOCKET_TIMEOUT = 2;
        $IPGSocket = "";
        
        $EncryptedReceipt = "";
        $DecryptedReceipt = "";
        
        $error_message = "";
        $encrypted_rcpt_sent_error = "";
        $encryptedRcpt_ERR = "";
        $decryptedRcpt_ERR = "";

        if(isset($_POST['encryptedReceiptPay'])){			
            $EncryptedReceipt = $_POST["encryptedReceiptPay"];

            if($EncryptedReceipt == "") {
                $error_message .= "Could not find Encrypted Receipt";
                $encryptedRcpt_ERR = true;
            }
            
        /**
        * Step 1 : Create the socket connection with IPG client
        */
            if(!$encryptedRcpt_ERR) {
                if ($IPGClientIP != "" && $IPGClientPort != "") {
                    $IPGSocket = fsockopen($IPGClientIP, $IPGClientPort, $ERRNO, $ERRSTR, $SOCKET_TIMEOUT);
                } else {
                    $error_message = "Could not establish a socket connection for given IPGClientIP = ". $IPGClientIP . "and IPGClientPort = ".$IPGClientPort; 
                    $socket_creation_err = true;
                }      
            }
            
        /**
        * Step 2 : Send Encrypted Receipt to IPG client 
        */
        
            if(!$socket_creation_err && !$encryptedRcpt_ERR) {
                socket_set_timeout($IPGSocket, $SOCKET_TIMEOUT);
        
                // Write the encrypted receipt to socket connection
                if(fwrite($IPGSocket,$EncryptedReceipt) === false) {
                    $error_message .= "Encrypted Receipt could not be written to socket connection";
                    $encrypted_rcpt_sent_error = true;
                }
            }
            
        /**
        * Step 3 : Recieve the decrypted Receipt from IPG client
        */
        
            if(!$socket_creation_err && !$encrypted_rcpt_sent_error) {
                while (!feof($IPGSocket)) {
                    $DecryptedReceipt .= fread($IPGSocket, 8192);
                }    
            }
            
        /**
        * Step 4 : Close the socket connection
        */
            if(!$socket_creation_err) {
                fclose($IPGSocket);
            }
                    
        /**
        * Step 5 : Process $DecryptedReceipt
        */
        $Error_code = "";
        $Error_msg = "";
        $Acc_No = "";
        $Action = "";
        $Bank_ref_ID = "";
        $Currency = "";
        $IPG_txn_ID = "";
        $Lang = "";
        $Merchant_txn_ID = "";
        $Merchant_var1 = "";
        $Merchant_var2 = "";
        $Merchant_var3 = "";
        $Merchant_var4 = "";
        $Name = "";
        $Reason = "";
        $Transaction_amount = "";
        $Transaction_status = "";

    if (!(strpos($DecryptedReceipt, '<error_code>') === false && strpos($DecryptedReceipt, '</error_code>') === false && strpos($DecryptedReceipt, '<error_msg>') === false && strpos($DecryptedReceipt, '</error_msg>') === false)) {
        $decryptedRcpt_ERR = true;
        
        $Error_code = substr($DecryptedReceipt, (strpos($DecryptedReceipt, '<error_code>')+12), (strpos($DecryptedReceipt, '</error_code>') - (strpos($DecryptedReceipt, '<error_code>')+12)));
    
        $Error_msg = substr($DecryptedReceipt, (strpos($DecryptedReceipt, '<error_msg>')+11), (strpos($DecryptedReceipt, '</error_msg>') - (strpos($DecryptedReceipt, '<error_msg>')+11)));
    
    } else {
    
        if (!(strpos($DecryptedReceipt, '<acc_no>') === false && strpos($DecryptedReceipt, '</acc_no>') === false)) {
            $Acc_No = substr($DecryptedReceipt, (strpos($DecryptedReceipt, '<acc_no>')+8), (strpos($DecryptedReceipt, '</acc_no>') - (strpos($DecryptedReceipt, '<acc_no>')+8)));
        }
        
        if (!(strpos($DecryptedReceipt, '<action>') === false && strpos($DecryptedReceipt, '</action>') === false)) {
            $Action = substr($DecryptedReceipt, (strpos($DecryptedReceipt, '<action>')+8), (strpos($DecryptedReceipt, '</action>')-(strpos($DecryptedReceipt, '<action>')+8)));
        }
        
        if (!(strpos($DecryptedReceipt, '<bank_ref_id>') === false && strpos($DecryptedReceipt, '</bank_ref_id>') === false)) {
            $Bank_ref_ID = substr($DecryptedReceipt, (strpos($DecryptedReceipt, '<bank_ref_id>')+13), (strpos($DecryptedReceipt, '</bank_ref_id>')-(strpos($DecryptedReceipt, '<bank_ref_id>')+13)));
        }
        
        if (!(strpos($DecryptedReceipt, '<cur>') === false && strpos($DecryptedReceipt, '</cur>') === false)) {
            $Currency = substr($DecryptedReceipt, (strpos($DecryptedReceipt, '<cur>')+5),(strpos($DecryptedReceipt, '</cur>')-(strpos($DecryptedReceipt, '<cur>')+5)) );
        }
        
        if (!(strpos($DecryptedReceipt, '<ipg_txn_id>') === false && strpos($DecryptedReceipt, '</ipg_txn_id>') === false)) {
            $IPG_txn_ID = substr($DecryptedReceipt, (strpos($DecryptedReceipt, '<ipg_txn_id>')+12),(strpos($DecryptedReceipt, '</ipg_txn_id>')-(strpos($DecryptedReceipt, '<ipg_txn_id>')+12)) );
        }
        
        if (!(strpos($DecryptedReceipt, '<lang>') === false && strpos($DecryptedReceipt, '</lang>') === false)) {
            $Lang = substr($DecryptedReceipt, (strpos($DecryptedReceipt, '<lang>')+6),(strpos($DecryptedReceipt, '</lang>')-(strpos($DecryptedReceipt, '<lang>')+6)) );
        }
        
        if (!(strpos($DecryptedReceipt, '<mer_txn_id>') === false && strpos($DecryptedReceipt, '</mer_txn_id>') === false)) {
            $Merchant_txn_ID = substr($DecryptedReceipt, (strpos($DecryptedReceipt, '<mer_txn_id>')+12),(strpos($DecryptedReceipt, '</mer_txn_id>')-(strpos($DecryptedReceipt, '<mer_txn_id>')+12)) );
        }
        
        if (!(strpos($DecryptedReceipt, '<mer_var1>') === false && strpos($DecryptedReceipt, '</mer_var1>') === false)) {
            $Merchant_var1 = substr($DecryptedReceipt, (strpos($DecryptedReceipt, '<mer_var1>')+10),(strpos($DecryptedReceipt, '</mer_var1>')-(strpos($DecryptedReceipt, '<mer_var1>')+10)) );
        }
        
        if (!(strpos($DecryptedReceipt, '<mer_var2>') === false && strpos($DecryptedReceipt, '</mer_var2>') === false)) {
            $Merchant_var2 = substr($DecryptedReceipt, (strpos($DecryptedReceipt, '<mer_var2>')+10),(strpos($DecryptedReceipt, '</mer_var2>')-(strpos($DecryptedReceipt, '<mer_var2>')+10)) );
        }
        
        if (!(strpos($DecryptedReceipt, '<mer_var3>') === false && strpos($DecryptedReceipt, '</mer_var3>') === false)) {
            $Merchant_var3 = substr($DecryptedReceipt, (strpos($DecryptedReceipt, '<mer_var3>')+10),(strpos($DecryptedReceipt, '</mer_var3>')-(strpos($DecryptedReceipt, '<mer_var3>')+10)) );
        }
        
        if (!(strpos($DecryptedReceipt, '<mer_var4>') === false && strpos($DecryptedReceipt, '</mer_var4>') === false)) {
            $Merchant_var4 = substr($DecryptedReceipt, (strpos($DecryptedReceipt, '<mer_var4>')+10),(strpos($DecryptedReceipt, '</mer_var4>')-(strpos($DecryptedReceipt, '<mer_var4>')+10)) );
        }
        
        if (!(strpos($DecryptedReceipt, '<name>') === false && strpos($DecryptedReceipt, '</name>') === false)) {
            $Name = substr($DecryptedReceipt, (strpos($DecryptedReceipt, '<name>')+6),(strpos($DecryptedReceipt, '</name>')-(strpos($DecryptedReceipt, '<name>')+6)) );
        }
        
        if (!(strpos($DecryptedReceipt, '<reason>') === false && strpos($DecryptedReceipt, '</reason>') === false)) {
            $Reason = substr($DecryptedReceipt, (strpos($DecryptedReceipt, '<reason>')+8),(strpos($DecryptedReceipt, '</reason>')-(strpos($DecryptedReceipt, '<reason>')+8)) );
        }
        
        if (!(strpos($DecryptedReceipt, '<txn_amt>') === false && strpos($DecryptedReceipt, '</txn_amt>') === false)) {
            $Transaction_amount = substr($DecryptedReceipt, (strpos($DecryptedReceipt, '<txn_amt>')+9),(strpos($DecryptedReceipt, '</txn_amt>')-(strpos($DecryptedReceipt, '<txn_amt>')+9)) );
        }
        
        if (!(strpos($DecryptedReceipt, '<txn_status>') === false && strpos($DecryptedReceipt, '</txn_status>') === false)) {
            $Transaction_status = substr($DecryptedReceipt, (strpos($DecryptedReceipt, '<txn_status>')+12),(strpos($DecryptedReceipt, '</txn_status>')-(strpos($DecryptedReceipt, '<txn_status>')+12)) );
        }
    }


        /**
        * Step 6 : Finish Transaction
        */
        
        $order 	= new WC_Order($order_id);
        $order_id = $Merchant_txn_ID;
        
        if(!$socket_creation_err && !$encrypted_rcpt_sent_error && !$decryptedRcpt_ERR) {
            if($Transaction_status == $this->sucess_responce_code) {
                
                $table_name = $wpdb->prefix . 'ntb_ipg';	
				$wpdb->update( 
				$table_name, 
				array( 
					'bank_reference_id' => $Bank_ref_ID,				
					'ipg_transaction_id' => $IPG_txn_ID,
					'transaction_name' => $Name,
					'amount' => $Transaction_amount,
                    'transaction_status' => $Transaction_status,
                    'transaction_reason' => $Reason                   
				), 
				array( 'merchant_transaction_id' => $Merchant_txn_ID ));
                
                $order 	= new WC_Order($order_id);
                $order->update_status('Processing', 'order_note');                                
                

                $woocommerce->cart->empty_cart();                
                $mailer = $woocommerce->mailer();
				$admin_email = get_option( 'admin_email', '' );

//$message = $mailer->wrap_message(__( 'Order confirmed','woocommerce'),sprintf(__('Order '.$order->get_order_number().' has been confirmed', 'woocommerce' ), $Merchant_txn_ID, $posted['reason_code']));	
//$mailer->send( $admin_email, sprintf( __( 'Payment for order %s confirmed', 'woocommerce' ), $Merchant_txn_ID ), $message );					
					
//$message = $mailer->wrap_message(__( 'Order confirmed','woocommerce'),sprintf(__('Order '.$order->get_order_number().' has been confirmed', 'woocommerce' ), $order->get_order_number(), $posted['reason_code']));	
//$mailer->send( $order->billing_email, sprintf( __( 'Payment for order %s confirmed', 'woocommerce' ), $order->get_order_number() ), $message );
                
                                         
                $order->payment_complete();

global $woocommerce;
$mailer = $woocommerce->mailer();
// Email customer with order-processing receipt
$email = $mailer->emails['WC_Email_Customer_Processing_Order'];
$email->trigger( $order_id );
// Email admin with new order email
$email = $mailer->emails['WC_Email_New_Order'];
$email->trigger( $order_id );

                wp_redirect( $this->responce_url_sucess );
                exit();
            }else {
                global $wpdb;
                $order->update_status('Failed');
                $order->add_order_note('Failed - Code'.$Reason);
                $order->add_order_note($this->msg['message']);
                
                $table_name = $wpdb->prefix . 'ntb_ipg';
                
                $myrows = $wpdb->get_results( "SELECT id FROM ".$table_name." WHERE merchant_transaction_id=".$Merchant_txn_ID." ORDER BY id DESC" );                
				$wpdb->update( 
				$table_name, 
				array( 
					'bank_reference_id' => $Bank_ref_ID,				
					'ipg_transaction_id' => $IPG_txn_ID,
					'transaction_name' => $Name,					
                    'transaction_status' => $Transaction_status,
                    'amount' => $Transaction_amount,
                    'transaction_reason' => $Reason
                    
				), 
				array( 'id' => $myrows[0]->id ));                                
                wp_redirect( $this->responce_url_fail ); exit;
                exit();
                
            }
        }
        			
		}
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


if(isset($_POST['encryptedReceiptPay'])){
	$WC = new WC_NTB();
}
   
   function woocommerce_add_ntb_gateway($methods) {
       $methods[] = 'WC_NTB';
       return $methods;
   }
	 	
    add_filter('woocommerce_payment_gateways', 'woocommerce_add_ntb_gateway' );
}

	global $jal_db_version;
	$jal_db_version = '1.0';
	
	function jal_install_ntb() {

		$plugin_path = plugin_dir_path( __FILE__ );
		$file = $plugin_path.'includes/auth.php';
		if(file_exists($file)){
			include 'includes/auth.php';
			$auth = new Auth();
			$auth->check_auth();
			if ( !$auth->get_status() ) {
				deactivate_plugins( plugin_basename( __FILE__ ) );
				if($auth->get_code() == 2){
					wp_die( "<h1>".ucfirst($auth->get_message())."</h1><br>Visit <a href='http://www.oganro.com/plugins/profile'>www.oganro.com/plugins/profile</a> and change the domain" ,"Activation Error","ltr" );
				}else{
					wp_die( "<h1>".ucfirst($auth->get_message())."</h1><br>Visit <a href='http://www.oganro.com/wordpress-plug-in-support'>www.oganro.com/wordpress-plug-in-support</a> for more info" ,"Activation Error","ltr" );
				}
			}
		}else{
			deactivate_plugins( plugin_basename( __FILE__ ) );
			$path =  plugin_basename( __FILE__ );
			$dir  = explode("/", $path);
			wp_die( "<h1>Buy serial key to activate this plugin</h1><br><a href='http://www.oganro.com/wordpress-plug-in-support'><img src=".site_url('wp-content/plugins/'.$dir[0].'/support.jpg')." style='width:700px;height:auto;' /></a><p>Visit <a href='http://www.oganro.com/plugins'>www.oganro.com/plugins</a> to buy this plugin<p>" ,"Activation Error","ltr" );
		}	
		global $wpdb;
		global $jal_db_version;
	
		$table_name = $wpdb->prefix . 'ntb_ipg';
		$charset_collate = '';
	
		if ( ! empty( $wpdb->charset ) ) {
		  $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
		}
	
		if ( ! empty( $wpdb->collate ) ) {
		  $charset_collate .= " COLLATE {$wpdb->collate}";
		}
	
		$sql = "CREATE TABLE $table_name (
					id int(9) NOT NULL AUTO_INCREMENT,
                    merchant_transaction_id int(9) NOT NULL,
					bank_reference_id VARCHAR(20) NOT NULL,
					currency_code VARCHAR(20) NOT NULL,
                    ipg_transaction_id VARCHAR(20) NOT NULL,
                    transaction_name VARCHAR(20) NOT NULL,
                    transaction_reason TEXT NOT NULL,
                    transaction_amount VARCHAR(20) NOT NULL,
                    transaction_status VARCHAR(20) NOT NULL,                    
                    amount VARCHAR(20) NOT NULL,
					or_date DATE NOT NULL,										
					UNIQUE KEY id (id)
				) $charset_collate;";
        		
	
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	
		add_option( 'jal_db_version', $jal_db_version );
	}
	
	function jal_install_data_ntb() {
		global $wpdb;
		
		$welcome_name = 'NTB IPG';
		$welcome_text = 'Congratulations, you just completed the installation!';
		
		$table_name = $wpdb->prefix . 'ntb_ipg';
		
		$wpdb->insert( 
			$table_name, 
			array( 
				'time' => current_time( 'mysql' ), 
				'name' => $welcome_name, 
				'text' => $welcome_text, 
			) 
		);
	}
	
	register_activation_hook( __FILE__, 'jal_install_ntb' );
	register_activation_hook( __FILE__, 'jal_install_data_ntb' );