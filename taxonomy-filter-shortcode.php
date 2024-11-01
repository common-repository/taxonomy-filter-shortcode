<?php
/*
Plugin Name: Taxonomy Filter Shortcode
Description: Add a taxonomy filter list that will filter a post list by ajax
Version: 1.2
Author: Richard Holmes
Author URI: https://ampersandstudio.uk/
License: GPL v2 or higher
License URI: License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'WP_ATFS' ) ) {
	
	class WP_ATFS {

		public function __construct() {
			// called just before the template functions are included
			add_action( 'init', array( $this, 'include_template_functions' ), 20 );
			
			// called after all plugins have loaded
			add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
			
			// add to header
			add_action( 'wp_head', array( $this, 'add_to_header' ) );
			
			// add to footer
			add_action( 'wp_footer', array( $this, 'add_to_footer' ) );
			
			// indicates we are running the admin
			if ( is_admin() ) {
			  // ...
			  add_action( 'admin_menu', array( $this, 'taxonomy_filter_menu' ) );
			  add_action( 'admin_init', array( $this, 'register_taxonomy_filter_settings' ) );
			}
		
			// indicates we are being served over ssl
			if ( is_ssl() ) {
			  // ...
			}
		
		// take care of anything else that needs to be done immediately upon plugin instantiation, here in the constructor
		}
		
		/**
		* Take care of anything that needs to be loaded on init
		*/
		public function include_template_functions() {
			// ...
			add_shortcode( 'taxonomy_filter', array( $this, 'taxonomy_filter_func' ) );
		}
		
		/**
		* Take care of anything that needs all plugins to be loaded
		*/
		public function plugins_loaded() {
			// ...
		}
		
		/**
		* Take care of anything that needs adding to the header
		*/
		public function add_to_header() {
			// ...
			wp_register_style( 'ampersand-taxonomy-filter-shortcode', plugins_url("/css/atfs.css", __FILE__) );
			wp_enqueue_style( 'ampersand-taxonomy-filter-shortcode' );
		}
		
		/**
		* Take care of anything that needs adding to the footer
		*/
		public function add_to_footer() {
			// ...
			
		}
		
		public function taxonomy_filter_func($atts) {
			
			$atfs_settings = get_option( 'taxonomy_filter_options' );
			$atfs_settings = json_decode($atfs_settings, true);
			
			$atts = shortcode_atts( array(
				'items' => ( isset( $atfs_settings['items'] ) ? $atfs_settings['items'] : 'taxonomy-filter-item' ),
				'animation' => ( isset( $atfs_settings['animation'] ) ? $atfs_settings['animation'] : 'fade' ),
				'showall' => ( isset( $atfs_settings['showall'] ) ? $atfs_settings['showall'] : '1' ),
				'shownocount' => ( isset( $atfs_settings['shownocount'] ) ? $atfs_settings['shownocount'] : '0' ),
				'alsohide' => ( isset( $atfs_settings['alsohide'] ) ? $atfs_settings['alsohide'] : '' ),
				'paging' => ( isset( $atfs_settings['paging'] ) ? $atfs_settings['paging'] : 0 ),
				'page_size' => ( isset( $atfs_settings['page_size'] ) ? $atfs_settings['page_size'] : '10' )
			), $atts, 'taxonomy_filter' );
			
			wp_register_script( 'ampersand-taxonomy-filter-shortcode', plugins_url("/js/atfs.js", __FILE__), array('jquery'), "1.1", true );
			wp_enqueue_script( 'ampersand-taxonomy-filter-shortcode' );
			
			wp_localize_script( 'ampersand-taxonomy-filter-shortcode', 'shortcode_atts', $atts );
			
			ob_start(); ?>
			
			<div class='taxonomy-filter-wrapper' data-items='<?php echo $atts['items']; ?>'>
<?php		
			$taxonomy = get_query_var('taxonomy');
			$queried_term = get_query_var($taxonomy);
			$terms = get_terms($taxonomy, 'slug='.$queried_term);
			if ($terms) {
				foreach($terms as $term) {
					$cat = get_term_children( $term->term_id, $taxonomy );
					if (count($cat)) { ?>
						<ul>
<?php					if ($atts['showall']) { ?>
							<li><input type="checkbox" value="*" class="taxonomy-filter-checkbox" />&nbsp;All</li>
<?php					}
						foreach($cat as $k=>$v) {
							$child_term = get_term_by( 'id', $v, $taxonomy ); 
							if ($atts['shownocount'] || $child_term->count) { ?>
							
							<li><input type="checkbox" value="<?php echo $child_term->slug; ?>" class="taxonomy-filter-checkbox" />&nbsp;<?php echo $child_term->name; ?></li>
<?php						}
						} ?>
						</ul>	
<?php				}
				}
			}
?>
			</div>
			
			<div class='taxonomy-filter-wrapper-mobile' data-items='<?php echo $atts['items']; ?>'>
<?php		
			$taxonomy = get_query_var('taxonomy');
			$queried_term = get_query_var($taxonomy);
			$terms = get_terms($taxonomy, 'slug='.$queried_term);
			if ($terms) {
				foreach($terms as $term) {
					$cat = get_term_children( $term->term_id, $taxonomy );
					if (count($cat)) { ?>
						<select class="taxonomy-filter-mobile-select">
							<option value="?" />&nbsp;All</option>
<?php					foreach($cat as $k=>$v) {
							$child_term = get_term_by( 'id', $v, $taxonomy ); 
							if ($atts['shownocount'] || $child_term->count) { ?>
							
							<option value="/<?php echo $child_term->taxonomy . "/" . $child_term->slug; ?>/" />&nbsp;<?php echo $child_term->name; ?></option>
<?php						}
						} ?>
						</select>	
<?php				}
				}
			}
?>
			</div>
			
<?php		return ob_get_clean();
		}
		
		/** ADMIN OPTIONS **/
		function register_mysettings() { // whitelist options
			register_setting( 'taxonomy-filter-group', 'taxonomy_filter_options' );
		}

		function taxonomy_filter_menu() {
			add_options_page( 'Taxonomy Filter Options', 'Taxonomy Filter', 'manage_options', 'wp_atfs', array( $this, 'taxonomy_filter_options' ) );
		}
		
		function taxonomy_filter_options() {
			if ( !current_user_can( 'manage_options' ) )  {
				wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
			}
			
			$opt_name = 'taxonomy_filter_options';
			
			// Read in existing option value from database
			$atfs_settings = get_option($opt_name);
			$atfs_settings = json_decode($atfs_settings, true);
			
			if ( !isset( $atfs_settings['showall'] ) ) {
				$atfs_settings['showall'] = "1";
			}
		
		    // See if the user has posted us some information
		    if( isset($_POST) && wp_verify_nonce( $_POST['_wpnonce'], 'taxonomy-filter-group-options' ) ) {
		        // Read their posted value
		        $opt_val = array(
		        	'items' => ( isset( $_POST['atfs_items'] ) ? sanitize_text_field($_POST['atfs_items']) : '.taxonomy-filter-item' ),
					'animation' => ( isset( $_POST['atfs_animation'] ) ? sanitize_text_field($_POST['atfs_animation']) : 'fade' ),
					'showall' => ( isset( $_POST['atfs_showall'] ) ? sanitize_text_field($_POST['atfs_showall']) : '0' ),
					'shownocount' => ( isset( $_POST['atfs_shownocount'] ) ? sanitize_text_field($_POST['atfs_shownocount']) : '0' ),
					'alsohide' => ( isset( $_POST['atfs_alsohide'] ) ? sanitize_text_field($_POST['atfs_alsohide']) : '' ),
					'paging' => ( isset( $_POST['atfs_paging'] ) ? sanitize_text_field($_POST['atfs_paging']) : 0 ),
					'page_size' => ( isset( $_POST['atfs_page_size'] ) ? (($_POST['atfs_page_size'] != "") ? sanitize_text_field($_POST['atfs_page_size']) : "10") : ( ($_POST['atfs_paging'] == "1") ? '10' : '0') )
		        );
		
		        // Save the posted value in the database
		        update_option( $opt_name, json_encode( $opt_val ) );
				
				$atfs_settings = $opt_val;
		        // Put a "settings saved" message on the screen
?>
				<div class="updated"><p><strong><?php _e('Settings Saved.', 'wp_atfs' ); ?></strong></p></div>
<?php
		
		    }
		
		    // Now display the settings editing screen
		
		    echo '<div class="wrap">';
		
		    // header
		
		    echo "<h2>" . __( 'Taxonomy Filter Settings', 'wp_atfs' ) . "</h2>";
		    
		    echo "<p>" . __( 'Each of these settings can be applied via the shortcode or set the defaults here. Precedence will be given to shortcode attributes first.', 'wp_atfs' ) . "</p>";
		
		    // settings form
		    
		    ?>
		
			<form name="form1" method="post" action="">
			<?php 
				settings_fields( 'taxonomy-filter-group' );
				do_settings_sections( 'taxonomy-filter-group' );
			?>
			<table class="form-table">
<!--
		        <tr valign="top">
		        <th scope="row"><?php _e("Animation Type:", 'wp_atfs' ); ?><br /><small></small></th>
		        <td><input type="text" name="atfs_animation" value="<?php echo esc_attr( $atfs_settings['animation'] ); ?>" /></td>
		        </tr>
-->
		         
		        <tr valign="top">
		        <th scope="row"><?php _e("Show All?:", 'wp_atfs' ); ?><br /><small>Show / Hide the 'all' checkbox. <i>Default: Yes</i></small></th>
		        <td><input type="checkbox" name="atfs_showall" value="1"<?php if ( esc_attr( $atfs_settings['showall'] ) == "1" ) { echo " checked='checked'"; } ?> /></td>
		        </tr>
		        
		        <tr valign="top">
		        <th scope="row"><?php _e("Show No Count?:", 'wp_atfs' ); ?><br /><small>Hide taxonomies that have a 0 count. <i>Default: No</i></small></th>
		        <td><input type="checkbox" name="atfs_shownocount" value="1"<?php if ( esc_attr( $atfs_settings['shownocount'] ) == "1" ) { echo " checked='checked'"; } ?> /></td>
		        </tr>
		        
		        <tr valign="top">
		        <th scope="row"><?php _e("Items:", 'wp_atfs' ); ?><br /><small>Enter the generic css element that all the items to be filtered all share. <i>Default: .taxonomy-filter-item</i></small></th>
		        <td><input type="text" name="atfs_items" value="<?php echo esc_attr( $atfs_settings['items'] ); ?>" /></td>
		        </tr>
		        
		        <tr valign="top">
		        <th scope="row"><?php _e("Also Hide:", 'wp_atfs' ); ?><br /><small>When the filter takes place add a comma separated list of css elements that should also be hidden when filtering.</small></th>
		        <td><input type="text" name="atfs_alsohide" value="<?php echo esc_attr( $atfs_settings['alsohide'] ); ?>" /></td>
		        </tr>
		        
		        <tr valign="top">
		        <th scope="row"><?php _e("Turn Paging On?:", 'wp_atfs' ); ?><br /><small>Items will load when scrolling the page.</small></th>
		        <td><input type="checkbox" name="atfs_paging" value="1"<?php if ( esc_attr( $atfs_settings['paging'] ) == "1" ) { echo " checked='checked'"; } ?> /></td>
		        </tr>
		        
		        <tr valign="top">
		        <th scope="row"><?php _e("Number of items to show?:", 'wp_atfs' ); ?><br /><small>Set the number of items to show and load on scroll.</small></th>
		        <td><input type="text" name="atfs_page_size" value="<?php echo esc_attr( $atfs_settings['page_size'] ); ?>" /></td>
		        </tr>
		       
		    </table>
		    
		    <hr />
		    
			<?php submit_button() ?>
			
			</form>
			</div>
<?php
		}
		
	}
	 
	// finally instantiate our plugin class and add it to the set of globals
	 
	$GLOBALS['wp_atfs'] = new WP_ATFS();
	
}