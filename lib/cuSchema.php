<?php

//require_once( ABSPATH . 'wp-admin/includes/upgrade.php' ); // required for dbDelta
require_once('cuDB.php');

class cuSchema {

	//private $wpdb; only use if a public function needs to use wpdb. It would be used in a public function like this  $this->wpdb->prefix for example
	private $db = false;
	private $prefix = false;
	private $charset_collate = false;
	private $tables = array(
		"ccollinsupdaterConfig" => "(
  		`name` varchar(100) NOT NULL,
  		`val` text,
  		`autoload` enum('no','yes') NOT NULL DEFAULT 'yes',
  		PRIMARY KEY (`name`)
		)",
	);

	public function __construct(){
		global $wpdb;
		$this->db = new cuDB();
		$this->prefix = $wpdb->base_prefix;
		$this->charset_collate = $wpdb->get_charset_collate();
	}

	public function createAll(){
		foreach($this->tables as $table => $def){
			$this->db->queryWrite("create table IF NOT EXISTS " . $this->prefix . $table . " " . $def . " " . $this->charset_collate . ";");
		}
	}

	public function dropAll(){
		foreach($this->tables as $table => $def){
			$this->db->queryWrite("drop table if exists " . $this->prefix . $table);
		}
	}

	public function create($table){
		$this->db->queryWrite("create table IF NOT EXISTS " . $this->prefix . $table . " " . $this->tables[$table] . " " . $this->charset_collate . ";");
	}

	public function drop($table){
	  $this->db->queryWrite("drop table if exists " . $this->prefix . $table);
	}

}
