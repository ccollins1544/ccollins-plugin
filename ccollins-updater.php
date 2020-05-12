<?php
/*
Plugin Name: ccollins Updater
Plugin URI: https://github.com/ccollins1544/ccollins-updater
Description: Template for creating wordpress plugins that auto-update through github. Also secures the token, user, project name in your wordpress database.
Version: 1.0.1
Author: Christopher Collins
Author URI: https://ccollins.io
License: GPL3
License URI: 
*/
defined('ABSPATH') or die("Direct access not allowed");

// Should be consistant with the above value
if(!defined('CCOLLINS_UPDATER_VERSION')){ define('CCOLLINS_UPDATER_VERSION', '1.0.1');   }
if(!defined('CU_VERSION_KEY')){ define('CU_VERSION_KEY','ccollinsupdater_version'); }

// add_option does nothing if option already exists.
add_option(CU_VERSION_KEY, CCOLLINS_UPDATER_VERSION);

global $wp_plugin_paths;
foreach ($wp_plugin_paths as $dir => $realdir) {
	if (strpos(__FILE__, $realdir) === 0) {
		define('CU_PLUGIN_PATH', $dir . '/' . basename(__FILE__));
		define('CU_PLUGIN_DIR', trailingslashit($dir));
		break;
	}
}

if (!defined('CU_PLUGIN_PATH')) {
	// /<path-to-your-public-html>/wp-content/plugins/ccollins-updater/ccollins-updater.php
	define('CU_PLUGIN_PATH', __FILE__);

	// /<path-to-your-public-html>/wp-content/plugins/ccollins-updater/
	define('CU_PLUGIN_DIR', trailingslashit(dirname(CU_PLUGIN_PATH)));
}

if(!defined('CU_THEME_DIR')){ // ~/public_html/wp-content/themes/<active-theme>/
  define('CU_THEME_DIR', trailingslashit(ABSPATH . 'wp-content/themes/' . get_template()) );
}
if (!defined('CU_PLUGIN_NAME')){ // ccollins-updater
  define('CU_PLUGIN_NAME', trim(dirname(plugin_basename(__FILE__)), '/'));
}
if(!defined('CU_PLUGIN_URL')){ // https://<website>/wp-content/plugins/ccollins-updater/
  define('CU_PLUGIN_URL', trailingslashit(WP_PLUGIN_URL . '/' . CU_PLUGIN_NAME) );
}

/* This should NEVER be true because deactivating the plugin means nothing in this file will run.
So it could only be true if there was an activation error. */
if(get_option('ccollinsupdaterActivated') != 1){
	add_action('activated_plugin','ccollinsupdater_save_activation_error');
	function ccollinsupdater_save_activation_error(){
		update_option('ccollinsupdater_plugin_act_error',  ob_get_contents());
	}
}

include_once( plugin_dir_path( __FILE__ ) . 'lib/CCOLLINS_Updater.php');
CCOLLINS_Updater::install_actions();

$ccollins_updater = new CCOLLINS_Updater( __FILE__ );   
$ccollins_updater->initialize(); 
