<?php
class Criteo_OneTag {

	private static $instance = null;

	public static function get_instance() {

		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;

    }

    static $partner_id = 123456;


	private function __construct() {
		if ( class_exists( 'WooCommerce' ) ) {
			add_action( 'wp_footer', array( $this, 'action_wp_footer' ) );
		}
    }

    function action_wp_footer() {
		ob_start();
		?>
			<script type="text/javascript" src="//static.criteo.net/js/ld/ld.js" async="true"></script>
			<script type="text/javascript">
				window.criteo_q = window.criteo_q || [];
				window.criteo_q.push(
					//{ event: "setAccount", account: 123456 },
					{ event: "setSiteType", type: "d" },
		<?php
		if ( is_shop()) {
		?>
			{ event: "viewHome"}
		<?
		}

		?>
				);
			</script>
		<?php

		$onetag = ob_get_clean();

		echo $onetag;
	}

}

Criteo_OneTag::get_instance();