<?php
/**
 * Class for rendering Criteo OneTags in Woocommerce.
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

	/**
	 * Construct.
	 */
	private function __construct() {
		if ( class_exists( 'WooCommerce' ) ) {
			add_action( 'wp_footer', array( $this, 'action_wp_footer' ) );

			add_action( 'woocommerce_thankyou', array( $this, 'action_woocommerce_thankyou' ) );
		}
	}

	/**
	 * Determine if OneTag should be outputted and render it.
	 *
	 * @action wp_footer
	 */
	function action_wp_footer() {
		if ( is_shop() ) {
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
					{ event: "viewList", item: [<?php echo esc_js( $product_ids ); ?>] }
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
		$product_id = $this->get_sku();

		ob_start();
		?>
			<script type="text/javascript" src="//static.criteo.net/js/ld/ld.js" async="true"></script>
			<script type="text/javascript">
				window.criteo_q = window.criteo_q || [];
				window.criteo_q.push(
					{ event: "setAccount", account: <?php echo self::PARTNERID; ?> },
					{ event: "setSiteType", type: "d" },
					{ event: "viewItem", item: <?php echo esc_js( $product_id ); ?> }
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

		$basket_products_array = array();

		foreach($items as $item => $values) {

			$product = $values['data'];

			if ( $sku = $product->get_sku() ) {
				$basket_products_array[] = '{ id: "' . esc_js( $sku ) . '", price: ' . esc_js( $product->price ). ', quantity: ' . esc_js( $values['quantity'] ) . ' }';
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

		$transaction_products_array = array();

		foreach ( $items as $item ) {

			$product = $order->get_product_from_item( $item );

			if ( $sku = $product->get_sku() ) {
				$transaction_products_array[] = '{ id: "' . esc_js( $sku ) . '", price: ' . esc_js( $product->price ). ', quantity: ' . esc_js( $item['qty'] ) . ' }';
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