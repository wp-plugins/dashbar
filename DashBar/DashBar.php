<?php
/*
Plugin Name: DashBar
Plugin URI: http://z720.net/produits/wordpress/dashbar
Description: Display a Enhanced WordPress.com-like navigation bar for logged users: direct acces to Dashboard, Write, Edit, Awaiting Moderation, Profile...
Version: 2.0.2
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
  	var label = "";
  	var url = "";
  	var credential = "";
  	var children = array();
  	
  	function DashBarLink($label, $url, $credential, $children = array()) {
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

/* Building the Menu (list of links)	*/
  	function build($elts = array()) {
  		$o = '';
  		if(empty($elts)) { return ''; }
  		foreach($ets as $elt) {
  			if($elt instanceof DashBarLink) {
  				$user_can = true;
  				if($elt->getCredential() != '') {
  					$user_can = current_user_can($elt->getCredential());
  				}
  				if($user_can) {
  					$str = '<li><a href="'.$elt->getUrl().'">'.$this->getLabel().'</a>'.
  					$str .= DashBarLink::build($this->getChildren());
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
  	var $domain = 'DashBar/DashBar';
  	var $version = '2.0.2';
  
  	var $default = array( 'bgcolor' => '#14568a', 
  	                      'height' => '11px', 
  	                      'align' => 'left', 
                          'color' => '#c3def1', 
                          'fgcolor' => '#6da6d1', 
                          'acolor' => '#000000',
                          'opacity' => '50');
    var $values = array();
  	var $user_menu = array();
  	
  	var $erreur = false;
  	var $togglealign = array('right'=>'left', 'left'=>'right', 'bottom' => 'top', 'top' => 'bottom');
  	
  	
  	function DashBar() {
// i18n : load texts
  		load_plugin_textdomain($this->domain);
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
  
    function init() {
  		get_currentuserinfo();
  		global $userdata, $user_login, $user_identity;
  		if($user_login) {
  			add_filter('wp_footer', array(&$this, 'display_bar'));
  			add_filter('wp_head', array(&$this, 'display_style'));
  		}
    }

    function admin_pages() {
      add_options_page(__('DashBar', $this->domain), __('DashBar',$this->domain), 'manage_options', basename(__FILE__), array(&$this, 'admin_page'));    
    }
  
  	function display_bar() {
  	  get_currentuserinfo();
  	  global $current_user;
/* Construction des liens */  	  
  	  $links = array();
  	  
/* Profile / Logout */      
  	  $account_link = new DashBarLink(__('My Account', $this->domain), '/wp-admin/profile.php', '');
  	  $logout_link = new DashBarLink(__('Logout',$this->domain),'/wp-login.php?action=logout', '');
  	  $account_link->setChildren(array($logout_link));

  	  $links[] = $account_link;

/* Dashboard / Admin menu items */
  	  $dashboard_link = new DashBarLink(__('Dashboard',$this->domain), '/wp-admin/', '');
  	  $manage_links = array();
  	  $manage_links[] = new DashBarLink(__('Categories',$this->domain), '/wp-admin/categories.php', 'manage_categories');
  	  $manage_links[] = new DashBarLink(__('Links',$this->domain), '/wp-admin/link_manager.php', 'manage_links');
  	  $manage_links[] = new DashBarLink(__('Files',$this->domain), '/wp-admin/templates.php', 'manage_files');
  	  $manage_links[] = new DashBarLink(__('Options',$this->domain), '/wp-admin/options-general.php', 'manage_options');
  	  $manage_links[] = new DashBarLink(__('Plugins',$this->domain), '/wp-admin/plugins.php', 'activate_plugins');
  	  $dashboard_links->setChildren($manage_links);
  	  
      $links[] = $dashboard_links;

/* Manage content */      
  	  $links[] = new DashBoardLink(__('Write Post',$this->domain), '/wp-admin/post-new.php', 'edit_posts');
      
      global $id, $authordata;
  	  rewind_posts();
  	  if(is_single()||is_page()) {
    		the_post();
    		$links[] = new DashBarLink(__('Edit',$this->domain), '/wp-admin/post.php?action=edit&amp;post='.$id, ($current_user->ID != $authordata->ID) ? 'edit_others_posts' : 'edit_posts');
  	  } elseif(!is_404() {
        $it = array();
        while(have_posts()) {
          $it[] = new DashBarLink(the_title('','',false), '/wp-admin/post.php?action=edit&amp;post='.$id, ($current_user->ID != $authordata->ID) ? 'edit_others_posts' : 'edit_posts');
        }
        if(!empty($it)) {
          $links[] = new DashBarLink(__('Manage',$this->domain), '/wp-admin/edit.php', 'edit_posts', $it);
        }
  	  }
  	  
/* Comment moderation */      
      global $wpdb;
   		$awaiting_mod = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->comments WHERE comment_approved = '0'");
  		if($awaiting_mod) {
        $links[] = new DashBarLink(sprintf(__("Awaiting Moderation (%s)",$this->domain), $awaiting_mod), '/wp-admin/moderation.php', 'moderate_comments');
   		}
/* Plugin management 
 * Every plugins should be as extensible as WordPress is...
/* */      
      $output = apply_filter('DashBar_display_links', $links);
/* Output the bar to HTML */      
      echo '<div id="DashBar">'.DashBarLink::build($links).'</div>';
  	}
  
  		
	  function display_style() {
?>
<!-- [Begin:DashBar Style and JS] -->
<style type="text/css">
#DashBar {position: fixed; top: 0; left: 0;opacity:<?php echo (str_replace(',','.',$this->values['opacity']/100))?>;background: <?php echo $this->values['bgcolor']; ?>;color:<?php echo $this->values['acolor']; ?>;width: 100%;font-family: sans-serif;font-size: <?php echo $this->values['height']; ?>!important;padding:0; margin:0;}
#DashBar a {display:block;padding:0.4em 1em;margin:0;color: <?php echo $this->values['color']; ?>;text-decoration: none;font-weight:normal;border:none;}
#DashBar li:hover, #DashBar li.over, #DashBar a:hover {background: <?php echo $this->values['fgcolor']; ?>;color: <?php echo $this->values['acolor']; ?>;}
#DashBar ul {list-style: none; float:<?php echo $this->values['align']; ?>; margin:0; padding:0;}
#DashBar li {float:left;text-align:left;}
#DashBar ul ul {display:none;position:absolute;background:<?php echo $this->values['bgcolor']; ?>;width:100%;left:0;}
#DashBar ul li.over ul, #DashBar ul li:hover ul {display:block;}
</style>
<!--[if lt IE 7]><style type="text/css">#DashBar {position:absolute;filter:alpha(Opacity=<?php echo ($this->values['opacity'])?>);}#DashBar .over {padding-bottom:2em;}</style><![endif]-->
<script type="text/javascript">
function checkDashBar() {
  if(!document.getElementById) {return;} 
  var Dashbar = document.getElementById('DashBar');
  if(!DashBar) {
    var bodies = document.getElementsByTagName('BODY');
    var wrn = document.createElement('p');
    wrn.id = 'DashBar';
    wrn.innerHTML = '<'+'a href="<?php echo get_option('siteurl').'/wp-admin/theme-editor.php'; ?>"'+'>'+'<?php echo addslashes(__('Your theme is not ready for the DashBar Plugin. To use this Plugin you should add &lt;?php wp_footer(); ?&gt; in the footer of your theme.', $this->domain))?>'+'<'+'/'+'a'+'>';
    bodies[0].appendChild(wrn);
  } else {
    if(document.all) {
      var elts = DashBar.getElementsByTagName('LI');
      for(i=0;i<elts.length;i++) {
        if((elts[i].parentNode.parentNode.id == 'DashBar') && (elts[i].childNodes.length > 1)) {
          elts[i].onmouseover = function() {this.className+= " over";};
          elts[i].onmouseout = function() {this.className = this.className.replace(" over","");};
        }
      }
    }
  }
}
function addDashBarLoadEvent(func) {if (typeof window.onload != 'function') {window.onload = func;} else {var old = window.onload;window.onload = function() {old();func();}}}
addDashBarLoadEvent(checkDashBar);
</script>
<!-- [End:DashBar Style and JS] -->
<?php		
	  }
 	
  // Administration part
  	function admin_page_header() {
  		if((isset($_REQUEST['page'])) and ($_REQUEST['page'] == basename(__FILE__))) {
  			switch($_REQUEST['DashBarAction']) {
  				case __('Change Style',$this->domain):
  					if(isset($_REQUEST['DashBarStyle']['bgcolor']) and preg_match('/^#[0-9a-f]{3,6}$/i',$_REQUEST['DashBarStyle']['bgcolor'])) {
  						$this->values['bgcolor'] = $_REQUEST['DashBarStyle']['bgcolor'];
  					} else {
  						$this->erreur[] = __("Incorrect color, it should be in hexadecimal form with 3 or 6 characters",$this->domain);
  					}
  					if(isset($_REQUEST['DashBarStyle']['fgcolor']) and preg_match('/^#[0-9a-f]{3,6}$/i',$_REQUEST['DashBarStyle']['fgcolor'])) {
  						$this->values['fgcolor'] = $_REQUEST['DashBarStyle']['fgcolor'];
  					} else {
  						$this->erreur[] = __("Incorrect color, it should be in hexadecimal form with 3 or 6 characters",$this->domain);
  					}
  					if(isset($_REQUEST['DashBarStyle']['color']) and preg_match('/^#[0-9a-f]{3,6}$/i',$_REQUEST['DashBarStyle']['color'])) {
  						$this->values['color'] = $_REQUEST['DashBarStyle']['color'];
  					} else {
  						$this->erreur[] = __("Incorrect color, it should be in hexadecimal form with 3 or 6 characters",$this->domain);
  					}
  					if(isset($_REQUEST['DashBarStyle']['acolor']) and preg_match('/^#[0-9a-f]{3,6}$/i',$_REQUEST['DashBarStyle']['acolor'])) {
  						$this->values['acolor'] = $_REQUEST['DashBarStyle']['acolor'];
  					} else {
  						$this->erreur[] = __("Incorrect color, it should be in hexadecimal form with 3 or 6 characters",$this->domain);
  					}
  					if(isset($_REQUEST['DashBarStyle']['height']) and preg_match('/^[0-9]+(|px|em|ex|%)$/i',$_REQUEST['DashBarStyle']['height'])) {
  						$this->values['height'] = $_REQUEST['DashBarStyle']['height'];
  					} else {
  						$this->erreur[] = __("Incorrect text size format",$this->domain);
  					}
  					if(isset($_REQUEST['DashBarStyle']['align']) and preg_match('/^(right|left)$/i',$_REQUEST['DashBarStyle']['align'])) {
  						$this->values['align'] = $_REQUEST['DashBarStyle']['align'];
  					} else {
  						$this->erreur[] = __("You can only align to right or left",$this->domain);
  					}
  					if(isset($_REQUEST['DashBarStyle']['opacity']) and preg_match('/^[0-9]{1,3}$/i',$_REQUEST['DashBarStyle']['opacity']) and ($_REQUEST['DashBarStyle']['opacity']<=100)) {
  						$this->values['opacity'] = $_REQUEST['DashBarStyle']['opacity'];
  					} else {
  						$this->erreur[] = __("Opacity must be an integer between 0 and 100",$this->domain);
  					}
  					if(!$this->erreur) {
  						update_option($this->prefixe, $this->values);
  						$this->msg = __('New settings successfully saved', $this->domain);
  					}
  					break;
  				case __('Restore Default',$this->domain):
  						update_option($this->prefixe, $this->default);
  						$this->values = $this->default;
  						$this->msg = __('Default settings successfully restored', $this->domain);
  					break;
  				default:
  			}
  			echo '
  			<script type="text/javascript" src="../wp-includes/js/colorpicker.js"></script>
  			<script type="text/javascript">
  			var cp = new ColorPicker();
  			function initColorPicker() {
  			  var elts = document.getElementsByTagName(\'INPUT\');
  			  for(i=0;i<elts.length;i++) {
  			    if(elts[i].id.substr(elts[i].id.length-7,elts[i].id.length) == \'_sample\') {
  			      elts[i].onclick = color_picker;
  			      elts[i].style.backgroundColor = document.getElementById(elts[i].id.replace(\'_sample\',\'\')).value;
  			    }
  			  }
  			}
  			addLoadEvent(initColorPicker);
  			function color_picker() {
  			  var input_id = this.id.replace(\'_sample\',\'\');
  			  var color_field = document.getElementById(input_id);
      		if ( cp.p == input_id && document.getElementById(cp.divName).style.visibility != "hidden" )
      			cp.hidePopup(\'prettyplease\');
      		else {
      			cp.p = input_id;
      			cp.select(color_field,input_id);
      		}
      	}
      	function pickColor(color) {
      		ColorPicker_targetInput.value = color;
      		document.getElementById(ColorPicker_targetInput.id+\'_sample\').style.backgroundColor = color;
      	}      	
  			</script>
  			';
  		}
  	} 
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
  						<td><input type="text" id="DashBar_acolor" name="DashBarStyle[acolor]" value="<?php echo $this->values['acolor'];?>" /> <input type="button" id="DashBar_acolor_sample"  value="<?php _e('Color Picker',$this->domain);?>" /></td>
  					</tr>					
  					<tr>
  						<th><?php _e('Text size', $this->domain);?></th>
  						<td><input type="text" id="DashBar_height" name="DashBarStyle[height]" value="<?php echo $this->values['height'];?>" /></td>
  					</tr>	
  					<tr>
  						<th><?php _e('Align', $this->domain);?></th>
  						<td>
  							<select id="DashBar_align" name="DashBarStyle[align]">
  								<option value="left"<?php if($this->values['align'] == 'left') echo ' selected="selected"';?>><?php _e('left', $this->domain); ?></option>
  								<option value="right"<?php if($this->values['align'] == 'right') echo ' selected="selected"';?>><?php _e('right', $this->domain); ?></option>
  							</select>
  						</td>
  					</tr>
  					<tr>
  						<th><?php _e('Opacity', $this->domain);?></th>
  						<td><input type="text" id="DashBar_opacity" name="DashBarStyle[opacity]" value="<?php echo $this->values['opacity'];?>" /></td>
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
$the_dashbar = new DashBar();


?>