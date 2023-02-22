<?php

class WC_ZGStripe_Gateway extends WC_Payment_Gateway
{

    public function __construct()
    {
        $this->id = 'zg-stripe'; // payment gateway plugin ID
        $this->icon = ''; // URL of the icon that will be displayed on checkout page near your gateway name
        $this->has_fields = true; // in case you need a custom credit card form
        $this->method_title = 'ZG Stripe Split Payments Gateway';
        $this->method_description = 'ZG Stripe Split Payments Gateway allows customers to use multiple cards to pay the order amount.'; // will be displayed on the options page

        // gateways can support subscriptions, refunds, saved payment methods,
        // but in this tutorial we begin with simple payments
        $this->supports = array(
            'products'
        );

        // Method with all the options fields
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->testmode = 'yes' === $this->get_option('testmode');
        $this->private_key = $this->testmode ? $this->get_option('test_private_key') : $this->get_option('private_key');
        $this->publishable_key = $this->testmode ? $this->get_option('test_publishable_key') : $this->get_option('publishable_key');

        // This action hook saves the settings
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        // We need custom JavaScript to obtain a token
        add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));

        // https://site-url.com/wc-api/zg-stripe
        add_action('woocommerce_api_zg-stripe', array($this, 'webhook'));
        // add_action('wp_footer', array($this, 'append_spinner'));
    }

    public function append_spinner()
    {
        if($this->enabled){
            echo '<canvas id="cards-spinner"></canvas>';
        }
    }

    /**
     * Plugin options, we deal with it in Step 3 too
     */
    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title'       => 'Enable/Disable',
                'label'       => 'Enable ZG Stripe Gateway',
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no'
            ),
            'title' => array(
                'title'       => 'Title',
                'type'        => 'text',
                'description' => 'This controls the title which the customer sees during checkout.',
                'default'     => 'Credit Card',
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => 'Description',
                'type'        => 'textarea',
                'description' => 'This controls the description which the customer sees during checkout.',
                'default'     => 'Pay with multiple credit cards via our ZG stripe payment gateway.',
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
                'title'       => 'Test Secret Key',
                'type'        => 'password',
            ),
            'publishable_key' => array(
                'title'       => 'Live Publishable Key',
                'type'        => 'text'
            ),
            'private_key' => array(
                'title'       => 'Live Secret Key',
                'type'        => 'password'
            )
        );
    }

    public function payment_fields()
    {
        // ok, let's display some description before the payment form
        if ($this->description) {
            // you can instructions for test mode, I mean test card numbers etc.
            if ($this->testmode) {
                $this->description .= ' TEST MODE ENABLED. In test mode, you can use the card numbers listed in <a href="#">documentation</a>.';
                $this->description  = trim($this->description);
            }
            // display the description with <p> tags etc.
            echo wpautop(wp_kses_post($this->description));
        }

        // I will echo() the form, but you can close PHP tags and print it directly in HTML
        echo '<fieldset id="wc-' . esc_attr($this->id) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">';

        // Add this action hook if you want your custom payment gateway to support it
        do_action('woocommerce_credit_card_form_start', $this->id); ?>
        <div class="zg-stripe-main-wrapper">

            <div class="step first-step">
        <h4>Split your payment cross multiple cards.</h4>

                <!-- Wheel picker -->
                <div id="spinner-here">
                    <p>SPlit your payment into how many cards?</p>
                    <canvas id="cards-spinner" height="50px" width="100%"></canvas>
                    <button class="red-btn next-btn" type="button">Next</button>
                </div>
                <!-- End - Wheel picker -->
            </div>

            <div class="step second-step">
            <h4>Enter the amount you would like to pay with your First Card.</h4>
                
            2</div>
        </div>
<?php
        // I recommend to use inique IDs, because other gateways could already use #ccNo, #expdate, #cvc
        // echo '<div class="form-row form-row-wide"><label>Card Number <span class="required">*</span></label>
        //     <input id="misha_ccNo" type="text" autocomplete="off">
        //     </div>
        //     <div class="form-row form-row-first">
        //         <label>Expiry Date <span class="required">*</span></label>
        //         <input id="misha_expdate" type="text" autocomplete="off" placeholder="MM / YY">
        //     </div>
        //     <div class="form-row form-row-last">
        //         <label>Card Code (CVC) <span class="required">*</span></label>
        //         <input id="misha_cvv" type="password" autocomplete="off" placeholder="CVC">
        //     </div>
        //     <div class="clear"></div>';

        do_action('woocommerce_credit_card_form_end', $this->id);

        echo '<div class="clear"></div></fieldset>';
    }

    public function payment_scripts()
    {
        if (!is_cart() && !is_checkout() && !isset($_GET['pay_for_order'])) {
            return;
        }

        // if our payment gateway is disabled, we do not have to enqueue JS too
        if ('no' === $this->enabled) {
            return;
        }

        // no reason to enqueue JavaScript if API keys are not set
        if (empty($this->private_key) || empty($this->publishable_key)) {
            return;
        }
        wp_enqueue_script('zg-number-spinner-picker-js', ZGSTRIPE_PLUGIN_URL . 'core/includes/assets/js/spinner_picker.js', array('jquery'), '1.0', true);
        // wp_enqueue_script('zg-jquery-steps-js', ZGSTRIPE_PLUGIN_URL . 'core/includes/assets/js/jquery.steps.min.js', array('jquery'), '1.0', true);
        wp_register_script('zg-stripe-gateway', ZGSTRIPE_PLUGIN_URL . 'core/includes/assets/js/zg-stripe.js', array('jquery', 'zg-number-spinner-picker-js', 'zg-jquery-steps-js'));
        wp_localize_script('zg-stripe-gateway', 'zg', array(
            'publishableKey' => $this->publishable_key
        ));
        wp_enqueue_script('zg-stripe-gateway');
        wp_enqueue_style('zg-stripe-css', ZGSTRIPE_PLUGIN_URL . 'core/includes/assets/css/zg-stripe.css', array(), '1.0.0');

    }

    public function validate_fields()
    {

        if (empty($_POST['billing_first_name'])) {
            wc_add_notice('First name is required!', 'error');
            return false;
        }
        return true;
    }

    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);
    }

    public function webhook()
    {
        $order = wc_get_order($_GET['id']);
        $order->payment_complete();
        $order->reduce_order_stock();

        update_option('webhook_debug', $_GET);
    }
}
