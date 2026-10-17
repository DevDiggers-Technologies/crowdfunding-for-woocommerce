<?php
/**
 * Admin campaigns list.
 *
 * @package DevDiggers Crowdfunding for WooCommerce
 * @version 1.0.0
 */

namespace DDWCCrowdFunding\Templates\Admin\Events;

use DDWCCrowdFunding\Helper\DDWCCF_Event_Helper;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

if ( ! class_exists( 'DDWCCF_Events_List_Template' ) ) {
	/**
	 * Campaigns list class.
	 */
	class DDWCCF_Events_List_Template extends \WP_List_Table {
		/**
		 * Configuration.
		 *
		 * @var array
		 */
		protected $config;

		/**
		 * Construct.
		 *
		 * @param array $config Configuration.
		 */
		public function __construct( $config = [] ) {
			$this->config = $config;

			parent::__construct(
				[
					'singular' => 'ddwccf-event',
					'plural'   => 'ddwccf-events',
					'ajax'     => false,
				]
			);
		}

		/**
		 * Base URL of the list.
		 *
		 * @return string
		 */
		protected function base_url() {
			return admin_url( 'admin.php?page=' . DDWCCF_DASHBOARD_PAGE . '&menu=events' );
		}

		/**
		 * Current view.
		 *
		 * @return string
		 */
		protected function get_current_view() {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only list filter.
			$view = isset( $_GET['event_status'] ) ? sanitize_key( wp_unslash( $_GET['event_status'] ) ) : 'all';
			return array_key_exists( $view, $this->get_view_labels() ) ? $view : 'all';
		}

		/**
		 * View labels.
		 *
		 * @return array
		 */
		protected function get_view_labels() {
			return [
				'all'     => __( 'All', 'devdiggers-crowdfunding-for-woocommerce' ),
				'publish' => __( 'Live', 'devdiggers-crowdfunding-for-woocommerce' ),
				'draft'   => __( 'Disabled', 'devdiggers-crowdfunding-for-woocommerce' ),
			];
		}

		/**
		 * Status views with counts.
		 *
		 * @return array
		 */
		protected function get_views() {
			$counts        = array_count_values( array_map( 'get_post_status', DDWCCF_Event_Helper::get_event_ids() ) );
			$counts['all'] = array_sum( $counts );
			$views         = [];

			foreach ( $this->get_view_labels() as $key => $label ) {
				$count = $counts[ $key ] ?? 0;

				$views[ $key ] = sprintf(
					'<a href="%1$s"%2$s>%3$s <span class="count">(%4$s)</span></a>',
					esc_url( 'all' === $key ? $this->base_url() : add_query_arg( 'event_status', $key, $this->base_url() ) ),
					$this->get_current_view() === $key ? ' class="current" aria-current="page"' : '',
					esc_html( $label ),
					esc_html( number_format_i18n( $count ) )
				);
			}

			return $views;
		}

		/**
		 * Print status views as the framework segmented control, without WordPress' " |" separators.
		 *
		 * @return void
		 */
		public function views() {
			echo '<ul class="subsubsub">';

			foreach ( $this->get_views() as $key => $view ) {
				echo '<li class="' . esc_attr( $key ) . '">' . wp_kses_post( $view ) . '</li>';
			}

			echo '</ul>';
		}

		/**
		 * Prepare items.
		 *
		 * @return void
		 */
		public function prepare_items() {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only list parameters.
			$search  = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
			$orderby = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'date';
			$order   = isset( $_GET['order'] ) && 'asc' === $_GET['order'] ? 'ASC' : 'DESC';
			// phpcs:enable WordPress.Security.NonceVerification.Recommended

			$per_page = $this->get_items_per_page( 'events_per_page', 20 );
			$view     = $this->get_current_view();
			$args     = [
				'post_type'      => 'product',
				'post_status'    => in_array( $view, [ 'publish', 'draft' ], true ) ? $view : [ 'publish', 'draft', 'pending', 'private' ],
				's'              => $search,
				'posts_per_page' => $per_page,
				'paged'          => $this->get_pagenum(),
				'fields'         => 'ids',
				'order'          => $order,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Campaigns are identified by this meta key.
				'meta_key'       => DDWCCF_Event_Helper::META_KEY,
			];

			$sorts = [
				'title'    => [ 'orderby' => 'title' ],
				'raised'   => [
					'orderby'  => 'meta_value_num',
					'meta_key' => '_ddwccf_collected', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Sortable mirror.
				],
				'end_date' => [
					'orderby'  => 'meta_value',
					'meta_key' => '_ddwccf_end_date', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Sortable mirror.
				],
			];
			$args  = array_merge( $args, $sorts[ $orderby ] ?? [ 'orderby' => 'date' ] );

			$query = new \WP_Query( $args );
			$items = array_map( [ $this, 'build_row' ], $query->posts );

			$this->_column_headers = [ $this->get_columns(), [], $this->get_sortable_columns(), 'event_name' ];
			$this->items           = $items;

			$this->set_pagination_args(
				[
					'total_items' => $query->found_posts,
					'per_page'    => $per_page,
				]
			);
		}

		/**
		 * Build one row.
		 *
		 * @param int $event_id Event ID.
		 * @return array
		 */
		protected function build_row( $event_id ) {
			$data = DDWCCF_Event_Helper::get_event_data( $event_id );

			return [
				'id'      => $event_id,
				'product' => wc_get_product( $event_id ),
				'data'    => $data,
				'state'   => DDWCCF_Event_Helper::get_state( $event_id, $data ),
				'creator' => get_userdata( get_post_field( 'post_author', $event_id ) ),
			];
		}

		/**
		 * Columns.
		 *
		 * @return array
		 */
		public function get_columns() {
			return apply_filters(
				'ddwccf_events_list_columns',
				[
					'cb'         => '<input type="checkbox" />',
					'event_name' => esc_html__( 'Campaign', 'devdiggers-crowdfunding-for-woocommerce' ),
					'raised'     => esc_html__( 'Raised', 'devdiggers-crowdfunding-for-woocommerce' ),
					'supporters' => esc_html__( 'Supporters', 'devdiggers-crowdfunding-for-woocommerce' ),
					'end_date'   => esc_html__( 'End Date', 'devdiggers-crowdfunding-for-woocommerce' ),
					'status'     => esc_html__( 'Status', 'devdiggers-crowdfunding-for-woocommerce' ),
				]
			);
		}

		/**
		 * Sortable columns.
		 *
		 * @return array
		 */
		public function get_sortable_columns() {
			return apply_filters(
				'ddwccf_events_list_sortable_columns',
				[
					'event_name' => [ 'title', false ],
					'raised'     => [ 'raised', true ],
					'end_date'   => [ 'end_date', false ],
				]
			);
		}

		/**
		 * Empty state.
		 *
		 * @return void
		 */
		public function no_items() {
			ddfw_print_empty_state(
				[
					'title'       => __( 'No campaigns yet', 'devdiggers-crowdfunding-for-woocommerce' ),
					'description' => __( 'Create your first campaign to start collecting contributions.', 'devdiggers-crowdfunding-for-woocommerce' ),
					'button_url'  => admin_url( 'admin.php?page=' . DDWCCF_DASHBOARD_PAGE . '&menu=events&action=add' ),
					'button_text' => __( 'Add Campaign', 'devdiggers-crowdfunding-for-woocommerce' ),
				]
			);
		}

		/**
		 * Default column.
		 *
		 * @param array  $item        Row.
		 * @param string $column_name Column.
		 * @return string
		 */
		public function column_default( $item, $column_name ) {
			$data = $item['data'];

			switch ( $column_name ) {
				case 'raised':
					$progress = DDWCCF_Event_Helper::get_progress( $data );
					$html     = '<strong>' . wp_kses_post( wc_price( $data['collected_amount'] ) ) . '</strong>';
					if ( $data['target_amount'] ) {
						/* translators: 1: target amount, 2: percentage. */
						$html .= '<br /><small>' . sprintf( esc_html__( 'of %1$s (%2$s%%)', 'devdiggers-crowdfunding-for-woocommerce' ), wp_kses_post( wc_price( $data['target_amount'] ) ), esc_html( $progress ) ) . '</small>';
						$html .= '<div class="ddwccf-progress"><span style="width:' . esc_attr( min( 100, $progress ) ) . '%"></span></div>';
					}
					return $html;
				case 'supporters':
					return sprintf( '<a href="%1$s">%2$s</a>', esc_url( admin_url( 'admin.php?page=' . DDWCCF_DASHBOARD_PAGE . '&menu=orders&ddwccf-event=' . $item['id'] ) ), esc_html( number_format_i18n( count( $data['contributors'] ) ) ) );
				case 'end_date':
					if ( ! $data['end_date'] ) {
						return '<span aria-hidden="true">&mdash;</span><span class="screen-reader-text">' . esc_html__( 'No end date', 'devdiggers-crowdfunding-for-woocommerce' ) . '</span>';
					}
					$days = DDWCCF_Event_Helper::get_days_left( $data );
					/* translators: %d: days left. */
					return esc_html( date_i18n( get_option( 'date_format' ), strtotime( $data['end_date'] ) ) ) . ( $days && ! DDWCCF_Event_Helper::is_expired( $data ) ? '<br /><small>' . esc_html( sprintf( _n( '%d day left', '%d days left', $days, 'devdiggers-crowdfunding-for-woocommerce' ), $days ) ) . '</small>' : '' );
				case 'status':
					return DDWCCF_Event_Helper::get_badge( $item['state'] );
			}

			return apply_filters( 'ddwccf_events_list_column_' . $column_name, '', $item );
		}

		/**
		 * Checkbox column.
		 *
		 * @param array $item Row.
		 * @return string
		 */
		public function column_cb( $item ) {
			return sprintf( '<input type="checkbox" name="ddwccf-product-id[]" value="%d" />', absint( $item['id'] ) );
		}

		/**
		 * Campaign column: thumbnail, name, organiser, rewards and row actions.
		 *
		 * @param array $item Row.
		 * @return string
		 */
		public function column_event_name( $item ) {
			$base  = $this->base_url();
			$nonce = wp_create_nonce( 'ddwccf_nonce_action' );
			$edit  = add_query_arg(
				[
					'action' => 'edit',
					'id'     => $item['id'],
				],
				$base
			);
			$act   = fn( $action ) => esc_url(
				add_query_arg(
					[
						'action'            => $action,
						'ddwccf-product-id' => $item['id'],
						'ddwccf_nonce'      => $nonce,
					],
					$base
				)
			);

			$actions = [
				'edit' => sprintf( '<a href="%s">%s</a>', esc_url( $edit ), esc_html__( 'Edit', 'devdiggers-crowdfunding-for-woocommerce' ) ),
				'view' => sprintf( '<a href="%s" target="_blank">%s</a>', esc_url( get_permalink( $item['id'] ) ), esc_html__( 'View', 'devdiggers-crowdfunding-for-woocommerce' ) ),
			];

			$actions['delete'] = sprintf( '<a href="%s" class="submitdelete">%s</a>', $act( 'delete' ), esc_html__( 'Trash', 'devdiggers-crowdfunding-for-woocommerce' ) );

			$meta = [];
			if ( $item['creator'] ) {
				/* translators: %s: creator name. */
				$meta[] = sprintf( esc_html__( 'by %s', 'devdiggers-crowdfunding-for-woocommerce' ), '<a href="' . esc_url( get_edit_user_link( $item['creator']->ID ) ) . '">' . esc_html( $item['creator']->display_name ) . '</a>' );
			}
			/* translators: %d: rewards count. */
			$meta[] = $item['data']['rewards'] ? esc_html( sprintf( _n( '%d reward', '%d rewards', count( $item['data']['rewards'] ), 'devdiggers-crowdfunding-for-woocommerce' ), count( $item['data']['rewards'] ) ) ) : '';

			return sprintf(
				'<div class="ddwccf-item-cell">%1$s<div><strong><a class="row-title" href="%2$s">%3$s</a></strong><div class="ddwccf-item-cell__meta">%4$s</div>%5$s</div></div>',
				wp_kses_post( $item['product']->get_image( [ 48, 48 ] ) ),
				esc_url( $edit ),
				esc_html( $item['product']->get_name() ),
				implode( ' &middot; ', array_filter( $meta ) ),
				$this->row_actions( apply_filters( 'ddwccf_events_list_line_actions', $actions, $item ) )
			);
		}

		/**
		 * Bulk actions.
		 *
		 * @return array
		 */
		public function get_bulk_actions() {
			return [
				'enable'  => esc_html__( 'Enable', 'devdiggers-crowdfunding-for-woocommerce' ),
				'disable' => esc_html__( 'Disable', 'devdiggers-crowdfunding-for-woocommerce' ),
				'delete'  => esc_html__( 'Move to trash', 'devdiggers-crowdfunding-for-woocommerce' ),
			];
		}
	}
}
