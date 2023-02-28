<?php

// Exit if accessed directly.
if (!defined('ABSPATH')) exit;

/**
 * Class Zg_Stripe_Run
 *
 * Thats where we bring the plugin to life
 *
 * @package		ZGSTRIPE
 * @subpackage	Classes/Zg_Stripe_Run
 * @author		Harshana Nishshanka
 * @since		1.3.1
 */
class Zg_Stripe_Run
{

	/**
	 * Our Zg_Stripe_Run constructor 
	 * to run the plugin logic.
	 *
	 * @since 1.3.1
	 */
	function __construct()
	{
		$this->add_hooks();
		// $this->test_payment();
	}

	/**
	 * ######################
	 * ###
	 * #### WORDPRESS HOOKS
	 * ###
	 * ######################
	 */

	/**
	 * Registers all WordPress and plugin related hooks
	 *
	 * @access	private
	 * @since	1.3.1
	 * @return	void
	 */
	private function add_hooks()
	{

		// add_action('wp_head', array($this, 'test_payment'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_backend_scripts_and_styles'), 20);
		// add_filter( 'woocommerce_payment_gateways', array($this, 'zg_add_gateway_class') );
		add_action("wp_ajax_ajaxify_cards", array($this, "ajaxify_cards"));
        add_action("wp_ajax_nopriv_ajaxify_cards", array($this, "ajaxify_cards"));

		add_action("wp_ajax_create_setup_intention", array($this, "create_setup_intention"));
        add_action("wp_ajax_nopriv_create_setup_intention", array($this, "create_setup_intention"));
	}

	public function create_setup_intention()
	{
		if ( !wp_verify_nonce( $_REQUEST['nonce'], "zg_cards_nonce")) {
            exit("Woof Woof Woof");
        }

		$options = get_option( 'woocommerce_zg-stripe_settings' );
		if($options['testmode'] == 'yes'){
			$privateKey = $options['test_private_key'];
		}else{
			$privateKey = $options['private_key'];
		}

		$stripe = new \Stripe\StripeClient($privateKey);
		$email = $_POST['email'];
		$customers = $stripe->customers->search([
			'query' => 'email:\''.$email.'\'',
		]);

		if (count($customers->data) == 0) {
			$customer = $stripe->customers->create([
				'email' => $email,
			]);
		} else {
			$customer = $customers->data[0];
		}

		$expiry = explode('/', str_replace(' ', '',$_POST['expiry']));

		$card = [
			'number' => str_replace(' ', '',$_POST['cardNo']),
			'exp_month' => $expiry[0],
			'exp_year' => '20'.$expiry[1],
			'cvc' => $_POST['csv']
		];

		try{
			$cardStripe = $stripe->paymentMethods->create([
				'type' => 'card',
				'card' => $card,
			]);

			$setupIntent = $stripe->setupIntents->create([
				'payment_method_types' => ['card'],
				'usage' => 'on_session',
				'customer' => $customer->id
			]);
			
	
			$setupIntentConfirmations = $stripe->setupIntents->confirm(
				$setupIntent->id,
				['payment_method' => $cardStripe]
			);
			// When payment is grabbing, use this $setupIntentConfirmation->payment_method as the payment method.
			// Along with it, send the amount correctly.

			$data = array(
				'type' => 'success',
				'dataTtype' => $cardStripe->type,
				'card' => $cardStripe->card->last4,
				'intention' => $setupIntentConfirmations
			);
			echo wp_send_json_success($data);

		} catch(\Stripe\Exception\CardException $e) {
			$data = array(
				'type' => 'error',
				'status' => 402,
				'code' => $e->getError()->code,
				'message' => $e->getError()->message,
				'last4' => $card['number']
			);

			echo wp_send_json_error($data);
		}
		wp_die();
	}

	// public function ajaxify_cards()
    // {
    //     if ( !wp_verify_nonce( $_REQUEST['nonce'], "zg_cards_nonce")) {
    //         exit("Woof Woof Woof");
    //     }
	// 	parse_str($_POST['form'], $form);
	// 	$email = $form['billing_email'];
	// 	$cards= $form['card'];

	// 	$options = get_option( 'woocommerce_zg-stripe_settings' );
	// 	if($options['testmode'] == 'yes'){
	// 		$privateKey = $options['test_private_key'];
	// 	}else{
	// 		$privateKey = $options['private_key'];
	// 	}

	// 	$stripe = new \Stripe\StripeClient($privateKey);
	// 	$customers = $stripe->customers->search([
	// 		'query' => 'email:\''.$email.'\'',
	// 	]);

	// 	if (count($customers->data) == 0) {
	// 		$customer = $stripe->customers->create([
	// 			'email' => $email,
	// 		]);
	// 	} else {
	// 		$customer = $customers->data[0];
	// 	}

	// 	$data = [];
	// 	// $success = [];
	// 	foreach($cards as $card){
	// 		$expiry = explode('/',$card['card_expiry']);
	// 		$amount = $card['card_amount'];
	// 		$card = [
	// 			'number' => str_replace(' ', '',$card['card_number']),
	// 			'exp_month' => $expiry[0],
	// 			'exp_year' => '20'.$expiry[1],
	// 			'cvc' => $card['card_csv']
	// 		];

	// 		try{
	// 			$cardStripe = $stripe->paymentMethods->create([
	// 				'type' => 'card',
	// 				'card' => $card,
	// 			]);
	// 			$data[] = array(
	// 				'type' => 'success',
	// 				'dataTtype' => $cardStripe->type,
	// 				'card' => $cardStripe->card->last4
	// 			);
	// 		} catch(\Stripe\Exception\CardException $e) {
	// 			$data[] = array(
	// 				'type' => 'error',
	// 				'status' => 402,
	// 				'code' => $e->getError()->code,
	// 				'message' => $e->getError()->message,
	// 				'last4' => $card['number']
	// 			);
	// 			// echo json_encode($errors);
	// 			// wp_die();
	// 			continue;
	// 			// die();
	// 		}

	// 		$setupIntent = $stripe->setupIntents->create([
	// 			'payment_method_types' => ['card'],
	// 			'usage' => 'on_session',
	// 			'customer' => $customer->id
	// 		]);
			
	// 		try{
	// 			$intent = $stripe->setupIntents->confirm(
	// 				$setupIntent->id,
	// 				['payment_method' => $cardStripe]
	// 			);
	// 		} catch(\Stripe\Exception\CardException $e) {
	// 			// echo '<pre>';print_r($e->getError()); echo '</pre>';
	// 			$data[] = array(
	// 				'type' => 'error',
	// 				'status' => 402,
	// 				'code' => $e->getError()->code,
	// 				'message' => $e->getError()->message,
	// 				'last4' => $e->getError()->payment_method->card->last4
	// 			);
	// 			// echo json_encode($errors);
	// 			// wp_die();
	// 			continue;
	// 			// continue;
	// 			// die();
	// 		}catch (\Stripe\Exception\RateLimitException $e) {
	// 			$data[] = array(
	// 				'type' => 'error',
	// 				'status' => 402,
	// 				'code' => $e->getError()->code,
	// 				'message' => $e->getError()->message,
	// 				'last4' => $e->getError()->payment_method->card->last4
	// 			);
	// 			// die();
	// 			continue;

	// 		} catch (\Stripe\Exception\InvalidRequestException $e) {
	// 			$data[] = array(
	// 				'type' => 'error',
	// 				'status' => 402,
	// 				'code' => $e->getError()->code,
	// 				'message' => $e->getError()->message,
	// 				'last4' => $e->getError()->payment_method->card->last4
	// 			);
	// 			continue;
	// 			// die();
	// 		} catch (\Stripe\Exception\AuthenticationException $e) {
	// 			$data[] = array(
	// 				'type' => 'error',
	// 				'status' => 402,
	// 				'code' => $e->getError()->code,
	// 				'message' => $e->getError()->message,
	// 				'last4' => $e->getError()->payment_method->card->last4
	// 			);
	// 			continue;
	// 			// die();
	// 		} catch (\Stripe\Exception\ApiConnectionException $e) {
	// 			$data[] = array(
	// 				'type' => 'error',
	// 				'status' => 402,
	// 				'code' => $e->getError()->code,
	// 				'message' => $e->getError()->message,
	// 				'last4' => $e->getError()->payment_method->card->last4
	// 			);
	// 			// continue;
	// 			// die();
	// 		} catch (\Stripe\Exception\ApiErrorException $e) {
	// 			$data[] = array(
	// 				'type' => 'error',
	// 				'status' => 402,
	// 				'code' => $e->getError()->code,
	// 				'message' => $e->getError()->message,
	// 				'last4' => $e->getError()->payment_method->card->last4
	// 			);
	// 			continue;
	// 		} catch (Exception $e) {
	// 			$data[] = array(
	// 				'type' => 'error',
	// 				'status' => 402,
	// 				'code' => $e->getError()->code,
	// 				'message' => $e->getError()->message,
	// 				'last4' => $e->getError()->payment_method->card->last4
	// 			);
	// 			continue;
	// 			// die();
	// 		}

	// 		$data[] = array(
	// 			'type' => 'setup_intention',
	// 			'amount' => $amount * 100,
	// 			'customer' => $customer->id,
	// 			'intent' => $intent
	// 		);
	// 	}

	// 	// if(!empty($errors)){
	// 		echo wp_send_json_success( $data );
	// 	// }else{
	// 	// 	echo json_encode($setupIntentConfirmations);
	// 	// }

	// 	// foreach($setupIntentConfirmations as $key => $intention){
	// 	// 	if ($setupIntentConfirmation->status == 'succeeded') {
	// 	// 		$intentions 
	// 	// 	}
	// 	// }

	// 	// foreach($setupIntentConfirmations as $key => $intention){
	// 	// 	$setupIntentConfirmation = $intention['intent'];
	// 	// 	// echo $intention['customer'];
	// 	// 	if ($setupIntentConfirmation->status == 'succeeded') {
	// 	// 		echo $setupIntentConfirmation->status . ' ' . $intention['amount'];
	
	// 	// 		$payment_intent = $stripe->paymentIntents->create([
	// 	// 			"payment_method" => $setupIntentConfirmation->payment_method,
	// 	// 			'customer' => $intention['customer'],
	// 	// 			"amount" => $intention['amount'],
	// 	// 			"currency" => "usd",
	// 	// 			"confirmation_method" => "automatic",
	// 	// 			"confirm" => true,
	// 	// 			"setup_future_usage" => "on_session"
	// 	// 		]);
	// 	// 		echo $payment_intent->status;
	
	// 	// 		// Handle successful payment
	// 	// 	} else {
	// 	// 		print_r($setupIntentConfirmation);
	// 	// 		// Handle failed payment
	// 	// 	}
	// 	// }
		
    //     // echo json_encode($form['card']);
    //     wp_die();
    // }
	/**
	 * ######################
	 * ###
	 * #### WORDPRESS HOOK CALLBACKS
	 * ###
	 * ######################
	 */

	/**
	 * Enqueue the backend related scripts and styles for this plugin.
	 * All of the added scripts andstyles will be available on every page within the backend.
	 *
	 * @access	public
	 * @since	1.3.1
	 *
	 * @return	void
	 */
	public function enqueue_backend_scripts_and_styles()
	{
		// wp_enqueue_style('zgstripe-backend-styles', ZGSTRIPE_PLUGIN_URL . 'core/includes/assets/css/backend-styles.css', array(), ZGSTRIPE_VERSION, 'all');
		// wp_enqueue_script('zgstripe-backend-scripts', ZGSTRIPE_PLUGIN_URL . 'core/includes/assets/js/backend-scripts.js', array(), ZGSTRIPE_VERSION, false);
		// wp_localize_script('zgstripe-backend-scripts', 'zgstripe', array(
		// 	'plugin_name'   	=> __(ZGSTRIPE_NAME, 'zg-stripe'),
		// ));
	}

	public function zg_add_gateway_class($gateways)
	{
		if ( class_exists( 'WC_Payment_Gateway' ) ) {
			$gateways[] = 'WC_ZGStripe_Gateway'; // your class name is here
		}
		return $gateways;
	}

	public function test_payment()
	{
		echo 'HAHAH';
		// \Stripe\Stripe::setApiKey('sk_test_51MZpJJCl6ckeSanWzPyusFagqlq7DF3Asg7OxI81gj7Yyfyygl1nBPYbw515hyxJWgdsZFsjFbKuD4sPcDBd8zol00PJ4EjH3S');

		// $charge = \Stripe\Charge::create([
		// 	'amount' => 1000, // amount in cents
		// 	'currency' => 'usd',
		// 	'description' => 'Example charge',
		// 	'source' => 'tok_visa', // token representing the card to charge
		// ]);

		$stripe = new \Stripe\StripeClient('sk_test_51MZpJJCl6ckeSanWzPyusFagqlq7DF3Asg7OxI81gj7Yyfyygl1nBPYbw515hyxJWgdsZFsjFbKuD4sPcDBd8zol00PJ4EjH3S');
		// \Stripe\Stripe::setApiKey('sk_test_51MZpJJCl6ckeSanWzPyusFagqlq7DF3Asg7OxI81gj7Yyfyygl1nBPYbw515hyxJWgdsZFsjFbKuD4sPcDBd8zol00PJ4EjH3S');

		$card1 = [
			'number' => '4242424242424242',
			'exp_month' => '12',
			'exp_year' => '2023',
			'cvc' => '123'
		];

		$card2 = [
			'number' => '4000000000009995',
			'exp_month' => '12',
			'exp_year' => '2023',
			'cvc' => '123'
		];

		$card3 = [
			'number' => '378282246310005',
			'exp_month' => '12',
			'exp_year' => '2023',
			'cvc' => '123'
		];

		$customers = $stripe->customers->search([
			'query' => 'email:\'harshu.nk62@gmail.com\'',
		]);

		if (count($customers->data) == 0) {
			$customer = $stripe->customers->create([
				'email' => 'harshu.nk62@gmail.com',
			]);
		} else {
			$customer = $customers->data[0];
		}

		// print_r($customer);

		$card1ST = $stripe->paymentMethods->create([
			'type' => 'card',
			'card' => $card1,
		]);

		try{
		  $card2ST = $stripe->paymentMethods->create([
			'type' => 'card',
			'card' => $card2,
		  ]);
		} catch (Exception $e) {
			// echo 'OOOOOO';
			$errors[] = array(
				'status' => 402,
				'code' => $e->getError()->code,
				'message' => $e->getError()->message,
				'last4' => $card2
			);
			// echo '<pre>';print_r($e->getError()); echo '</pre>';
			die();
		}

		$setupIntent = $stripe->setupIntents->create([
			'payment_method_types' => ['card'],
			'usage' => 'on_session',
			'customer' => $customer->id
		]);
		

		$setupIntentConfirmations[1000] = $stripe->setupIntents->confirm(
			$setupIntent->id,
			['payment_method' => $card1ST]
		);

		try {
		$setupIntent2 = $stripe->setupIntents->create([
			'payment_method_types' => ['card'],
			'usage' => 'on_session',
			'customer' => $customer->id
		]);
		$setupIntentConfirmations[1500] = $stripe->setupIntents->confirm(
			$setupIntent2->id,
			['payment_method' => $card2ST]
		);
		} catch(\Stripe\Exception\CardException $e) {
			// echo '<pre>';print_r($e->getError()); echo '</pre>';
			$errors[] = array(
				'status' => 402,
				'code' => $e->getError()->code,
				'message' => $e->getError()->message,
				'last4' => $e->getError()->card
			);
			echo '<pre>';
			print_r($e->getError()->payment_method->card->last4);
			echo '</pre>';

			die();
		}catch (\Stripe\Exception\RateLimitException $e) {
			$errors[] = array(
				'status' => 402,
				'code' => $e->getError()->code,
				'message' => $e->getError()->message,
				'last4' => $card2
			);
			die();
		} catch (\Stripe\Exception\InvalidRequestException $e) {
			$errors[] = array(
				'status' => 402,
				'code' => $e->getError()->code,
				'message' => $e->getError()->message,
				'last4' => $card2
			);
			die();
		} catch (\Stripe\Exception\AuthenticationException $e) {
			$errors[] = array(
				'status' => 402,
				'code' => $e->getError()->code,
				'message' => $e->getError()->message,
				'last4' => $card2
			);
			die();
		} catch (\Stripe\Exception\ApiConnectionException $e) {
			$errors[] = array(
				'status' => 402,
				'code' => $e->getError()->code,
				'message' => $e->getError()->message,
				'last4' => $card2
			);
			die();
		} catch (\Stripe\Exception\ApiErrorException $e) {
			$errors[] = array(
				'status' => 402,
				'code' => $e->getError()->code,
				'message' => $e->getError()->message,
				'last4' => $card2
			);
			die();
		} catch (Exception $e) {
			$errors[] = array(
				'status' => 402,
				'code' => $e->getError()->code,
				'message' => $e->getError()->message,
				'last4' => $card2
			);
			die();
		}

		// if($setupIntent2){
			
		// }

		foreach($setupIntentConfirmations as $amount => $setupIntentConfirmation){
			if ($setupIntentConfirmation->status == 'succeeded') {
				echo $setupIntentConfirmation->status;
	
				$payment_intent = $stripe->paymentIntents->create([
					"payment_method" => $setupIntentConfirmation->payment_method,
					'customer' => $customer->id,
					"amount" => $amount,
					"currency" => "usd",
					"confirmation_method" => "automatic",
					"confirm" => true,
					"setup_future_usage" => "on_session"
				]);
				echo $payment_intent->status;
	
				// Handle successful payment
			} else {
				echo 'ERROR';
				print_r($setupIntentConfirmation);
				// Handle failed payment
			}
		}
		
	}
}
