<?php
/**
 * Class for rendering Criteo OneTags in Woocommerce. Exercises are product category based.
 *
 * Home Page:
 * OK
 *
 * Listing:
 * Jerseys - OK
 * Hats - No product ID's in array
 * Novelties - Product ID's are passed as string instead of items in an array
 *
 * Product:
 * Jerseys - OK
 * Hats - No product ID's passed resulting in JS error
 * Novelties - Different ID type, not matching ID's in feed
 *
 * Basket:
 * Jerseys - Child ID's passed which are not in feed.
 * Hats - Missing comma between item objects.
 * Novelties - OK
 *
 * Sales:
 * Jerseys - Prices are line totals, not unit price
 * Hats - OK
 * Novelties - Hardcoded transaction ID
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

	private $exercise = 1;

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
			add_filter( 'woocommerce_widget_cart_is_hidden', array( $this, 'filter_woocommerce_widget_cart_is_hidden' ), 99 );
			add_filter( 'woocommerce_get_checkout_page_permalink', array( $this, 'filter_woocommerce_get_checkout_page_permalink' ), 9999 );


		}
	}

	/**
	 * Add rewrite endpoint for exercises.
	 *
	 * @action init
	 */
	function action_init() {
		add_rewrite_endpoint( 'exercise', EP_ALL );

		add_rewrite_rule( 'product-category/(.+?)/page/?([0-9]{1,})/exercise(/(.*))?/?$', 'index.php?product_cat=$matches[1]&paged=$matches[2]&exercise=$matches[4]', 'top');
		add_rewrite_rule( 'product-category/(.+?)/exercise(/(.*))?/?$', 'index.php?product_cat=$matches[1]&exercise=$matches[3]', 'top');
		add_rewrite_rule( '(.?.+?)/order-received/(.*)/exercise(/(.*))?/?$', 'index.php?pagename=$matches[1]&order-received=$matches[2]&exercise=$matches[4]', 'top');
		add_rewrite_rule( '(.?.+?)/exercise(/(.*))?/order-received(/(.*))?/?$', 'index.php?pagename=$matches[1]&exercise=$matches[3]&order-received=$matches[5]', 'top');
	}

	/**
	 * Set exercise.
	 *
	 * @action parse_query
	 */
	function action_parse_query() {
		$this->exercise = get_query_var( 'exercise', 1 );

	}

	/**
	 * Enqueue JS.
	 *
	 * @action wp_enqueue_scripts
	 */
	function action_wp_enqueue_scripts() {
		wp_enqueue_script( 'criteo-exercises', get_stylesheet_directory_uri() . '/js/criteo-exercises.js', array( 'jquery' ), '20161206', true );
	}


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
	 * Render Home Page tag.
	 */
	function render_home_page_tag() {
		ob_start();
		?>
			<script type="text/javascript" src="//static.criteo.net/js/ld/ld.js" async="true"></script>
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
					<?php if ( is_tax( 'product_cat', 'hats' ) ) { ?>
						{ event: "viewList", item: [] }
					<?php } else if ( is_tax( 'product_cat', 'novelties' ) ) { ?>
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
					<?php if ( has_term( 'hats', 'product_cat' ) ) { ?>
						{ event: "viewItem", item:  }
					<?php } else if ( has_term( 'novelties', 'product_cat' ) ) { ?>
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

			$first_post = '';

			foreach( $items as $item => $values ) {

				$product = $values['data'];

				if ( empty( $first_post ) ) {
					$first_post = $product->post;
				}

				if ( $sku = $product->get_sku() ) {

					if ( has_term( 'jerseys', 'product_cat', $first_post ) ) {

						$random_number = mt_rand( 0, 90 );
						$padded_random_number = str_pad( $random_number, 2, '0', STR_PAD_LEFT );

						$sku = $sku . $padded_random_number;
					}

					$basket_products_array[] = '{ id: "' . esc_js( $sku ) . '", price: ' . esc_js( $product->price ). ', quantity: ' . esc_js( $values['quantity'] ) . ' }';
				}
			}

			$seperator = has_term( 'hats', 'product_cat', $first_post ) ? '' : ', ';

			$basket_products = implode( $seperator, $basket_products_array );

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

			$first_post = '';

			foreach ( $items as $item ) {

				$product = $order->get_product_from_item( $item );

				if ( empty( $first_post ) ) {
					$first_post = $product->post;
				}

				if ( $sku = $product->get_sku() ) {

					$price = $product->price;

					if ( has_term( 'jerseys', 'product_cat', $first_post ) ) {
						$price = $item['item_meta']['_line_total'][0];
					}

					$transaction_products_array[] = '{ id: "' . esc_js( $sku ) . '", price: ' . esc_js( $price ). ', quantity: ' . esc_js( $item['qty'] ) . ' }';


				}
			}

			$transaction_products = implode( ', ', $transaction_products_array );

			$transaction_id = has_term( 'novelties', 'product_cat', $first_post ) ? 'TRANSACTION_ID' : $order_id;

			ob_start();
			?>
				<script type="text/javascript" src="//static.criteo.net/js/ld/ld.js" async="true"></script>
				<script type="text/javascript">
					window.criteo_q = window.criteo_q || [];
					window.criteo_q.push(
						{ event: "setAccount", account: <?php echo self::PARTNERID; ?> },
						{ event: "setSiteType", type: "d" },
						{ event: "trackTransaction" , id: <?php echo esc_js( $transaction_id ); ?>, item: [<?php echo $transaction_products ; ?>] }
					);
				</script>
			<?php
			$onetag = ob_get_clean();

			echo $onetag;
		}
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

	/**
	 * Renders exercise select box.
	 */
	static function render_exercise_select_box() {
		ob_start();
			?>
				<div class="site-search">
					<label class="exercise-select-label"><?php _e( '- Select Exercise -', 'criteostorefrontchild' ); ?></label>
					<select id="exercise-select">
						<option class="exercise-select-option" value="/">Beginner - 1</option>
						<option class="exercise-select-option" value="/">Beginner - 2</option>
						<option class="exercise-select-option" value="/">Beginner - 3</option>
						<option class="exercise-select-option" value="/">Intermediate - 1</option>
						<option class="exercise-select-option" value="/">Intermediate - 2</option>
						<option class="exercise-select-option" value="/">Intermediate - 3</option>
						<option class="exercise-select-option" value="/">Advanced - 1</option>
						<option class="exercise-select-option" value="/">Advanced - 2</option>
						<option class="exercise-select-option" value="/">Advanced - 3</option>
					</select>
				</div>
			<?php
		$select_list = ob_get_clean();

		echo $select_list;

	}

	/**
	 * Override storefront_site_title_or_logo function.
	 */
	static function site_title_or_logo() {

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
	 * Add exercise endpoint to home URL.
	 */
	static function filter_home_url( $url ) {

		$url = trailingslashit( trailingslashit( $url ) . 'exercise/' . self::get_instance()->exercise );

		return $url;
	}

	/**
	 * Add exercise endpoint to cart URL.
	 */
	static function filter_woocommerce_get_cart_url( $url ) {

		$url = trailingslashit( trailingslashit( $url ) . 'exercise/' . self::get_instance()->exercise );

		//error_log( $url );

		return $url;
	}

	/**
	 * Disable cart widget.
	 */
	static function filter_woocommerce_widget_cart_is_hidden( $disable ) {

		return true;
	}

	/**
	 * Add exercise endpoint to checkout URL.
	 */
	static function filter_woocommerce_get_checkout_page_permalink( $url  ) {

		$url = trailingslashit( trailingslashit( $url ) . 'exercise/' . self::get_instance()->exercise );

		return $url;
	}

}

Criteo_OneTag::get_instance();