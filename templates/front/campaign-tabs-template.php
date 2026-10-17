<?php
/**
 * Campaign tab bodies: rewards and the supporters wall.
 *
 * @package DevDiggers Crowdfunding for WooCommerce
 * @version 1.0.0
 */

namespace DDWCCrowdFunding\Templates\Front;

use DDWCCrowdFunding\Helper\DDWCCF_Event_Helper;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCCF_Campaign_Tabs_Template' ) ) {
	/**
	 * Campaign tabs template class.
	 */
	class DDWCCF_Campaign_Tabs_Template {
		/**
		 * Rewards list.
		 *
		 * @param int $event_id Event ID.
		 * @return void
		 */
		public static function rewards( $event_id ) {
			$data = DDWCCF_Event_Helper::get_event_data( $event_id );
			?>
			<div class="ddwccf-reward-list">
				<?php foreach ( $data['rewards'] as $reward ) : ?>
					<div class="ddwccf-reward-card<?php echo DDWCCF_Event_Helper::is_reward_available( $reward ) ? '' : ' is-sold-out'; ?>">
						<span class="ddwccf-reward-card__amount"><?php echo wp_kses_post( wc_price( $reward['amount'] ) ); ?></span>
						<h4><?php echo esc_html( $reward['title'] ); ?></h4>
						<?php if ( $reward['description'] ) : ?>
							<p><?php echo nl2br( esc_html( $reward['description'] ) ); ?></p>
						<?php endif; ?>
						<ul>
							<li>
								<?php
								/* translators: %d: number of backers. */
								echo esc_html( sprintf( _n( '%d backer', '%d backers', (int) $reward['claimed'], 'devdiggers-crowdfunding-for-woocommerce' ), (int) $reward['claimed'] ) );
								?>
							</li>
							<?php if ( $reward['limit'] ) : ?>
								<li>
									<?php
									echo esc_html(
										DDWCCF_Event_Helper::is_reward_available( $reward )
											/* translators: %d: rewards left. */
											? sprintf( __( 'Limited: %d left', 'devdiggers-crowdfunding-for-woocommerce' ), $reward['limit'] - $reward['claimed'] )
											: __( 'Sold out', 'devdiggers-crowdfunding-for-woocommerce' )
									);
									?>
								</li>
							<?php endif; ?>
							<?php if ( $reward['delivery'] ) : ?>
								<li>
									<?php
									/* translators: %s: estimated delivery. */
									printf( esc_html__( 'Estimated delivery: %s', 'devdiggers-crowdfunding-for-woocommerce' ), esc_html( $reward['delivery'] ) );
									?>
								</li>
							<?php endif; ?>
						</ul>
					</div>
				<?php endforeach; ?>
			</div>
			<?php
		}

		/**
		 * Supporters wall with messages.
		 *
		 * @param int  $event_id   Event ID.
		 * @param int  $limit      Number of supporters.
		 * @param bool $with_title Print a heading.
		 * @return void
		 */
		public static function supporters( $event_id, $limit = 10, $with_title = false ) {
			$data = DDWCCF_Event_Helper::get_event_data( $event_id );
			$rows = [];

			foreach ( array_reverse( $data ? $data['contributors'] : [] ) as $order_id ) {
				$order = wc_get_order( $order_id );

				if ( ! $order || ! DDWCCF_Event_Helper::is_order_counted( $order ) ) {
					continue;
				}

				$amount  = 0;
				$message = '';
				foreach ( DDWCCF_Event_Helper::get_event_items( $order ) as $item ) {
					if ( (int) $item->get_product_id() === (int) $event_id ) {
						// Net of any fee a supporter covered while the store ran Pro.
						$amount += (float) $item->get_total() - (float) $item->get_meta( '_ddwccf_fee_covered' );
						$message = $item->get_meta( '_ddwccf_message' );
					}
				}

				$rows[] = [
					'name'    => DDWCCF_Event_Helper::get_supporter_name( $order ),
					'amount'  => $amount,
					'message' => $message,
					'date'    => $order->get_date_paid() ? $order->get_date_paid() : $order->get_date_created(),
				];

				if ( count( $rows ) >= $limit ) {
					break;
				}
			}

			if ( empty( $rows ) ) {
				return;
			}
			?>
			<section class="ddwccf-supporters">
				<?php if ( $with_title ) : ?>
					<h2><?php esc_html_e( 'Recent supporters', 'devdiggers-crowdfunding-for-woocommerce' ); ?></h2>
				<?php endif; ?>
				<ul>
					<?php foreach ( $rows as $row ) : ?>
						<li>
							<span class="ddwccf-supporters__avatar" aria-hidden="true"><?php echo esc_html( mb_strtoupper( mb_substr( $row['name'], 0, 1 ) ) ); ?></span>
							<span class="ddwccf-supporters__body">
								<span class="ddwccf-supporters__line">
									<span class="ddwccf-supporters__name"><?php echo esc_html( $row['name'] ); ?></span>
									<span class="ddwccf-supporters__amount"><?php echo wp_kses_post( wc_price( $row['amount'] ) ); ?></span>
									<?php if ( $row['date'] ) : ?>
										<time datetime="<?php echo esc_attr( $row['date']->date( 'c' ) ); ?>">
											<?php
											/* translators: %s: human time difference. */
											printf( esc_html__( '%s ago', 'devdiggers-crowdfunding-for-woocommerce' ), esc_html( human_time_diff( $row['date']->getTimestamp() ) ) );
											?>
										</time>
									<?php endif; ?>
								</span>
								<?php if ( $row['message'] ) : ?>
									<span class="ddwccf-supporters__message"><?php echo esc_html( $row['message'] ); ?></span>
								<?php endif; ?>
							</span>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
			<?php
		}
	}
}
