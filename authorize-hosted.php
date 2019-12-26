<?php
/*
Plugin Name: Authorize.net Hosted Payment Gateway For WooCommerce
Description: Extends WooCommerce to Process Payments with Authorize.net Hosted gateway
Version: 1.0
Author: Isuru Ratnayake
Author URI: http://www.ratnayake.info
License: Under GPL2   

*/

add_action('plugins_loaded', 'woocommerce_tech_authohosted_init', 0);

function woocommerce_tech_authohosted_init() {

   if ( !class_exists( 'WC_Payment_Gateway' ) ) 
      return;

   /**
   * Localisation
   */
   load_plugin_textdomain('wc-tech-authohosted', false, dirname( plugin_basename( __FILE__ ) ) . '/languages');
   
   /**
   * Authorize.net Hosted Payment Gateway class
   */
   class WC_Tech_Authohosted extends WC_Payment_Gateway 
   {
      protected $msg = array();
      
      public function __construct(){

         $this->id               = 'authorizehosted';
         $this->method_title     = __('Authorize.net Hosted', 'wc-tech-authohosted');
         $this->icon             = WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/images/logo.gif';
         $this->has_fields       = false;
         $this->init_form_fields();
         $this->init_settings();
         $this->title            = $this->settings['title'];
         $this->description      = $this->settings['description'];
         $this->login            = $this->settings['login_id'];
         $this->mode             = $this->settings['working_mode'];
         $this->transaction_key  = $this->settings['transaction_key'];
         $this->success_message  = $this->settings['success_message'];
         $this->failed_message   = $this->settings['failed_message'];
         $this->liveurl          = 'https://api.authorize.net/xml/v1/request.api';
		 $this->liveurl2 = "https://accept.authorize.net/payment/payment";
         $this->testurl          = 'https://apitest.authorize.net/xml/v1/request.api';
		 $this->testurl2 = "https://test.authorize.net/payment/payment";
         $this->msg['message']   = "";
         $this->msg['class']     = "";
        
         
         
         if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
             add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
          } else {
             add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
         }

         add_action('woocommerce_receipt_authorizehosted', array(&$this, 'receipt_page'));
         add_action('woocommerce_thankyou_authorizehosted',array(&$this, 'thankyou_page'));
      }

      function init_form_fields()
      {

         $this->form_fields = array(
            'enabled'      => array(
                  'title'        => __('Enable/Disable', 'wc-tech-authohosted'),
                  'type'         => 'checkbox',
                  'label'        => __('Enable Authorize.net Hosted Payment Module.', 'wc-tech-authohosted'),
                  'default'      => 'no'),
            'title'        => array(
                  'title'        => __('Title:', 'wc-tech-authohosted'),
                  'type'         => 'text',
                  'description'  => __('This controls the title which the user sees during checkout.', 'wc-tech-authohosted'),
                  'default'      => __('Authorize.net Hosted', 'wc-tech-authohosted')),
            'description'  => array(
                  'title'        => __('Description:', 'wc-tech-authohosted'),
                  'type'         => 'textarea',
                  'description'  => __('This controls the description which the user sees during checkout.', 'wc-tech-authohosted'),
                  'default'      => __('Pay securely by Credit or Debit Card through Authorize.net Hosted Secure Servers.', 'wc-tech-authohosted')),
            'login_id'     => array(
                  'title'        => __('Login ID', 'wc-tech-authohosted'),
                  'type'         => 'text',
                  'description'  => __('This is API Login ID')),
            'transaction_key' => array(
                  'title'        => __('Transaction Key', 'wc-tech-authohosted'),
                  'type'         => 'text',
                  'description'  =>  __('API Transaction Key', 'wc-tech-authohosted')),
            'success_message' => array(
                  'title'        => __('Transaction Success Message', 'wc-tech-authohosted'),
                  'type'         => 'textarea',
                  'description'=>  __('Message to be displayed on successful transaction.', 'wc-tech-authohosted'),
                  'default'      => __('Your payment has been procssed successfully.', 'wc-tech-authohosted')),
            'failed_message'  => array(
                  'title'        => __('Transaction Failed Message', 'wc-tech-authohosted'),
                  'type'         => 'textarea',
                  'description'  =>  __('Message to be displayed on failed transaction.', 'wc-tech-authohosted'),
                  'default'      => __('Your transaction has been declined.', 'wc-tech-authohosted')),
            'working_mode'    => array(
                  'title'        => __('API Mode'),
                  'type'         => 'select',
            'options'      => array('false'=>'Live Mode', 'true'=>'Test/Sandbox Mode'),
                  'description'  => "Live/Test Mode" )
         );
      }
      
      /**
       * Admin Panel Options
       * 
      **/
      public function admin_options()
      {
         echo '<h3>'.__('Authorize.net Hosted Payment Gateway', 'wc-tech-authohosted').'</h3>';
         echo '<p>'.__('Authorize.net Hosted is most popular payment gateway for online payment processing').'</p>';
         echo '<table class="form-table">';
         $this->generate_settings_html();
         echo '</table>';

      }
	  
	  	public function get_return_url( $order_id = null ) {
			$order = new WC_Order($order_id);
		if ( $order ) {
			$return_url = $order->get_checkout_order_received_url();
		} else {
			$return_url = wc_get_endpoint_url( 'order-received', '', wc_get_page_permalink( 'checkout' ) );
		}

		if ( is_ssl() || get_option( 'woocommerce_force_ssl_checkout' ) == 'yes' ) {
			$return_url = str_replace( 'http:', 'https:', $return_url );
		}

		return apply_filters( 'woocommerce_get_return_url', $return_url, $order );
	}
	  
      public function thankyou_page($order_id) 
      {
		  //var_dump($order_id);
		  //var_dump($_GET);
		  //var_dump($_POST);

			//echo '<p>'.__('Thank you for your order.', 'wc-tech-authohosted').'</p>';
			$order = new WC_Order($order_id);
			$order->payment_complete();
			$order->add_order_note("Your payment has been procssed successfully.This transaction has been approved.Transaction ID: " . $_GET['trn_id'] .  " Please verify with authorize.net before do the shipment");
      }
      
      
      /**
      * Receipt Page
      **/
      function receipt_page($order_id)
      {
         //echo '<p>'.__('Thank you for your order.', 'wc-tech-authohosted').'</p>';
		 
		$order = new WC_Order($order_id);
		$poNumber = $order_id . time();
		$total = $order->data["total"];
		$customer_ip_address = $order->data["customer_ip_address"];
	  //var_dump($order);
	  //exit();
	          $xmlStr = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<getHostedPaymentPageRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
    <merchantAuthentication></merchantAuthentication>
    <transactionRequest>
        <transactionType>authCaptureTransaction</transactionType>
        <amount>$total</amount>
        <order>
            <invoiceNumber>$order_id</invoiceNumber>
            <description>Order # $order_id</description>
        </order>
        <poNumber>$poNumber</poNumber>
        <customerIP>$customer_ip_address</customerIP>
    </transactionRequest>
    <hostedPaymentSettings>
        <setting>
            <settingName>hostedPaymentIFrameCommunicatorUrl</settingName>
        </setting>
        <setting>
            <settingName>hostedPaymentButtonOptions</settingName>
            <settingValue>{"text": "Pay"}</settingValue>
        </setting>
        <setting>
            <settingName>hostedPaymentReturnOptions</settingName>
        </setting>
        <setting>
            <settingName>hostedPaymentOrderOptions</settingName>
            <settingValue>{"show": false}</settingValue>
        </setting>
        <setting>
            <settingName>hostedPaymentPaymentOptions</settingName>
            <settingValue>{"cardCodeRequired": true}</settingValue>
        </setting>
        <setting>
            <settingName>hostedPaymentBillingAddressOptions</settingName>
            <settingValue>{"show": true, "required":true}</settingValue>
        </setting>
        <setting>
            <settingName>hostedPaymentShippingAddressOptions</settingName>
            <settingValue>{"show": false, "required":false}</settingValue>
        </setting>
        <setting>
            <settingName>hostedPaymentSecurityOptions</settingName>
            <settingValue>{"captcha": false}</settingValue>
        </setting>
        <setting>
            <settingName>hostedPaymentStyleOptions</settingName>
            <settingValue>{"bgColor": "green"}</settingValue>
        </setting>
        <setting>
            <settingName>hostedPaymentCustomerOptions</settingName>
            <settingValue>{"showEmail": true, "requiredEmail":true}</settingValue>
        </setting>
    </hostedPaymentSettings>
</getHostedPaymentPageRequest>
XML;
        $xml = simplexml_load_string($xmlStr, '\SimpleXMLElement', LIBXML_NOWARNING);
$xml = new \SimpleXMLElement($xmlStr,LIBXML_NOWARNING);
        $xml->merchantAuthentication->addChild('name', $this->login);
        $xml->merchantAuthentication->addChild('transactionKey', $this->transaction_key);
        $commUrl = json_encode(array('url' => plugin_dir_url( __FILE__ ) . "/html/IFrameCommunicator.html" ), JSON_UNESCAPED_SLASHES);
        $xml->hostedPaymentSettings->setting[0]->addChild('settingValue', $commUrl);
        $retUrl = json_encode(array("showReceipt" => false , 'url' => explode('&',$this->get_return_url($order_id))[0], "urlText"=>"Continue to site", "cancelUrl" => site_url() ."/checkout/", "cancelUrlText" => "Cancel" ), JSON_UNESCAPED_SLASHES);
        //var_dump($retUrl);
		$xml->hostedPaymentSettings->setting[2]->addChild('settingValue', $retUrl);
		if($this->mode)
		{
			$url = $this->liveurl;
		}
		else{
			$url = $this->testurl;
		}
        
        try {   //setting the curl parameters.
            $ch = curl_init();
            if (false === $ch) {
                throw new Exception('failed to initialize');
            }
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/xml'));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xml->asXML());
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 300);
            // The following two curl SSL options are set to "false" for ease of development/debug purposes only.
            // Any code used in production should either remove these lines or set them to the appropriate
            // values to properly use secure connections for PCI-DSS compliance.
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);    //for production, set value to true or 1
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);    //for production, set value to 2
            curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, false);
            //curl_setopt($ch, CURLOPT_PROXY, 'userproxy.visa.com:80');
            $content = curl_exec($ch);
            $content = str_replace('xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd"', '', $content);
            $hostedPaymentResponse = new \SimpleXMLElement($content);
            if (false === $content) {
                throw new Exception(curl_error($ch), curl_errno($ch));
            }
            curl_close($ch);
			//var_dump($hostedPaymentResponse);
			if($hostedPaymentResponse->messages->resultCode == "Ok"){
				//var_dump($hostedPaymentResponse);
					echo "<div id=\iframe_holder\" class=\"center-block\" style=\"width:90%;max-width: 1000px\">
	<iframe id=\"add_payment\" class=\"embed-responsive-item panel\" name=\"add_payment\" width=\"100%\"    frameborder=\"0\" scrolling=\"no\" hidden=\"true\">
	</iframe>
</div>";
		if($this->mode)
		{
			$accept_url = $this->liveurl2;
		}
		else{
			$accept_url = $this->testurl2;
		}

echo "<form id=\"send_token\" action=\"" . $accept_url . "\" method=\"post\" target=\"add_payment\">
	<input type=\"hidden\" name=\"token\" value=\"". $hostedPaymentResponse->token . "\" />
</form>
    <script>
	jQuery(document).ready(function(){
		jQuery(\"#add_payment\").show();
		jQuery(\"#send_token\").submit();
		});
		
					(function () {
			if (!window.AuthorizeNetIFrame) window.AuthorizeNetIFrame = {};
				AuthorizeNetIFrame.onReceiveCommunication = function (querystr) {
					var params = parseQueryString(querystr);
						switch (params[\"action\"]) {
							case \"successfulSave\":
								break;
							case \"cancel\":
								var ifrm = document.getElementById(\"add_payment\");
								ifrm.style.display = 'none';
								window.location.replace(\"" . site_url() ."/checkout/". "\");
							case \"resizeWindow\":
								var w = parseInt(params[\"width\"]);
								var h = parseInt(params[\"height\"]);
								var ifrm = document.getElementById(\"add_payment\");
								ifrm.style.width = w.toString() + \"px\";
								ifrm.style.height = h.toString() + \"px\";
								break;
							case \"transactResponse\":
								var ifrm = document.getElementById(\"add_payment\");
								ifrm.style.display = 'none';
								obj_response = jQuery.parseJSON(params[\"response\"]);
								if(parseInt(obj_response.responseCode) == 1){
									window.location.replace(\"" . $this->get_return_url($order_id) . '&trn_id='. "\" + obj_response.transId );
								}
							}
					};

				function parseQueryString(str) {
					var vars = [];
					var arr = str.split('&');
					var pair;
					for (var i = 0; i < arr.length; i++) {
						pair = arr[i].split('=');
						vars.push(pair[0]);
						vars[pair[0]] = unescape(pair[1]);
						}
					return vars;
					}
		}());
	
	</script>
";
			}
        } catch (Exception $e) {
            trigger_error(sprintf('Curl failed with error #%d: %s', $e->getCode(), $e->getMessage()), E_USER_ERROR);
        }
		
      }
      
      /**
       * Process the payment and return the result
      **/
      function process_payment($order_id)
      {
            $order = new WC_Order($order_id);
            return array('result' => 'success', 'redirect' => add_query_arg('order',
                $order_id, add_query_arg('key', $order->order_key, get_permalink(wc_get_page_id('pay'))))
            );
      }

      
   }

   /**
    * Add this Gateway to WooCommerce
   **/
   function woocommerce_add_tech_authohosted_gateway($methods) 
   {
      $methods[] = 'WC_Tech_Authohosted';
      return $methods;
   }

   add_filter('woocommerce_payment_gateways', 'woocommerce_add_tech_authohosted_gateway' );
}
