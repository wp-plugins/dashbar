<?php
/*
Plugin Name: DashBar
Plugin URI: http://z720.net/produits/wordpress/dashbar
Description: Display a WordPress.com-like navigation bar for logged users: direct acces to Dashboard, Write, Edit, Awaiting Moderation, Profile.
Version: 1.1
Author: Sebastien Erard
Author URI: http://z720.net/
*/

class DashBar {
	var $color = '#14568a';
	
	function DashBar() {
		$c = get_option('DashBar_bgcolor');
		if($c) {
			$this->color = $c;
		}
		add_action('init', array(&$this, 'init'));
		//add_filter('admin_head', array(&$this, 'admin_header'));
		//add_action('admin_menu', array(&$this, 'set_mgt')); 			
	}

  function init() {
		get_currentuserinfo();
		global $userdata, $user_login, $user_identity;
		if($user_login) {
			add_filter('wp_footer', array(&$this, 'display_footer'));
			add_filter('wp_head', array(&$this, 'display_header'));
		}
  }
	
	function set_mgt() {
		add_submenu_page('plugins.php', __('Barre d\'admin', $this->domain), __('Barre d\'admin',$this->domain), 3, basename(__FILE__), array(&$this, 'management_page'));
	}

	function display_footer() {
		echo '<div id="DashBar">'
				.'<div id="quicklinks">'
				.'<ul>'
				.'<li><a href="'.get_option('siteurl').'/wp-admin/">'.__('Dashboard').'</a></li>'
				.'<li><a href="'.get_option('siteurl').'/wp-admin/post.php">'.__('Write Post').'</a></li>';
		rewind_posts();
		if(is_single()||is_page()) {
			the_post();
			global $id;
			echo '<li><a href="'.get_option('siteurl').'/wp-admin/post.php?action=edit&amp;post='.$id.'" title="'.get_the_title().'">'.__('Edit').'</a></li>';
		} elseif(!is_404()) {
			if(have_posts()){
				echo '<li><form action="'.get_option('siteurl').'/wp-admin/post.php" method="get"><input type="hidden" name="action" value="edit" /><select name="post">';
				while(have_posts()) {
					the_post();
					global $id;
					echo '<option value="'.$id.'">'.get_the_title('','',false).'</option>';
				}
				echo '</select><input type="submit" value="&raquo;" /></form></li>';
			}
		}
		global $wpdb;
		$awaiting_mod = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->comments WHERE comment_approved = '0'");
		if($awaiting_mod) {
			echo '<li><a href="'.get_option('siteurl').'/wp-admin/moderation.php">'.sprintf(__("Awaiting Moderation (%s)"), $awaiting_mod).'</a></li>';
		}
		global $user_identity;
		echo '</ul>'
				.'</div>'
				.'<div id="loginout">'
				.'<strong>'
				.$user_identity
				.'</strong>. [<a href="'.get_option('siteurl').'/wp-login.php?action=logout">'.__('Logout').'</a>, <a href="'.get_option('siteurl').'/wp-admin/profile.php">'.__('Your Profile').'</a>]</div>'
				.'</div>';
	}

		
	function display_header() {
?>
<style type="text/css">
#DashBar {
	position: absolute;
	top: 0;
	left: 0;
	background: <?php echo $this->color; ?>;
	width: 100%;
	height: 30px;
	font-family: "Lucida Grande", "Lucida Sans Unicode", Tahoma, Verdana;
	font-size: 12px;
}

#DashBar #quicklinks ul {
	list-style: none;
	margin: 0;
	padding: 0;
}

#DashBar #quicklinks li {
	float: left;
}

#DashBar #quicklinks a,
#DashBar #quicklinks form {
	display: block;
	padding: .5em 1em;
	color: #c3def1;
	text-decoration: none;
	font-weight: normal;
}
#DashBar #quicklinks select {width:200px;}
#DashBar #quicklinks a:hover {
	background: #6da6d1;
	color: black;
}

#DashBar #loginout {
	float:right;
	margin: 0 1em 0 0;
	padding: 7px 0 0 0;
	color: #c3def1;
}

#DashBar #loginout strong {
	color: #c3def1;
}

#DashBar #loginout a,
#DashBar #loginout a:hover {
	color: white;
}

body {
	padding-top: 30px;
}
</style>
<?php		
	}
	
}

$the_dashbar_variable = new DashBar();


?>