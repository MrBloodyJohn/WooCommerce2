<?php

class WC_Gateway_Dotpay extends WC_Gateway_Dotpay_Abstract {

    /**
     * initialise gateway with custom settings
     */
    public function __construct() {
        parent::__construct();
    }

    public function process_payment($order_id) {
        global $woocommerce;

        $order = new WC_Order($order_id);

        $order->reduce_order_stock();

        $woocommerce->cart->empty_cart();

        return array(
            'result' => 'success',
            'redirect' => $order->get_checkout_payment_url(true)
        );
    }

    public function receipt_page($order) {
        $orderRecord = new WC_Order($order);
        
        /**
         * 
         */
        $this->agreementByLaw = $this->getDotpayAgreement($orderRecord, 'bylaw');
        $this->agreementPersonalData = $this->getDotpayAgreement($orderRecord, 'personal_data');
        
        echo $this->generate_dotpay_form($order);
    }
    
    protected function generate_dotpay_form($order_id) {
        /**
         * 
         */
        $agreements = array(
            'bylaw' => $this->agreementByLaw,
            'personal_data' => $this->agreementPersonalData,
        );
        
        /**
         * hidden fields One-Click, MasterPass, BLIK, Dotpay
         */
        $hiddenFields = array();
        
        /**
         * One-Click Cards
         */
        $dbOneClick = new WC_Gateway_Dotpay_Oneclick();
        $cardList = $dbOneClick->card_list($this->getUserId());
        foreach($cardList as $cardV) {
            $oneclick = array(
                'active' => $this->isDotOneClick(),
                'fields' => $this->getHiddenFieldsOneClickCard($order_id, $cardV['oneclick_card_hash'], $cardV['oneclick_card_id']),
                'agreements' => $agreements,
                'icon' => $this->getIconOneClick(),
                'text' => 'One-Click',
                'text2' => "{$cardV['oneclick_card_title']}",
            );
            
            $hiddenFields["oneclick_card_{$cardV['oneclick_id']}"] = $oneclick;
        }
        
        /**
         * One-Click Register
         */
        $hiddenFields["oneclick_register"] = array(
            'active' => $this->isDotOneClick(),
            'fields' => $this->getHiddenFieldsOneClickRegister($order_id),
            'agreements' => $agreements,
            'icon' => $this->getIconOneClick(),
            'text' => 'One-Click',
            'text2' => __('Card register', 'dotpay-payment-gateway'),
        );

        /**
         * MasterPass
         */
        $hiddenFields['mp'] = array(
            'active' => $this->isDotMasterPass(),
            'fields' => $this->getHiddenFieldsMasterPass($order_id),
            'agreements' => $agreements,
            'icon' => $this->getIconMasterPass(),
            'text' => 'MasterPass (First Data Polska S.A.)',
            'text2' => '',
        );
        
        /**
         * BLIK
         */
        $hiddenFields['blik'] = array(
            'active' => $this->isDotBlik(),
            'fields' => $this->getHiddenFieldsBlik($order_id),
            'agreements' => $agreements,
            'icon' => $this->getIconBLIK(),
            'text' => 'BLIK (Polski Standard Płatności Sp. z o.o.)',
            'text2' => '',
        );
        
        /**
         * Dotpay
         */
        $hiddenFields['dotpay'] = array(
            'active' => $this->isDotWidget(),
            'fields' => $this->getHiddenFieldsDotpay($order_id),
            'agreements' => $agreements,
            'icon' => $this->getIconDotpay(),
            'text' => '',
            'text2' => '',
        );
        
        /**
         * 
         */
        $dotpay_url = $this->getDotpayUrl();
        
        /**
         * url build signature
         */
        $signature_url = str_replace('https:', 'http:', add_query_arg('wc-api', 'WC_Gateway_Dotpay_2', home_url('/')));
        
        /**
         * 
         */
        if($this->isDotOneClick() || $this->isDotMasterPass() || $this->isDotBlik() || $this->isDotWidget()) {
            /**
             * 
             */
            $tagP = __('You chose payment by Dotpay. Select a payment channel and click Continue do proceed', 'dotpay-payment-gateway');
            $message = esc_js(__('Thank you for your order. We are now redirecting you to channel payment.', 'dotpay-payment-gateway'));
        } else {
            $tagP = __('You chose payment by Dotpay. Click Continue do proceed', 'dotpay-payment-gateway');
            $message = esc_js(__('Thank you for your order. We are now redirecting you to Dotpay to make payment.', 'dotpay-payment-gateway'));
        }
        
        /**
         * 
         */
        if($this->isDotSecurity()) {
            foreach($hiddenFields as $key => $val) {
                $chk = $this->buildSignature4Request($hiddenFields, $key);
                
                if(!isset($_SESSION['hiddenFields'])) {
                    $_SESSION['hiddenFields'] = array();
                }
                
                $_SESSION['hiddenFields'][$key] = $val;
                
                $hiddenFields[$key]['fields']['chk'] = $chk;
            }
        }
        
        /**
         * js code
         */
        ob_start();
        WC_Gateway_Dotpay_Include('/includes/block-ui.js.phtml', array(
            'oneclick' => $this->isDotOneClick(),
            'mp' => $this->isDotMasterPass(),
            'blik' => $this->isDotBlik(),
            'widget' => $this->isDotWidget(),
            'message' => $message,
            'signature_url' => $signature_url,
        ));
        $js = ob_get_contents();
        wc_enqueue_js($js);
        ob_end_clean();
        
        /**
         * html code
         */
        ob_start();
        WC_Gateway_Dotpay_Include('/includes/form-redirect.phtml', array(
            'oneclick' => $this->isDotOneClick(),
            'mp' => $this->isDotMasterPass(),
            'blik' => $this->isDotBlik(),
            'blikTxtValid' => __('Only 6 digits', 'dotpay-payment-gateway'),
            'widget' => $this->isDotWidget(),
            'h3' => __('Transaction Details', 'dotpay-payment-gateway'),
            'p' => $tagP,
            'submit' => __('Continue', 'dotpay-payment-gateway'),
            'action' => esc_attr($dotpay_url),
            'hiddenFields' => $hiddenFields,
        ));
        $html = ob_get_contents();
        ob_end_clean();
        
        return $html;
    }
    
    /**
     * 
     */
    public function oneclick_card_register() {
        die(__METHOD__);
    }
    
    /**
     * 
     */
    public function oneclick_card_list() {
        die(__METHOD__);
    }
    
    public function build_dotpay_signature() {
        $chk = '';
        
        $type = isset($_POST['type']) ? $_POST['type'] : '';
        $channel = isset($_POST['channel']) ? $_POST['channel'] : '';
        
        $hiddenFields = isset($_SESSION['hiddenFields']) ? $_SESSION['hiddenFields'] : null;
        
        if($hiddenFields) {
            switch ($type) {
                case 'oneclick_card':
                    $cardHash = isset($_POST['cardhash']) ? $_POST['cardhash'] : '';
                    $chk = $this->buildSignature4Request($hiddenFields, $type, $channel, null, $cardHash);
                    break;
                case 'oneclick_register':
                    $chk = $this->buildSignature4Request($hiddenFields, $type, $channel);
                    break;
                case 'mp':
                    $chk = $this->buildSignature4Request($hiddenFields, $type, $channel);
                    break;
                case 'blik':
                    $blik = isset($_POST['blik']) ? $_POST['blik'] : '';
                    $chk = $this->buildSignature4Request($hiddenFields, $type, $channel, $blik);
                    break;
                case 'dotpay':
                    if(isset($_POST['widget'])) {
                        $widget = (bool) $_POST['widget'];
                        if(!$widget) {
                            $this->dotpayAgreements = false;
                        }
                    }
                    $chk = $this->buildSignature4Request($hiddenFields, $type, $channel);
                    break;
                default:
            } 
        }
        
        die($chk);
    }

    public function check_dotpay_response() {
        $this->checkRemoteIP();
        $this->getPostParams();

        /**
         * check order
         */
        $order = $this->getOrder($this->fieldsResponse['control']);

        /**
         * check currency, amount, email
         */
        $this->checkCurrency($order);
        $this->checkAmount($order);
        $this->checkEmail($order);

        /**
         * check signature
         */
        $this->checkSignature($order);

        /**
         * update status
         */
        $status = $this->fieldsResponse['operation_status'];
        $note = __("Gateway Dotpay send status {$status}.");
        switch ($status) {
            case 'completed':
                $order->update_status('completed', $note);
                break;
            case 'rejected':
                $order->update_status('cancelled', $note);
                break;
            default:
                $order->update_status('processing', $note);
        }

        /**
         * OK
         */
        die('OK');
    }

}
