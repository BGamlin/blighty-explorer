<?php
/**
 * Plugin Name: Blighty Explorer
 * Plugin URI: http://blighty.net/wordpress-blighty-explorer-plugin/
 * Description: Provides an easy integrateable read-only layer between a folder hierarchy on Dropbox and the website.
 * The folder tree can be navigated and files downloaded. Changes to the original Dropbox folder are reflected through
 * to the website.
 * (C) 2015 Chris Murfin (Blighty)
 * Version: 1.1.0
 * Author: Blighty
 * Author URI: http://blighty.net
 * License: GPLv3 or later
 **/
 
/**

Copyright (C) 2015 Chris Murfin

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

**/
 
defined('ABSPATH') or die('Plugin file cannot be accessed directly.');

define('PLUGIN_NAME', 'Blighty Explorer');
define('PLUGIN_VERSION', '1.1.0');
 
require_once("Dropbox/DropboxClient.php");

$dropbox = new DropboxClient(array(
	'app_key' => "ktms6mtlygelqeg", 
	'app_secret' => "wvl0ll46s2vz9pf",
	'app_full_access' => false,
	),'en');

if ( is_admin() ){ // admin actions
	add_action('admin_menu', 'bex_setup_menu');
	add_action('admin_notices', 'bex_plugin_prequesites');
	add_action('admin_init', 'bex_init');
}


 
function bex_plugin_prequesites() {
	$slug = "svg-vector-icon-plugin";
	$path = $slug .'/wp-svg-icons.php';
	$plugins = get_plugins();
	
	if (is_plugin_active($path)) {
		return;
	}
	
	echo '<div class="update-nag"><p><b>' . PLUGIN_NAME .'</b> plugin requires <b>WP SVG Icons</b> plugin.<br />';

	if (empty($plugins[$path])) {
		$install_url = wp_nonce_url(admin_url('update.php?action=install-plugin&plugin=' .$slug), 'install-plugin_' .$slug );
		echo '<a href="' .$install_url .'">Install Plugin</a>';
	} else {	
		$activate_url = wp_nonce_url(admin_url('plugins.php?action=activate&plugin=' .$path), 'activate-plugin_' .$path );
		echo '<a href="' .$activate_url .'">Activate Plugin</a>';
	}

	echo '</p></div>';
}

function bex_init() {
	register_setting( 'bex_option-settings', 'bex_folder', 'bex_folder_validate');
	register_setting( 'bex_option-settings-bts', 'bex_dropbox_token' );
	register_setting( 'bex_option-settings-bts', 'bex_dropbox_temp_token' );	
}

function bex_setup_menu(){
	add_menu_page( 'Blighty Explorer', 'Blighty Explorer', 'manage_options', 'blighty-explorer-plugin', 'bex_admin_settings', 'dashicons-index-card' );
}
 
function bex_admin_settings(){
	global $dropbox;
?>
	<div class="wrap">
		<h2><?php echo PLUGIN_NAME; ?> version <?php echo PLUGIN_VERSION; ?></h2>
		<?php
//		if( isset($_GET['settings-updated']) ) { 
//			echo '<div class="updated"><p>Settings saved.</p></div>';
//		} else 
		if (isset($_GET['auth_callback'])) {
			echo '<div class="updated"><p>Dropbox connection successful.</p></div>';
		} else if (isset($_GET['bex_reset'])) {
			echo '<div class="updated"><p>Dropbox connection has been reset.</p></div>';
		}
		settings_errors();
		?>
			<div id="poststuff" class="metabox-holder has-right-sidebar">
				<div class="inner-sidebar">
					<div id="side-sortables" class="meta-box-sortabless ui-sortable" style="position:relative;">						<div class="postbox">
						<h3>Support this plugin</h3>
						<div class="inside">
							If you find this plugin useful, please consider supporting it and future development. Thank you.<br /><br />
							<div align="center">
								<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
								<input type="hidden" name="cmd" value="_donations">
								<input type="hidden" name="business" value="2D9PDAS9FDDCA">
								<input type="hidden" name="lc" value="US">
								<input type="hidden" name="item_name" value="Blighty Explorer Plugin">
								<input type="hidden" name="item_number" value="BEP001A">
								<input type="hidden" name="button_subtype" value="services">
								<input type="hidden" name="no_note" value="1">
								<input type="hidden" name="no_shipping" value="1">
								<input type="hidden" name="currency_code" value="USD">
								<input type="hidden" name="bn" value="PP-BuyNowBF:btn_donateCC_LG.gif:NonHosted">
								<input type="hidden" name="on0" value="website">
								<input type="hidden" name="os0" value="<?php echo $_SERVER['SERVER_NAME']; ?>">
								<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
								<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
								</form>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="has-sidebar">
				<div id="post-body-content" class="has-sidebar-content">
					<div class="meta-box-sortabless">
						<div class="postbox">
							<h3>Configuration and Usage</h3>
							<div class="inside">
								<ol>
									<li>Connect this plugin to your Dropbox account (see below).</li>
									<li>This will create a subfolder called <b>Blighty Explorer</b> in your <b>Apps</b> folder within Dropbox.</li>
									<li>Place your folders and files you wish to share with this Wordpress installation inside the <b>Apps/Blighty Explorer</b> subfolder.</li>
									<li>Use the shortcode <b>[bex_folder]</b> in your post or page to display a folder structure / file navigator.</li>
								</ol>
							</div>
						</div>
						<div class="postbox">
							<h3>Dropbox Authentication &amp; Setup</h3>
							<div class="inside">
								<form method="post" action="options.php">
								<?php
									settings_fields('bex_option-settings'); 
//									do_settings_fields('bex_option-settings', '');
								?>

								<?php 
								$rc = handle_dropbox_auth($dropbox);
								if ($rc == 0) {
									echo "You have successfully connected the Blighty Explorer plugin to your Dropbox account.<br /><br />";
									echo "By default, folders and files are shared from your <strong>Dropbox Folder/Apps/Blighty Explorer</strong>. ";
									echo "If you want to set the root folder to be shared to a subfolder under <strong>Apps/Blighty Explorer</strong>, set it here as the root folder.<br /><br />";
									
									?>
									<b>Root Folder:</b> <input type="text" name="bex_folder" value="<?php echo esc_attr( get_option('bex_folder') ); ?>" />
									<?php 
									submit_button();
									echo '<a href="?page=blighty-explorer-plugin&bex_reset=1">Reset Dropbox connection.</a><br /><br />';
								} elseif ($rc == 2) {
									echo "Dropbox connection has been reset.<br /><br />";
									$rc = handle_dropbox_auth($dropbox);
								}
								?>		
								</form>					
							</div>
						</div>
					</div>
				</div>
				<?php echo PLUGIN_NAME; ?> version <?php echo PLUGIN_VERSION; ?> by <a href="http://blighty.net" target="_blank">Blighty</a>
			</div>

	</div>
<?php
}

function bex_folder_validate($input){

	if (preg_match('#^(\/)?((\w)+(\.|\&|\-|\/|\(|\))*(\w)*)*(\/)?$#',$input)) {
		$output = $input;
		// Valid path, but add / if not at front...
		if (substr($input,0,1) != '/') {
			$output = '/' .$output;
		}
	} else {
		add_settings_error( 'mbex_option-settings', 'invalid-folder', 'You have entered an invalid root folder.', "error" );
		$output = "";
	}
	
	return $output;

//	return apply_filters( 'bex_folder_validate', $output, $input );
}

function store_token($token, $name)
{
	if ($name == 'access') {
		update_option('bex_dropbox_token',serialize($token));
	} else {
		update_option('bex_dropbox_temp_token',serialize($token));
	}
}

function load_token($name)
{
	if ($name == 'access') {
		return @unserialize(get_option('bex_dropbox_token'));
	} else {
		return @unserialize(get_option('bex_dropbox_temp_token'));
	}
}

function delete_token($name)
{
	if ($name == 'access') {
		delete_option('bex_dropbox_token');
	} else {
		delete_option('bex_dropbox_temp_token');
	}
}

function handle_dropbox_auth($dropbox)
{

	// first try to load existing access token
	$access_token = load_token("access");
	if(!empty($access_token)) {
		if(!empty($_GET['bex_reset'])) // are we performing a dropbox connection reset?
		{
			delete_token("access");
			return 2;
		}
		$dropbox->SetAccessToken($access_token);
		return 0;
	}
	elseif(!empty($_GET['auth_callback'])) // are we coming from dropbox's auth page?
	{
		// then load our previosly created request token
		$request_token = load_token($_GET['oauth_token']);
		if(empty($request_token)) die('Dropbox request token not found!');
		
		// get & store access token, the request token is not needed anymore
		$access_token = $dropbox->GetAccessToken($request_token);	
		store_token($access_token, "access");
		delete_token($_GET['oauth_token']);
		return 0;
	}

	elseif(!$dropbox->IsAuthorized())
	{
		// redirect user to dropbox auth page
		$return_url = "http://".$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']."?page=blighty-explorer-plugin&auth_callback=1";
		$auth_url = $dropbox->BuildAuthorizeUrl($return_url);
		$request_token = $dropbox->GetRequestToken();
		store_token($request_token, $request_token['t']);
		echo("Dropbox authentication is required. <a href='$auth_url'>Authenticate with Dropbox.</a>");
		return 3;
	}
}

function bex_folder( $atts ) {
	global $dropbox;
			
	$rootFolder = trailingslashit(get_option('bex_folder'));
	
	if (!empty($_GET["folder"])) {
		$folder = esc_attr($_GET["folder"]);
		$folder = ltrim($folder, ".");
	} else {
		$folder = "";
	}
	
	$workingFolder = trailingslashit($rootFolder .$folder);
	
	$folder = trailingslashit($folder);

	if (!empty($_GET["file"])) {
		$file = esc_attr($_GET["file"]);
	} else {
		$file = null;
	} 
	
	$access_token = load_token("access");
	if(!empty($access_token)) {
		$dropbox->SetAccessToken($access_token);
		if ($dropbox->IsAuthorized()) {
			$out .= '<pre class="bxe-wrapper">';
			if (!is_null($file)) {
				$url = $dropbox->GetLink($rootFolder .$file,false,false);
				echo '<script language="javascript">window.open("'.$url.'");</script>';
			}
			$files = $dropbox->GetFiles($workingFolder);
		
			$out .= do_shortcode('[wp-svg-icons icon="folder-open" wrap="i"] ');	
			$out .= '<a href="?folder=/">Home</a><br />';
			if (substr($folder, 0, strlen($rootFolder)) == $rootFolder) {
				$folder = substr($folder, strlen($rootFolder));
			} 
			if (strlen($folder) > 1) {
				$splits = explode("/",untrailingslashit($folder));
				$size = count($splits);
				$j = 1;
				for ($i = 0; $i < $size; $i++) {
					$slashpos = strpos($folder,"/",$j);
					$j = $slashpos + 1;
					$out .= str_repeat("&nbsp;",$i * 2 + 2) ." &raquo; ";
					$out .= '<a href="?folder=' .substr($folder,0,$slashpos) .'">' .$splits[$i] .'</a><br />';
				}				
			}
			$out .= "<br />";
			foreach ($files as $file) {
				if ($file->is_dir) {
					$out .= do_shortcode('[wp-svg-icons icon="folder" wrap="i"] ');
					$out .= '<a href="?folder=' .str_ireplace($rootFolder,"",$file->path) .'">' .str_ireplace($workingFolder,"",$file->path) ."</a><br />";
				} else {
					$out .= do_shortcode('[wp-svg-icons icon="file-4" wrap="i"] ');				
					$out .= '<a href="?folder=' .$folder . '&file=' .str_ireplace($rootFolder,"",$file->path) .'">' .str_ireplace($workingFolder,"",$file->path) ."</a><br />";
				}
			}			
			$out .= "</pre>";
		}
	}
	
	return $out;
}

add_shortcode( 'bex_folder', 'bex_folder' );
?>