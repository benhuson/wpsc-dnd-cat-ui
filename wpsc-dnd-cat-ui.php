<?php

/*
Plugin Name: WPEC Drag-n-Drop Categories UI
Plugin URI: 
Description: If you have loads of WP e-Commerce products and have to selected to order them by drag-n-drop, you may find your admin struggles or crashes when it tries to load all those products. This plugin reverts the product admin list to be paginated again and rather than dragging-n-dropping there, it adds a separate admin page where you can order products which is leaner and faster. Tested with WP e-Commerce 3.8.10.beta.
Version: 0.2
Author: Ben Huson
Author URI: 
License: GPL2
*/

class WPSC_DnDCatUI {

	var $version = '0.2';

	function WPSC_DnDCatUI() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_filter( 'option_wpsc_sort_by', array( $this, 'option_wpsc_sort_by' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 11 );
		add_action( 'wp_ajax_dragndrop_save_product_order', array( $this, 'save_product_order_ajax_callback' ) );
	}

	function admin_enqueue_scripts( $hook ) {
		if ( 'wpsc-product_page_wpsc_dnd_cat_ui' == $hook ) {
			wp_enqueue_style( 'wpsc_dnd_cat_ui', plugins_url( 'css/wpsc-dnd-cat-ui.css' , __FILE__ ), false, $this->version );
			wp_enqueue_script( 'wpsc_dnd_cat_ui', plugins_url( 'js/wpsc-dnd-cat-ui.js' , __FILE__ ), array( 'jquery-ui-sortable' ), $this->version );
		}
    }

	function option_wpsc_sort_by( $option ) {
		global $wp_query, $current_screen;
		if ( is_admin() && $current_screen->id == 'edit-wpsc-product' ) {
			$option = 'id';
		}
		return $option;
	}

	function admin_menu() {
		add_submenu_page( 'edit.php?post_type=wpsc-product', __( 'Product Order', 'wpsc-dnd-cat-ui' ), __( 'Product Order', 'wpsc-dnd-cat-ui' ), 'edit_posts', 'wpsc_dnd_cat_ui', array( $this, 'dnd_cat_ui_page' ) );
	}

	function dnd_cat_ui_page() {
		echo '<div class="wrap wpsc-dnd-cat-ui">
				<div id="icon-edit" class="icon32 icon32-posts-wpsc-product"><br></div>
				<h2>' . __( 'Product Order', 'wpsc-dnd-cat-ui' ) . '</h2>
				<p>' . __( 'To order products, please select a category below, then drag products into the prefered order.', 'wpsc-dnd-cat-ui' ) . '</p>
				<form id="posts-filter" action="" method="get">';
		if ( function_exists( 'wpsc_cats_restrict_manage_posts' ) )
			wpsc_cats_restrict_manage_posts();
		echo '		<input type="submit" name="" id="post-query-submit" class="button" value="' . __( 'Get Products', 'wpsc-dnd-cat-ui' ) . '">
					<input type="hidden" name="post_type" value="wpsc-product">
					<input type="hidden" name="page" value="wpsc_dnd_cat_ui">
				</form>';
		$this->sortable_product_list();
		echo '</div>';
	}

	function sortable_product_list() {
		global $wpdb;
		$term = null;
		if ( isset( $_GET['wpsc_product_category'] ) ) {
			$term = get_term_by( 'slug', $_GET['wpsc_product_category'], 'wpsc_product_category' );
		}
		if ( ! $term )
			return;

		$posts = $wpdb->get_results( $wpdb->prepare( "
			SELECT ID, post_title FROM {$wpdb->posts}
			LEFT JOIN {$wpdb->term_relationships} as tr
				ON tr.object_id = ID
			WHERE post_status = 'publish'
				AND post_parent = 0
				AND post_type = 'wpsc-product'
				AND term_taxonomy_id = %d
			GROUP BY ID
			ORDER BY menu_order ASC
		", $term->term_taxonomy_id ) );
		echo '<div class="sortable-posts">';
		foreach ( $posts as $post ) {
			echo '<div id="post-' . $post->ID . '" class="post">';
			echo '<span class="loader"></span>';
			echo '<span class="thumbnail">' . get_the_post_thumbnail( $post->ID, array( 50, 50 ) ) . '</span>';
			echo edit_post_link( apply_filters( 'the_title', $post->post_title, $post->ID ), '', '', $post->ID );
			echo '</div>';
		}
		echo '</div>';
	}

	function save_product_order_ajax_callback() {
		global $wpdb;

		foreach ( $_POST['post'] as $product ) {
			$products[] = (int) str_replace( 'post-', '', $product );
		}

		$failed = array();
		foreach ( $products as $order => $product_id ) {
			$result = $wpdb->update(
				$wpdb->posts,
				array( 'menu_order' => $order ),
				array( 'ID' => $product_id ),
				array( '%d' ),
				array( '%d' )
			);
			if ( $result == 0 )
				$failed[] = $product_id;
		}

		if ( ! empty( $failed ) ) {
			$error_data = array(
				'failed_ids' => $failed
			);
			return new WP_Error( 'wpsc_cannot_save_product_sort_order', __( 'Unable to save the product sort order. Please try again.', 'wpsc-dnd-cat-ui' ), $error_data );
		}

		return array(
			'ids' => $products,
		);
		die();
	}

}
new WPSC_DnDCatUI();
