<?php
/**
 * My Account: campaigns the customer has backed.
 *
 * @package DevDiggers Crowdfunding for WooCommerce
 * @version 1.0.0
 */

namespace DDWCCrowdFunding\Templates\Front\MyAccount;

use DDWCCrowdFunding\Helper\DDWCCF_Event_Helper;
use DDWCCrowdFunding\Templates\Front\DDWCCF_Campaign_Card_Template;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCCF_Backed_List_Template' ) ) {
	/**
	 * Backed campaigns template class.
	 */
	class DDWCCF_Backed_List_Template {
		/**
		 * Construct.
		 *
		 * @param array $ddwccf_configuration Configuration.
		 * @param int   $current_page         Page number.
		 */
		public function __construct( $ddwccf_configuration, $current_page = 1 ) {
			$results = wc_get_orders(
				[
					'customer_id' => get_current_user_id(),
					'limit'       => 10,
					'paged'       => $current_page,
					'paginate'    => true,
					'type'        => 'shop_order',
					'meta_query'  => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Contribution orders are tagged with this meta.
						[
							'key'   => '_ddwccf_event_order',
							'value' => 'yes',
						],
					],
				]
			);
			?>
			<div class="ddwccf-account">
				<div class="ddwccf-account-header">
					<div>
						<h3><?php echo esc_html( $ddwccf_configuration['backed_endpoint_title'] ); ?></h3>
						<p><?php esc_html_e( 'Campaigns you supported, your rewards and how each campaign is doing.', 'devdiggers-crowdfunding-for-woocommerce' ); ?></p>
					</div>
				</div>

				<?php if ( empty( $results->orders ) ) : ?>
					<div class="ddwccf-empty-state">
						<p><strong><?php esc_html_e( 'You have not backed a campaign yet', 'devdiggers-crowdfunding-for-woocommerce' ); ?></strong></p>
						<p><?php esc_html_e( 'When you support a campaign, it shows up here so you can follow its progress.', 'devdiggers-crowdfunding-for-woocommerce' ); ?></p>
					</div>
				<?php endif; ?>

				<div class="ddwccf-campaign-rows">
					<?php foreach ( $results->orders as $order ) : ?>
						<?php
						foreach ( DDWCCF_Event_Helper::get_event_items( $order ) as $item ) :
							$event_id = $item->get_product_id();
							$data     = DDWCCF_Event_Helper::get_event_data( $event_id );
							$product  = wc_get_product( $event_id );
							$state    = DDWCCF_Event_Helper::get_state( $event_id, $data );
							?>
							<article class="ddwccf-campaign-row">
								<a class="ddwccf-campaign-row__thumb" href="<?php echo esc_url( get_permalink( $event_id ) ); ?>"><?php echo wp_kses_post( $product ? $product->get_image( [ 96, 96 ] ) : '' ); ?></a>
								<div class="ddwccf-campaign-row__main">
									<div class="ddwccf-campaign-row__title">
										<a href="<?php echo esc_url( get_permalink( $event_id ) ); ?>"><strong><?php echo esc_html( $item->get_name() ); ?></strong></a>
										<?php echo wp_kses_post( DDWCCF_Event_Helper::get_badge( $state ) ); ?>
									</div>
									<div class="ddwccf-campaign-row__meta">
										<span>
											<?php
											/* translators: %s: amount. */
											printf( esc_html__( 'You gave %s', 'devdiggers-crowdfunding-for-woocommerce' ), wp_kses_post( wc_price( $item->get_total(), [ 'currency' => $order->get_currency() ] ) ) );
											?>
										</span>
										<?php if ( $item->get_meta( '_ddwccf_reward' ) ) : ?>
											<span>
												<?php
												/* translators: %s: reward title. */
												printf( esc_html__( 'Reward: %s', 'devdiggers-crowdfunding-for-woocommerce' ), esc_html( $item->get_meta( '_ddwccf_reward' ) ) );
												?>
											</span>
										<?php endif; ?>
										<span><?php echo esc_html( $order->get_date_created() ? wc_format_datetime( $order->get_date_created() ) : '' ); ?></span>
										<span><a href="<?php echo esc_url( $order->get_view_order_url() ); ?>"><?php echo esc_html( '#' . $order->get_order_number() . ' ' . wc_get_order_status_name( $order->get_status() ) ); ?></a></span>
									</div>
									<?php
									if ( $data ) {
										DDWCCF_Campaign_Card_Template::render_progress( $data );
									}
									?>
								</div>
							</article>
						<?php endforeach; ?>
					<?php endforeach; ?>
				</div>

				<?php if ( $results->max_num_pages > 1 ) : ?>
					<div class="ddwccf-account-pagination">
						<?php if ( $current_page > 1 ) : ?>
							<a class="ddwccf-btn" href="<?php echo esc_url( wc_get_endpoint_url( $ddwccf_configuration['backed_endpoint'], $current_page - 1 ) ); ?>"><?php esc_html_e( 'Previous', 'devdiggers-crowdfunding-for-woocommerce' ); ?></a>
						<?php endif; ?>
						<?php if ( $current_page < $results->max_num_pages ) : ?>
							<a class="ddwccf-btn" href="<?php echo esc_url( wc_get_endpoint_url( $ddwccf_configuration['backed_endpoint'], $current_page + 1 ) ); ?>"><?php esc_html_e( 'Next', 'devdiggers-crowdfunding-for-woocommerce' ); ?></a>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
			<?php
		}
	}
}
