<?php
/**
 * Event helper: single source of truth for campaign data, state and contribution maths.
 *
 * @package DevDiggers Crowdfunding for WooCommerce
 * @version 1.0.0
 */

namespace DDWCCrowdFunding\Helper;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCCF_Event_Helper' ) ) {
	/**
	 * Event helper class.
	 */
	class DDWCCF_Event_Helper {
		/**
		 * Meta key that holds the campaign data array.
		 */
		const META_KEY = '_ddwccf_event_data';

		/**
		 * Default campaign data.
		 *
		 * @return array
		 */
		public static function get_defaults() {
			return [
				'contribution_type' => 'dynamic',
				'target_amount'     => '',
				'end_date'          => '',
				'collected_amount'  => 0,
				'contributors'      => [],
				'rewards'           => [],
				'ended_notified'    => '',
			];
		}

		/**
		 * Get normalized campaign data, or false when the product is not a campaign.
		 *
		 * @param int $event_id Event (product) ID.
		 * @return array|false
		 */
		public static function get_event_data( $event_id ) {
			$data = $event_id ? get_post_meta( $event_id, self::META_KEY, true ) : '';

			if ( empty( $data ) || ! is_array( $data ) ) {
				return false;
			}

			// 1.x campaigns were fixed by default and had no funding mode.
			if ( empty( $data['contribution_type'] ) ) {
				$data['contribution_type'] = 'fixed';
			}

			$data                     = wp_parse_args( $data, self::get_defaults() );
			$data['collected_amount'] = max( 0, (float) $data['collected_amount'] );
			$data['contributors']     = array_values( array_map( 'absint', (array) $data['contributors'] ) );
			$data['rewards']          = array_values( (array) $data['rewards'] );

			return $data;
		}

		/**
		 * Whether a product is a campaign.
		 *
		 * @param int $event_id Event ID.
		 * @return bool
		 */
		public static function is_event( $event_id ) {
			return false !== self::get_event_data( $event_id );
		}

		/**
		 * Save campaign data and mirror sortable values into their own meta.
		 *
		 * @param int   $event_id Event ID.
		 * @param array $data     Data.
		 * @return void
		 */
		public static function update_event_data( $event_id, $data ) {
			update_post_meta( $event_id, self::META_KEY, $data );
			update_post_meta( $event_id, '_ddwccf_collected', wc_format_decimal( $data['collected_amount'] ) );
			update_post_meta( $event_id, '_ddwccf_end_date', $data['end_date'] ? $data['end_date'] : '9999-12-31' );
		}

		/**
		 * Goal progress percentage, uncapped (can pass 100 when overfunded).
		 *
		 * @param array $data Campaign data.
		 * @return int
		 */
		public static function get_progress( $data ) {
			return ! empty( $data['target_amount'] ) && (float) $data['target_amount'] > 0 ? (int) floor( $data['collected_amount'] * 100 / (float) $data['target_amount'] ) : 0;
		}

		/**
		 * Whether the target has been reached.
		 *
		 * @param array $data Campaign data.
		 * @return bool
		 */
		public static function is_goal_reached( $data ) {
			return ! empty( $data['target_amount'] ) && $data['collected_amount'] >= (float) $data['target_amount'];
		}

		/**
		 * Whether the end date has passed.
		 *
		 * @param array $data Campaign data.
		 * @return bool
		 */
		public static function is_expired( $data ) {
			return ! empty( $data['end_date'] ) && current_time( 'Y-m-d' ) > $data['end_date'];
		}

		/**
		 * Days left until the end date, or null when there is no end date.
		 *
		 * @param array $data Campaign data.
		 * @return int|null
		 */
		public static function get_days_left( $data ) {
			if ( empty( $data['end_date'] ) ) {
				return null;
			}

			return max( 0, (int) floor( ( strtotime( $data['end_date'] . ' 23:59:59' ) - strtotime( current_time( 'mysql' ) ) ) / DAY_IN_SECONDS ) );
		}

		/**
		 * Whether a campaign stopped accepting contributions.
		 *
		 * @param int        $event_id Event ID.
		 * @param array|null $data     Campaign data.
		 * @return bool
		 */
		public static function is_closed( $event_id, $data = null ) {
			$data = null === $data ? self::get_event_data( $event_id ) : $data;

			if ( ! $data ) {
				return true;
			}

			$closes_on_goal = 'yes' === get_option( '_ddwccf_close_on_goal', 'yes' );
			$closed         = 'publish' !== get_post_status( $event_id ) || self::is_expired( $data ) || ( $closes_on_goal && self::is_goal_reached( $data ) );

			return (bool) apply_filters( 'ddwccf_is_event_closed', $closed, $event_id, $data );
		}

		/**
		 * Campaign state key used for badges and filtering.
		 *
		 * @param int   $event_id Event ID.
		 * @param array $data     Campaign data.
		 * @return string active|funded|ended|pending|disabled
		 */
		public static function get_state( $event_id, $data ) {
			$status = get_post_status( $event_id );

			if ( 'pending' === $status ) {
				return 'pending';
			}
			if ( self::is_goal_reached( $data ) ) {
				return 'funded';
			}
			if ( self::is_expired( $data ) ) {
				return 'ended';
			}

			return 'publish' === $status ? 'active' : 'disabled';
		}

		/**
		 * Badge colour class for a state.
		 *
		 * @param string $state State.
		 * @return string
		 */
		public static function get_badge_class( $state ) {
			$map = [
				'active'   => 'active',
				'funded'   => 'info',
				'pending'  => 'warning',
				'ended'    => 'neutral',
				'disabled' => 'inactive',
			];

			return 'ddwccf-status ddwccf-status-' . ( isset( $map[ $state ] ) ? $map[ $state ] : 'neutral' );
		}

		/**
		 * Badge markup.
		 *
		 * @param string $state State key.
		 * @param string $label Optional label override.
		 * @return string
		 */
		public static function get_badge( $state, $label = '' ) {
			return '<mark class="' . esc_attr( self::get_badge_class( $state ) ) . '">' . esc_html( $label ? $label : self::get_label( $state ) ) . '</mark>';
		}

		/**
		 * Translated labels for campaign states.
		 *
		 * @param string $word Key.
		 * @return string
		 */
		public static function get_label( $word ) {
			$labels = [
				'pending'  => __( 'Pending', 'devdiggers-crowdfunding-for-woocommerce' ),
				'active'   => __( 'Active', 'devdiggers-crowdfunding-for-woocommerce' ),
				'funded'   => __( 'Funded', 'devdiggers-crowdfunding-for-woocommerce' ),
				'ended'    => __( 'Ended', 'devdiggers-crowdfunding-for-woocommerce' ),
				'disabled' => __( 'Disabled', 'devdiggers-crowdfunding-for-woocommerce' ),
			];

			return isset( $labels[ $word ] ) ? $labels[ $word ] : $word;
		}

		/**
		 * Parse comma separated amounts into a sorted list of positive floats.
		 *
		 * @param string $raw Comma separated amounts.
		 * @return float[]
		 */
		public static function parse_preset_amounts( $raw ) {
			$amounts = array_filter( array_map( 'floatval', explode( ',', (string) $raw ) ) );
			$amounts = array_unique( array_filter( $amounts, fn( $amount ) => $amount > 0 ) );
			sort( $amounts );

			return array_values( $amounts );
		}

		/**
		 * One reward by ID.
		 *
		 * @param array  $data      Campaign data.
		 * @param string $reward_id Reward ID.
		 * @return array|null
		 */
		public static function get_reward( $data, $reward_id ) {
			foreach ( $data['rewards'] as $reward ) {
				if ( strtolower( (string) $reward['id'] ) === strtolower( (string) $reward_id ) ) {
					return $reward;
				}
			}

			return null;
		}

		/**
		 * Whether a reward still has stock.
		 *
		 * @param array $reward Reward.
		 * @return bool
		 */
		public static function is_reward_available( $reward ) {
			return empty( $reward['limit'] ) || (int) $reward['claimed'] < (int) $reward['limit'];
		}

		/**
		 * Campaign IDs.
		 *
		 * @param array $args Extra WP_Query args.
		 * @return int[]
		 */
		public static function get_event_ids( $args = [] ) {
			$query = new \WP_Query(
				array_merge(
					[
						'post_type'      => 'product',
						'post_status'    => [ 'publish', 'draft', 'pending', 'private' ],
						'posts_per_page' => -1,
						'fields'         => 'ids',
						'no_found_rows'  => true,
						// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Campaigns are identified by this meta key.
						'meta_key'       => self::META_KEY,
					],
					$args
				)
			);

			return array_map( 'absint', $query->posts );
		}

		/**
		 * Whether an order's contribution is currently counted in its campaigns.
		 * Honours the 1.x meta so orders counted before the update are not counted twice.
		 *
		 * @param \WC_Order $order Order.
		 * @return bool
		 */
		public static function is_order_counted( $order ) {
			$counted = $order->get_meta( '_ddwccf_contribution_counted' );

			if ( '' !== $counted ) {
				return 'yes' === $counted;
			}

			return 'yes' === $order->get_meta( '_ddwccf_order_completion_handled' ) && ! $order->get_meta( '_ddwccf_order_cancelled_handled' ) && ! $order->get_meta( '_ddwccf_order_fully_refund_handled' );
		}

		/**
		 * Campaign line items of an order, keyed by item ID.
		 *
		 * @param \WC_Order $order Order.
		 * @return \WC_Order_Item_Product[]
		 */
		public static function get_event_items( $order ) {
			return array_filter( $order->get_items(), fn( $item ) => self::is_event( $item->get_product_id() ) );
		}

		/**
		 * Supporter display name for an order, honouring the anonymous choice.
		 *
		 * @param \WC_Order $order Order.
		 * @return string
		 */
		public static function get_supporter_name( $order ) {
			foreach ( self::get_event_items( $order ) as $item ) {
				if ( 'yes' === $item->get_meta( '_ddwccf_anonymous' ) ) {
					return __( 'Anonymous', 'devdiggers-crowdfunding-for-woocommerce' );
				}
			}

			$first = $order->get_billing_first_name();
			$last  = $order->get_billing_last_name();

			return $first ? trim( $first . ' ' . ( $last ? mb_substr( $last, 0, 1 ) . '.' : '' ) ) : __( 'Anonymous', 'devdiggers-crowdfunding-for-woocommerce' );
		}

		/**
		 * Add or remove the order's contribution from its campaigns, once per direction.
		 *
		 * @param \WC_Order $order Order.
		 * @param int       $sign  1 to count, -1 to reverse.
		 * @return void
		 */
		public static function apply_order_contribution( $order, $sign ) {
			$counted = self::is_order_counted( $order );

			// Only count once, and only reverse what was counted.
			if ( ( 1 === $sign && $counted ) || ( -1 === $sign && ! $counted ) ) {
				return;
			}

			$touched = [];

			foreach ( self::get_event_items( $order ) as $item ) {
				$event_id    = $item->get_product_id();
				$data        = isset( $touched[ $event_id ] ) ? $touched[ $event_id ][0] : self::get_event_data( $event_id );
				$was_reached = isset( $touched[ $event_id ] ) ? $touched[ $event_id ][1] : self::is_goal_reached( $data );

				$data['collected_amount'] = max( 0, $data['collected_amount'] + $sign * (float) $item->get_total() );

				if ( 1 === $sign && ! in_array( $order->get_id(), $data['contributors'], true ) ) {
					$data['contributors'][] = $order->get_id();
				} elseif ( -1 === $sign ) {
					$data['contributors'] = array_values( array_diff( $data['contributors'], [ $order->get_id() ] ) );
				}

				$reward_id = $item->get_meta( '_ddwccf_reward_id' );
				foreach ( $data['rewards'] as $index => $reward ) {
					if ( $reward_id && strtolower( (string) $reward['id'] ) === strtolower( (string) $reward_id ) ) {
						$data['rewards'][ $index ]['claimed'] = max( 0, (int) $reward['claimed'] + $sign );
					}
				}

				$touched[ $event_id ] = [ $data, $was_reached ];
			}

			if ( empty( $touched ) ) {
				return;
			}

			foreach ( $touched as $event_id => $state ) {
				self::update_event_data( $event_id, $state[0] );
			}

			$order->update_meta_data( '_ddwccf_event_order', 'yes' );
			$order->update_meta_data( '_ddwccf_contribution_counted', 1 === $sign ? 'yes' : 'no' );
			foreach ( array_keys( $touched ) as $event_id ) {
				$order->update_meta_data( '_ddwccf_event_' . $event_id, '1' );
				$order->update_meta_data( '_ddwccf_event_author_' . (int) get_post_field( 'post_author', $event_id ), '1' );
			}
			$order->save();

			foreach ( $touched as $event_id => $state ) {
				list( $data, $was_reached ) = $state;

				if ( 1 === $sign ) {
					/**
					 * Fires when a paid contribution is counted.
					 *
					 * @param int       $event_id Event ID.
					 * @param \WC_Order $order    Order.
					 * @param array     $data     Campaign data.
					 */
					do_action( 'ddwccf_contribution_counted', $event_id, $order, $data );

					if ( ! $was_reached && self::is_goal_reached( $data ) ) {
						/**
						 * Fires once when a campaign reaches its target.
						 *
						 * @param int   $event_id Event ID.
						 * @param array $data     Campaign data.
						 */
						do_action( 'ddwccf_event_goal_reached', $event_id, $data );
					}
				}
			}
		}

		/**
		 * Mark orders that contain campaigns so they show up in lists before payment.
		 *
		 * @param \WC_Order $order Order.
		 * @return void
		 */
		public static function tag_event_order( $order ) {
			$items = self::get_event_items( $order );

			if ( empty( $items ) ) {
				return;
			}

			$order->update_meta_data( '_ddwccf_event_order', 'yes' );
			foreach ( $items as $item ) {
				$order->update_meta_data( '_ddwccf_event_' . $item->get_product_id(), '1' );
				$order->update_meta_data( '_ddwccf_event_author_' . (int) get_post_field( 'post_author', $item->get_product_id() ), '1' );
			}
			$order->save();
		}

		/**
		 * Brand mark used in the dashboard header and setup wizard.
		 *
		 * @param int $size Size in px.
		 * @return string
		 */
		public static function get_brand_icon( $size = 32 ) {
			return '<svg width="' . absint( $size ) . '" height="' . absint( $size ) . '" viewBox="0 0 30 30" fill="none" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg"><circle cx="15" cy="15" r="14" fill="var(--ddfw-tab-background-color)"/><rect x="10.5" y="7.5" width="9" height="2.6" rx="1" fill="var(--ddfw-primary-color)"/><path d="M11.2 10.1h7.6v1.2c1.9.9 3.2 2.8 3.2 5v3.6a3 3 0 0 1-3 3h-8a3 3 0 0 1-3-3v-3.6c0-2.2 1.3-4.1 3.2-5v-1.2Z" fill="var(--ddfw-tab-background-color)" stroke="var(--ddfw-primary-color)" stroke-width="1.5" stroke-linejoin="round"/><path d="M15 20.6l-2.7-2.6a1.75 1.75 0 0 1 2.5-2.5l.2.2.2-.2a1.75 1.75 0 0 1 2.5 2.5L15 20.6Z" fill="var(--ddfw-primary-color)"/><circle cx="22" cy="6.6" r="2.4" fill="#ffffff" stroke="var(--ddfw-primary-color)" stroke-width="1.3"/><path d="M22 5.6v2" stroke="var(--ddfw-primary-color)" stroke-width="1.1" stroke-linecap="round"/></svg>';
		}
	}
}
