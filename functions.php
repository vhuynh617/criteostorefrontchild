<?php

$stylesheet_directory = get_stylesheet_directory();

/**
 * Include
 */
require_once( $stylesheet_directory . '/inc/class-criteo-onetag.php' );

function criteostorefrontchild_enqueue_styles() {
	wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
}
add_action( 'wp_enqueue_scripts', 'criteostorefrontchild_enqueue_styles' );

/**
 * Override storefront_page_header function. Remove title from home page template.
 */
function storefront_page_header() {
	?>
	<header class="entry-header">
		<?php
		if ( ! is_page() ) {
			storefront_post_thumbnail( 'full' );
			the_title( '<h1 class="entry-title">', '</h1>' );
		}
		?>
	</header><!-- .entry-header -->
	<?php
}

/**
 * Don't display credit link.
 */
function criteostorefrontchild_storefront_credit_link() {
	return false;
}
add_filter( 'storefront_credit_link', 'criteostorefrontchild_storefront_credit_link', 99 );

/**
 * Disable reviews.
 *
 * @param array $tabs Tabs for product detail page.
 */
function criteostorefrontchild_woocommerce_product_tabs( $tabs ) {

	unset( $tabs['reviews'] );

	return $tabs;
}
add_filter( 'woocommerce_product_tabs', 'criteostorefrontchild_woocommerce_product_tabs', 99 );

/**
 * Replace product search with Exercise selector
 */
function storefront_product_search() {
	Criteo_OneTag::render_exercise_select_box();
}


function storefront_site_title_or_logo() {
	Criteo_OneTag::site_title_or_logo();
}

// Remove breadcrumbs
function woocommerce_breadcrumb() {
	?>
	<nav class="woocommerce-breadcrumb" <?php echo ( is_single() ? 'itemprop="breadcrumb"' : '' ); ?>>
		<span class="exercise-description"><?php echo esc_html( Criteo_OneTag::get_instance()->get_exercise_description() ); ?></span>
		<?php
		if ( is_user_logged_in() ) {
		?>
			<span class="exercise-answer"><?php echo esc_html( Criteo_OneTag::get_instance()->get_exercise_answer() ); ?></span>
		<?php
		}
		?>
	</nav>
	<?php
}

// Perform actions in wp_head
function criteostorefrontchild_wp_head() {
	// Remove sorting
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 10 );
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
	remove_action( 'woocommerce_before_shop_loop', 'storefront_woocommerce_pagination', 30 );
	remove_action( 'woocommerce_after_shop_loop', 'woocommerce_catalog_ordering', 10 );
	remove_action( 'woocommerce_after_shop_loop', 'woocommerce_result_count', 20 );
	remove_action( 'woocommerce_after_shop_loop', 'woocommerce_pagination', 30 );
}
add_action( 'wp_head', 'criteostorefrontchild_wp_head' );

// Remove AJAX add to cart button
function criteostorefrontchild_woocommerce_loop_add_to_cart_link() {
	return '';
}
add_filter( 'woocommerce_loop_add_to_cart_link', 'criteostorefrontchild_woocommerce_loop_add_to_cart_link', 99 );
