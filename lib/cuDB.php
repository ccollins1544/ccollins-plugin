<?php

class cuDB {
	public $dataTable = false;
	public $configTable = false;
	public $searchField = false;
	public $base_prefix = false;

	/**
   * The last error during query.
   *
   * @since 2.5.0
   * @var string
   */
  public $last_error = '';

	public function __construct($Tbl='',$searchFld=''){
		global $wpdb;
		$this->base_prefix = $wpdb->base_prefix;

		// Define Tables
		if(! $this->dataTable ){  $this->dataTable = $this->base_prefix . 'ccollinsupdaterData';     }
		if(! $this->configTable){ $this->configTable = $this->base_prefix . 'ccollinsupdaterConfig'; }

		// Define Search Field
    $this->searchField = (!empty($searchFld)) ? $searchFld : "id";
	}

	public function __destruct(){ // echo "cuDB __destruct(), ";
  }

	/**
	 * Sometimes you just want to run the query function instead of querySelect
	 * Notice that querySelect expects fetch_assoc but that's not always needed.
	 */
	public function query(){
		global $wpdb;
		$args = func_get_args();

		if(func_num_args() == 1){
			$queryStr = $args[0];
			return $wpdb->query($queryStr);
		} // endif

		return $wpdb->query($args);
	}

	public function queryWrite(){
		global $wpdb;
		if(func_num_args() > 1){
			$args = func_get_args();
			return $wpdb->query(call_user_func_array(array($wpdb, 'prepare'), $args));
		} else {
			return $wpdb->query(func_get_arg(0));
		}
	}

	public function querySingle(){
		global $wpdb;
		if(func_num_args() > 1){
			$args = func_get_args();
			return $wpdb->get_var(call_user_func_array(array($wpdb, 'prepare'), $args));
		} else {
			return $wpdb->get_var(func_get_arg(0));
		}
	}

	public function querySingleRec(){ //queryInSprintfFormat, arg1, arg2, ... :: Returns a single assoc-array or null if nothing found.
		global $wpdb;
		if(func_num_args() > 1){
			$args = func_get_args();
			return $wpdb->get_row(call_user_func_array(array($wpdb, 'prepare'), $args), ARRAY_A);
		} else {
			return $wpdb->get_row(func_get_arg(0), ARRAY_A);
		}
	}

	public function flush(){ //Clear cache
		global $wpdb;
		$wpdb->flush();
	}

	public function querySelect(){ //sprintfString, arguments :: always returns array() and will be empty if no results.
		global $wpdb;
		if(func_num_args() > 1){
			$args = func_get_args();
			return $wpdb->get_results(call_user_func_array(array($wpdb, 'prepare'), $args), ARRAY_A);
		} else {
			return $wpdb->get_results(func_get_arg(0), ARRAY_A);
		}
	}

	/*
	*  $wpdb->insert(
 	*    $wpdb->postmeta,
 	*    array(
 	*      'post_id'    => $_POST['post_id'],
 	*      'meta_key'   => $_POST['meta_key'],
 	*      'meta_value' => $_POST['meta_value']
 	*    )
 	*  );
 	*/
	public function insert($table, $data){
		global $wpdb;
		return $wpdb->insert($table, $data);
	}

	public function update($table, $data, $whereKeyValArray, $whereDataFormat=null, $whereFormat=null){
		global $wpdb;
		return $wpdb->update($table, $data, $whereKeyValArray, $whereDataFormat, $whereFormat);
	}

	public function get_var($query){
		global $wpdb;
		return $wpdb->get_var($query);
	}

	public function last_error() {
		global $wpdb;
		return $wpdb->last_error;
	}

	public function check_duplicate($table, $whereKeyValArray){
		if(empty($table) || empty($whereKeyValArray)){
			return 0;
		}

		$compositeKey = ""; // multiple primary keys

		if(sizeof($whereKeyValArray) > 1){
    	$len = sizeof($whereKeyValArray);
    	$i = 0;

	  	foreach ($whereKeyValArray as $col => $val){
	    	$compositeKey .= $col."='".$val."'";

	    	if($i != $len - 1){
	      	$compositeKey .= " AND ";
	    	}

	    	$i++;
		  };

		}else{
		  $KEY = array_keys($whereKeyValArray)[0];
		  $VALUE = array_values($whereKeyValArray)[0];
		  $compositeKey = $KEY."='".$VALUE."'";
		};

		$duplicateFound = "SELECT COUNT(*) FROM {$table} WHERE {$compositeKey}";

		global $wpdb;
		return $wpdb->get_var($duplicateFound);
	}

	public function delete($table, $whereKeyValArray, $whereFormat=null){
		global $wpdb;
		return $wpdb->delete($table, $whereKeyValArray, $whereFormat);
	}

	public function bulkDelete($tableName, $colName, $whereKeyValArray){
		global $wpdb;
		if (is_array($whereKeyValArray)) $whereKeyValArray = implode(',', $whereKeyValArray);

		$sql = "DELETE FROM {$tableName} WHERE {$colName} IN ({$whereKeyValArray})";
		return $wpdb->query($sql);
	}

	public function fetchRow($id){
    $q = "SELECT * FROM {$this->dataTable} WHERE `{$this->searchField}` = '{$id}'";
    return $this->querySingleRec($q);
  }

  public function fetchRows($id){
    $q = "SELECT * FROM {$this->dataTable} WHERE `{$this->searchField}` = '{$id}'";
    return $this->querySelect($q);
  }

  public function fetchRowsLike($id){
    $q = "SELECT * FROM {$this->dataTable} WHERE `{$this->searchField}` LIKE '%{$id}%'";
    return $this->querySelect($q);
  }

  public function fetchValue($id,$field){
    $q = "SELECT `$field` FROM {$this->dataTable} WHERE `{$this->searchField}` = '{$id}'";
    return $this->querySingle($q);
  }

  public function fetchIDArray($idsArray){
    $queryANDstring = "";
    $queryORstring = "";

    if( !is_array($idsArray)){
      return $this->fetchRowsLike($idsArray);

    }else{

      foreach($idsArray as $a){
        // $a = cleanString($a);
        $queryANDstring .= "`{$this->searchField}` LIKE '%{$a}%' AND ";
        $queryORstring .= "`{$this->searchField}` LIKE '%{$a}%' OR ";
      }

      $queryANDstring = rtrim($queryANDstring, " AND ");
      $queryORstring = rtrim($queryORstring, " OR ");
    }

    // BUILD THE QUERY
    $qAND = "SELECT * FROM {$this->dataTable} WHERE {$queryANDstring}";
    $qOR = "SELECT * FROM {$this->dataTable} WHERE {$queryORstring}";

    $resultsArray = $this->querySelect($qAND);

    if( empty($resultsArray)){
      $resultsArray = $this->querySelect($qOR);
    }

    return $resultsArray;
  }

  public function fetchFields($id,$fields){
    $FieldNames = "";
    if( sizeof($fields) > 1 ){
      foreach($fields as $f){ $FieldNames .= "`{$f}`,"; }
      $FieldNames = rtrim($FieldNames, ",");

    }elseif(is_array($fields)){ $FieldNames .= "`".$fields[0]."`";
    }else{ $FieldNames .= "`".$fields."`"; }

    $q = "SELECT {$FieldNames} FROM {$this->dataTable} WHERE `{$this->searchField}` = '{$id}'";

    return $this->querySingle($q);
  }

  public function countByID($id){
    $q = "SELECT COUNT(*) as `count` FROM {$this->dataTable} WHERE `{$this->searchField}` = '{$id}'";
    $row = $this->querySingleRec($q);
    return $row['count'];
  }

  public function columnExists($table, $col){
    $q = $this->querySelect("desc $table");
    foreach($q as $row){
      if($row['Field'] == $col){ return true; }
    }
    return false;
  }

	public function getColumns($table=""){
		if(empty($table)){ $table=$this->dataTable; }
		//$table_structure = $this->querySelect("sp_columns $table");
		$table_structure = $this->querySelect("DESCRIBE $table");

		$columns=array();
		foreach ($table_structure as $e => $col) {
			//array_push($columns,$col['COLUMN_NAME']);
			array_push($columns,$col['Field']);
		}
		return $columns;
	}

	public function get_charset_collate() {
		global $wpdb;
		return $wpdb->get_charset_collate();
  }

	/**
   * NOTE: careful when using $statement->bind_param($param_types, $params);
   * If $params_type is "ssii", then this will fail because it wants to see 4 parameters
   * even though we have an array of $params that only counts as ONE parameter.
   * So that line will work if $param_type is "s" because now there is only one $params.
   */
  public function updateFields($id,$fieldsKeyVal){
    if( !is_array($fieldsKeyVal)){ return $fieldsKeyVal; } // GTFO
		global $wpdb;
		$results = "";

    // IFF primary key does not exist THEN INSERT that record
    if(array_key_exists($this->searchField, $fieldsKeyVal) ){
      $primaryKey = $fieldsKeyVal[$this->searchField];

      if($this->countByID($primaryKey) == 0){
        return $this->insertFields($fieldsKeyVal);
      }

      unset($fieldsKeyVal[$this->searchField]);

    }elseif ($this->countByID($id) == 0 && !is_array($id) ) {
      $fieldsKeyVal[$this->searchField] = $id;
      return $this->insertFields($fieldsKeyVal);
    }

    $qSET = "";
    foreach ($fieldsKeyVal as $key => $val) {
			// $val=mysqli_real_escape_string($wpdb, $val);
      $val=esc_sql($val);
      $qSET .= "`{$key}`='{$val}',";
    }

    $qSET = rtrim($qSET, ", ");
    $queryStr = "UPDATE {$this->dataTable} SET {$qSET} WHERE `{$this->searchField}`='{$id}'";

		return $wpdb->query($queryStr);
  }

  /*
   * Consider 'ON DUPLICATE KEY UPDATE'
   */
  public function insertFields($fieldsKeyVal){
    if( !is_array($fieldsKeyVal)){ return $fieldsKeyVal; } // GTFO
		global $wpdb;

    $results = "";
    $qINSFields = "";
    $qINSValues = "";
    $qINSDuplicates = "";
    foreach ($fieldsKeyVal as $key => $val) {
      $qINSFields .= "{$key},";
			// $val=mysqli_real_escape_string($wpdb, $val);
      $val=esc_sql($val);
      $val=is_numeric($val) ? $val."," : "'".$val."',";
      $qINSValues .= $val;
      $qINSDuplicates .= "{$key}={$val}";
    }

    $qINSFields = rtrim($qINSFields, ",");
    $qINSValues = rtrim($qINSValues, ",");
    $qINSDuplicates = rtrim($qINSDuplicates, ",");
    $queryStr = "INSERT INTO `{$this->dataTable}` ({$qINSFields}) VALUES ({$qINSValues})";

    // IFF primary key already exists THEN UPDATE that record
    if(array_key_exists($this->searchField, $fieldsKeyVal) ){
      $primaryKey = $fieldsKeyVal[$this->searchField];

      if($this->countByID($primaryKey) > 0){
        $queryStr .= " ON DUPLICATE KEY UPDATE {$qINSDuplicates}";
        /*unset($fieldsKeyVal[$this->searchField]);
        return $this->updateFields($primaryKey,$fieldsKeyVal);*/
      }
    }

		return $wpdb->query($queryStr);
  }

  public function deleteIDs($ids){
    $results = "";
    $queryStr = "";

    if( !is_array($ids)){
      $queryStr = "DELETE FROM `{$this->dataTable}` WHERE `{$this->searchField}` ='{$ids}'";

    }else{
      $qIN = "";
      foreach ($ids as $i) { $qIN .= "'{$i}',"; }
      $qIN = rtrim($qIN, ",");
      $queryStr = "DELETE FROM `{$this->dataTable}` WHERE `{$this->searchField}` IN ({$qIN})";
    }

		global $wpdb;
		return $wpdb->query($queryStr);
  } // END deleteIDs
} // END cuDB
