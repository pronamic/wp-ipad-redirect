<?php
/*
Plugin Name: iPad redirect
Plugin URI:	http://pronamic.eu/wp-plugins/ipad-redirect/
Description: This plugin makes it easy to redirect iPad users to an alternative URL.

Version: 0.1
Requires at least: 3.0

Author: Pronamic
Author URI: http://pronamic.eu/

Text Domain: ipad_redirect
Domain Path: /languages/

License: GPL
*/

function ipad_redirect() {
	if( is_singular( ) ) {
		global $post;

		$user_agent = filter_input( INPUT_SERVER, 'HTTP_USER_AGENT', FILTER_SANITIZE_STRING );
	
		$is_ipad = strpos( $user_agent, 'iPad') !== false;
		$is_iphone = strpos( $user_agent, 'iPhone') !== false;

		if ( $is_ipad || $is_iphone ) {
			$ipad_redirect_url = get_post_meta( $post->ID, '_ipad_redirect_url', true );
	
			if ( ! empty( $ipad_redirect_url ) ) {
				wp_redirect( $ipad_redirect_url, 303 );
	
				exit;
			}
		}
	}
}

add_action( 'template_redirect', 'ipad_redirect', 1 );

function ipad_redirect_meta_box( $post ) {
	wp_nonce_field( plugin_basename( __FILE__ ), 'ipad_redirect_nonce' );

	printf( 
		'<label for="ipad_redirect_url">%s</label>' , 
		__( 'URL', 'ipad_redirect' )
	);

	printf( 
		'<input type="url" id="ipad_redirect_url" name="ipad_redirect_url" value="%s" size="25" />' ,
		esc_attr( get_post_meta( $post->ID, '_ipad_redirect_url', true ) )
	);
}

function ipad_redirect_add_meta_boxes() {
    add_meta_box(
    	'ipad_redirect' , 
		__( 'iPad Redirect', 'ipad_redirect' ) , 
        'ipad_redirect_meta_box' , 
        'page' , 
		'side'
    );
}

add_action( 'add_meta_boxes', 'ipad_redirect_add_meta_boxes' );

function ipad_redirect_save_post( $post_id, $post ) {
	// verify if this is an auto save routine. 
	// If it is our form has not been submitted, so we dont want to do anything
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
		return;

	// verify this came from the our screen and with proper authorization,
	// because save_post can be triggered at other times
	if ( !wp_verify_nonce( filter_input( INPUT_POST, 'ipad_redirect_nonce', FILTER_SANITIZE_STRING ), plugin_basename( __FILE__ ) ) )
		return;
  
	// Check permissions
	if ( 'page' == $post->post_type ) {
    	if ( !current_user_can( 'edit_page', $post_id ) )
        	return;
  	} else {
		if ( !current_user_can( 'edit_post', $post_id ) )
			return;
	}

	// OK, we're authenticated
	$url = filter_input( INPUT_POST, 'ipad_redirect_url', FILTER_VALIDATE_URL );

	if ( ! empty ( $url ) ) {
		update_post_meta( $post_id, '_ipad_redirect_url', $url );
	} else {
		delete_post_meta( $post_id, '_ipad_redirect_url' );
	}
}

add_action( 'save_post', 'ipad_redirect_save_post', 10, 2 );
