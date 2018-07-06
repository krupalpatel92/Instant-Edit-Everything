<?php
/*
Plugin Name: Instant Edit Everything
Plugin URI: 
description: This plugin will helps you to save your time. It is helps you to edit any record instantly, on the edit screen without wasting your time to found any record on listing page by just select ercord from 'Instant Edit' dropdown.
Version: 1.0
Author: Krupal Patel
Author URI:
Tags: Edit, Quick edit, Edit post, Edit page, Instant edit, Post, Page
Requires at least: 3.7
Tested up to: 4.9.6
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Requires PHP: 5.6
*/

define( 'INE_VERSION', '1.0' );
define( 'INE_PATH', dirname( __FILE__ ) );
define( 'INE_PATH_ASSETS', dirname( __FILE__ ) . '/assets' );
define( 'INE_FOLDER', basename( INE_PATH ) );
define( 'INE_URL', plugins_url() . '/' . INE_FOLDER );
define( 'INE_URL_ASSETS', INE_URL . '/assets' );

class InstantEdit {
	private $screen = array(
		'post',
		'page',
	);

	private $meta_fields = array(
		array(
			'label' => 'Select Post',
			'id' => 'selectpost',
			'data_type' => 'post',
			'type' => 'select',
			'options' => "",
		),
		array(
			'label' => 'Select Page',
			'id' => 'selectpage',
			'data_type' => 'page',
			'type' => 'select',
			'options' => "",
		),
	);

	public function __construct() {
		
		$this->set_meta_field_data();		

		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		
		// add scripts and styles only available in admin
		add_action( 'admin_enqueue_scripts', array( $this, 'ine_add_admin_JS' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'ine_add_admin_CSS' ) );
	}

	public function ine_add_admin_JS( $hook ) {
		wp_enqueue_script( 'jquery' );
		wp_register_script( 'ine-select2-min-js', INE_URL_ASSETS.'/plugins/select2/js/select2.min.js', array('jquery'), '1.0', true );
		wp_enqueue_script( 'ine-select2-min-js' );
		
		wp_register_script( 'ine-admin-js', INE_URL_ASSETS.'/js/ine-admin.js', array('jquery'), '1.0', true );
		wp_enqueue_script( 'ine-admin-js' );
	}

	public function ine_add_admin_CSS( $hook ) {		
		wp_register_style( 'ine-select2-min-style', INE_URL_ASSETS.'/plugins/select2/css/select2.min.css', array(), '1.0', 'screen' );
		wp_enqueue_style( 'ine-select2-min-style' );

		wp_register_style( 'ine-admin-style', INE_URL_ASSETS.'/css/ine-admin.css', array(), '1.0', 'screen' );
		wp_enqueue_style( 'ine-admin-style' );	
	}

	public function get_edit_post_url($post_id,$post_type){

		$admin_url = admin_url();

		if($post_type == "page" || $post_type == "post" ){

			return $admin_url."post.php?post=".$post_id."&action=edit";

		}

	}

	public function set_meta_field_data(){

		foreach($this->meta_fields as $key => $data){

			$post_data = $this->get_field_data($data['data_type']);
			
			if(empty($post_data)){
				$this->meta_fields[$key]['options'] = "";
				continue;
			}

			$option_html = "<option value=''>Select ".$data['data_type']."</option>";
			foreach($post_data as $pgkey => $pgroup){

				$option_html .= "<optgroup label='".ucfirst($pgkey)." (".count($pgroup).")'>";
				$all_option = array_map(function($poption){
					return "<option value='".$poption['link']."' data-id='".$poption['id']."' ".$poption['is_selected'].">".ucfirst($poption['label'])."</option>";
				},$pgroup);

				$option_html .= implode("\n",$all_option);
				$option_html .= "</optgroup>";
			}

			$this->meta_fields[$key]['options'] = $option_html;

		}

	}

	public function get_field_data($data_type){
		
		$data = get_posts(array(
			'post_type' => $data_type,
			'posts_per_page' => -1, 
			//'post_status' => ['publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash'],
			'post_status' => ['publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit'],
			'orderby' => 'title',
			'order' => 'ASC',
		));

		if(empty($data)){
			return [];
		}

		$return_data = [];
		foreach($data as $key => $post){
			$post_status = $post->post_status;
			$is_selected = (isset($_GET['post']) && $_GET['post']!="" && $_GET['post'] == $post->ID)? "selected" : "";
			$post_data = ["link"=>$this->get_edit_post_url($post->ID,$post->post_type),"label"=>$post->post_title,"id"=>$post->ID, "is_selected"=>$is_selected];
			$return_data[$post_status][] = $post_data;
		}
		asort($return_data);
		return $return_data;

	}

	public function add_meta_boxes() {
		foreach ( $this->screen as $single_screen ) {
			add_meta_box(
				'instantedit',
				__( 'Instant Edit Everything', 'textdomain' ),
				array( $this, 'meta_box_callback' ),
				$single_screen,
				'side',
				'high'
			);
		}
	}
	public function meta_box_callback( $post ) {
		wp_nonce_field( 'instantedit_data', 'instantedit_nonce' );
		//echo 'Select Post/Page for edit now';
		$this->field_generator( $post );
	}
	public function field_generator( $post ) {
		$output = '';
		foreach ( $this->meta_fields as $meta_field ) {
			$label = '<label for="' . $meta_field['id'] . '"><i class="dashicons-before dashicons-admin-'.$meta_field['data_type'].'" style="padding: 5px; display: inline-block;"></i>' . $meta_field['label'] . '</label>';
			switch ( $meta_field['type'] ) {
				case 'select':
					$input = sprintf(
						'<select id="%s" name="%s" data-for="%s" class="%s">',
						$meta_field['id'],
						$meta_field['id'],
						$meta_field['data_type'],
						"ine_select_box"
					);
					$input .= $meta_field['options'];
					$input .= '</select>';
					break;
				default:
					$input = sprintf(
						'<input %s id="%s" name="%s" type="%s" value="%s">',
						$meta_field['type'] !== 'color' ? 'style="width: 100%"' : '',
						$meta_field['id'],
						$meta_field['id'],
						$meta_field['type'],
						$meta_value
					);
			}
			$output .= $this->format_rows( $label, $input );
		}
		echo '<div>' . $output . '</div>';
	}
	public function format_rows( $label, $input ) {
		return '<div class="row"><div class="col-md-12">'.$label.'</br>'.$input.'</div></div><br/>';
	}

}

if (class_exists('InstantEdit')) {
	new InstantEdit;
};

?>