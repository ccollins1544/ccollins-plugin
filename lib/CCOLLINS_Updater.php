<?php 
require_once('cuDB.php');
require_once('cuUtils.php');
require_once('cuConfig.php');
require_once('cuSchema.php');

if ( !class_exists('CCOLLINS_Updater') ) :
class CCOLLINS_Updater {
  protected $file;
  protected $plugin;
  protected $basename;
  protected $base_url; 
  protected $active;
  private static $runInstallCalled = false;
  public static $diagnosticParams = array(
		'debugOn',
	);

  // GitHub Credentials
  private $username;
  private $repository;
  private $authorize_token;
  private $github_response;

  public function __construct ( $file ) {
    $this->file = $file;
    add_action( 'admin_init', array( $this, 'set_plugin_properties' ));
    return $this;
  }

  public function set_plugin_properties() {
    $this->plugin   = get_plugin_data( $this->file );
    $this->basename = plugin_basename( $this->file );
    $this->base_url = trailingslashit(WP_PLUGIN_URL . '/' . current( explode('/', $this->basename ) ));
    $this->active   = is_plugin_active( $this->basename );
  }

  public function set_username( $username=null ) {
    $this->username = $username;
  }

  public function set_repository( $repository=null ) {
    $this->repository = $repository;
  }

  public function authorize( $token=null ) {
    $this->authorize_token = $token;
  }

  private function get_repository_info() {
    if( is_null( $this->username )){
      $this->username = cuConfig::get('license_username', false, false);
    }

    if( is_null( $this->repository )){
      $this->repository = cuConfig::get('license_repo', false, false);
    }

    if( is_null( $this->authorize_token )){
      $this->authorize_token = cuConfig::get('license_code', false, false);
    }

    if ( is_null( $this->github_response ) ) { // Do we have a response?
      $request_uri = sprintf( 'https://api.github.com/repos/%s/%s/releases', $this->username, $this->repository ); // Build URI
      
      if( $this->authorize_token ) { // Is there an access token?
        $request_uri = add_query_arg( 'access_token', $this->authorize_token, $request_uri ); // Append it
      }
      
      $response = json_decode( wp_remote_retrieve_body( wp_remote_get( $request_uri ) ), true ); // Get JSON and parse it
      if( is_array( $response ) ) { // If it is an array
        $response = current( $response ); // Get the first item
      }

      if( $this->authorize_token ) { // Is there an access token?
        $response['zipball_url'] = add_query_arg( 'access_token', $this->authorize_token, $response['zipball_url'] ); // Update our zip url with token
      }

      $this->github_response = $response; // Set it to our property  
    }
  }

  public static function install_actions(){
    register_activation_hook(CU_PLUGIN_PATH, 'CCOLLINS_Updater::installPlugin');
    register_deactivation_hook(CU_PLUGIN_PATH, 'CCOLLINS_Updater::uninstallPlugin');

    $versionInOptions = get_option('ccollinsupdater_version', false);
		if( (! $versionInOptions) || version_compare(CCOLLINS_UPDATER_VERSION, $versionInOptions, '>')){
			//Either there is no version in options or the version in options is greater and we need to run the upgrade
			self::runInstall();
		}

    // Setup functions, Ajax hooks, and main_init [public/private:not-logged/logged-in]
		if (file_exists(CU_PLUGIN_DIR .'/func.php' )){ 		  include_once CU_PLUGIN_DIR .'/func.php'; }
		if (file_exists(CU_PLUGIN_DIR .'/ajax-hooks.php')){ include_once CU_PLUGIN_DIR .'/ajax-hooks.php'; }
    add_action('init', 'CCOLLINS_Updater::main_init');

    if(is_admin()){
      // Setup Admin Dashboard Pages
      add_action( 'admin_enqueue_scripts', 'CCOLLINS_Updater::enqueueDashboard' );

      // Setup Admin Menus
      add_action( 'admin_menu', 'CCOLLINS_Updater::admin_menus' );
    }
  }

  public function initialize() {
    add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'modify_transient' ), 10, 1 );
    add_filter( 'plugins_api', array( $this, 'plugin_popup' ), 10, 3);
    add_filter( 'upgrader_post_install', array( $this, 'after_install' ), 10, 3 );
  }

  public function modify_transient( $transient ) {
    if( property_exists( $transient, 'checked') ) { // Check if transient has a checked property
      if( $checked = $transient->checked ) { // Did WordPress check for updates?
        $this->get_repository_info(); // Get the repo info
        $out_of_date = version_compare( $this->github_response['tag_name'], $checked[$this->basename], 'gt' ); // Check if we're out of date
        
        if( $out_of_date ) {
          $new_files = $this->github_response['zipball_url']; // Get the ZIP
          $slug = current( explode('/', $this->basename ) ); // Create valid slug
          $plugin = array( // setup our plugin info
            'url' => $this->plugin["PluginURI"],
            'slug' => $slug,
            'package' => $new_files,
            'new_version' => $this->github_response['tag_name']
          );
          
          $transient->response[ $this->basename ] = (object) $plugin; // Return it in response
        }
      }
    }

    return $transient; // Return filtered transient
  }

  public function plugin_popup( $result, $action, $args ) {
    if( ! empty( $args->slug ) ) { // If there is a slug
      if( $args->slug == current( explode( '/' , $this->basename ) ) ) { // And it's our slug
        $this->get_repository_info(); // Get our repo info
        // Set it to an array
        $plugin = array(
          'name'              => $this->plugin["Name"],
          'slug'              => $this->basename,
          'version'           => $this->github_response['tag_name'],
          'author'            => $this->plugin["AuthorName"],
          'author_profile'    => $this->plugin["AuthorURI"],
          'last_updated'      => $this->github_response['published_at'],
          'homepage'          => $this->plugin["PluginURI"],
          'short_description' => $this->plugin["Description"],
          'sections'          => array( 
              'Description'   => $this->plugin["Description"],
              'Updates'       => $this->github_response['body'],
          ),
          'download_link'     => $this->github_response['zipball_url']
        );
        return (object) $plugin; // Return the data
      }
    }   
    return $result; // Otherwise return default
  }

  public function after_install( $response, $hook_extra, $result ) {
    global $wp_filesystem; // Get global FS object
  
    $install_directory = plugin_dir_path( $this->file ); // Our plugin directory 
    $wp_filesystem->move( $result['destination'], $install_directory ); // Move files to the plugin dir
    $result['destination'] = $install_directory; // Set the destination for the rest of the stack
  
    if ( $this->active ) { // If it was active
      activate_plugin( $this->basename ); // Reactivate
    }

    return $result;
  }

  public static function installPlugin(){ self::runInstall(); }

	public static function runInstall(){
		if(self::$runInstallCalled){ return; }
		self::$runInstallCalled = true;
	  update_option(CU_VERSION_KEY, CCOLLINS_UPDATER_VERSION); //In case we have a fatal error we don't want to keep running install.
    
		$schema = new cuSchema();
		$schema->createAll(); // If not exists

		/** @var wpdb $wpdb */
		global $wpdb;

		//6.1.15
		$configTable = "{$wpdb->base_prefix}ccollinsupdaterConfig";
		$hasAutoload = $wpdb->get_col($wpdb->prepare(<<<SQL
SELECT * FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA=DATABASE()
AND COLUMN_NAME='autoload'
AND TABLE_NAME=%s
SQL
			, $configTable));
		if (!$hasAutoload) {
			$wpdb->query("ALTER TABLE {$configTable} ADD COLUMN autoload ENUM('no', 'yes') NOT NULL DEFAULT 'yes'");
		}

		cuConfig::setDefaults(); // If not set
		update_option('ccollinsupdaterActivated', 1);

		global $cuDB;
		if(!isset($cuDB) || !is_object($cuDB)) { $cuDB = new cuDB();   }
		$cuDB->query("SET TIME_ZONE = '-07:00'"); // Mountain Standard Time (America/Denver)

	} // END runInstall()

	public static function uninstallPlugin(){
		update_option('ccollinsupdaterActivated', 0);

		if(cuConfig::get('deleteTablesOnDeact')){
			$schema = new cuSchema();
			$schema->dropAll();
			cuConfig::updateTableExists();

			foreach(array('ccollinsupdater_version', 'ccollinsupdaterActivated') as $opt){
				if (is_multisite() && function_exists('delete_network_option')) {
					delete_network_option(null, $opt);
				} // endif
				delete_option($opt);
			} // endforeach
		} // endif
	}

  public static function main_init() { // This runs as add_action('init', 'CCOLLINS_Updater::main_init');
		global $cuDB;
		if(!isset($cuDB) || !is_object($cuDB)) { $cuDB = new cuDB();   }
	} // END main_init()

  public static function enqueueDashboard() {
    wp_enqueue_style( 'load-fa', 'https://use.fontawesome.com/releases/v5.13.0/css/all.css' );
    wp_enqueue_style('cu-adminbar', cuUtils::getBaseURL() . 'css/cu-adminbar.css', '', CCOLLINS_UPDATER_VERSION);
  }
  
  public static function admin_menus(){
		$dashboardExtra = " <span class='update-plugins cu-menu-badge cu-notification-count-container' title='notificationCount'><span class='update-count cu-notification-count-value'>0</span></span>";
		// $dashboardExtra = " ";

		$parent_page_title  = "ccollins Updater";
  	$parent_menu_title  = "ccollins Updater{$dashboardExtra}";
  	$capability  				= "activate_plugins";
  	$parent_slug 				= "ccollins-updater";
  	$parent_function    = "CCOLLINS_Updater::dashboard_menu";
  	$icon_url    				= cuUtils::getBaseURL() . 'images/crown-solid.svg';
  	$position    				= null;

  	add_menu_page( $parent_page_title, $parent_menu_title, $capability, $parent_slug, $parent_function, $icon_url, $position );
		add_submenu_page($parent_slug, "Settings", "Settings", $capability, $parent_slug, $parent_function);
	}

	public static function dashboard_menu() { require('dashboard_menu.php'); } 
}
endif; 