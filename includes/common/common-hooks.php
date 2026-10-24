<?php
/**
 * Common hooks: shared by admin, front, AJAX, cron and REST requests.
 *
 * @author DevDiggers
 * @package DevDiggers Crowdfunding for WooCommerce
 * @version 1.0.0
 */

namespace DDWCCrowdFunding\Includes\Common;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCCF_Common_Hooks' ) ) {
	/**
	 * Common hooks class.
	 */
	class DDWCCF_Common_Hooks extends DDWCCF_Common_Functions {
		/**
		 * Construct.
		 *
		 * @param array $ddwccf_configuration Configuration.
		 */
		public function __construct( $ddwccf_configuration ) {
			parent::__construct( $ddwccf_configuration );

			add_action( 'init', [ $this, 'ddwccf_add_endpoints' ] );
			add_action( 'init', [ $this, 'ddwccf_schedule_events' ] );

			add_filter( 'woocommerce_email_classes', [ $this, 'ddwccf_add_new_email_notification' ] );
			add_filter( 'woocommerce_email_actions', [ $this, 'ddwccf_add_notification_actions' ] );

			// Contribution details travel from the cart to the order line.
			add_action( 'woocommerce_checkout_create_order_line_item', [ $this, 'ddwccf_add_order_item_meta' ], 10, 3 );
			add_filter( 'woocommerce_order_item_display_meta_key', [ $this, 'ddwccf_order_item_meta_label' ], 10, 2 );
			add_filter( 'woocommerce_hidden_order_itemmeta', [ $this, 'ddwccf_hidden_order_item_meta' ] );
			add_filter( 'woocommerce_order_item_get_formatted_meta_data', [ $this, 'ddwccf_customer_order_item_meta' ], 10, 2 );

			// Tag campaign orders on classic and block checkout.
			add_action( 'woocommerce_checkout_order_processed', [ $this, 'ddwccf_tag_event_order' ] );
			add_action( 'woocommerce_store_api_checkout_order_processed', [ $this, 'ddwccf_tag_event_order' ] );

			// Count paid contributions, reverse them when the money goes back.
			add_action( 'woocommerce_order_status_processing', [ $this, 'ddwccf_handle_order_paid' ] );
			add_action( 'woocommerce_order_status_completed', [ $this, 'ddwccf_handle_order_paid' ] );
			add_action( 'woocommerce_order_status_cancelled', [ $this, 'ddwccf_handle_order_reversed' ] );
			add_action( 'woocommerce_order_status_refunded', [ $this, 'ddwccf_handle_order_reversed' ] );
			add_action( 'woocommerce_order_status_failed', [ $this, 'ddwccf_handle_order_reversed' ] );

			add_action( 'ddwccf_contribution_counted', [ $this, 'ddwccf_notify_contribution' ], 10, 3 );
			add_action( 'ddwccf_event_goal_reached', [ $this, 'ddwccf_notify_goal_reached' ] );
			add_action( 'ddwccf_daily_check', [ $this, 'ddwccf_check_ended_campaigns' ] );

			add_filter( 'woocommerce_is_purchasable', [ $this, 'ddwccf_is_purchasable' ], 10, 2 );
		}
	}
}
