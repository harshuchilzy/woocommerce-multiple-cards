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
	}

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
		wp_enqueue_style('zgstripe-backend-styles', ZGSTRIPE_PLUGIN_URL . 'core/includes/assets/css/backend-styles.css', array(), ZGSTRIPE_VERSION, 'all');
		wp_enqueue_script('zgstripe-backend-scripts', ZGSTRIPE_PLUGIN_URL . 'core/includes/assets/js/backend-scripts.js', array(), ZGSTRIPE_VERSION, false);
		wp_localize_script('zgstripe-backend-scripts', 'zgstripe', array(
			'plugin_name'   	=> __(ZGSTRIPE_NAME, 'zg-stripe'),
		));
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
			'number' => '5555555555554444',
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

		print_r($customer);

		$card1ST = $stripe->paymentMethods->create([
			'type' => 'card',
			'card' => $card1,
		]);

		  $card2ST = $stripe->paymentMethods->create([
			'type' => 'card',
			'card' => $card2,
		  ]);

		$setupIntent = $stripe->setupIntents->create([
			'payment_method_types' => ['card'],
			'usage' => 'on_session',
			'customer' => $customer->id
		]);
		

		$setupIntentConfirmations[1000] = $stripe->setupIntents->confirm(
			$setupIntent->id,
			['payment_method' => $card1ST]
		);

		$setupIntent2 = $stripe->setupIntents->create([
			'payment_method_types' => ['card'],
			'usage' => 'on_session',
			'customer' => $customer->id
		]);

		$setupIntentConfirmations[1500] = $stripe->setupIntents->confirm(
			$setupIntent2->id,
			['payment_method' => $card2ST]
		);

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
				print_r($setupIntentConfirmation);
				// Handle failed payment
			}
		}
		
	}
}
