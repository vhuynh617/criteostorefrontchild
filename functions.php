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
 * Override storefront_cart_link. Only include cart in primary navigation for woocommerce related pages.
 */
/*
function storefront_cart_link() {
	if ( is_woocommerce() || is_cart() || is_checkout() ) {
		?>
			<a class="cart-contents" href="<?php echo esc_url( WC()->cart->get_cart_url() ); ?>" title="<?php esc_attr_e( 'View your shopping cart', 'storefront' ); ?>">
				<span class="amount"><?php echo wp_kses_data( WC()->cart->get_cart_subtotal() ); ?></span> <span class="count"><?php echo wp_kses_data( sprintf( _n( '%d item', '%d items', WC()->cart->get_cart_contents_count(), 'storefront' ), WC()->cart->get_cart_contents_count() ) );?></span>
			</a>
		<?php
	}
}
*/

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
