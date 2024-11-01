<?php
// Class for configuring Facebook App id and App secret key.  
class Wp2sfba_Fbapp_Auth {
	
	function __construct() {
		// do stuff 
	}	
	public function wp2sfba_facebook_auth($uid) {		
		add_action('wp_enqueue_scripts', array($this,'wp2sfba_fb_auth_enqueuescripts'),15);
		 
		$wp2sfba_wp_redirect_tourl = $_REQUEST['redirect_to'];
		require_once(WP2SFBA_TEMPLATES.'/fbapp_auth.php');
	}
	 
	// Enqueue required css and js file required by plugin
	function wp2sfba_fb_auth_enqueuescripts() {	
		$fbappid = esc_attr(get_option('wp2sfba_appid'));
		$afterlogin_redirect_to = $_REQUEST['redirect_to'];		
		wp_register_style( 'wp2sfba_css', WP2SFBA_URL.'/assets/css/style.css' );
		wp_enqueue_style( 'wp2sfba_css');
		  
		wp_enqueue_script(array('jquery'));
		wp_enqueue_script('wp2sfba_js1', WP2SFBA_URL.'/assets/js/wp2sfba_script.js');		 
	
		wp_localize_script( 'wp2sfba_js1', 'wp2sfba_ajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ), 'fbappid' => $fbappid, 'afterlogin_redirect_to' => $afterlogin_redirect_to, 'abs_url' => WP2SFBA_URL ) );  
	}
	
}

?>