<?php
/*
Plugin Name: DashBar
Plugin URI: http://z720.net/produits/wordpress/dashbar
Description: Display a Enhanced WordPress.com-like navigation bar for logged users: direct acces to Dashboard, Write, Edit, Awaiting Moderation, Profile...
Version: 2.7
Author: Sebastien Erard
Author URI: http://z720.net/
*/


if(class_exists('DashBar')) {
	die('You must deactivate previous version of DashBar Plugin for this version to work');
} else {


/**
 * Class DashBarLink
 * Manage a link in the DashBar
*/
	class DashBarLink {
		var $label = '';
		var $url = '';
		var $credential = '';
		var $children = array();
		static $popup = false;
		
		function DashBarLink($label, $url, $credential = '', $children = array()) {
			$this->setLabel($label);
			$this->setURL($url);
			$this->setCredential($credential);
			$this->setChildren($children);
		}

/* Label Management the text of the link */ 
		function setLabel($label) { $this->label = $label; }
		function getLabel() { return $this->label; }

/* Url Management the relative url of the page to display within the wordpress install */ 
		function setUrl($url) { $this->url = get_option('siteurl').$url; }
		function setExtUrl($url) { $this->url = $url; }
		function getUrl() { return $this->url; }

/* Credential Management : credential needed to display the link */ 
		function setCredential($cred) { $this->credential = $cred; }
		function getCredential() { return $this->credential; }
		
/* Sub Links management */	
		function setChildren($children) {
			$c = array();
			foreach($children as $child) {
				if($child instanceof DashBarLink) {
					$c[] = $child;
				}
			}
			$this->children = $c;
		}
		function getChildren() { return $this->children; }
		function hasChildren() { return (empty($this->children) == true); }
		
/* popup flag */
		public static function setPopup($popup) {
			DashBarLink::$popup = (boolean) $popup;
		}
		public static function getPopup() {
			return DashBarLink::$popup;
		}

/* Building the Menu (list of links)	*/
		function build($elts = array()) {
			$o = '';
			if(empty($elts)) { return ''; }
			foreach($elts as $elt) {
				if($elt instanceof DashBarLink) {
					$user_can = true;
					if($elt->getCredential() != '') {
						$user_can = current_user_can($elt->getCredential());
					}
					if($user_can) {
						if(DashBarLink::getPopup()) {
							$str = '<li><a href="'.$elt->getUrl().'" target="_new">'.$elt->getLabel().'</a>';
						} else {
							$str = '<li><a href="'.$elt->getUrl().'">'.$elt->getLabel().'</a>';
						}
						$str .= DashBarLink::build($elt->getChildren());
						$str .= '</li>';
						$o .= $str;
					}
				}
			}
			if($o != '') {
				$o = '<ul>'.$o.'</ul>';
			}
			return $o;
		}
	}

/* Class DashBar the plugin itself */	 
	class DashBar {
	/* Attributes */	
		var $prefixe = 'DashBar';
		var $domain = 'DashBar';
		var $version = '2.7.0-dev';
	
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
			add_action('init', array(&$this, 'init'));
// WP admin hooks
			add_action('admin_head', array(&$this, 'admin_page_header'));
			add_action('admin_menu', array(&$this, 'admin_pages')); 
		
		}
		
		function __($str) {
			return __($str, $this->domain);
		}
		
		function init() {
			global $user_ID;
			get_currentuserinfo();
			if($user_ID != '') {
				add_filter('wp_footer', array(&$this, 'display_bar'));
				add_filter('wp_head', array(&$this, 'display_style'));
				add_filter('wp_head', array(&$this, 'display_script'));
			}
		}

		function admin_pages() {
			add_options_page($this->__('DashBar'), $this->__('DashBar'), 'manage_options', basename(__FILE__), array(&$this, 'admin_page'));		 
		}
	
		function display_bar() {
			DashBarLink::setPopup($this->values['popup']);
			get_currentuserinfo();
			global $current_user;
/* Links construction */			
			$links = array();
/* Dashboard Links, Profile and Logout */
			$links[] = new DashBarLink($this->__('Dashboard')
																, '/wp-admin/index.php'
																, 'read'
																, array(new DashBarLink($this->__('My Account'), '/wp-admin/profile.php')
																			 ,new DashBarLink($this->__('Logout'), '/wp_login.php?action=logout')
																			 )
																);
/* Post related Menu : context related edit (posts in the loop)*/
			 global $id, $authordata;
			rewind_posts();
			$edit_ar = array();
			if(is_single()||is_page()) {
				the_post();
				$links[] = new DashBarLink($this->__('Edit'), '/wp-admin/post.php?action=edit&amp;post='.$id, ($current_user->ID != $authordata->ID) ? 'edit_others_posts' : 'edit_posts');
			} elseif(!is_404()) {
				 while(have_posts()) {
					 the_post();
					 $edit_ar[] = new DashBarLink(get_the_title(), '/wp-admin/post.php?action=edit&amp;post='.$id, ($current_user->ID != $authordata->ID) ? 'edit_others_posts' : 'edit_posts');
				 }
				 if(!empty($edit_ar)) {
					 $links[] = new DashBarLink($this->__('Edit'), '/wp-admin/edit.php', 'edit_posts', &$edit_ar);
				 }
			}
/* Comments moderation */
			$awaiting_mod = wp_count_comments();
			$awaiting_mod = $awaiting_mod->moderated;
			if($awaiting_mod) {
				$links[] = new DashBarLink(sprintf($this->__("Awaiting Moderation (%s)"), $awaiting_mod), '/wp-admin/edit-comments.php?comment_status=moderated', 'moderate_comments');
			} else {
				$links[] = new DashBarLink($this->__('Comments'), '/wp-admin/edit-comments.php', 'moderation_comments'); 
			}
/* Content creation: posts, pages, links, upload media */
			$links[] = new DashBarLink($this->__('New Post')
																, '/wp-admin/post-new.php'
																, 'edit_post'
																, array(new DashBarLink($this->__('New Page'), '/wp-admin/page-new.php', 'edit_pages')
																			, new DashBarLink($this->__('New Link'), '/wp-admin/link-add.php', 'manage_links')
																			, new DashBarLink($this->__('Upload'), '/wp-admin/media-new.php', 'upload_files')
																			 )
																);
/* Management shortcuts : posts, pages, links, media, themes */
			$links[] = new DashBarLink($this->__('Manage')
																, '/wp-admin/edit.php'
																, 'edit_posts'
																, array( new DashBarLink($this->__('Pages'), '/wp-admin/edit-pages.php', 'edit_page')
																				,new DashBarLink($this->__('Links'), '/wp-admin/link_manager.php', 'manage_links')
																				,new DashBarLink($this->__('Media Library'), '/wp-admin/upload.php', 'upload_files')
																				,new DashBarLink($this->__('Design'), '/wp-admin/themes.php', 'switch_themes')
																				)
																);
/* Plugin management 
 * Every plugins should be as extensible as WordPress is...
/* */			 
			$output = apply_filters('DashBar_pre_links', $links);
			include_once(ABSPATH . 'wp-admin/includes/template.php');
/* Output the bar to HTML */			
			echo '<div id="DashBar">';
			//favorite_actions();
			echo DashBarLink::build($links);
			echo '</div>';
		}
/* Plugin dir getter */
		function getPluginURL() {
			$url = '';
			if(!defined('WP_PLUGIN_URL')) {
				$url = get_option('siteurl').'/'.PLUGINDIR;
			} else {
				$url = WP_PLUGIN_URL;
			}
			$rep = $this->getInstallDir();
			return $url.'/'.$rep;
		}

/* Display inline style with customizing and link to DashBar structure CSS*/
		function display_style() {
?><!-- [Begin:DashBar Style] -->
<link rel="stylesheet" href="<?php echo $this->getPluginURL() ?>/DashBar.css" type="text/css" />
<style type="text/css">
#DashBar { background-color: <?php echo $this->values['bgcolor']; ?>; background-image: url(<?php echo get_option('siteurl') ?>/wp-admin/images/logo-ghost.png); color:<?php echo $this->values['acolor']; ?>;font-size: <?php echo $this->values['height']; ?>!important;}
#DashBar a { color: <?php echo $this->values['color']; ?>; }
#DashBar li:hover, #DashBar li.over, #DashBar a:hover {background: <?php echo $this->values['fgcolor']; ?>;	 color: <?php echo $this->values['acolor']; ?>;}
#DashBar ul ul li a {width:140px;background: <?php echo $this->values['bgcolor']; ?>;}
</style>
<!--[if lt IE 7]><style type="text/css">#DashBar {position:absolute;}</style><![endif]-->
<!-- [End:DashBar Style] -->
<?php
	}
/* Load necessary JS Script */
		function display_script() {  ?>
<!-- [Begin:DashBar Script] -->
<script type="text/javascript">var DashBarInner = '<'+'a href="<?php echo get_option('siteurl').'/wp-admin/theme-editor.php'; ?>"'+'>'+'<?php echo addslashes($this->__('Your theme is not ready for the DashBar Plugin. To use this Plugin you should add &lt;?php wp_footer(); ?&gt; in the footer of your theme.'))?>'+'<'+'/'+'a'+'>';</script>
<script type="text/javascript" src="<?php echo $this->getPluginURL() ?>/DashBar.js"></script>
<!-- [End:DashBar Script] -->
<?php		
		}
	
	// Administration part : form handling
		function admin_page_header() {
			if((isset($_REQUEST['page'])) and ($_REQUEST['page'] == basename(__FILE__))) {
				switch($_REQUEST['DashBarAction']) {
					case $this->__('Change Style'):
						if(isset($_REQUEST['DashBarStyle']['bgcolor']) and preg_match('/^#[0-9a-f]{3,6}$/i',$_REQUEST['DashBarStyle']['bgcolor'])) {
							$this->values['bgcolor'] = $_REQUEST['DashBarStyle']['bgcolor'];
						} else {
							$this->erreur[] = $this->__("Incorrect color, it should be in hexadecimal form with 3 or 6 characters");
						}
						if(isset($_REQUEST['DashBarStyle']['fgcolor']) and preg_match('/^#[0-9a-f]{3,6}$/i',$_REQUEST['DashBarStyle']['fgcolor'])) {
							$this->values['fgcolor'] = $_REQUEST['DashBarStyle']['fgcolor'];
						} else {
							$this->erreur[] = $this->__("Incorrect color, it should be in hexadecimal form with 3 or 6 characters");
						}
						if(isset($_REQUEST['DashBarStyle']['color']) and preg_match('/^#[0-9a-f]{3,6}$/i',$_REQUEST['DashBarStyle']['color'])) {
							$this->values['color'] = $_REQUEST['DashBarStyle']['color'];
						} else {
							$this->erreur[] = $this->__("Incorrect color, it should be in hexadecimal form with 3 or 6 characters");
						}
						if(isset($_REQUEST['DashBarStyle']['acolor']) and preg_match('/^#[0-9a-f]{3,6}$/i',$_REQUEST['DashBarStyle']['acolor'])) {
							$this->values['acolor'] = $_REQUEST['DashBarStyle']['acolor'];
						} else {
							$this->erreur[] = $this->__("Incorrect color, it should be in hexadecimal form with 3 or 6 characters");
						}
						if(isset($_REQUEST['DashBarStyle']['height']) and preg_match('/^[0-9]+(|px|em|ex|%)$/i',$_REQUEST['DashBarStyle']['height'])) {
							$this->values['height'] = $_REQUEST['DashBarStyle']['height'];
						} else {
							$this->erreur[] = $this->__("Incorrect text size format");
						}
						$this->values['popup'] = (isset($_REQUEST['DashBarStyle']['popup']) && ($_REQUEST['DashBarStyle']['popup'] == '1')) ? true : false;						
						if(!$this->erreur) {
							update_option($this->prefixe, $this->values);
							$this->msg = $this->__('New settings successfully saved');
						}
						break;
					case $this->__('Restore Default'):
							update_option($this->prefixe, $this->default);
							$this->values = $this->default;
							$this->msg = $this->__('Default settings successfully restored');
						break;
					default:
				}  ?>
				<script type="text/javascript" src="../wp-includes/js/colorpicker.js"></script>
				<script type="text/javascript">
				var cp = new ColorPicker();
				function initColorPicker() {
					var elts = document.getElementsByTagName('INPUT');
					for(i=0;i<elts.length;i++) {
						if(elts[i].id.substr(elts[i].id.length-7,elts[i].id.length) == '_sample') {
							elts[i].onclick = color_picker;
							elts[i].style.backgroundColor = document.getElementById(elts[i].id.replace('_sample','')).value;
						}
					}
				}
				addLoadEvent(initColorPicker);
				function color_picker() {
					var input_id = this.id.replace('_sample','');
					var color_field = document.getElementById(input_id);
					if ( cp.p == input_id && document.getElementById(cp.divName).style.visibility != "hidden" )
						cp.hidePopup('prettyplease');
					else {
						cp.p = input_id;
						cp.select(color_field,input_id);
					}
				}
				function pickColor(color) {
					ColorPicker_targetInput.value = color;
					document.getElementById(ColorPicker_targetInput.id+'_sample').style.backgroundColor = color;
				}				
				</script>
<?php
			}
		} 

		// Administration part : form display
		function admin_page() {
			if(isset($this->erreur) and is_array($this->erreur)) {
				echo '<div id="message" class="error"><ul>';
				foreach($this->erreur as $msg) {
					echo '<li>'.$msg.'</li>';
				}
				echo '</ul></div>';
			} elseif(isset($this->msg)) {
				echo '<div id="message" class="updated fade"><p>'.$this->msg.'</p></div>';
			}
			$popupSelected = ($this->value['popup']) ? ' checked="checked"' : '';
	?>
		<div class="wrap">
			<form action="" method="post">
				<h2><?php _e('Style Customizing', $this->domain); ?></h2>
				<div id="colorPickerDiv" style="z-index: 100;background:#eee;border:1px solid #ccc;position:absolute;visibility:hidden;"> </div>
				<table class="optiontable">
					<tbody>
						<tr>
							<th><?php _e('DashBar color', $this->domain);?></th>
							<td><input type="text" id="DashBar_bgcolor" name="DashBarStyle[bgcolor]" value="<?php echo $this->values['bgcolor'];?>" /> <input type="button" id="DashBar_bgcolor_sample" value="<?php _e('Color Picker',$this->domain);?>" /></td>
						</tr>
						<tr>
							<th><?php _e('Text Color', $this->domain);?></th>
							<td><input type="text" id="DashBar_color" name="DashBarStyle[color]" value="<?php echo $this->values['color'];?>" /> <input type="button" id="DashBar_color_sample" value="<?php _e('Color Picker',$this->domain);?>" /></td>
						</tr>					
						<tr>
							<th><?php _e('Highlighted element color', $this->domain);?></th>
							<td><input type="text" id="DashBar_fgcolor" name="DashBarStyle[fgcolor]" value="<?php echo $this->values['fgcolor'];?>" /> <input type="button" id="DashBar_fgcolor_sample" value="<?php _e('Color Picker',$this->domain);?>" /> </td>
						</tr>					
						<tr>
							<th><?php _e('Highlighted text color', $this->domain);?></th>
							<td><input type="text" id="DashBar_acolor" name="DashBarStyle[acolor]" value="<?php echo $this->values['acolor'];?>" /> <input type="button" id="DashBar_acolor_sample"	 value="<?php _e('Color Picker',$this->domain);?>" /></td>
						</tr>					
						<tr>
							<th><?php _e('Text size', $this->domain);?></th>
							<td><input type="text" id="DashBar_height" name="DashBarStyle[height]" value="<?php echo $this->values['height'];?>" /></td>
						</tr> 
					</tbody>
				</table>
				<h2><?php echo $this->__('Link Behaviour'); ?></h2>
				<table class="optiontable">
					<tbody>
						<tr>
							<th><?php echo $this->__('Open links in a new windows') ?></th>
							<td><input type="checkbox" id="DashBar_popup" name="DashBarStyle[popup]" value="1" <?php echo ($this->values['popup'] === true)? 'checked="checked" ' : '' ?> /></td>
						</tr>
					</tbody>
				</table>
				<p class="submit"><input type="submit" name="DashBarAction" value="<?php _e('Change Style',$this->domain); ?>" /> <input type="submit" name="DashBarAction" value="<?php _e('Restore Default',$this->domain); ?>" /></p>
			</form>
		</div>
	<?php 
		}		 
	}
}

// run the plugin
$the_dashbar = new DashBar();


?>