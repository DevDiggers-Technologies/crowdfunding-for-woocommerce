<?php
/**
 * Dashboard data: period KPIs from paid contribution orders, snapshot KPIs from campaigns.
 *
 * @package DevDiggers Crowdfunding for WooCommerce
 * @version 1.0.0
 */

namespace DDWCCrowdFunding\Helper\Dashboard;

use DDWCCrowdFunding\Helper\DDWCCF_Event_Helper;
use DevDiggers\Framework\Includes\DDFW_Dashboard_Data;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCCF_Dashboard_Helper' ) ) {
	/**
	 * Dashboard helper class.
	 */
	class DDWCCF_Dashboard_Helper {
		/**
		 * Build all dashboard data.
		 *
		 * @return array
		 */
		public function get_dashboard_data() {
			$range    = DDFW_Dashboard_Data::get_date_range();
			$previous = $this->get_previous_range( $range );
			$current  = $this->get_order_stats( $range['from'], $range['to'] );
			$before   = $this->get_order_stats( $previous['from'], $previous['to'] );

			return [
				'date_range' => $range,
				'summary'    => [
					'raised'        => $this->make_summary( $current['raised'], $before['raised'] ),
					'contributions' => $this->make_summary( $current['count'], $before['count'] ),
					'supporters'    => $this->make_summary( $current['supporters'], $before['supporters'] ),
					'average'       => $this->make_summary( $current['count'] ? $current['raised'] / $current['count'] : 0, $before['count'] ? $before['raised'] / $before['count'] : 0 ),
				],
				'trend'      => DDFW_Dashboard_Data::build_time_series(
					$range['from'],
					$range['to'],
					[
						'raised'        => $current['raised_by_date'],
						'contributions' => $current['count_by_date'],
					]
				),
				'events'     => $this->get_event_stats(),
				'recent'     => $current['recent'],
			];
		}

		/**
		 * Summary card values.
		 *
		 * @param float $value    Current value.
		 * @param float $previous Previous value.
		 * @return array
		 */
		protected function make_summary( $value, $previous ) {
			$value    = (float) $value;
			$previous = (float) $previous;
			$change   = $previous > 0 ? ( $value - $previous ) / $previous * 100 : ( $value > 0 ? 100 : 0 );

			return [
				'value'       => $value,
				'change'      => round( $change, 1 ),
				'is_positive' => $value >= $previous,
			];
		}

		/**
		 * The equal-length range right before the given one.
		 *
		 * @param array $range Range.
		 * @return array
		 */
		protected function get_previous_range( $range ) {
			$from = strtotime( $range['from'] );
			$days = max( 1, (int) round( ( strtotime( $range['to'] ) - $from ) / DAY_IN_SECONDS ) + 1 );

			return [
				'from' => gmdate( 'Y-m-d', strtotime( '-' . $days . ' days', $from ) ),
				'to'   => gmdate( 'Y-m-d', strtotime( '-1 day', $from ) ),
			];
		}

		/**
		 * Paid contribution stats for a period.
		 *
		 * @param string $from From date.
		 * @param string $to   To date.
		 * @return array
		 */
		protected function get_order_stats( $from, $to ) {
			// ponytail: loads every paid contribution order in the range; aggregate in SQL if a store has tens of thousands.
			$orders = wc_get_orders(
				[
					'limit'        => -1,
					'type'         => 'shop_order',
					'status'       => [ 'wc-processing', 'wc-completed' ],
					'date_created' => $from . '...' . $to,
					'orderby'      => 'date',
					'order'        => 'DESC',
					'meta_query'   => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Contribution orders are tagged with this meta.
						[
							'key'   => '_ddwccf_event_order',
							'value' => 'yes',
						],
					],
				]
			);

			$stats  = [
				'raised'         => 0.0,
				'count'          => 0,
				'supporters'     => 0,
				'raised_by_date' => [],
				'count_by_date'  => [],
				'recent'         => [],
			];
			$people = [];

			foreach ( $orders as $order ) {
				$amount = 0.0;
				$names  = [];

				foreach ( DDWCCF_Event_Helper::get_event_items( $order ) as $item ) {
					$amount += (float) $item->get_total();
					$names[] = $item->get_name();
				}

				if ( ! $names ) {
					continue;
				}

				$date = $order->get_date_created() ? $order->get_date_created()->getTimestamp() + (int) $order->get_date_created()->getOffset() : time();

				$stats['raised']                 += $amount;
				$stats['count']                  += 1;
				$stats['raised_by_date'][ $date ] = ( $stats['raised_by_date'][ $date ] ?? 0 ) + $amount;
				$stats['count_by_date'][ $date ]  = ( $stats['count_by_date'][ $date ] ?? 0 ) + 1;

				$people[ $order->get_billing_email() ? strtolower( $order->get_billing_email() ) : 'order-' . $order->get_id() ] = true;

				if ( count( $stats['recent'] ) < 6 ) {
					$name              = trim( $order->get_formatted_billing_full_name() );
					$stats['recent'][] = [
						'number'   => $order->get_order_number(),
						'customer' => $name ? $name : __( 'Guest', 'devdiggers-crowdfunding-for-woocommerce' ),
						'events'   => implode( ', ', $names ),
						'amount'   => $amount,
						'date'     => $order->get_date_created() ? $order->get_date_created()->date_i18n( get_option( 'date_format' ) ) : '',
						'url'      => $order->get_edit_order_url(),
					];
				}
			}

			$stats['supporters'] = count( $people );

			return $stats;
		}

		/**
		 * Campaign snapshot: states, top campaigns and ending soon.
		 *
		 * @return array
		 */
		protected function get_event_stats() {
			$base  = admin_url( 'admin.php?page=' . DDWCCF_DASHBOARD_PAGE . '&menu=events' );
			$stats = [
				'states'  => array_fill_keys( [ 'active', 'funded', 'ended', 'pending', 'disabled' ], 0 ),
				'top'     => [],
				'ending'  => [],
				'success' => null,
			];

			$finished  = 0;
			$succeeded = 0;

			foreach ( DDWCCF_Event_Helper::get_event_ids() as $event_id ) {
				$data  = DDWCCF_Event_Helper::get_event_data( $event_id );
				$state = DDWCCF_Event_Helper::get_state( $event_id, $data );
				$row   = [
					'id'        => $event_id,
					'name'      => get_the_title( $event_id ),
					'collected' => $data['collected_amount'],
					'target'    => $data['target_amount'],
					'progress'  => DDWCCF_Event_Helper::get_progress( $data ),
					'days_left' => DDWCCF_Event_Helper::get_days_left( $data ),
					'url'       => add_query_arg(
						[
							'action' => 'edit',
							'id'     => $event_id,
						],
						$base
					),
				];

				++$stats['states'][ $state ];
				$stats['top'][] = $row;

				if ( $data['target_amount'] && ( DDWCCF_Event_Helper::is_expired( $data ) || 'funded' === $state ) ) {
					++$finished;
					$succeeded += 'funded' === $state ? 1 : 0;
				}

				if ( 'active' === $state && null !== $row['days_left'] && $row['days_left'] <= 14 ) {
					$stats['ending'][] = $row;
				}
			}

			usort( $stats['top'], fn( $a, $b ) => $b['collected'] <=> $a['collected'] );
			usort( $stats['ending'], fn( $a, $b ) => $a['days_left'] <=> $b['days_left'] );

			$stats['top']     = array_slice( array_filter( $stats['top'], fn( $row ) => $row['collected'] > 0 ), 0, 5 );
			$stats['ending']  = array_slice( $stats['ending'], 0, 5 );
			$stats['success'] = $finished ? round( $succeeded * 100 / $finished ) : null;

			return $stats;
		}
	}
}
