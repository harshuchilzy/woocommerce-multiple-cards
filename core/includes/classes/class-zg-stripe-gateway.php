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

        // add_action("wp_ajax_ajaxify_cards", array($this, "ajaxify_cards"));
        // add_action("wp_ajax_nopriv_ajaxify_cards", array($this, "ajaxify_cards"));
    }

    public function append_spinner()
    {
        if ($this->enabled) {
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
        do_action('woocommerce_credit_card_form_start', $this->id); 
        $cartTotal = WC()->cart->total;
        ?>
        <script src="https://js.stripe.com/v3/"></script>
        <div class="zg-stripe-main-wrapper">

            <div class="step first-step">
                <h4>Split your payment cross multiple cards.</h4>

                <!-- Wheel picker -->
                <div id="spinner-here">
                    <p>Split your payment into how many cards?</p>
                    <input type="number" name="card_Count" class="form-control card_count" value="1" min="1" max="10"/>
                    <!-- <div id="card-count" class="number-swiper" data-value="1">
                        <ol class="number-swiper-column number-swiper-column-1" data-column="1" data-value="1">
                            <li id="center-1" class="number-swiper-active-number">1</li>
                            <li>2</li>
                            <li>3</li>
                            <li>4</li>
                            <li>5</li>
                            <li>6</li>
                            <li>7</li>
                            <li>8</li>
                            <li>9</li>
                        </ol>
                        <input class="number-swiper-value" type="hidden" min="0" max="10" value="1">
                    </div> -->
                    <div class="button-wrap justify-center">
                        <button class="red-btn next-btn" type="button">Next</button>
                    </div>
                </div>
                <!-- End - Wheel picker -->
            </div>

            <div class="step second-step repeatable-card card-amount-wrap" data-index="1" style="display: none">
                <div>
                    <span class="text-right w-100 inline-block">2 of 8</span>
                    <div class="bar-wrap">
                        <div class="bar w-20"></div>
                    </div>
                </div>
                <h4>Enter the amount you would like to pay with your <b>First Card.</b></h4>
                <div class="step-inner">
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><?php echo get_woocommerce_currency_symbol(); ?></span>
                        </div>
                        <input type="number" data-name="card_amount" name="card[1][card_amount]" class="form-control card-val" step="0.01" placeholder="100" max="<?php echo $cartTotal ?>">
                    </div>
                    <div class="predefine-value-wrapper">
                        <?php
                        
                        // for ($i = $cartTotal / 4; $i <= $cartTotal; $i += $cartTotal / 4) {
                        //     $value = number_format(round($i - 1, 0, PHP_ROUND_HALF_DOWN), 2);
                        //     echo "<button class='pink-btn assign-value' value='{$value}' type='button'>" . wc_price($value) . "</button>";
                        // }
                        echo "<button class='pink-btn assign-value' value='50' type='button' ". ($cartTotal <= 50 ? 'disabled' : '') .">" . wc_price(50) . "</button>";
                        echo "<button class='pink-btn assign-value' value='100' type='button' ". ($cartTotal <= 100 ? 'disabled' : '') .">" . wc_price(100) . "</button>";
                        echo "<button class='pink-btn assign-value' value='250' type='button' ". ($cartTotal <= 250 ? 'disabled' : '') .">" . wc_price(250) . "</button>";
                        echo "<button class='pink-btn assign-value' value='500' type='button' ". ($cartTotal <= 500 ? 'disabled' : '') .">" . wc_price(500) . "</button>";

                        ?>
                    </div>

                    <div class="amount-left-to-pay">
                        <span>Amount left to pay</span>
                        <span class="amount-to-pay"><?php echo wc_price(WC()->cart->total); ?></span>
                    </div>
                </div>
                <div class="button-wrap">
                    <button class="red-btn prev-btn" type="button"><i class="fas fa-chevron-left"></i> Back</button>
                    <button class="red-btn next-btn" type="button" disabled>Next</button>
                </div>
            </div>

            <div class="step second-step repeatable-card card-element-wrap" data-index="1" style="display: none">
                <div>
                    <span class="text-right w-100 inline-block">2 of 8</span>
                    <div class="bar-wrap">
                        <div class="bar w-40"></div>
                    </div>
                </div>
                <h4>This card will be charged <b class="card-chargable">0</b></h4>
                <div class="step-inner">
                    <div class="card-element">
                        <div class="form-row form-row-wide"><label>Card Number</label>
                            <input class="card_ccNo" data-name="card_number" name="card[1][card_number]" type="text" autocomplete="off">
                        </div>
                        <div class="form-row form-row-first">
                            <label>Expiry Date</label>
                            <input class="card_expdate" maxlength="5" data-name="card_expiry" name="card[1][card_expiry]" type="text" autocomplete="off" placeholder="MM / YY">
                        </div>
                        <div class="form-row form-row-last">
                            <label>Cvv</label>
                            <input class="card_cvv" type="password" data-name="card_csv" name="card[1][card_csv]" autocomplete="off" placeholder="CVC">
                        </div>
                        <div class="clear"></div>

                        <div class="amount-left-to-pay">
                            <span>Amount left to pay</span>
                            <span class="card-amount-to-pay"><?php echo wc_price(WC()->cart->total); ?></span>
                        </div>
                    </div>
                    <div class="process-elements" style="display:none">
                        <div class="zg-card-processing">
                            <div style="width: 150px; margin: 0 auto;padding: 45px; border-radius: 100%; border: solid 2px #ccc">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="#999" class="w-6 h-6" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                                </svg>
                            </div>
                            <p class="text-center">Processing</p>
                        </div>

                        <div class="zg-card-error zg-card-stat-note" style="display:none">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="0.3" stroke="#bc3017" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-center">Please use another card. <span id="error-msg"></span></p>
                        </div>
                        <div class="zg-card-success zg-card-stat-note" style="display:none">
                            <div style="width: 150px; margin: 0 auto;padding: 45px; border-radius: 100%; border: solid 2px #44df1e">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="#44df1e" class="w-2 h-2" style="height: 50px; width: 58px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                </svg>
                            </div>
                            <p class="text-center">Verifying Balance on cards</p>
                        </div>
                    </div>
                    <div class="button-wrap">
                        <button class="red-btn prev-btn" type="button"><i class="fas fa-chevron-left"></i> Back</button>
                        <button class="red-btn next-btnzz verify-card" type="button">Next</button>
                    </div>
                </div>
            </div>

            <!-- <div class="step verify-step" style="display: nonez">
                <div>
                    <span class="text-right w-100 inline-block">7 of 8</span>
                    <div class="bar-wrap">
                        <div class="bar w-90"></div>
                    </div>
                </div>
                <div class="step-inner">
                    <div class="zg-card-processing">
                        <div style="width: 150px; margin: 0 auto;padding: 45px; border-radius: 100%; border: solid 2px #ccc">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="#999" class="w-6 h-6" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                            </svg>
                        </div>
                        <p class="text-center">Processing</p>
                    </div>

                    <div class="zg-card-error zg-card-stat-note" style="display:none">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="0.3" stroke="#bc3017" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-center">Please use another card. <span id="error-msg"></span></p>
                    </div>
                    <div class="zg-card-success zg-card-stat-note" style="display:none">
                        <div style="width: 150px; margin: 0 auto;padding: 45px; border-radius: 100%; border: solid 2px #44df1e">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="#44df1e" class="w-2 h-2" style="height: 50px; width: 58px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                            </svg>
                        </div>
                        <p class="text-center">Verifying Balance on cards</p>
                    </div>
                    <div class="button-wrap">
                        <button class="red-btn prev-btn" type="button"><i class="fas fa-chevron-left"></i> Back</button>
                        <button class="red-btn next-btn" type="button">Next</button>
                    </div>
                </div>
            </div> -->

            <div class="step last-step" style="display: none">
                <div>
                    <span class="text-right w-100 inline-block">2 of 8</span>
                    <div class="bar-wrap">
                        <div class="bar w-90"></div>
                    </div>
                </div>
                <h4 id="zg-card-default-note">These cards will charged the respective amounts. tap any row to edit card or amount</h4>
                <h4 id="zg-card-notices"></h4>
                <div class="step-inner">
                    <ul class="cards-list"></ul>
                    <input type="hidden" id="zg-nonce" value="<?php echo wp_create_nonce("zg_cards_nonce") ?>" />
                </div>
            </div>
        </div>
<?php
        do_action('woocommerce_zg_credit_card_form_end', $this->id);

        echo '<div class="clear"></div></fieldset>';
    }

    // public function ajaxify_cards()
    // {
    //     // if ( !wp_verify_nonce( $_REQUEST['nonce'], "zg_cards_nonce")) {
    //     //     exit("Woof Woof Woof");
    //     // } 
    //     echo 'OK';
    //     die();
    // }

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
        wp_enqueue_script('zg-number-swiper-js', ZGSTRIPE_PLUGIN_URL . 'core/includes/assets/js/number-swiper.js', array('jquery'), '1.0', true);

        // wp_enqueue_script('zg-number-spinner-picker-js', ZGSTRIPE_PLUGIN_URL . 'core/includes/assets/js/spinner_picker.js', array('jquery'), '1.0', true);
        wp_enqueue_script('zg-number-card-js', ZGSTRIPE_PLUGIN_URL . 'core/includes/assets/js/jquery.card.js', array('jquery'), '1.0', true);
        // wp_enqueue_script('zg-jquery-steps-js', ZGSTRIPE_PLUGIN_URL . 'core/includes/assets/js/jquery.steps.min.js', array('jquery'), '1.0', true);
        wp_register_script('zg-stripe-gateway', ZGSTRIPE_PLUGIN_URL . 'core/includes/assets/js/zg-stripe.js', array('jquery'));
        wp_localize_script('zg-stripe-gateway', 'zg', array(
            'publishableKey' => $this->publishable_key,
            'orderTotal' => WC()->cart->total,
            'currency' => get_woocommerce_currency_symbol(),
            'ajaxurl' => admin_url('admin-ajax.php')
        ));
        wp_enqueue_script('zg-stripe-gateway');
        wp_enqueue_style('zg-stripe-css', ZGSTRIPE_PLUGIN_URL . 'core/includes/assets/css/zg-stripe.css', array(), '1.0.0');
        wp_enqueue_style('zg-number-swiper-css', ZGSTRIPE_PLUGIN_URL . 'core/includes/assets/css/number-swiper.css', array(), '1.0.0');
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

        $cards = $_POST['card'];
        $options = get_option( 'woocommerce_zg-stripe_settings' );
		if($options['testmode'] == 'yes'){
			$privateKey = $options['test_private_key'];
		}else{
			$privateKey = $options['private_key'];
		}

		$stripe = new \Stripe\StripeClient($privateKey);
        $payment_intent = array();
        foreach($cards as $card){
            $amount = $card['amount'] * 100;
            try{
                $payment_intent[] = $stripe->paymentIntents->create([
					"payment_method" => $card['payment_method'],
					'customer' => $card['customer'],
					"amount" => $amount,
					"currency" => strtolower(get_woocommerce_currency()),
					"confirmation_method" => "automatic",
					"confirm" => true,
					"setup_future_usage" => "on_session"
				]);
            } catch(\Stripe\Exception\CardException $e) {
                throw new Exception($e->getError()->message);
            } catch (\Stripe\Exception\RateLimitException $e) {
                throw new Exception($e->getError()->message);
            } catch (\Stripe\Exception\InvalidRequestException $e) {
                throw new Exception($e->getError()->message);
            } catch (\Stripe\Exception\AuthenticationException $e) {
                throw new Exception($e->getError()->message);
            } catch (\Stripe\Exception\ApiConnectionException $e) {
                throw new Exception($e->getError()->message);
            } catch (\Stripe\Exception\ApiErrorException $e) {
                throw new Exception($e->getError()->message);
            } catch (Exception $e) {
                throw new Exception($e->getError()->message);
            }
        }

        if(!empty($payment_intent)){
            
        }
    }

    public function webhook()
    {
        $order = wc_get_order($_GET['id']);
        $order->payment_complete();
        $order->reduce_order_stock();

        update_option('webhook_debug', $_GET);
    }
}
