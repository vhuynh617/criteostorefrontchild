<?php
/**
 * Class for rendering Criteo OneTags in Woocommerce. Exercises are product category based.
 */

class Criteo_OneTag {

	private static $instance = null;

	public static function get_instance() {

		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;

	}

	const PARTNERID = "32730";

	private $exercise = 0;

	private $exercises = array(
		// Default
		0 => array(
			'level' => 'Default',
			'description' => 'All Criteo tags are correctly implemented. Catalog quality is 100%. Use partner [32730] mmsuststraining.',
			'answer' => 'OK.',
		),
		// Beginner
		1 => array(
			'level' => 'Beginner 1',
			'description' => 'Check Home Page tag.',
			'answer' => 'Missing loader file.',
		),
		2 => array(
			'level' => 'Beginner 2',
			'description' => 'Check Listing tag.',
			'answer' => "No product ID's in array.",
		),
		3 => array(
			'level' => 'Beginner 3',
			'description' => 'Check Product tag.',
			'answer' => "No product ID's passed resulting in JS error.",
		),
		// Intermediate
		4 => array(
			'level' => 'Intermediate 1',
			'description' => 'Check Listing tag.',
			'answer' => "Product ID's are passed as string instead of items in an array.",
		),
		5 => array(
			'level' => 'Intermediate 2',
			'description' => 'Check Product tag.',
			'answer' => "Different ID type passed in tag, not matching ID's in feed.",
		),
		6 => array(
			'level' => 'Intermediate 3',
			'description' => 'Add multiple items to the cart and check Basket tag.',
			'answer' => 'Dollar sign prepended to prices causing JS error.',
		),
		// Advanced
		7 => array(
			'level' => 'Advanced 1',
			'description' => 'Add jersey type items to the cart and check Basket tag.',
			'answer' => "Child ID's are being passed which are not in feed.",
		),
		8 => array(
			'level' => 'Advanced 2',
			'description' => 'Purchase items of varying quanities and check Sales tag.',
			'answer' => 'Prices are line totals, not unit prices.',
		),
		9 => array(
			'level' => 'Advanced 3',
			'description' => 'Purchase novelty items and check Sales tag.',
			'answer' => 'Regular prices being passed instead of sale prices.',
		),
	);


	/**
	 * Construct.
	 */
	private function __construct() {
		if ( class_exists( 'WooCommerce' ) ) {
			// Actions
			add_action( 'wp_loaded', array( $this, 'action_init' ) );
			add_action( 'parse_query', array( $this, 'action_parse_query' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'action_wp_enqueue_scripts' ) );
			add_action( 'wp_footer', array( $this, 'action_wp_footer' ) );

			add_action( 'woocommerce_thankyou', array( $this, 'action_woocommerce_thankyou' ) );

			// Filters
			add_filter( 'post_link', array( $this, 'filter_the_permalink' ), 99 );
			add_filter( 'post_type_link', array( $this, 'filter_the_permalink' ), 99 );
			add_filter( 'post_type_link', array( $this, 'filter_the_permalink' ), 99 );
			add_filter( 'nav_menu_link_attributes', array( $this, 'filter_nav_menu_link_attributes' ), 99, 3 );
			add_filter( 'metaslider_image_slide_attributes', array( $this, 'filter_metaslider_image_slide_attributes' ), 99, 3 );
			add_filter( 'login_headerurl', array( $this, 'filter_login_headerurl' ), 99 );
			add_filter( 'woocommerce_get_cart_url', array( $this, 'filter_woocommerce_get_cart_url' ), 99 );
			add_filter( 'woocommerce_get_checkout_page_permalink', array( $this, 'filter_woocommerce_get_checkout_page_permalink' ), 9999 );

			add_filter( 'woocommerce_widget_cart_is_hidden', array( $this, 'filter_woocommerce_widget_cart_is_hidden' ), 99 );
			add_filter( 'wc_add_to_cart_message', array( $this, 'filter_wc_add_to_cart_message' ), 99, 2 );
		}
	}

	/**
 	 *
 	 *	General setup and initialization functions.
 	 *
 	 */

	/**
	 * Add rewrite endpoint for exercises.
	 *
	 * @action init
	 */
	function action_init() {
		add_rewrite_endpoint( 'exercise', EP_ALL );

		add_rewrite_rule( 'product-category/(.+?)/page/?([0-9]{1,})/exercise(/(.*))?/?$', 'index.php?product_cat=$matches[1]&paged=$matches[2]&exercise=$matches[4]', 'top');
		add_rewrite_rule( 'product-category/(.+?)/exercise(/(.*))?/?$', 'index.php?product_cat=$matches[1]&exercise=$matches[3]', 'top');
		add_rewrite_rule( '(.?.+?)/exercise(/(.*))?/order-received(/(.*))?/?$', 'index.php?pagename=$matches[1]&exercise=$matches[3]&order-received=$matches[5]', 'top');
	}

	/**
	 * Set exercise.
	 *
	 * @action parse_query
	 */
	function action_parse_query() {
		$this->exercise = get_query_var( 'exercise', 0 );
	}

	/**
	 * Enqueue JS.
	 *
	 * @todo Fix cart page URL updating after markup is rendered. URL in markup is correct.
	 *
	 * @action wp_enqueue_scripts
	 */
	function action_wp_enqueue_scripts() {
		wp_enqueue_script( 'criteo-exercises', get_stylesheet_directory_uri() . '/js/criteo-exercises.js', array( 'jquery' ), '20161206', true );

		// Workaround for cart page URL updating after markup is rendered.
		wp_localize_script( 'criteo-exercises', 'wcCartURL', esc_url( WC()->cart->get_cart_url() ) );
	}

	/**
 	 *
 	 *	Criteo OneTag logic and tag rendering funcitons.
 	 *
 	 */

	/**
	 * Determine if OneTag should be outputted and render it.
	 *
	 * @action wp_footer
	 */
	function action_wp_footer() {
		if ( is_shop() || is_front_page() ) {
			$this->render_home_page_tag();
		} else if ( is_product_category() ) {
			$this->render_listing_tag();
		} else if ( is_product() ) {
			$this->render_product_tag();
		} else if ( is_page( 'cart' ) ) {
			$this->render_basket_tag();
		}
	}

	/**
	 * Render Home Page tag.
	 */
	function render_home_page_tag() {
		ob_start();

		if ( 1 !== (int) $this->exercise ) {
		?>
			<script type="text/javascript" src="//static.criteo.net/js/ld/ld.js" async="true"></script>
		<?php
		}
		?>
			<script type="text/javascript">
				window.criteo_q = window.criteo_q || [];
				window.criteo_q.push(
					{ event: "setAccount", account: <?php echo self::PARTNERID; ?> },
					{ event: "setSiteType", type: "d" },
					{ event: "viewHome"}
				);
			</script>
		<?php
		$onetag = ob_get_clean();

		echo $onetag;
	}

	/**
	 * Render Listing tag.
	 */
	function render_listing_tag() {

		wp_reset_query();

		$product_ids_array = array();
		$count = 0;

		while ( have_posts() && $count < 3 ) {
			the_post();

			$product_ids_array[] = $this->get_sku();

			$count++;
		}

		$product_ids = implode( ', ', $product_ids_array );

		ob_start();
		?>
			<script type="text/javascript" src="//static.criteo.net/js/ld/ld.js" async="true"></script>
			<script type="text/javascript">
				window.criteo_q = window.criteo_q || [];
				window.CriteoProductIDList = window.CriteoProductIDList || [];
				window.criteo_q.push(
					{ event: "setAccount", account: <?php echo self::PARTNERID; ?> },
					{ event: "setSiteType", type: "d" },
					<?php if ( 2 === (int) $this->exercise ) { ?>
						{ event: "viewList", item: [] }
					<?php } else if ( 4 === (int) $this->exercise ) { ?>
						{ event: "viewList", item: "<?php echo esc_js( $product_ids ); ?>" }
					<?php } else { ?>
						{ event: "viewList", item: [<?php echo esc_js( $product_ids ); ?>] }
					<?php } ?>
				);
			</script>
		<?php
		$onetag = ob_get_clean();

		echo $onetag;

		wp_reset_query();
	}

	/**
	 * Render Product tag.
	 */
	function render_product_tag() {
		global $post;

		$post_id = empty( $post->ID ) ? 0 : $post->ID;

		$product_id = $this->get_sku();

		ob_start();
		?>
			<script type="text/javascript" src="//static.criteo.net/js/ld/ld.js" async="true"></script>
			<script type="text/javascript">
				window.criteo_q = window.criteo_q || [];
				window.criteo_q.push(
					{ event: "setAccount", account: <?php echo self::PARTNERID; ?> },
					{ event: "setSiteType", type: "d" },
					<?php if ( 3 === (int) $this->exercise ) { ?>
						{ event: "viewItem", item:  }
					<?php } else if ( 5 === (int) $this->exercise ) { ?>
						{ event: "viewItem", item: <?php echo esc_js( $post_id ); ?> }
					<?php } else { ?>
						{ event: "viewItem", item: <?php echo esc_js( $product_id ); ?> }
					<?php } ?>
				);
			</script>
		<?php
		$onetag = ob_get_clean();

		echo $onetag;
	}

	/**
	 * Render Basket tag.
	 */
	function render_basket_tag() {

		global $woocommerce;
		$items = $woocommerce->cart->get_cart();

		if ( ! empty( $items ) ) {

			$basket_products_array = array();

			foreach( $items as $item => $values ) {

				$product = $values['data'];

				$sku = $product->get_sku();

				if ( ! empty( $product->parent ) && ( 7 !== (int) $this->exercise ) ) {
					$sku = $product->parent->get_sku();
				}

				if ( $sku ) {

					$product_price = ( 6 === (int) $this->exercise ) ? '$' . $product->price : $product->price;

					$basket_products_array[] = '{ id: "' . esc_js( $sku ) . '", price: ' . esc_js( $product_price ). ', quantity: ' . esc_js( $values['quantity'] ) . ' }';
				}
			}

			$basket_products = implode( ', ', $basket_products_array );

			ob_start();
			?>
				<script type="text/javascript" src="//static.criteo.net/js/ld/ld.js" async="true"></script>
				<script type="text/javascript">
					window.criteo_q = window.criteo_q || [];
					window.criteo_q.push(
						{ event: "setAccount", account: <?php echo self::PARTNERID; ?> },
						{ event: "setSiteType", type: "d" },
						{ event: "viewBasket", item: [<?php echo $basket_products; ?>] }
					);
				</script>
			<?php
			$onetag = ob_get_clean();

			echo $onetag;
		}
	}

	/**
	 * Render Sales tag.
	 *
	 * @param int $order_id The order ID
	 *
	 * @action woocommerce_thankyou
	 */
	function action_woocommerce_thankyou( $order_id = 0 ) {

		if ( empty( $order_id ) ) {
			return;
		}

		$order = wc_get_order( $order_id );
		$items = $order->get_items();

		if ( ! empty( $items ) ) {

			$transaction_products_array = array();

			foreach ( $items as $item ) {

				$product = $order->get_product_from_item( $item );

				$sku = $product->get_sku();

				if ( ! empty( $product->parent ) ) {
					$sku = $product->parent->get_sku();
				}

				if ( $sku ) {

					$price = $product->price;

					if ( 8 === (int) $this->exercise ) {
						$price = $item['item_meta']['_line_total'][0];
					}

					if ( 9 === (int) $this->exercise ) {
						$price = $product->regular_price;
					}

					$transaction_products_array[] = '{ id: "' . esc_js( $sku ) . '", price: ' . esc_js( $price ). ', quantity: ' . esc_js( $item['qty'] ) . ' }';
				}
			}

			$transaction_products = implode( ', ', $transaction_products_array );

			ob_start();
			?>
				<script type="text/javascript" src="//static.criteo.net/js/ld/ld.js" async="true"></script>
				<script type="text/javascript">
					window.criteo_q = window.criteo_q || [];
					window.criteo_q.push(
						{ event: "setAccount", account: <?php echo self::PARTNERID; ?> },
						{ event: "setSiteType", type: "d" },
						{ event: "trackTransaction" , id: <?php echo esc_js( $order_id ); ?>, item: [<?php echo $transaction_products ; ?>] }
					);
				</script>
			<?php
			$onetag = ob_get_clean();

			echo $onetag;
		}
	}

	/**
 	 *
 	 * Filter links to add exercise end point.
 	 *
 	 */

	/**
	 * Add exercise endpoint
	 *
	 * @param string $permalink The permalink for the current post.
	 *
	 * @filter the_permalink
	 */
	function filter_the_permalink( $permalink ) {

		$exercise_permalink = trailingslashit( $permalink . 'exercise/' . $this->exercise );

		return $exercise_permalink;
	}

	/**
	 * Add exercise endpoint to menu items.
	 */
	function filter_nav_menu_link_attributes( $atts, $item, $args ) {

		$atts['href'] = trailingslashit( trailingslashit( $atts['href'] ) . 'exercise/' . $this->exercise );

		return $atts;
	}

	/**
	 * Add exercise endpoint to metaslider slides.
	 */
	function filter_metaslider_image_slide_attributes( $slide, $slider_id, $settings ) {

		$slide['url'] = trailingslashit( trailingslashit( $slide['url'] ) . 'exercise/' . $this->exercise );

		return $slide;
	}

	/**
	 * Add exercise endpoint to logo.
	 */
	function filter_login_headerurl( $url ) {

		$url = trailingslashit( trailingslashit( $url ) . 'exercise/' . $this->exercise );

		return $url;
	}

	/**
	 * Add exercise endpoint to cart URL.
	 */
	function filter_woocommerce_get_cart_url( $url ) {

		$url = trailingslashit( trailingslashit( $url ) . 'exercise/' . self::get_instance()->exercise );

		return $url;
	}

	/**
	 * Add exercise endpoint to checkout URL.
	 */
	function filter_woocommerce_get_checkout_page_permalink( $url ) {

		$url = trailingslashit( trailingslashit( $url ) . 'exercise/' . self::get_instance()->exercise );

		return $url;
	}

	/**
 	 *
 	 *	Various filters.
 	 *
 	 */

	/**
	 * Disable cart widget.
	 */
	function filter_woocommerce_widget_cart_is_hidden( $disable ) {

		return true;
	}

	/**
	 * Disable add to cart widget.
	 */
	function filter_wc_add_to_cart_message( $message, $product_id ) {

		return '';
	}

	/**
 	 *
 	 *	Rendering functions.
 	 *
 	 */

	/**
	 * Override storefront_site_title_or_logo function.
	 */
	function site_title_or_logo() {

		add_filter( 'home_url', array( 'Criteo_OneTag', 'filter_home_url' ), 99 );

		if ( function_exists( 'the_custom_logo' ) && has_custom_logo() ) {
			$logo = get_custom_logo();

			echo $logo = is_home() ? '<h1 class="logo">' . $logo . '</h1>' : $logo;
		} elseif ( function_exists( 'jetpack_has_site_logo' ) && jetpack_has_site_logo() ) {
			jetpack_the_site_logo();
		} else {
			$tag = is_home() ? 'h1' : 'div';

			echo '<' . esc_attr( $tag ) . ' class="beta site-title"><a href="' . esc_url( home_url( '/' ) ) . '" rel="home">' . esc_attr( get_bloginfo( 'name' ) ) . '</a></' . esc_attr( $tag ) .'>';

			if ( '' != get_bloginfo( 'description' ) ) { ?>
				<p class="site-description"><?php echo bloginfo( 'description' ); ?></p>
				<?php
			}
		}

		remove_filter( 'home_url', array( 'Criteo_OneTag', 'filter_home_url' ), 99 );

	}

	/**
	 * Renders exercise select box.
	 */
	static function render_exercise_select_box() {

		$exercise = self::get_instance()->exercise;
		$exercises = self::get_instance()->exercises;

		$current_url = '//' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

		$pattern = '/exercise\/(\d+)/';

		ob_start();
		?>
			<div class="site-search">
				<label class="exercise-select-label"><?php _e( '- Select Exercise -', 'criteostorefrontchild' ); ?></label>
				<select id="exercise-select">
				<?php
				for ( $i = 0; $i < count( $exercises ); $i++ ) {
					if ( preg_match( $pattern, $current_url ) ) {
						$exercise_url = preg_replace( $pattern, 'exercise/' . $i, $current_url );
					} else {
						$exercise_url = trailingslashit( trailingslashit( $current_url ) . 'exercise/' . $i );
					}
				?>
					<option class="exercise-select-option" value="<?php echo esc_url( $exercise_url ); ?>" <?php selected( $exercise, $i ); ?>><?php echo esc_html( $exercises[ $i ]['level'] ); ?></option>
				<?php
				}
				?>
				</select>
			</div>
		<?php

		$select_list = ob_get_clean();

		echo $select_list;
	}

	/**
 	 *
 	 *	Getter functions.
 	 *
 	 */

	/**
	 * Add exercise endpoint to home URL.
	 */
	static function filter_home_url( $url ) {

		$url = trailingslashit( trailingslashit( $url ) . 'exercise/' . self::get_instance()->exercise );

		return $url;
	}

	/**
	 * Get current exercise description.
	 */
	public function get_exercise_description() {
		return $this->exercises[ $this->exercise ]['level'] . ': ' . $this->exercises[ $this->exercise ]['description'];
	}

	/**
	 * Get current exercise answer.
	 */
	public function get_exercise_answer() {
		return $this->exercises[ $this->exercise ]['answer'];
	}

	/**
	 * Gets the SKU of the current product.
	 *
	 * @return string The SKU of the current product.
	 */
	function get_sku() {
		global $product;

		return $product->get_sku();
	}
}

Criteo_OneTag::get_instance();
