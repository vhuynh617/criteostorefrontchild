<?php

$stylesheet_directory = get_stylesheet_directory();

require_once( $stylesheet_directory . '/inc/class-criteo-onetag.php' );

function criteostorefrontchild_enqueue_styles() {
	wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
}
add_action( 'wp_enqueue_scripts', 'criteostorefrontchild_enqueue_styles' );