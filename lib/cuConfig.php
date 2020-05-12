<?php
if(!class_exists('cuDB')){ require_once( 'cuDB.php' ); }

class cuConfig {
	const DEBUG = true;
	const DEFAULT_SALT = AUTH_SALT;
	const DEFAULT_IV = NONCE_KEY;
	const AUTOLOAD = 'yes';
	const DONT_AUTOLOAD = 'no';

	private static $table = false;
	private static $tableExists = true;
	private static $DB = false;

	public static $defaultConfig = array(
		// All exportable boolean options
		"checkboxes" => array(
			"debugOn" => array('value' => self::DEBUG, 'autoload' => self::AUTOLOAD),
			"deleteTablesOnDeact" => array('value' => false, 'autoload' => self::AUTOLOAD),
		),
		// All exportable variable type options
		"otherParams" => array(),

		"encryptedParams" => array(
			"license_username" => array('value' => "", 'autoload' => self::DONT_AUTOLOAD),
			"license_repo" => array('value' => "", 'autoload' => self::DONT_AUTOLOAD),
			"license_code" => array('value' => "", 'autoload' => self::DONT_AUTOLOAD),
		),
	);

	public static $serializedOptions = array();

	public static $configTableFields = array(
		"name" => "%s",
		"val" => "%s",
		"autoload" => "%s",
	);

	public static function setDefaults() {
		foreach (self::$defaultConfig['checkboxes'] as $key => $config) {
			$val = $config['value'];
			$autoload = $config['autoload'];
			if (self::get($key) === false) {
				self::set($key, $val ? '1' : '0', $autoload);
			}
		}

		foreach (self::$defaultConfig['otherParams'] as $key => $config) {
			$val = $config['value'];
			$autoload = $config['autoload'];
			if (self::get($key) === false) {
				self::set($key, $val, $autoload);
			}
		}

		foreach (self::$defaultConfig['encryptedParams'] as $key => $config) {
			$val = $config['value'];
			$autoload = $config['autoload'];
			if (self::get($key) === false) {
				self::set($key, $val, $autoload);
			}
		}
	}

	/**
	 * simple method to encrypt or decrypt a plain text string
	 * initialization vector(IV) has to be the same when encrypting and decrypting
	 *
	 * @param string $action: can be 'encrypt' or 'decrypt'
	 * @param string $string: string to encrypt or decrypt
	 *
	 * @return string
	 */
	public static function encrypt_decrypt($action, $string) {
	  $output = false;

	  $encrypt_method = "AES-256-CBC";
	  $secret_key = self::DEFAULT_SALT;
	  $secret_iv = self::DEFAULT_IV;

	  // hash
	  $key = hash('sha256', $secret_key);

	  // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
	  $iv = substr(hash('sha256', $secret_iv), 0, 16);

	  if ( $action == 'encrypt' ) {
	    $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
	    $output = base64_encode($output);
	  } else if( $action == 'decrypt' ) {
	    $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
	  }

	  return $output;
	}

	public static function loadAllOptions() {
		global $wpdb;

		$options = wp_cache_get('alloptions', 'ccollinsupdater');
		if (!$options) {
			$table = self::table();
			self::updateTableExists();
			$suppress = $wpdb->suppress_errors();
			if (!($rawOptions = $wpdb->get_results("SELECT name, val FROM {$table} WHERE autoload = 'yes'"))) {
				$rawOptions = $wpdb->get_results("SELECT name, val FROM {$table}");
			}
			$wpdb->suppress_errors($suppress);
			$options = array();
			foreach ((array) $rawOptions as $o) {
				if (in_array($o->name, self::$serializedOptions)) {
					$val = maybe_unserialize($o->val);
					if ($val) {
						$options[$o->name] = $val;
					}
				}
				else {
					$options[$o->name] = $o->val;
				}
			}

			wp_cache_add_non_persistent_groups('ccollinsupdater');
			wp_cache_add('alloptions', $options, 'ccollinsupdater');
		}

		return $options;
	}

	public static function updateTableExists() {
		global $wpdb;
		self::$tableExists = $wpdb->get_col($wpdb->prepare(<<<SQL
SELECT TABLE_NAME FROM information_schema.TABLES
WHERE TABLE_SCHEMA=DATABASE()
AND TABLE_NAME=%s
SQL
			, self::table()));
	}

	private static function updateCachedOption($name, $val) {
		$options = self::loadAllOptions();
		$options[$name] = $val;
		wp_cache_set('alloptions', $options, 'ccollinsupdater');
	}

	private static function removeCachedOption($name) {
		$options = self::loadAllOptions();
		if (isset($options[$name])) {
			unset($options[$name]);
			wp_cache_set('alloptions', $options, 'ccollinsupdater');
		}
	}

	private static function getCachedOption($name) {
		$options = self::loadAllOptions();
		if (isset($options[$name])) {
			return $options[$name];
		}

		$table = self::table();
		$val = self::getDB()->querySingle("SELECT val FROM {$table} WHERE name='%s'", $name);
		if ($val !== null) {
			$options[$name] = $val;
			wp_cache_set('alloptions', $options, 'ccollinsupdater');
		}
		return $val;
	}

	private static function hasCachedOption($name) {
		$options = self::loadAllOptions();
		return isset($options[$name]);
	}

	public static function getExportableOptionsKeys(){
		$ret = array();
		foreach(self::$defaultConfig['checkboxes'] as $key => $val){
			$ret[] = $key;
		}
		foreach(self::$defaultConfig['otherParams'] as $key => $val){
			if($key != 'apiKey'){
				$ret[] = $key;
			}
		}
		foreach(self::$defaultConfig['encryptedParams'] as $key => $val){
			if($key != 'license_code' || $key != 'license_username' || $key != 'license_repo'){
				$ret[] = $key;
			}
		}
		return $ret;
	}

	public static function parseOptions($excludeOmitted = false) {
		$ret = array();
		foreach (self::$defaultConfig['checkboxes'] as $key => $val) { //value is not used. We just need the keys for validation
			if ($excludeOmitted && isset($_POST[$key])) {
				$ret[$key] = (int) $_POST[$key];
			}
			else if (!$excludeOmitted || isset($_POST[$key])) {
				$ret[$key] = isset($_POST[$key]) ? '1' : '0';
			}
		}
		foreach (self::$defaultConfig['otherParams'] as $key => $val) {
			if (!$excludeOmitted || isset($_POST[$key])) {
				if (isset($_POST[$key])) {
					if(in_array($key, self::$serializedOptions)){
						// $ret[$key] = self::$defaultConfig['otherParams'][$key]['value']; // RESETS to default values
						$ret[$key] = self::makeSerialized($_POST[$key],$key);

					} else { $ret[$key] = stripslashes($_POST[$key]); }
				}	else {
					error_log("Missing options param \"$key\" when parsing parameters.");
				}
			}
		}
		foreach (self::$defaultConfig['encryptedParams'] as $key => $val) {
			if (!$excludeOmitted || isset($_POST[$key])) {
				if (isset($_POST[$key])) {
					$ret[$key] = stripslashes($_POST[$key]);

				}	else {
					error_log("Missing options param \"$key\" when parsing parameters.");
				}
			}
		}

		return $ret;
	}

	public static function setArray($arr){
		foreach($arr as $key => $val){
			self::set($key, $val);
		}
	}

	public static function getHTML($key){
		return esc_html(self::get($key));
	}

	public static function getSerializedString($key, $newLine=true){
		$v = self::get($key);
		$vstring = "";

		if(self::is_multi_array($v)){
			foreach($v as $e){
				$vstring .= implode(":", $e);
				$vstring .= "\n";
				// foreach ($e as $key => $value) { $vstring .= $key.":".$value."\n"; }
		  }
		}else{
			$vstring .= implode(":", $v);
			$vstring .= "\n";
			// foreach ($v as $key => $value) { $vstring .= $key.":".$value."\n"; }
		}

		return $vstring;
	}

	public static function setSerialized($key, $val){
		$valString = str_replace("\n", ",", $val);
		$valArray = array();

		foreach (explode(",", $valString) as $a){
			$b = explode(":",$a);
			$valArray[$b[0]] = $b[1];
		}

		$valArray = (array_filter($valArray));
		$valSerial = serialize($valArray);

		self::set($key,$valSerial);
	}

	/* vstring = "key:value\n key:value\n ...";
	*/
	/**
	 * makeSerialized will accomplish the opposite logic of getSerializedString
	 * However it's more complicated because we must pass in the $key so we can fetch
	 * the correct array keys to match up to the line-by-line exploded array values.
	 *
	 * If there's any mismatch this function will just serialize whatever it has when it failed.
	 * This also makes the function very dynamic in the sense that if we don't pass in a key
	 * we'll just run serialize on wahtever $vstring is.
	 *
	 * @param string $vstring: The text to be serialized. Can be lines of text. Should contain delimiter :
	 * @param string $key: Will be used to fetch the array keys from the $defaultConfig only if the key exists in $serializedOptions
	 *
	 * @return string serialized string that should be stored in the database.
	 */
	public static function makeSerialized($vstring,$key=''){
		if(is_array($vstring) || is_object($vstring) || empty($key) ){ return serialize($vstring); }

		$vstring = trim($vstring);
		$vstring_arr = explode("\n",$vstring);
		$vstring_arr = array_filter($vstring_arr,'trim');

		// Make sure the delimter : exists so we know where to explode the string.
		// Also make sure we can get the array keys from the $serializedOptions
		if( substr_count($vstring_arr[0], ":") === 0 || !in_array($key,self::$serializedOptions) ){
			return self::makeSerialized($vstring_arr);
		}

		// Format into a multi-dimensional array
		// First Get array keys
		$arr_keys = array();
		foreach(self::$defaultConfig as $option){
			foreach($option as $i=>$o){
				if($i===$key){
					$arr=unserialize($o['value']);
					$first_element=reset($arr);
					$arr_keys=array_keys($first_element);
				}
			}
		}

		// Make sure there's not a size mismatch in the first element of $vstring_arr[0] and $arr_keys
		if( (substr_count($vstring_arr[0], ":")+1) !== sizeof($arr_keys) || empty($arr_keys)){
			return self::makeSerialized($vstring_arr);
		}

		$valArray = array();
		$count=1;
    foreach($vstring_arr as $line){
			$line_arr=explode(":",str_replace("\r","",$line));
			foreach($line_arr as $i=>$v){
				$valArray[$count][$arr_keys[$i]]=$v;
			}
			$count++;
    }

		// Now that we have the array formated correctly we can rescurse back to ourself
		// and SELF function will serialize the array correctly.
		return self::makeSerialized($valArray);
	} // END makeSerialized

	public static function inc($key){
		$val = self::get($key, false);
		if(! $val){
			$val = 0;
		}
		self::set($key, $val + 1);
		return $val + 1;
	}

	public static function set($key, $val, $autoload = self::AUTOLOAD) {
		global $wpdb;

		if (is_array($val)) {
			$msg = "cuConfig::set() got an array as second param with key: $key and value: " . var_export($val, true);
			return;
		}

		if (!self::$tableExists) {
			return;
		}

		// Encrypt Data
		if(array_key_exists($key, self::$defaultConfig['encryptedParams'])){
			$val = self::encrypt_decrypt('encrypt', $val);
		}

		$table = self::table();
		if ($wpdb->query($wpdb->prepare("INSERT INTO {$table} (name, val, autoload) values (%s, %s, %s) ON DUPLICATE KEY UPDATE val = %s, autoload = %s", $key, $val, $autoload, $val, $autoload)) !== false && $autoload != self::DONT_AUTOLOAD) {
			self::updateCachedOption($key, $val);
		}

	}

	public static function get($key, $default = false, $allowCached = true) {
		global $wpdb;

		if ($allowCached && self::hasCachedOption($key)) {
			if(array_key_exists($key, self::$defaultConfig['encryptedParams'])){
				return self::encrypt_decrypt('decrypt', self::getCachedOption($key));
			}
			return self::getCachedOption($key);
		}

		if (!self::$tableExists) {
			return $default;
		}

		$table = self::table();
		if (!($option = $wpdb->get_row($wpdb->prepare("SELECT name, val, autoload FROM {$table} WHERE name = %s", $key)))) {
			return $default;
		}

		if ($option->autoload != self::DONT_AUTOLOAD) {
			self::updateCachedOption($key, $option->val);
		}

		// Decrypt Data
		if(array_key_exists($key, self::$defaultConfig['encryptedParams'])){
			return self::encrypt_decrypt('decrypt', $option->val);
		}

		return $option->val;
	}

	public static function cb($key){
		if(self::get($key)){
			echo ' checked ';
		}
	}

	public static function sel($key, $val, $isDefault = false){
		if((! self::get($key)) && $isDefault){ echo ' selected '; }
		if(self::get($key) == $val){ echo ' selected '; }
	}

	private static function getDB(){
		if(! self::$DB){
			self::$DB = new cuDB();
		}
		return self::$DB;
	}

	private static function table(){
		if(! self::$table){
			global $wpdb;
			self::$table = $wpdb->base_prefix . 'ccollinsupdaterConfig';
		}
		return self::$table;
	}

	private static function is_multi_array($array=[],$mode='every_key'){
    $result = false;

    if(is_array($array)){
      if($mode=='first_key_only'){
        if(is_array(array_shift($array))){
          $result = true;
        }

      }elseif($mode=='every_key'){

        $result = true;

        foreach($array as $key => $value){
          if(!is_array($value)){
            $result = false;
            break;
          }
        }

      }elseif($mode=='at_least_one_key'){
        if(count($array)!==count($array, COUNT_RECURSIVE)){
          $result = true;
        }
      }
    }

    return $result;
  } // END function is_multi_array

}
