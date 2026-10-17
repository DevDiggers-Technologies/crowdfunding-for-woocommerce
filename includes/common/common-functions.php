<?php
/**
 * Common callbacks: taxonomy, endpoints, emails and the contribution lifecycle.
 *
 * @author DevDiggers
 * @package DevDiggers Crowdfunding for WooCommerce
 * @version 1.0.0
 */

namespace DDWCCrowdFunding\Includes\Common;

use DDWCCrowdFunding\Includes\DDWCCF_Email_Notification_Handler;
use DDWCCrowdFunding\Helper\DDWCCF_Event_Helper;
use DDWCCrowdFunding\Helper\DDWCCF_Email_Helper;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCCF_Common_Functions' ) ) {
	/**
	 * Common functions class.
	 */
	class DDWCCF_Common_Functions {
		/**
		 * Configuration.
		 *
		 * @var array
		 */
		protected $ddwccf_configuration;

		/**
		 * Construct.
		 *
		 * @param array $ddwccf_configuration Configuration.
		 */
		public function __construct( $ddwccf_configuration ) {
			$this->ddwccf_configuration = $ddwccf_configuration;
		}

		/**
		 * Backed Campaigns endpoint in My Account.
		 *
		 * @return void
		 */
		public function ddwccf_add_endpoints() {
			add_rewrite_endpoint( $this->ddwccf_configuration['backed_endpoint'], EP_ROOT | EP_PAGES );

			if ( get_transient( 'ddwccf_flush_rewrite_rules' ) ) {
				delete_transient( 'ddwccf_flush_rewrite_rules' );
				flush_rewrite_rules();
			}
		}

		/**
		 * Daily check for campaigns that just ended.
		 *
		 * @return void
		 */
		public function ddwccf_schedule_events() {
			if ( ! wp_next_scheduled( 'ddwccf_daily_check' ) ) {
				wp_schedule_event( strtotime( 'tomorrow 00:15:00' ), 'daily', 'ddwccf_daily_check' );
			}
		}

		/**
		 * Register the email class.
		 *
		 * @param array $email_classes Email classes.
		 * @return array
		 */
		public function ddwccf_add_new_email_notification( $email_classes ) {
			$email_classes['WC_Email_Crowd_Funding_Notification'] = new DDWCCF_Email_Notification_Handler();
			return $email_classes;
		}

		/**
		 * Register the email action.
		 *
		 * @param array $actions Actions.
		 * @return array
		 */
		public function ddwccf_add_notification_actions( $actions ) {
			$actions[] = 'ddwccf_mail';
			return $actions;
		}

		/**
		 * Copy reward, anonymity and message onto the order line.
		 *
		 * @param \WC_Order_Item_Product $item          Order item.
		 * @param string                 $cart_item_key Cart key.
		 * @param array                  $values        Cart item.
		 * @return void
		 */
		public function ddwccf_add_order_item_meta( $item, $cart_item_key, $values ) {
			$data = DDWCCF_Event_Helper::get_event_data( $values['product_id'] ?? 0 );

			if ( ! $data ) {
				return;
			}

			if ( ! empty( $values['ddwccf_reward_id'] ) ) {
				$reward = DDWCCF_Event_Helper::get_reward( $data, $values['ddwccf_reward_id'] );
				if ( $reward ) {
					$item->add_meta_data( '_ddwccf_reward_id', $reward['id'], true );
					$item->add_meta_data( '_ddwccf_reward', $reward['title'], true );
				}
			}

			if ( ! empty( $values['ddwccf_anonymous'] ) ) {
				$item->add_meta_data( '_ddwccf_anonymous', 'yes', true );
			}
			if ( ! empty( $values['ddwccf_message'] ) ) {
				$item->add_meta_data( '_ddwccf_message', $values['ddwccf_message'], true );
			}
		}

		/**
		 * Readable labels for our order item meta (hidden keys show in admin only when labelled).
		 *
		 * @param string        $display_key Key.
		 * @param \WC_Meta_Data $meta        Meta.
		 * @return string
		 */
		public function ddwccf_order_item_meta_label( $display_key, $meta ) {
			$labels = [
				'_ddwccf_reward'    => __( 'Reward', 'devdiggers-crowdfunding-for-woocommerce' ),
				'_ddwccf_anonymous' => __( 'Anonymous', 'devdiggers-crowdfunding-for-woocommerce' ),
				'_ddwccf_message'   => __( 'Message', 'devdiggers-crowdfunding-for-woocommerce' ),
			];

			return isset( $labels[ $meta->key ] ) ? $labels[ $meta->key ] : $display_key;
		}

		/**
		 * Internal IDs never show in the order screen.
		 *
		 * @param array $keys Hidden keys.
		 * @return array
		 */
		public function ddwccf_hidden_order_item_meta( $keys ) {
			$keys[] = '_ddwccf_reward_id';
			return $keys;
		}

		/**
		 * Supporters see their reward and message in order details and emails,
		 * where WooCommerce hides underscore meta.
		 *
		 * @param array                  $formatted Formatted meta.
		 * @param \WC_Order_Item_Product $item      Item.
		 * @return array
		 */
		public function ddwccf_customer_order_item_meta( $formatted, $item ) {
			$shown = wp_list_pluck( $formatted, 'key' );

			foreach ( [ '_ddwccf_reward', '_ddwccf_message' ] as $key ) {
				$value = is_callable( [ $item, 'get_meta' ] ) ? $item->get_meta( $key ) : '';

				if ( $value && ! in_array( $key, $shown, true ) ) {
					$formatted[ $key ] = (object) [
						'key'           => $key,
						'value'         => $value,
						'display_key'   => '_ddwccf_reward' === $key ? __( 'Reward', 'devdiggers-crowdfunding-for-woocommerce' ) : __( 'Message', 'devdiggers-crowdfunding-for-woocommerce' ),
						'display_value' => wpautop( esc_html( $value ) ),
					];
				}
			}

			return $formatted;
		}

		/**
		 * Tag a new order that contains campaigns.
		 *
		 * @param int|\WC_Order $order Order or ID.
		 * @return void
		 */
		public function ddwccf_tag_event_order( $order ) {
			$order = $order instanceof \WC_Order ? $order : wc_get_order( $order );

			if ( $order ) {
				DDWCCF_Event_Helper::tag_event_order( $order );
			}
		}

		/**
		 * Count a paid contribution.
		 *
		 * @param int $order_id Order ID.
		 * @return void
		 */
		public function ddwccf_handle_order_paid( $order_id ) {
			$order = wc_get_order( $order_id );

			if ( $order && ! DDWCCF_Event_Helper::is_order_counted( $order ) ) {
				DDWCCF_Event_Helper::apply_order_contribution( $order, 1 );
			}
		}

		/**
		 * Reverse a counted contribution.
		 *
		 * @param int $order_id Order ID.
		 * @return void
		 */
		public function ddwccf_handle_order_reversed( $order_id ) {
			$order = wc_get_order( $order_id );

			if ( $order ) {
				DDWCCF_Event_Helper::apply_order_contribution( $order, -1 );
			}
		}

		/**
		 * Thank the supporter and tell the creator about a new contribution.
		 *
		 * @param int       $event_id Event ID.
		 * @param \WC_Order $order    Order.
		 * @return void
		 */
		public function ddwccf_notify_contribution( $event_id, $order ) {
			$extra = [
				'order'  => $order,
				'amount' => 0,
			];

			foreach ( DDWCCF_Event_Helper::get_event_items( $order ) as $item ) {
				if ( (int) $item->get_product_id() === (int) $event_id ) {
					$extra['amount'] += (float) $item->get_total();
					$extra['reward']  = $item->get_meta( '_ddwccf_reward' );
					$extra['message'] = $item->get_meta( '_ddwccf_message' );
				}
			}

			DDWCCF_Email_Helper::send( 'contribution_received', $event_id, $extra );

			$creator = get_userdata( get_post_field( 'post_author', $event_id ) );
			if ( $creator && strtolower( $creator->user_email ) !== strtolower( $order->get_billing_email() ) ) {
				DDWCCF_Email_Helper::send( 'new_contribution', $event_id, $extra );
			}
		}

		/**
		 * Goal reached email.
		 *
		 * @param int $event_id Event ID.
		 * @return void
		 */
		public function ddwccf_notify_goal_reached( $event_id ) {
			DDWCCF_Email_Helper::send( 'goal_reached', $event_id );
		}

		/**
		 * Email organisers once their campaign passes its end date.
		 *
		 * @return void
		 */
		public function ddwccf_check_ended_campaigns() {
			foreach ( DDWCCF_Event_Helper::get_event_ids( [ 'post_status' => 'publish' ] ) as $event_id ) {
				$data = DDWCCF_Event_Helper::get_event_data( $event_id );

				if ( ! DDWCCF_Event_Helper::is_expired( $data ) || $data['ended_notified'] ) {
					continue;
				}

				$data['ended_notified'] = current_time( 'mysql' );
				DDWCCF_Event_Helper::update_event_data( $event_id, $data );

				DDWCCF_Email_Helper::send( 'campaign_ended', $event_id );
			}
		}

		/**
		 * Closed campaigns cannot be bought.
		 *
		 * @param bool        $purchasable Purchasable.
		 * @param \WC_Product $product     Product.
		 * @return bool
		 */
		public function ddwccf_is_purchasable( $purchasable, $product ) {
			$data = DDWCCF_Event_Helper::get_event_data( $product->get_id() );

			if ( ! $data ) {
				return $purchasable;
			}

			// Campaigns without a fixed price are zero priced, which WooCommerce treats as not purchasable.
			return ! DDWCCF_Event_Helper::is_closed( $product->get_id(), $data ) && ( $purchasable || 'dynamic' === $data['contribution_type'] || $data['rewards'] );
		}
	}
}
