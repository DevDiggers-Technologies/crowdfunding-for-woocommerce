<?php
/**
 * [ddwccf_campaigns] grid with sorting and pagination.
 *
 * @package DevDiggers Crowdfunding for WooCommerce
 * @version 1.0.0
 */

namespace DDWCCrowdFunding\Templates\Front;

use DDWCCrowdFunding\Helper\DDWCCF_Event_Helper;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCCF_Campaigns_Grid_Template' ) ) {
	/**
	 * Campaigns grid template class.
	 */
	class DDWCCF_Campaigns_Grid_Template {
		/**
		 * Render.
		 *
		 * @param array $atts   Shortcode attributes.
		 * @param array $config Configuration.
		 */
		public function __construct( $atts, $config ) {
			$atts = shortcode_atts(
				[
					'limit'    => 12,
					'columns'  => 3,
					'status'   => 'active',
					'orderby'  => 'date',
					'ids'      => '',
					'paginate' => 'yes',
				],
				$atts,
				'ddwccf_campaigns'
			);

			$status  = $atts['status'];
			$orderby = $atts['orderby'];
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public read-only pagination.
			$page = isset( $_GET['cf_page'] ) ? max( 1, absint( $_GET['cf_page'] ) ) : 1;

			$args = [
				'post_status' => 'publish',
				'orderby'     => 'date',
				'order'       => 'DESC',
			];

			if ( $atts['ids'] ) {
				$args['post__in'] = wp_parse_id_list( $atts['ids'] );
			}

			$states = 'all' === $status ? [] : array_map( 'trim', explode( ',', $status ) );
			$items  = [];

			// ponytail: filters and sorts in PHP over published campaigns; move to SQL if a store runs thousands.
			foreach ( DDWCCF_Event_Helper::get_event_ids( $args ) as $event_id ) {
				$data = DDWCCF_Event_Helper::get_event_data( $event_id );

				if ( ! $states || in_array( DDWCCF_Event_Helper::get_state( $event_id, $data ), $states, true ) ) {
					$items[ $event_id ] = $data;
				}
			}

			if ( 'raised' === $orderby ) {
				uasort( $items, fn( $a, $b ) => $b['collected_amount'] <=> $a['collected_amount'] );
			} elseif ( 'funded' === $orderby ) {
				uasort( $items, fn( $a, $b ) => DDWCCF_Event_Helper::get_progress( $b ) <=> DDWCCF_Event_Helper::get_progress( $a ) );
			} elseif ( 'ending' === $orderby ) {
				uasort( $items, fn( $a, $b ) => ( $a['end_date'] ? $a['end_date'] : '9999' ) <=> ( $b['end_date'] ? $b['end_date'] : '9999' ) );
			} elseif ( 'popular' === $orderby ) {
				uasort( $items, fn( $a, $b ) => count( $b['contributors'] ) <=> count( $a['contributors'] ) );
			}

			$limit = max( 1, absint( $atts['limit'] ) );
			$total = count( $items );
			$pages = 'yes' === $atts['paginate'] ? (int) ceil( $total / $limit ) : 1;
			$items = array_slice( $items, ( min( $page, max( 1, $pages ) ) - 1 ) * $limit, $limit, true );
			?>
			<div class="ddwccf-campaigns">
				<?php if ( ! $items ) : ?>
					<div class="ddwccf-empty-state">
						<p><strong><?php esc_html_e( 'No campaigns found', 'devdiggers-crowdfunding-for-woocommerce' ); ?></strong></p>
						<p><?php esc_html_e( 'Check back soon for new campaigns.', 'devdiggers-crowdfunding-for-woocommerce' ); ?></p>
					</div>
				<?php else : ?>
					<div class="ddwccf-campaigns-grid" style="--ddwccf-columns: <?php echo esc_attr( min( 6, max( 1, absint( $atts['columns'] ) ) ) ); ?>">
						<?php
						foreach ( array_keys( $items ) as $event_id ) {
							DDWCCF_Campaign_Card_Template::render( $event_id, $config, 'grid' );
						}
						?>
					</div>
				<?php endif; ?>

				<?php if ( $pages > 1 ) : ?>
					<nav class="ddwccf-pagination" aria-label="<?php esc_attr_e( 'Campaigns pages', 'devdiggers-crowdfunding-for-woocommerce' ); ?>">
						<?php for ( $i = 1; $i <= $pages; $i++ ) : ?>
							<a href="<?php echo esc_url( add_query_arg( 'cf_page', $i ) ); ?>" class="<?php echo $i === $page ? 'is-current' : ''; ?>" <?php echo $i === $page ? 'aria-current="page"' : ''; ?>><?php echo esc_html( number_format_i18n( $i ) ); ?></a>
						<?php endfor; ?>
					</nav>
				<?php endif; ?>
			</div>
			<?php
		}
	}
}
