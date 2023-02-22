<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'Zg_Stripe' ) ) :

	/**
	 * Main Zg_Stripe Class.
	 *
	 * @package		ZGSTRIPE
	 * @subpackage	Classes/Zg_Stripe
	 * @since		1.3.1
	 * @author		Harshana Nishshanka
	 */
	final class Zg_Stripe {

		/**
		 * The real instance
		 *
		 * @access	private
		 * @since	1.3.1
		 * @var		object|Zg_Stripe
		 */
		private static $instance;

		/**
		 * ZGSTRIPE helpers object.
		 *
		 * @access	public
		 * @since	1.3.1
		 * @var		object|Zg_Stripe_Helpers
		 */
		public $helpers;

		/**
		 * ZGSTRIPE settings object.
		 *
		 * @access	public
		 * @since	1.3.1
		 * @var		object|Zg_Stripe_Settings
		 */
		public $settings;

		/**
		 * Throw error on object clone.
		 *
		 * Cloning instances of the class is forbidden.
		 *
		 * @access	public
		 * @since	1.3.1
		 * @return	void
		 */
		public function __clone() {
			_doing_it_wrong( __FUNCTION__, __( 'You are not allowed to clone this class.', 'zg-stripe' ), '1.3.1' );
		}

		/**
		 * Disable unserializing of the class.
		 *
		 * @access	public
		 * @since	1.3.1
		 * @return	void
		 */
		public function __wakeup() {
			_doing_it_wrong( __FUNCTION__, __( 'You are not allowed to unserialize this class.', 'zg-stripe' ), '1.3.1' );
		}

		/**
		 * Main Zg_Stripe Instance.
		 *
		 * Insures that only one instance of Zg_Stripe exists in memory at any one
		 * time. Also prevents needing to define globals all over the place.
		 *
		 * @access		public
		 * @since		1.3.1
		 * @static
		 * @return		object|Zg_Stripe	The one true Zg_Stripe
		 */
		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Zg_Stripe ) ) {
				self::$instance					= new Zg_Stripe;
				self::$instance->base_hooks();
				self::$instance->includes();
				self::$instance->helpers		= new Zg_Stripe_Helpers();
				self::$instance->settings		= new Zg_Stripe_Settings();

				//Fire the plugin logic
				new Zg_Stripe_Run();

				/**
				 * Fire a custom action to allow dependencies
				 * after the successful plugin setup
				 */
				do_action( 'ZGSTRIPE/plugin_loaded' );
			}

			return self::$instance;
		}

		/**
		 * Include required files.
		 *
		 * @access  private
		 * @since   1.3.1
		 * @return  void
		 */
		private function includes() {
			require_once ZGSTRIPE_PLUGIN_DIR . 'vendor/autoload.php';
			require_once ZGSTRIPE_PLUGIN_DIR . 'core/includes/classes/class-zg-stripe-helpers.php';
			require_once ZGSTRIPE_PLUGIN_DIR . 'core/includes/classes/class-zg-stripe-settings.php';

			require_once ZGSTRIPE_PLUGIN_DIR . 'core/includes/classes/class-zg-stripe-run.php';
			require_once ZGSTRIPE_PLUGIN_DIR . 'core/includes/classes/class-zg-stripe-gateway.php';

		}

		/**
		 * Add base hooks for the core functionality
		 *
		 * @access  private
		 * @since   1.3.1
		 * @return  void
		 */
		private function base_hooks() {
			add_action( 'plugins_loaded', array( self::$instance, 'load_textdomain' ) );
		}

		/**
		 * Loads the plugin language files.
		 *
		 * @access  public
		 * @since   1.3.1
		 * @return  void
		 */
		public function load_textdomain() {
			load_plugin_textdomain( 'zg-stripe', FALSE, dirname( plugin_basename( ZGSTRIPE_PLUGIN_FILE ) ) . '/languages/' );
		}

	}

endif; // End if class_exists check.