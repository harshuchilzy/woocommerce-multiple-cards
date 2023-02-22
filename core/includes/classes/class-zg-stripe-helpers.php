<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Zg_Stripe_Helpers
 *
 * This class contains repetitive functions that
 * are used globally within the plugin.
 *
 * @package		ZGSTRIPE
 * @subpackage	Classes/Zg_Stripe_Helpers
 * @author		Harshana Nishshanka
 * @since		1.3.1
 */
class Zg_Stripe_Helpers{

	/**
	 * ######################
	 * ###
	 * #### CALLABLE FUNCTIONS
	 * ###
	 * ######################
	 */

	public function __construct()
	{
		add_filter( 'woocommerce_payment_gateways', array($this, 'misha_add_gateway_class') );
	}

	public function misha_add_gateway_class($gateways)
	{
		$gateways[] = 'WC_ZGStripe_Gateway'; // your class name is here
		return $gateways;
	}

}
