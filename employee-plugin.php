<?php


if ( ! defined( 'ABSPATH' ) ) { 
	exit; // Exit if accessed directly
}

/*
Plugin Name: Employee Plugin NEW
Description: Plugin for Employees
Author: Ludvig Ohlsson
Developer: Ludvig Ohlsson

Version: 1.0.1
*/

if ( !class_exists( 'employee_plugin' ) ) {
	
	class employee_plugin {

		const NAME_POST_TYPE = 'employee';
		const NAME_SINGLE = 'Employee';
		const NAME_PLURAL = 'Employees';

		const LOAD_MORE = 6; // 5;

		public function __construct() {

			// create piggybaq required tables
			register_activation_hook( __FILE__, array( &$this, 'install' ) );

			// deactivate plugin
			register_deactivation_hook( __FILE__, array( &$this, 'deactivation' ) );

			// called just before the other template functions are included
			add_action( 'init', array( &$this, 'init' ), 20 );

			// call to support 
			add_action( 'admin_init', array( &$this, 'admin_init' ), 20 );

			// to save custom meta in meta box
			add_action( 'save_post', array( &$this, 'save_custom_fields_meta' ), 20 );

			//short code
			add_shortcode( 'employee_list', array(&$this, 'shortcode_employee_plugin') );

			//short code
			add_shortcode('employee_alphabet', array($this, 'shortcode_alphabet_employee_plugin') );

			//add second functions php-page
			require_once('functions/functions_employee.php');

		}

		public function install () {}

		public function deactivation () {}

		public function init() {

			$this->register_post_type();
			$this->custom_taxonomy();
			
		}

		public function admin_init() {

			$this->add_meta_box();
		}

		public function register_post_type () {

			$name_single = self::NAME_SINGLE;
			$name_plural = self::NAME_PLURAL;
			$name_post_type = self::NAME_POST_TYPE;

			register_post_type( $name_post_type,
				array(
					'labels' => array(
						'name' => $name_plural,
						'singular_name' => $name_single,
						'add_new' => 'Add New ' . $name_single,
						'add_new_item' => 'Add New ' . $name_single,
						'edit' => 'Edit',
						'edit_item' => 'Edit ' . $name_single,
						'new_item' => 'New ' . $name_single,
						'view' => 'View',
						'view_item' => 'View ' . $name_single,
						'search_items' => 'Search ' . $name_plural,
						'not_found' => 'No ' . $name_plural . ' found',
						'not_found_in_trash' => 'No ' . $name_plural . ' found in Trash',
						'parent' => 'Parent ' . $name_single
					),

					'public' => true,
					'menu_position' => 15,
					'supports' => array( 'title', 'editor', 'thumbnail' ),
					'taxonomies' => array( '' ),
					'has_archive' => false
				)
			);

		}


		public function custom_taxonomy () {
			//create new taxonomy 
			register_taxonomy(
					'employee_type',
					'employee',
					array(
						'label' => 'Employee Type',
						'rewrite' => array('type'),
						'hierarchical' => true, 
						'show_ui' => true, 
						'show_tagcloud' => false,
						'show_admin_column' => true
						)
				);
			wp_insert_term('Partner','employee_type' );
		}

		public function add_meta_box () {

			$name_single = self::NAME_SINGLE;
			$name_plural = self::NAME_PLURAL;
			$name_post_type = self::NAME_POST_TYPE;

			add_meta_box( $name_post_type . '_meta_box',
				$name_single . ' Details',
				array( &$this, 'display_meta_box' ),
				$name_post_type, 'normal', 'high'
			);
		}

		public function display_meta_box () {
			global $post;

			$name_post_type = self::NAME_POST_TYPE;

			$custom_meta_fields = $this->get_custom_fields();

			// Use nonce for verification
			echo '<!-- Get the defination in functions-jl-admin.php -->';
			echo '<input type="hidden" name="'.$name_post_type.'_fields_meta_box_nonce" value="'.wp_create_nonce(basename(__FILE__)).'" />';

			// Begin the field table and loop
			echo '<table class="form-table">';

			foreach ($custom_meta_fields as $field) {

				// get value of this field if it exists for this post
				$meta = get_post_meta($post->ID, $field['id'], true);

				// begin a table row with
				echo '<tr>';
					echo '<th><label for="'.$field['id'].'">'.$field['label'].'</label></th>';
					echo '<td>';
						switch($field['type']) {
							case 'text':
								echo '<input type="text" name="'.$field['id'].'" id="'.$field['id'].'" value="'.$meta.'" size="30" />
								<br /><span class="description">'.$field['desc'].'</span>';
								break;
						} //end switch
					echo '</td>';
				echo '</tr>';

			} // end foreach

			echo '</table>'; // end table

		}

		private function get_custom_fields () {

			$name_post_type = self::NAME_POST_TYPE;

			return array(
				array(
					'label'=> 'Name',
					'desc' => 'Employee Name',
					'id' => $name_post_type.'name',
					'type' => 'text'
				),
				array(
					'label' => 'Title',
					'desc' => 'Employee Title',
					'id' => $name_post_type.'title',
					'type' => 'text'
				),
				array(
					'label' => 'Phone',
					'desc' => 'Employee Phone Number',
					'id' => $name_post_type.'phone',
					'type' => 'text'
				),
				array(
					'label' => 'Mail',
					'desc' => 'Employee E-Mail address',
					'id' => $name_post_type.'mail',
					'type' => 'text'
				)
			);
		}

		public function save_custom_fields_meta ( $post_id ) {

			$name_post_type = self::NAME_POST_TYPE;

			$custom_meta_fields = $this->get_custom_fields();

			// verify nonce
			if (!wp_verify_nonce($_POST[$name_post_type.'_fields_meta_box_nonce'], basename(__FILE__)))
				return $post_id;


			// check autosave
			if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
				return $post_id;

			// check permissions
			if ('page' == $_POST['post_type']) {
				if (!current_user_can('edit_page', $post_id))
				return $post_id;
			} else if (!current_user_can('edit_post', $post_id)) {
				return $post_id;
			}

			// loop through fields and save the data
			foreach ($custom_meta_fields as $field) {
				$old = get_post_meta($post_id, $field['id'], true);
				$new = $_POST[$field['id']];
				if ($new && $new != $old) {
					update_post_meta($post_id, $field['id'], $new);
				} else if ('' == $new && $old) {
					delete_post_meta($post_id, $field['id'], $old);
				}
			} // end foreach
		}

		public function shortcode_employee_plugin($atts) {
			$name_post_type = self::NAME_POST_TYPE;
			
			wp_enqueue_style('employee_style', plugin_dir_url( __FILE__).'css/employee.css' );
			wp_enqueue_script('phone_toggle_script', plugin_dir_url(__FILE__).'js/phone-toggle-script.js', array('jquery'),'',true);


			// Extract parameters from shortcode
			$merged = shortcode_atts( array(
				'type' => ''
				)
			, $atts);			

			if ( $merged['type'] == 'partner' ) {
				$args = array(
						'tax_query' => array(
								array(
									'taxonomy' => 'employee_type',
									'field' => 'slug',
									'terms' => 'Partner'
									)
							),
				'post_type' => $name_post_type,
				'post_status' => 'publish', 
				'posts_per_page' => -1,//self::LOAD_MORE
				'offset' => 0,
				'meta_key' => $name_post_type.'name',
				'orderby' => 'meta_value',
				'order' => 'ASC'
					);
			}
			else {
			$args = array(
				'post_type' => $name_post_type,
				'post_status' => 'publish', 
				'posts_per_page' => -1,//self::LOAD_MORE
				'offset' => 0,
				'meta_key' => $name_post_type.'name',
				'orderby' => 'meta_value',
				'order' => 'ASC'
				);
			}

			$employee_query = new WP_QUERY($args);
			$employees = $employee_query->get_posts();
			$topletter = "";

			if ( count($employees) > 0) {

				//container div
				echo '<div class="container">';
				
					foreach($employees as $emp) {

						$meta = get_post_meta( $emp->ID);
						$thumbnail = get_the_post_thumbnail($emp->ID, array(950,950));
						$first_letter = strtolower(substr($meta [$name_post_type.'name'][0], 0, 1));
						$src = wp_get_attachment_image_src( get_post_thumbnail_id($emp->ID), array( 950,950 ), false, '' ); 

						$name = $meta[$name_post_type.'name'][0];
						$title = $meta[$name_post_type.'title'][0];
						$phone = $meta[$name_post_type.'phone'][0];
						$mail = $meta[$name_post_type.'mail'][0];


						// Create row-div
						echo '<div class="row">';


							// Thumbnail div
							echo '<div class="col-sm-6 '.$name_post_type.'_thumbnail">';
								//Thumb wrapper
							 	echo '<div class="wpb_text_column wpb_content_element" style="height: 700px; background-image: url('.$src[0].') !important;background-size: cover;    background-position: center top;" >';
									//echo $thumbnail;
							 	echo '</div>'; // end thumb wrapper
							echo '</div>'; //end thumb div


							// Information div 
							echo '<div class="col-sm-6 '.$name_post_type.'_info">';
								
								
								
								// Information div wrapper
									echo '<div class="wpb_text_column wpb_content_element">';
									
									// Information div center 
									echo '<div class="text_wrap_center">';

										if ($topletter !== $first_letter) {
											$topletter = $first_letter;
											echo '<h1><span id="people_'.$topletter.'" class="active_anchor">'.$name.'</span></h1>';
										}else {
											echo '<h1>'.$name.'</h1>';
										}
										
										// Divider line 
									    echo '<div class="vc_sep_width_10">';
									        echo '<span class="vc_sep_holder vc_sep_holder_l">';
									            echo '<span style="border-color:#000000;" class="vc_sep_line">';
									            echo '</span>'; // end divider line span 2
									        echo '</span>'; // end divider line span 1
									    echo '</div>'; // end divider line

										echo '<p class="people_title"><strong>'.$title.'</strong></p>';
										echo '<p class="people_info">'.$emp->post_content.'</p>';

										if ($phone) {
											// echo '<p class="'.$name_post_type.'_phone">Phone: <a href="tel:/'.$phone.'">'.$phone.'</a></p>';
											echo '<div class="vc_general vc_btn3 vc_btn3-size-md vc_btn3-shape-square vc_btn3-style-flat vc_btn3-color-black '.$name_post_type.'_phone"><a href="tel:+'.$phone.'">Phone</a></div>';
										}

										if ($mail) {
											// echo '<p class="'.$name_post_type.'_mail"> Email: <a href="mailto:/'.$mail.'">'.$mail.'</a></p>';
											echo '<div class="vc_general vc_btn3 vc_btn3-size-md vc_btn3-shape-square vc_btn3-style-flat vc_btn3-color-black '.$name_post_type.'_mail"><a href="mailto:'.$mail.'">E-Mail</a></div>';
										}
									echo '</div>'; // end div center wrapper	

									echo '</div>'; // end div wrapper
								
							echo '</div>'; // end information div

						
						echo '</div>'; // end row

					}
				echo '</div>'; // end container
			}
		}


		public function shortcode_alphabet_employee_plugin( $atts ) {
			$name_post_type = self::NAME_POST_TYPE;
			wp_enqueue_style('employee_style', plugins_url('/css/employee.css', __FILE__) );
			
			// Extract parameters from shortcode
			$merged = shortcode_atts( array(
				'type' => ''
				)
			, $atts);			

			if ( $merged['type'] == 'partner' ) {
				$args = array(
						'tax_query' => array(
								array(
									'taxonomy' => 'employee_type',
									'field' => 'slug',
									'terms' => 'Partner'
									)
							),
				'post_type' => $name_post_type,
				'post_status' => 'publish', 
				'posts_per_page' => -1,//self::LOAD_MORE
				'offset' => 0,
				'meta_key' => $name_post_type.'name',
				'orderby' => 'meta_value',
				'order' => 'ASC'
					);
			}
			else {
			$args = array(
				'post_type' => $name_post_type,
				'post_status' => 'publish', 
				'posts_per_page' => -1,//self::LOAD_MORE
				'offset' => 0,
				'meta_key' => $name_post_type.'name',
				'orderby' => 'meta_value',
				'order' => 'ASC'
				);
			}

			$employee_query = new WP_QUERY($args);
			$employees = $employee_query->get_posts();
			$letters = employee_alphabet($employees);

			echo '<div class="alpha-cont">';
            echo '<div class="alpha-wrap">';
			foreach ($letters as $letter=>$value) {
				$currentchar = strtoupper($value['letter']);

				if ($value['link_exists'] == TRUE) {
					echo '<span class="alphabet"><a class="active_link" href="#people_'.$value['letter'].'">'.$currentchar.'</span></a>';

				}

				elseif ($value['link_exists'] == FALSE) {
					echo '<span class="alphabet inactive">'.$currentchar.'</span>';

				}
			
			}
			echo '<span class="alphabet">'.htmlentities('Å').'</span>';
			echo '<span class="alphabet">'.htmlentities('Ä').'</span>';
			echo '<span class="alphabet">'.htmlentities('Ö').'</span>';
			// Animated arrow
			echo '<div class="animate_arrow"><img src="'.plugins_url('/img/arrow_down.png', __FILE__).'" style="width:30px;"/></div>';
			echo '</div>';
			echo '</div>';

		}




	}

	// finally instantiate our plugin class and add it to the set of globals
	$GLOBALS['employee_plugin'] = new employee_plugin();

}