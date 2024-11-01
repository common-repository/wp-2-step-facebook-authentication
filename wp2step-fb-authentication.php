<?php 
/**
 * Plugin Name: WP 2 Step Facebook Authentication
 * Plugin URI: https://eluminoustechnologies.com/
 * Description: This plugin adds an extra layer of Facebook authentication to Wordpress account.
 * Version: 1.0.0
 * Text Domain: wp2step-fb-authentication
 * Author: Rajendra Mahajan
 * Author URI: https://wordpress.org/support/users/emahajan/
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */
  
 // Plugin directory url.
 define('WP2SFBA_URL', WP_PLUGIN_URL."/".dirname( plugin_basename( __FILE__ ) ) );
 
    /**
     * For development: To display error, set WP_DEBUG to true.
     * In production: set it to false
    */
    define('WP_DEBUG',true);
 
 // Get absolute path 
 if ( !defined('WP2SFBA_ABSPATH'))
    define('WP2SFBA_ABSPATH', dirname(__FILE__) . '/');

 // Get absolute path 
if ( !defined('ABSPATH'))
    define('ABSPATH', dirname(__FILE__) . '/');

/**
 *  Current plugin version.
 */
 if ( ! defined( 'WP2STEP_FACEBOOK_AUTHENTICATION_VER' ) ) {
	define( 'WP2STEP_FACEBOOK_AUTHENTICATION_VER', '1.0.0' );
 }
 
 define('WP2SFBA_INC',WP2SFBA_ABSPATH.'includes');
 define('WP2SFBA_TEMPLATES',WP2SFBA_ABSPATH.'templates');
  
 // Main Class
 class Wp2StepFacebookAuthentication {
	
	var $wp2sfba_page_menu;
	
	// Obj
	var $fbauth;
	
	// facebook app id
	var $wp2sfba_fbappid;
	
	// Flag to maintain, while roaming in admin area
	var $wp2sfba_is_allowed_to_skip;
	
	// for user info
	var $wp2sfba_uid;
	var $wp2sfba_user_login;
	var $wp2sfba_display_name;
	var $wp2sfba_user_email;
	
	// for facebook config
	var $wp2sfba_fb_appid;
	var $wp2sfba_fb_app_secretkey;
		
	function __construct() {	
		global $wpdb;
        require_once(WP2SFBA_INC . '/fbapp_auth.php');
		 
		//Initial loading				 
		$is_fb_flag = get_option('wp2sfba_skip_fb_auth');	
		$this->wp2sfba_is_allowed_to_skip = $is_fb_flag['radio1'];
		add_action('admin_init',array($this,'init'),0);
		add_action('admin_init',array($this,'wp2sfba_create_facebook_users_table'));		
		add_action('wp_login', array($this,'wp2sfba_fb_auth_process'), 30, 2); 
		add_action('admin_menu', array($this, 'wp2sfba_admin_menu'));
		
		add_action('wp_logout', array($this,'end_session')); 
		add_action('end_session_action', array($this,'end_session'));
		   
		// Check for facebook authentication flag, if not set redirect to login
		if(empty($_SERVER['HTTP_X_REQUESTED_WITH']) && is_admin()){ 
			if(!session_id()) {
				session_start();
			} 			 
			if($this->wp2sfba_is_allowed_to_skip=='n' && !isset($_SESSION['wp2sfba_fbuser_flag'])) {				 
				@header("Location:".wp_login_url());				 
			}  		  	 
		}
	}	
	
	function end_session() { 
		if(!session_id()) {
			session_start();
		} 
		unset($_SESSION['wp2sfba_fbuser_flag']);
		session_destroy();		 
	}
	
	function wp2sfba_fb_auth_process($username, $password ) {
	  	
	  if (!empty($username) && !empty($password)) {
		 
		if (empty($this->fbauth)) {		 
			$this->fbauth = new Wp2sfba_Fbapp_Auth($this);				 
		}	
	   
	    // For role based authentication.
		$arr_wp2sfba_role = get_option('wp2sfba_role'); 	
		$wp2sfba_rsauth = wp_authenticate($_POST['log'], $_POST['pwd'] );	
		 
		$wp2sfba_final_array = array();
		foreach($wp2sfba_rsauth->roles as $key=>$val){
			if(in_array($val,$arr_wp2sfba_role)){
				$wp2sfba_final_array[] = $val;
			}
		}
		 
		if(count($wp2sfba_final_array)>0) {
		  $this->fbauth->wp2sfba_facebook_auth($this->wp2sfba_uid);		
		} else {
		    isset($_POST['redirect_to'])? $wp2sfba_redirect_to = $_POST['redirect_to']: $wp2sfba_redirect_to = home_url();
			wp_redirect($wp2sfba_redirect_to);
		}
		exit;
	  }
	}
	// initial processing	
	function init() {
		// if session is not start, then start it.
		if(!session_id()) {
			session_start();
		} 
		$this->load(); 
		//register our settings
		register_setting( 'wp2sfba-fb-authentication-settings-group', 'wp2sfba_appid' , array($this,'wp2sfba_fb_authentication_settings_validation'));
		register_setting( 'wp2sfba-fb-authentication-settings-group', 'wp2sfba_appsecretkey' , array($this,'wp2sfba_fb_authentication_settings_validation'));
		register_setting( 'wp2sfba-fb-authentication-settings-group', 'wp2sfba_skip_fb_auth' );	
		register_setting( 'wp2sfba-fb-authentication-settings-group', 'wp2sfba_role' );

		add_action( 'wp_ajax_nopriv_wp2sfba_ajx_save_fbuser_info', array($this,'wp2sfba_ajx_save_fbuser_info') );
		add_action( 'wp_ajax_wp2sfba_ajx_save_fbuser_info', array($this,'wp2sfba_ajx_save_fbuser_info') );	
	} 
	
	// validation Facebook s
	function wp2sfba_fb_authentication_settings_validation($input){
	 
		$input['wp2sfba_appid'] = sanitize_text_field( $input['wp2sfba_appid'] );

		// Strip HTML tags from text to make it safe
		$input['wp2sfba_appsecretkey'] = sanitize_text_field( $input['wp2sfba_appsecretkey'] );
	  
		return $input;
		 
	}
	
	function load() {
		$this->fbauth = new Wp2sfba_Fbapp_Auth($this);	
		if (empty($this->fbauth)) {
			// do stuff		 
			$this->fbauth = new Wp2sfba_Fbapp_Auth($this);				 
		} 
	}
	 
	// add menu to admin
	function wp2sfba_admin_menu() {
		add_menu_page('WP 2Step FB Setting','2 Step FB Setting','administrator', __FILE__,array($this,'wp2sfba_admin_facebook_config_page'),'',100);   		 
    }
	
	// include facebook config file
	function wp2sfba_admin_facebook_config_page() {		 
		require_once(WP2SFBA_TEMPLATES . '/fbapp_config.php');
	}		
	
	function wp2sfba_start_fbuser_session() {		
		$_SESSION['wp2sfba_fbuser_flag'] = 'available'; 	 
	}
	
	// function to check and store the facebook user with wordpress user
	function wp2sfba_checkAndStoreFBUser($wp_usr_id,$wp_usr_email,$usr_json) { //$fb_usr_id,$fb_usr_email  
		global $wpdb;
		if(!session_id()){
		  session_start();
		} 
		$fb_usr_id = $usr_json['id'];
		$fb_usr_email = $usr_json['email'];
		
		// check if user exist, else save record
		$table_name = $wpdb->prefix.'wp2sfba_facebook_users';				
		$rs_vals = $wpdb->get_row( "SELECT oauth_uid,email FROM $table_name where wpuid=$wp_usr_id" );		 	 
		//print_r($usr_json);
		 
		if(count($rs_vals)>0 && $fb_usr_email==$rs_vals->email) {
			 $this->wp2sfba_start_fbuser_session(); 
			echo json_encode(array('status'=> 'success', 'message' => 'Facebook records matched successfully.'));
			die();
			 
		} else {			 
			//insert record to fb table.
			$wp2sfba_sql = $wpdb->query( 
				$wpdb->prepare("
					INSERT INTO `$table_name`
						( `wpuid`, `oauth_provider`, `oauth_uid`, `first_name`, `last_name`, `email`, `picture`)
					VALUES ( %d, %s, %s, %s, %s, %s, %s  )
					", 
					array(
						$wp_usr_id, 			
						'facebook',		
						$fb_usr_id,	
						$usr_json['first_name'],
						$usr_json['last_name'],
						$usr_json['email'],
						$usr_json['picture']['data']['url'] 
					) 
				) 
			);			
			 
			// $wpdb->show_errors();
			if($wp2sfba_sql) { 
				$this->wp2sfba_start_fbuser_session();
				echo json_encode(array('status'=> 'success', 'message' => 'Process FB user data successfully.'));
			} else {
				echo json_encode(array('status'=> 'failed', 'message' => 'Unable to save user record to table.'));
			} 
		}		 
	}
	/*
	
	*/
	// Ajax Function: to save facebook user info
	function wp2sfba_ajx_save_fbuser_info(){
	 
		global $wpdb;
		$table_name = $wpdb->prefix.'wp2sfba_facebook_users';		
		$udata = stripslashes($_POST['userData']); 
		$usr_json = json_decode($udata,true);
		
		$hf_user = wp_get_current_user();			 	
		$hf_user_id = $hf_user->ID;		
		$hf_user_email = $hf_user->user_email;		 
		
		// Check if WP User Email == FB Email
		if(trim($usr_json['email']) == trim($hf_user_email)) {
			
			//Check in DB exist/not, if not then save it.
			$this->wp2sfba_checkAndStoreFBUser($hf_user_id,$hf_user_email,$usr_json);	//$usr_json['id'],$usr_json['email']
			
		} else {
			// Failed to match records..login again to proceed.
			wp_logout();	
			echo json_encode(array('status'=> 'error', 'message' => "Error: Your account email id doesn't matched with Facebook email id. Try <a href='".wp_login_url()."' title='Login'>Login Again</a>."));
			die();
			
		}		 
		die();
	}	
	
	function wp2sfba_create_facebook_users_table() {
		global $wpdb;
		
		$charset_collate = $wpdb->get_charset_collate();
		$table_name = $wpdb->prefix . 'wp2sfba_facebook_users';
  		
		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`wpuid` int(11) NOT NULL,
				`oauth_provider` enum('','facebook') COLLATE utf8_unicode_ci NOT NULL,
				`oauth_uid` bigint(50) NOT NULL,
				`first_name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
				`last_name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
				`email` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
				`gender` varchar(10) COLLATE utf8_unicode_ci,
				`locale` varchar(10) COLLATE utf8_unicode_ci,
				`picture` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
				`link` varchar(255) COLLATE utf8_unicode_ci,
				`created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, 
				
				 UNIQUE KEY id(id)
				) $charset_collate;";
		
		require_once(ABSPATH.'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	 }
	
 } // Classe
 
 // Call class
 new Wp2StepFacebookAuthentication();
 
?>