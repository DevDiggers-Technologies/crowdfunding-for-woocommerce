<?php
/**
 * Front end hooks.
 *
 * @author DevDiggers
 * @package DevDiggers Crowdfunding for WooCommerce
 * @version 1.0.0
 */

namespace DDWCCrowdFunding\Includes\Front;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCCF_Front_Hooks' ) ) {
	/**
	 * Front end hooks class.
	 */
	class DDWCCF_Front_Hooks extends DDWCCF_Front_Functions {
		/**
		 * Construct.
		 *
		 * @param array $ddwccf_configuration Configuration.
		 */
		public function __construct( $ddwccf_configuration ) {
			parent::__construct( $ddwccf_configuration );

			if ( 'yes' !== $ddwccf_configuration['enabled'] ) {
				return;
			}

			add_action( 'wp_enqueue_scripts', [ $this, 'ddwccf_front_scripts' ] );

			// My Account.
			add_filter( 'woocommerce_account_menu_items', [ $this, 'ddwccf_add_woocommerce_menu' ] );
			add_filter( 'woocommerce_get_query_vars', [ $this, 'ddwccf_add_query_vars' ] );
			add_action( 'woocommerce_account_' . $ddwccf_configuration['backed_endpoint'] . '_endpoint', [ $this, 'ddwccf_add_backed_content_on_my_account_page' ] );
			add_filter( 'woocommerce_endpoint_' . $ddwccf_configuration['backed_endpoint'] . '_title', [ $this, 'ddwccf_backed_endpoint_title' ] );
			add_filter( 'sidebars_widgets', [ $this, 'ddwccf_remove_sidebar_from_custom_menu_page' ] );

			// Campaign page.
			add_filter( 'woocommerce_product_single_add_to_cart_text', [ $this, 'ddwccf_modify_product_single_add_to_cart_text' ], 10, 2 );
			add_filter( 'woocommerce_product_add_to_cart_text', [ $this, 'ddwccf_modify_product_single_add_to_cart_text' ], 10, 2 );
			add_filter( 'woocommerce_product_add_to_cart_url', [ $this, 'ddwccf_loop_add_to_cart_url' ], 10, 2 );
			add_filter( 'woocommerce_product_supports', [ $this, 'ddwccf_disable_ajax_add_to_cart' ], 10, 3 );
			add_action( 'woocommerce_single_product_summary', [ $this, 'ddwccf_handle_remove_default_hooks' ], 1 );
			add_action( 'woocommerce_single_product_summary', [ $this, 'ddwccf_add_content_on_single_product_page' ], 25 );
			add_action( 'woocommerce_before_add_to_cart_button', [ $this, 'ddwccf_add_content_before_add_to_cart_button' ] );
			add_action( 'woocommerce_single_product_summary', [ $this, 'ddwccf_add_share_box' ], 45 );
			add_filter( 'woocommerce_product_tabs', [ $this, 'ddwccf_product_tabs' ], 99 );
			add_action( 'woocommerce_after_single_product_summary', [ $this, 'ddwccf_add_supporters_list' ], 5 );

			// Cart and checkout.
			add_filter( 'woocommerce_add_to_cart_validation', [ $this, 'ddwccf_validate_contribution' ], 10, 2 );
			add_filter( 'woocommerce_add_cart_item_data', [ $this, 'ddwccf_add_cart_item_data' ], 10, 2 );
			add_filter( 'woocommerce_get_item_data', [ $this, 'ddwccf_display_cart_item_data' ], 10, 2 );
			add_action( 'woocommerce_before_calculate_totals', [ $this, 'ddwccf_change_event_product_price' ] );
			add_action( 'template_redirect', [ $this, 'ddwccf_add_notice_for_event_product' ] );
			add_action( 'woocommerce_calculate_totals', [ $this, 'ddwccf_check_event_product_in_cart' ] );
			add_filter( 'woocommerce_available_payment_gateways', [ $this, 'ddwccf_remove_payment_gateway' ] );

			add_shortcode( 'ddwccf_campaigns', [ $this, 'ddwccf_campaigns_shortcode' ] );
			add_shortcode( 'ddwccf_campaign', [ $this, 'ddwccf_campaign_shortcode' ] );
		}
	}
}
