<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Zg_Stripe_Settings
 *
 * This class contains all of the plugin settings.
 * Here you can configure the whole plugin data.
 *
 * @package		ZGSTRIPE
 * @subpackage	Classes/Zg_Stripe_Settings
 * @author		Harshana Nishshanka
 * @since		1.3.1
 */
class Zg_Stripe_Settings{

	/**
	 * The plugin name
	 *
	 * @var		string
	 * @since   1.3.1
	 */
	private $plugin_name;

	/**
	 * Our Zg_Stripe_Settings constructor 
	 * to run the plugin logic.
	 *
	 * @since 1.3.1
	 */
	function __construct(){

		$this->plugin_name = ZGSTRIPE_NAME;
	}

	/**
	 * ######################
	 * ###
	 * #### CALLABLE FUNCTIONS
	 * ###
	 * ######################
	 */

	/**
	 * Return the plugin name
	 *
	 * @access	public
	 * @since	1.3.1
	 * @return	string The plugin name
	 */
	public function get_plugin_name(){
		return apply_filters( 'ZGSTRIPE/settings/get_plugin_name', $this->plugin_name );
	}
}
