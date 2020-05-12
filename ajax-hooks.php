<?php
/*--------------------------------------------------------------
>>> TABLE OF CONTENTS:
----------------------------------------------------------------
1.0 Setting up Ajax for CCOLLINS Updater Plugin
  1.1 Add Actions Hooks Receivers for Public and Private AJAX Calls
  1.2 Register, Localize, and Enqueue JS Object
  1.3 Private Ajax Receiver
  1.4 Public Ajax Receiver

2.0 AJAX PRIVATE Callback Functions
  2.1 ccollinsu_ajax_saveConfig_privcallback()
  2.2 ccollinsu_ajax_savePartialConfig_privcallback()
  2.3 ccollinsu_ajax_updateConfig_privcallback()
  2.4 ccollinsu_ajax_saveDebuggingConfig_privcallback()

3.0 AJAX PUBLIC Callback Functions

----------------------------------------------------------------
NOTE: The 'admin_init' action is triggered before any other hook when a user accesses the admin area.
This does not just run on user-facing admin screens. It runs on admin-ajax.php and admin-post.php as well.
The 'init' action fires after WordPress has finished loading but before any headers are sent.
Most of WP is loaded at this stage.
Also notice that both public and private ajaxVars share the same nonce.
----------------------------------------------------------------*/
/*=======[ 1.0 Setting up Ajax for CCOLLINS Updater Plugin ]=======*/
// 1.1 Add Actions Hooks Receivers for Public and Private AJAX Calls 
add_action("init", "ccollinsu_ajax_for_admin"); // the admin_init action does not work here

// Admin Only Private AJAX
function ccollinsu_ajax_for_admin(){
  if(! cuUtils::isAdmin()){ return; }
  $admin_ajax_functions = array('saveConfig', 'savePartialConfig', 'updateConfig', 'saveDebuggingConfig');
  foreach ($admin_ajax_functions as $func) {
    add_action('wp_ajax_ccollinsupdater_' . $func, 'ccollinsu_ajaxReceiverPrivate');
  }

  // Register and localize
  ccollinsu_setupAdminVars();
}

/*==============================================================
  1.2 Register, Localize, and Enqueue JS Object
==============================================================*/
function ccollinsu_setupAdminVars(){
  if(isset($_GET['page']) && preg_match('/ccollins-updater/', @$_GET['page']) ) {
    wp_enqueue_style('wp-pointer');
    wp_enqueue_script('wp-pointer');

    wp_enqueue_style('ccollins-main-style', cuUtils::getBaseURL() . 'css/main.css', '', CCOLLINS_UPDATER_VERSION);
    wp_enqueue_style('cu-colorbox-style', CU_PLUGIN_URL. 'css/colorbox.css', '', CCOLLINS_UPDATER_VERSION);

    wp_enqueue_script('json2');
    wp_enqueue_script('jquery.cutmpl', CU_PLUGIN_URL. 'js/jquery.tmpl.min.js', array('jquery'), CCOLLINS_UPDATER_VERSION);
    wp_enqueue_script('jquery.cucolorbox', CU_PLUGIN_URL . 'js/jquery.colorbox-min.js', array('jquery'), CCOLLINS_UPDATER_VERSION);
    wp_enqueue_script('jquery.cudataTables', CU_PLUGIN_URL . 'js/jquery.dataTables.min.js', array('jquery'), CCOLLINS_UPDATER_VERSION);
    wp_enqueue_script('jquery.qrcode', CU_PLUGIN_URL . 'js/jquery.qrcode.min.js', array('jquery'), CCOLLINS_UPDATER_VERSION);
    wp_enqueue_script('cuAdminjs', CU_PLUGIN_URL . 'js/admin.js', array('jquery'), CCOLLINS_UPDATER_VERSION);
  }else{
    wp_enqueue_style('wp-pointer');
    wp_enqueue_script('wp-pointer');
  }

  wp_register_script('cuAdminjs', CU_PLUGIN_URL . "js/admin.js", array('jquery'), CCOLLINS_UPDATER_VERSION );
  wp_localize_script('cuAdminjs', 'CUAdminVars', array(
    'ajaxURL' => admin_url('admin-ajax.php'),
    'firstNonce' => wp_create_nonce('ccollinsupdater-ajax'),
    'siteBaseURL' => rtrim(site_url(), '/') . '/',
    'baseURL' => CU_PLUGIN_URL,
    'debugOn' => cuConfig::get('debugOn', 0),
    )
  );
}

/*====[ 1.3 Private Ajax Receiver ]=====================*/
function ccollinsu_ajaxReceiverPrivate(){
  if( ! cuUtils::isAdmin() ){
    die(json_encode(array('errorMsg' => "You appear to have logged out or you are not an admin. Please sign-out and sign-in again.")));
  }

  $func = (isset($_POST['action']) && $_POST['action'] ) ? $_POST['action'] : $_GET['action'];
  $nonce = (isset($_POST['nonce']) && $_POST['nonce'] ) ? $_POST['nonce'] : $_GET['nonce'];

  if( ! wp_verify_nonce($nonce, 'ccollinsupdater-ajax')){
    die(json_encode(array('errorMsg' => "Your browser sent an invalid security token to ccollinsupdater. [{$nonce}] Please try reloading this page or signing out and in again.")));
  }

  //func is e.g. ccollinsupdater_ticker so need to munge it
  $func = str_replace('ccollinsupdater_', '', $func);
  $returnArr = call_user_func('ccollinsu_ajax_' . $func . '_privcallback');

  if($returnArr === false){
    $returnArr = array('errorMsg' => "ccollinsupdater encountered an internal error executing that request.");
  }

  if( ! is_array($returnArr)){
    error_log("Function " . wp_kses($func, array()) . " did not return an array and did not generate an error.");
    $returnArr = array();
  }
  if(isset($returnArr['nonce'])){
    error_log("ccollinsupdater ajax function return an array with 'nonce' already set. This could be a bug.");
  }

  $returnArr['nonce'] = wp_create_nonce('ccollinsupdater-ajax');
  die(json_encode($returnArr));
}

/*========[ 1.4 Public Ajax Receiver  ]===================*/
function ccollinsu_ajaxReceiverPublic(){
  $func = (isset($_POST['action']) && $_POST['action'] ) ? $_POST['action'] : $_GET['action'];
  $nonce = (isset($_POST['nonce']) && $_POST['nonce'] ) ? $_POST['nonce'] : $_GET['nonce'];

  if( ! wp_verify_nonce($nonce, 'ccollinsupdater-ajax')){
    die(json_encode(array('errorMsg' => "Your browser sent an invalid security token to ccollinsupdater. [{$nonce}] Please try reloading this page or signing out and in again.")));
  }

  //func is e.g. ccollinsupdater_ticker so need to munge it
  $func = str_replace('cuPage_', '', $func);
  $returnArr = call_user_func('ajax_' . $func . '_pubcallback');

  if($returnArr === false){
    $returnArr = array('errorMsg' => "ccollinsupdater encountered an internal error executing that request.");
  }

  if( ! is_array($returnArr)){
    error_log("Function " . wp_kses($func, array()) . " did not return an array and did not generate an error.");
    $returnArr = array();
  }
  if(isset($returnArr['nonce'])){
    error_log("ccollinsupdater ajax function return an array with 'nonce' already set. This could be a bug.");
  }

  $returnArr['nonce'] = wp_create_nonce('ccollinsupdater-ajax');
  die(json_encode($returnArr));
}

/*===========[ 2.0 AJAX PRIVATE Callback Functions ]==========*/
// 2.1 ajax_saveConfig_privcallback()
function ccollinsu_ajax_saveConfig_privcallback(){
  $reload = '';
  $opts = cuConfig::parseOptions();

  // These are now on the Diagnostics page, so they aren't sent across.
  foreach (CCOLLINS_Updater::$diagnosticParams as $param) {
    $opts[$param] = cuConfig::get($param);
  }

  foreach($opts as $key => $val){
    if($key != 'apiKey'){ //Don't save API key yet
      cuConfig::set($key, $val);
    }
  }

  return array('ok' => 1, 'reload' => $reload);
}

// 2.2 ccollinsu_ajax_savePartialConfig_privcallback()
function ccollinsu_ajax_savePartialConfig_privcallback() {
  $opts = cuConfig::parseOptions(true);

  foreach($opts as $key => $val){
    cuConfig::set($key, $val);
  }

  return array('ok' => 1);
}

// 2.3 ccollinsu_ajax_updateConfig_privcallback()
function ccollinsu_ajax_updateConfig_privcallback(){
  $key = $_POST['key'];
  $val = $_POST['val'];

  cuConfig::set($key, $val);
  return array('ok' => 1);
}

// 2.4 ccollinsu_ajax_saveDebuggingConfig_privcallback()
function ccollinsu_ajax_saveDebuggingConfig_privcallback() {
  foreach (CCOLLINS_Updater::$diagnosticParams as $param) {
    cuConfig::set($param, array_key_exists($param, $_POST) ? '1' : '0');
  }

  return array('ok' => 1, 'reload' => false);
}

/*=============[ 3.0 AJAX PUBLIC Callback Functions ]==========*/