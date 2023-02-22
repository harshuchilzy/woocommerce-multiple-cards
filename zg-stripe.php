<?php
/**
 * ZG - Stripe
 *
 * @package       ZGSTRIPE
 * @author        Harshana Nishshanka
 * @version       1.3.1
 *
 * @wordpress-plugin
 * Plugin Name:   ZG - Stripe
 * Plugin URI:    https://dayzsolutions.com
 * Description:   This allow customers to use multiple cards to pay for the order using Stripe API
 * Version:       1.3.1
 * Author:        Harshana Nishshanka
 * Author URI:    https://dayzsolutions.com
 * Text Domain:   zg-stripe
 * Domain Path:   /languages
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;
// Plugin name
define( 'ZGSTRIPE_NAME',			'ZG - Stripe' );

// Plugin version
define( 'ZGSTRIPE_VERSION',		'1.3.1' );

// Plugin Root File
define( 'ZGSTRIPE_PLUGIN_FILE',	__FILE__ );

// Plugin base
define( 'ZGSTRIPE_PLUGIN_BASE',	plugin_basename( ZGSTRIPE_PLUGIN_FILE ) );

// Plugin Folder Path
define( 'ZGSTRIPE_PLUGIN_DIR',	plugin_dir_path( ZGSTRIPE_PLUGIN_FILE ) );

// Plugin Folder URL
define( 'ZGSTRIPE_PLUGIN_URL',	plugin_dir_url( ZGSTRIPE_PLUGIN_FILE ) );

/**
 * Load the main class for the core functionality
 */
require_once ZGSTRIPE_PLUGIN_DIR . 'core/class-zg-stripe.php';

/**
 * The main function to load the only instance
 * of our master class.
 *
 * @author  Harshana Nishshanka
 * @since   1.3.1
 * @return  object|Zg_Stripe
 */
function ZGSTRIPE() {
	return Zg_Stripe::instance();
}

ZGSTRIPE();
