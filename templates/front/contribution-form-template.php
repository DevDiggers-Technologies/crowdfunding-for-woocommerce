<?php
/**
 * Contribution fields inside the campaign add to cart form.
 *
 * @package DevDiggers Crowdfunding for WooCommerce
 * @version 1.0.0
 */

namespace DDWCCrowdFunding\Templates\Front;

use DDWCCrowdFunding\Helper\DDWCCF_Event_Helper;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCCF_Contribution_Form_Template' ) ) {
	/**
	 * Contribution form template class.
	 */
	class DDWCCF_Contribution_Form_Template {
		/**
		 * Render.
		 *
		 * @param \WC_Product $product Product.
		 * @param array       $config  Configuration.
		 * @return void
		 */
		public static function render( $product, $config ) {
			$data = DDWCCF_Event_Helper::get_event_data( $product->get_id() );

			if ( ! $data || DDWCCF_Event_Helper::is_closed( $product->get_id(), $data ) ) {
				return;
			}

			$dynamic = 'dynamic' === $data['contribution_type'];
			$minimum = (float) $product->get_regular_price();
			$maximum = (float) $config['max_contribution'];
			$presets = array_values(
				array_filter(
					DDWCCF_Event_Helper::parse_preset_amounts( $config['preset_amounts'] ),
					fn( $amount ) => $amount >= $minimum && ( ! $maximum || $amount <= $maximum )
				)
			);
			$value   = $dynamic ? ( $presets ? $presets[0] : max( $minimum, 1 ) ) : $minimum;
			?>
			<div class="ddwccf-contribution" data-dynamic="<?php echo esc_attr( $dynamic ? '1' : '0' ); ?>" data-minimum="<?php echo esc_attr( $minimum ); ?>" data-fixed="<?php echo esc_attr( $minimum ); ?>">
				<?php if ( $data['rewards'] ) : ?>
					<fieldset class="ddwccf-rewards">
						<legend><?php esc_html_e( 'Choose a reward', 'devdiggers-crowdfunding-for-woocommerce' ); ?></legend>
						<label class="ddwccf-reward">
							<input type="radio" name="ddwccf_reward_id" value="" data-amount="0" checked />
							<span class="ddwccf-reward__body">
								<span class="ddwccf-reward__head"><strong><?php esc_html_e( 'No reward, I just want to help', 'devdiggers-crowdfunding-for-woocommerce' ); ?></strong></span>
							</span>
						</label>
						<?php foreach ( $data['rewards'] as $reward ) : ?>
							<?php $available = DDWCCF_Event_Helper::is_reward_available( $reward ); ?>
							<label class="ddwccf-reward<?php echo $available ? '' : ' is-sold-out'; ?>">
								<input type="radio" name="ddwccf_reward_id" value="<?php echo esc_attr( $reward['id'] ); ?>" data-amount="<?php echo esc_attr( $reward['amount'] ); ?>" <?php disabled( ! $available ); ?> />
								<span class="ddwccf-reward__body">
									<span class="ddwccf-reward__head">
										<strong><?php echo esc_html( $reward['title'] ); ?></strong>
										<span class="ddwccf-reward__amount">
											<?php
											/* translators: %s: amount. */
											printf( esc_html( $dynamic ? __( '%s or more', 'devdiggers-crowdfunding-for-woocommerce' ) : '%s' ), wp_kses_post( wc_price( $reward['amount'] ) ) );
											?>
										</span>
									</span>
									<?php if ( $reward['description'] ) : ?>
										<span class="ddwccf-reward__desc"><?php echo esc_html( $reward['description'] ); ?></span>
									<?php endif; ?>
									<span class="ddwccf-reward__meta">
										<?php if ( $reward['delivery'] ) : ?>
											<span>
												<?php
												/* translators: %s: estimated delivery. */
												printf( esc_html__( 'Estimated delivery: %s', 'devdiggers-crowdfunding-for-woocommerce' ), esc_html( $reward['delivery'] ) );
												?>
											</span>
										<?php endif; ?>
										<?php if ( $reward['limit'] ) : ?>
											<span>
												<?php
												echo esc_html(
													$available
														/* translators: 1: left, 2: limit. */
														? sprintf( __( '%1$d of %2$d left', 'devdiggers-crowdfunding-for-woocommerce' ), $reward['limit'] - $reward['claimed'], $reward['limit'] )
														: __( 'Sold out', 'devdiggers-crowdfunding-for-woocommerce' )
												);
												?>
											</span>
										<?php endif; ?>
									</span>
								</span>
							</label>
						<?php endforeach; ?>
					</fieldset>
				<?php endif; ?>

				<?php if ( $dynamic ) : ?>
					<?php if ( $presets ) : ?>
						<div class="ddwccf-presets" role="group" aria-label="<?php esc_attr_e( 'Suggested amounts', 'devdiggers-crowdfunding-for-woocommerce' ); ?>">
							<?php foreach ( $presets as $index => $amount ) : ?>
								<button type="button" class="ddwccf-preset<?php echo 0 === $index ? ' is-active' : ''; ?>" data-amount="<?php echo esc_attr( $amount ); ?>"><?php echo wp_kses_post( wc_price( $amount, [ 'decimals' => floor( $amount ) === $amount ? 0 : wc_get_price_decimals() ] ) ); ?></button>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
					<label class="ddwccf-amount-field" for="ddwccf-contribution-amount">
						<span><?php esc_html_e( 'Your contribution', 'devdiggers-crowdfunding-for-woocommerce' ); ?></span>
						<span class="ddwccf-amount-input">
							<span class="ddwccf-currency"><?php echo esc_html( html_entity_decode( get_woocommerce_currency_symbol() ) ); ?></span>
							<input type="number" id="ddwccf-contribution-amount" name="ddwccf_contribution_amount" step="any" min="<?php echo esc_attr( max( $minimum, 0.01 ) ); ?>" <?php echo $maximum ? 'max="' . esc_attr( $maximum ) . '"' : ''; ?> value="<?php echo esc_attr( $value ); ?>" inputmode="decimal" required />
						</span>
					</label>
					<?php if ( $minimum > 0 ) : ?>
						<small class="ddwccf-amount-hint">
							<?php
							/* translators: %s: minimum amount. */
							printf( esc_html__( 'Minimum contribution: %s', 'devdiggers-crowdfunding-for-woocommerce' ), wp_kses_post( wc_price( $minimum ) ) );
							?>
						</small>
					<?php endif; ?>
				<?php elseif ( ! $data['rewards'] ) : ?>
					<p class="ddwccf-contribution-fixed"><?php esc_html_e( 'Contribution', 'devdiggers-crowdfunding-for-woocommerce' ); ?>: <strong><?php echo wp_kses_post( wc_price( $minimum ) ); ?></strong></p>
				<?php endif; ?>

				<?php if ( 'yes' === $config['allow_anonymous'] ) : ?>
					<label class="ddwccf-check">
						<input type="checkbox" name="ddwccf_anonymous" value="1" />
						<span><?php esc_html_e( 'Hide my name from the public supporters list', 'devdiggers-crowdfunding-for-woocommerce' ); ?></span>
					</label>
				<?php endif; ?>

				<?php if ( 'yes' === $config['allow_messages'] ) : ?>
					<label class="ddwccf-message-field" for="ddwccf-message">
						<span><?php esc_html_e( 'Leave a message (optional)', 'devdiggers-crowdfunding-for-woocommerce' ); ?></span>
						<textarea id="ddwccf-message" name="ddwccf_message" rows="2" maxlength="300" placeholder="<?php esc_attr_e( 'Words of support for the campaign', 'devdiggers-crowdfunding-for-woocommerce' ); ?>"></textarea>
					</label>
				<?php endif; ?>
			</div>
			<?php
		}
	}
}
