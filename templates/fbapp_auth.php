<?php get_header(); ?> 
<div class="error-msg" id="errorFBnotice" style="display:none; text-align:center;"></div>
<div class="success-msg" id="successFBnotice" style="display:none;text-align:center;"></div> 
<div class="wrap" style="text-align:center; padding-top:35px;min-height:330px;;max-height:530px;">
 
<!-- Display login status -->
<div id="status"><img src="<?php echo WP2SFBA_URL.'/assets/load.gif';?>"></div>

<!-- Display user profile data -->
<div id="userData" style="display:none;"></div>
<!-- Facebook login or logout button --> 
<a href="javascript:void(0);" onclick="fbLogin()" id="fbLink" style="display:none;"><button class="loginBtn loginBtn--facebook">Connect with Facebook</button></a>
<?php  
// to show skip facebook authentication link 
$myplug_options = get_option('wp2sfba_skip_fb_auth');	 
if($myplug_options['radio1']=='y') {
?>
<br /><br />
<a href="<?php echo $wp2sfba_wp_redirect_tourl;?>" class="skipCls blue">Skip</a>
<?php } ?>
</div>