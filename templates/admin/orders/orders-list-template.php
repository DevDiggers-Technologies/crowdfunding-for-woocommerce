<?php
/**
 * Admin contributions list (HPOS compatible).
 *
 * @package DevDiggers Crowdfunding for WooCommerce
 * @version 1.0.0
 */

namespace DDWCCrowdFunding\Templates\Admin\Orders;

use DDWCCrowdFunding\Helper\DDWCCF_Event_Helper;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

if ( ! class_exists( 'DDWCCF_Orders_List_Template' ) ) {
	/**
	 * Contributions list class.
	 */
	class DDWCCF_Orders_List_Template extends \WP_List_Table {
		/**
		 * Construct.
		 */
		public function __construct() {
			parent::__construct(
				[
					'singular' => 'ddwccf-order',
					'plural'   => 'ddwccf-orders',
					'ajax'     => false,
				]
			);
		}

		/**
		 * Prepare items.
		 *
		 * @return void
		 */
		public function prepare_items() {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only list filters.
			$search   = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
			$event_id = isset( $_GET['ddwccf-event'] ) ? absint( wp_unslash( $_GET['ddwccf-event'] ) ) : 0;
			$counted  = isset( $_GET['ddwccf-counted'] ) ? sanitize_key( wp_unslash( $_GET['ddwccf-counted'] ) ) : '';
			$order    = isset( $_GET['order'] ) && 'asc' === $_GET['order'] ? 'ASC' : 'DESC';
			// phpcs:enable WordPress.Security.NonceVerification.Recommended

			$per_page = $this->get_items_per_page( 'orders_per_page', 20 );
			$meta     = [
				[
					'key'   => '_ddwccf_event_order',
					'value' => 'yes',
				],
			];

			if ( in_array( $counted, [ 'yes', 'no' ], true ) ) {
				$meta[] = 'yes' === $counted ? [
					'key'   => '_ddwccf_contribution_counted',
					'value' => 'yes',
				] : [
					'relation' => 'OR',
					[
						'key'   => '_ddwccf_contribution_counted',
						'value' => 'no',
					],
					[
						'key'     => '_ddwccf_contribution_counted',
						'compare' => 'NOT EXISTS',
					],
				];
			}

			$args = [
				'limit'      => $per_page,
				'paged'      => $this->get_pagenum(),
				'paginate'   => true,
				'type'       => 'shop_order',
				'orderby'    => 'date',
				'order'      => $order,
				'meta_query' => $meta, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Contribution orders are tagged with this meta.
			];

			$ids = null;
			if ( $event_id ) {
				$args['meta_query'][] = [
					'key'   => '_ddwccf_event_' . $event_id,
					'value' => '1',
				];
			}
			if ( is_numeric( $search ) ) {
				$ids = null === $ids ? [ absint( $search ) ] : array_intersect( $ids, [ absint( $search ) ] );
			} elseif ( is_email( $search ) ) {
				$args['billing_email'] = $search;
			}
			if ( null !== $ids ) {
				$args['post__in'] = $ids ? array_values( $ids ) : [ 0 ];
			}

			$results = wc_get_orders( $args );

			$this->_column_headers = [ $this->get_columns(), [], $this->get_sortable_columns(), 'order' ];
			$this->items           = $results->orders;

			$this->set_pagination_args(
				[
					'total_items' => $results->total,
					'per_page'    => $per_page,
				]
			);
		}

		/**
		 * Filters above the table.
		 *
		 * @param string $which Position.
		 * @return void
		 */
		protected function extra_tablenav( $which ) {
			if ( 'top' !== $which ) {
				return;
			}

			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only list filters.
			$current = isset( $_GET['ddwccf-event'] ) ? absint( wp_unslash( $_GET['ddwccf-event'] ) ) : 0;
			$counted = isset( $_GET['ddwccf-counted'] ) ? sanitize_key( wp_unslash( $_GET['ddwccf-counted'] ) ) : '';
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
			?>
			<div class="alignleft actions">
				<select name="ddwccf-event" data-placeholder="<?php esc_attr_e( 'All campaigns', 'devdiggers-crowdfunding-for-woocommerce' ); ?>">
					<option value=""><?php esc_html_e( 'All campaigns', 'devdiggers-crowdfunding-for-woocommerce' ); ?></option>
					<?php foreach ( DDWCCF_Event_Helper::get_event_ids() as $id ) : ?>
						<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $current, $id ); ?>><?php echo esc_html( get_the_title( $id ) ); ?></option>
					<?php endforeach; ?>
				</select>
				<select name="ddwccf-counted" data-placeholder="<?php esc_attr_e( 'Paid and unpaid', 'devdiggers-crowdfunding-for-woocommerce' ); ?>">
					<option value=""><?php esc_html_e( 'Paid and unpaid', 'devdiggers-crowdfunding-for-woocommerce' ); ?></option>
					<option value="yes" <?php selected( $counted, 'yes' ); ?>><?php esc_html_e( 'Counted toward goal', 'devdiggers-crowdfunding-for-woocommerce' ); ?></option>
					<option value="no" <?php selected( $counted, 'no' ); ?>><?php esc_html_e( 'Not counted', 'devdiggers-crowdfunding-for-woocommerce' ); ?></option>
				</select>
				<?php submit_button( __( 'Filter', 'devdiggers-crowdfunding-for-woocommerce' ), '', 'filter_action', false ); ?>
				<?php if ( $current || $counted ) : ?>
					<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=' . DDWCCF_DASHBOARD_PAGE . '&menu=orders' ) ); ?>"><?php esc_html_e( 'Reset', 'devdiggers-crowdfunding-for-woocommerce' ); ?></a>
				<?php endif; ?>
			</div>
			<?php
		}

		/**
		 * Columns.
		 *
		 * @return array
		 */
		public function get_columns() {
			return apply_filters(
				'ddwccf_orders_list_columns',
				[
					'order'   => esc_html__( 'Supporter', 'devdiggers-crowdfunding-for-woocommerce' ),
					'event'   => esc_html__( 'Campaign', 'devdiggers-crowdfunding-for-woocommerce' ),
					'reward'  => esc_html__( 'Reward', 'devdiggers-crowdfunding-for-woocommerce' ),
					'total'   => esc_html__( 'Amount', 'devdiggers-crowdfunding-for-woocommerce' ),
					'status'  => esc_html__( 'Order Status', 'devdiggers-crowdfunding-for-woocommerce' ),
					'counted' => esc_html__( 'Counted', 'devdiggers-crowdfunding-for-woocommerce' ),
					'date'    => esc_html__( 'Date', 'devdiggers-crowdfunding-for-woocommerce' ),
				]
			);
		}

		/**
		 * Sortable columns.
		 *
		 * @return array
		 */
		public function get_sortable_columns() {
			return apply_filters( 'ddwccf_orders_list_sortable_columns', [ 'date' => [ 'date', true ] ] );
		}

		/**
		 * Empty state.
		 *
		 * @return void
		 */
		public function no_items() {
			ddfw_print_empty_state(
				[
					'title'       => __( 'No contributions yet', 'devdiggers-crowdfunding-for-woocommerce' ),
					'description' => __( 'Paid contributions show up here as soon as supporters back a campaign.', 'devdiggers-crowdfunding-for-woocommerce' ),
				]
			);
		}

		/**
		 * Render a column.
		 *
		 * @param \WC_Order $order       Order.
		 * @param string    $column_name Column.
		 * @return string
		 */
		public function column_default( $order, $column_name ) {
			$items = DDWCCF_Event_Helper::get_event_items( $order );

			switch ( $column_name ) {
				case 'order':
					$buyer   = trim( $order->get_formatted_billing_full_name() );
					$message = '';
					$anon    = false;
					foreach ( $items as $item ) {
						$message = $item->get_meta( '_ddwccf_message' ) ? $item->get_meta( '_ddwccf_message' ) : $message;
						$anon    = $anon || 'yes' === $item->get_meta( '_ddwccf_anonymous' );
					}
					return sprintf(
						'<div class="ddwccf-person-cell">%7$s<div><a class="row-title" href="%1$s">#%2$s %3$s</a>%4$s<div class="ddwccf-item-cell__meta">%5$s</div>%6$s</div></div>',
						esc_url( $order->get_edit_order_url() ),
						esc_html( $order->get_order_number() ),
						esc_html( $buyer ? $buyer : __( 'Guest', 'devdiggers-crowdfunding-for-woocommerce' ) ),
						$anon ? ' <mark class="ddwccf-status ddwccf-status-neutral">' . esc_html__( 'Anonymous', 'devdiggers-crowdfunding-for-woocommerce' ) . '</mark>' : '',
						esc_html( $order->get_billing_email() ),
						$message ? '<em class="ddwccf-quote">&ldquo;' . esc_html( $message ) . '&rdquo;</em>' : '',
						get_avatar( $order->get_billing_email(), 36 )
					);
				case 'date':
					$date = $order->get_date_created();
					return $date ? sprintf( '<time datetime="%1$s" title="%2$s">%3$s</time>', esc_attr( $date->date( 'c' ) ), esc_attr( $date->date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ), esc_html( $date->date_i18n( get_option( 'date_format' ) ) ) ) : '-';
				case 'event':
					$links = [];
					foreach ( $items as $item ) {
						$links[] = sprintf( '<a href="%1$s">%2$s</a>', esc_url( admin_url( 'admin.php?page=' . DDWCCF_DASHBOARD_PAGE . '&menu=events&action=edit&id=' . $item->get_product_id() ) ), esc_html( $item->get_name() ) );
					}
					return $links ? implode( ', ', $links ) : '-';
				case 'reward':
					$rewards = array_filter( array_map( fn( $item ) => $item->get_meta( '_ddwccf_reward' ), $items ) );
					return $rewards ? esc_html( implode( ', ', $rewards ) ) : '<span aria-hidden="true">&mdash;</span>';
				case 'status':
					return sprintf( '<mark class="order-status %1$s"><span>%2$s</span></mark>', esc_attr( sanitize_html_class( 'status-' . $order->get_status() ) ), esc_html( wc_get_order_status_name( $order->get_status() ) ) );
				case 'counted':
					return DDWCCF_Event_Helper::is_order_counted( $order ) ? DDWCCF_Event_Helper::get_badge( 'active', __( 'Counted', 'devdiggers-crowdfunding-for-woocommerce' ) ) : DDWCCF_Event_Helper::get_badge( 'ended', __( 'Not counted', 'devdiggers-crowdfunding-for-woocommerce' ) );
				case 'total':
					$amount = array_sum( array_map( fn( $item ) => (float) $item->get_total(), $items ) );
					return wp_kses_post( wc_price( $amount, [ 'currency' => $order->get_currency() ] ) );
			}

			return apply_filters( 'ddwccf_orders_list_column_' . $column_name, '', $order );
		}
	}
}
