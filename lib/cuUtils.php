<?php
class cuUtils {
	public static function getBaseURL(){
		return plugins_url('', CU_PLUGIN_PATH) . '/';
	}

  public static function getSiteBaseURL(){
    return rtrim(site_url(), '/') . '/';
  }

	public static function cleanupOneEntryPerLine($string) {
		$string = str_replace(",", "\n", $string); // fix old format
		return implode("\n", array_unique(array_filter(array_map('trim', explode("\n", $string)))));
	}

	public static function implodeKeyValArray($keyValArray){
		$keyValString = "";
		if(is_array($keyValArray)){

			$keyValArray = array_map(
        function($item) {
        	return trim($item, "'");
        },
        $keyValArray
    	);

    	$keyValString = "'" . implode("', '", $keyValArray) . "'";

		}else{

    	if(substr(trim($keyValArray), 0,1) == "'" || substr(trim($keyValArray), -1) == "'"){

        if(substr(trim($keyValArray), 0,1) != "'"){
            $keyValArray = "'" . trim($keyValArray);
        }

        if(substr(trim($keyValArray), -1) != "'"){
            $keyValArray = trim($keyValArray) . "'";
        }

    	}else{
        $keyValArray = "'" . $keyValArray . "'";
      }

      $keyValString = $keyValArray;
	  }

	  return $keyValString;
	}

	public static function isAdmin($user = false){
    if($user){
      if(is_multisite()){
        if(user_can($user, 'manage_network')){
          return true;
        }
      } else {
        if(user_can($user, 'manage_options')){
          return true;
        }
      }
    } else {
      if(is_multisite()){
        if(current_user_can('manage_network')){
          return true;
        }
      } else {
        if(current_user_can('manage_options')){
          return true;
        }
      }
    }
    return false;
  }

  public static function addSpaces($some_string){
		// replace underscores with spaces and uppercase first letter of each word
		$some_string = str_replace("_"," ",$some_string);
		$some_string = ucwords($some_string);

		// remove any spaces
    $some_string = preg_replace('/\s/', '', $some_string);

		// split into array on uppercase letters
		$string_arr = preg_split('/(?=[A-Z])/', $some_string, -1, PREG_SPLIT_NO_EMPTY);

		// convert to string with spaces
		$spaced_string = "";
		for($i=0; $i<sizeof($string_arr); $i++){
			$spaced_string .= $string_arr[$i];
			if($i+1 < sizeof($string_arr) && strlen($string_arr[$i+1]) != 1) { $spaced_string .= " "; }
		}

		return $spaced_string;
	} // END addSpaces

	public static function showarray($a){
    echo "<pre>";
    print_r($a);
    echo "</pre>";
  }

	/**
   * Tests if an input is valid PHP serialized string.
   *
   * Checks if a string is serialized using quick string manipulation
   * to throw out obviously incorrect strings. Unserialize is then run
   * on the string to perform the final verification.
   *
   * Valid serialized forms are the following:
   * <ul>
   * <li>boolean: <code>b:1;</code></li>
   * <li>integer: <code>i:1;</code></li>
   * <li>double: <code>d:0.2;</code></li>
   * <li>string: <code>s:4:"test";</code></li>
   * <li>array: <code>a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}</code></li>
   * <li>object: <code>O:8:"stdClass":0:{}</code></li>
   * <li>null: <code>N;</code></li>
   * </ul>
   *
   * @param  string   $value  Value to test for serialized form
   * @param  mixed    $result Result of unserialize() of the $value
   * @return boolean  True if $value is serialized data, otherwise false
   */
  public static function is_serialized($value, &$result = null) {
    // Bit of a give away this one
    if (!is_string($value))
    {
      return false;
    }
    // Serialized false, return true. unserialize() returns false on an
    // invalid string or it could return false if the string is serialized
    // false, eliminate that possibility.
    if ($value === 'b:0;')
    {
      $result = false;
      return true;
    }
    $length = strlen($value);
    $end  = '';
    switch ($value[0])
    {
      case 's':
        if ($value[$length - 2] !== '"')
        {
          return false;
        }
      case 'b':
      case 'i':
      case 'd':
        // This looks odd but it is quicker than isset()ing
        $end .= ';';
      case 'a':
      case 'O':
        $end .= '}';
        if ($value[1] !== ':')
        {
          return false;
        }
        switch ($value[2])
        {
          case 0:
          case 1:
          case 2:
          case 3:
          case 4:
          case 5:
          case 6:
          case 7:
          case 8:
          case 9:
          break;
          default:
            return false;
        }
      case 'N':
        $end .= ';';
        if ($value[$length - 1] !== $end[0])
        {
          return false;
        }
      break;
      default:
        return false;
    }
    if (($result = @unserialize($value)) === false)
    {
      $result = null;
      return false;
    }
    return true;
  }

} // END cuUtils
