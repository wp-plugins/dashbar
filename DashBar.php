<?php
/*
Plugin Name: DashBar
Plugin URI: http://z720.net/blog/categories/web/wordpress/dashbar
Description: Enhance admin bar to add features present in previous versions of DashBar: mainly edit posts display on current page
Version: 3.0
Author: Sebastien Erard
Author URI: http://z720.net/
*/


if(class_exists('DashBar')) {
	die('You must deactivate previous version of DashBar Plugin for this version to work');
} else {

/* Currently Dashbar only support PHP5 */
if (version_compare(PHP_VERSION, '5.0.0', '<')) {
    class DashBar {
		function DashBar() {
			add_action('admin_notices', array(&$this, 'warning'));
		}
		function warning() {
			echo '
		<div id="dashbar-warning" class="updated fade">
			<p><strong>WordPress seems to be running with PHP 4 but DashBar only supports PHP 5.</strong></p>
			<p>You can ask your hosting provider if PHP5 is installed on the server and how to activate it. DashBar will be ineffective until PHP 5 is activated.</p>
		</div>
			';
		}
	}
	
} else {

/* Class DashBar the plugin itself */	 
	class DashBar {
	/* Attributes */	
		var $prefixe = 'DashBar';
		var $domain = 'DashBar';
		var $version = '3.0';
	
		var $default = array( 'bgcolor' => '#464646'
													,'height' => '14px'
													,'color' => '#eee'
													,'fgcolor' => '#777'
													,'acolor' => '#eee'
													,'popup' => false
												);
		var $values = array();
		var $user_menu = array();
		
		var $erreur = false;
		
		function getInstallDir() {
			return basename(dirname(__FILE__));
		}
		
		function DashBar() {
// i18n : load texts
			load_plugin_textdomain($this->domain,false,basename(dirname(__FILE__)));
// Load options to overwrite defaults
/* TODO: Cleanup
			$c = get_option($this->prefixe);
			foreach($this->default as $k => $v) {
				if(($c === false) or (!isset($c[$k]))) {
					$this->values[$k] = $this->default[$k];
				} else {
					$this->values[$k] = $c[$k];
				}
			}
// save options to database or create default for first time
			update_option($this->prefixe, $this->values);
// WP init
*/
			add_action('init', array(&$this, 'init'));
// WP admin hooks
// TODO: Cleanup
//			add_action('admin_head', array(&$this, 'admin_page_header'));
//			add_action('admin_menu', array(&$this, 'admin_pages')); 
		}
		
		function __($str) {
			return __($str, $this->domain);
		}
		
		function init() {
            add_action( 'admin_bar_menu', array(&$this, 'update_bar'), 31 );
            // add_action('wp_before_admin_bar_render', array(&$this, 'update_bar'));
            add_action( 'wp_before_admin_bar_render', array(&$this, 'add_network'));
		}

        /* TODO: Cleanup
		function admin_pages() {
			add_options_page($this->__('DashBar'), $this->__('DashBar'), 'manage_options', basename(__FILE__), array(&$this, 'admin_page'));		 
		}
        /**/
	
        function update_bar() {
            global $wp_admin_bar;
            global $wp_query;
            // We're on a page with a list of posts
            if(!empty($wp_query->posts)) {
                // display menu if user has edit permission
                if(current_user_can('edit_posts')) {
                    $id = 'dashbar_edit'; // Edit menu
                    $wp_admin_bar->add_menu(array('id' => $id, 'title' => $this->__('Edit'), 'href'=> admin_url('post.php'))); 
                    rewind_posts();
                    global $post;
                    // loop for posts on current page
                    while(have_posts()) {
                        the_post();
                        $current_object = $post;
                        // link to edit post from wp-includes/admin-bar.php @ 173 (v3.1.1)
                        //if ( ! empty( $current_object->post_type ) && ( $post_type_object = get_post_type_object( $current_object->post_type ) ) && current_user_can( $post_type_object->cap->edit_post, $current_object->ID ) && $post_type_object->show_ui ) {
                            $wp_admin_bar->add_menu( array('id'=>$id.'-'.$current_object->ID, 'parent' => $id, 'title' => $current_object->post_title,  'href' => get_edit_post_link( $current_object->ID ) ) );
                        //}
                    }
                }
            }

        }
        
        function add_network() {
            if(is_multisite() && current_user_can('manage_network') && !is_network_admin()) {
                global $wp_admin_bar;
                /* Add the 'My Account' menu */
                $avatar = get_avatar( get_current_user_id(), 16 );
                $id = ( ! empty( $avatar ) ) ? 'my-account-with-avatar' : 'my-account';
                $wp_admin_bar->add_menu(array('id'=>'network_admin', 'parent'=> $id, 'href'=>network_admin_url(), 'title' => __('Network Admin')));
            }
        }
	}
}
}

// run the plugin
$the_dashbar = new DashBar();


?>