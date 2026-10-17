<?php
/**
 * Repeatable reward rows for the campaign editor.
 * Rows are cloned client side from a text/html script template, the format framework kses keeps.
 *
 * @package DevDiggers Crowdfunding for WooCommerce
 * @version 1.0.0
 */

namespace DDWCCrowdFunding\Templates\Common;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCCF_Repeater_Template' ) ) {
	/**
	 * Repeater template class.
	 */
	class DDWCCF_Repeater_Template {
		/**
		 * Reward rows.
		 *
		 * @param array $rewards Rewards.
		 * @param int   $max     Most rows the campaign can have.
		 * @return string
		 */
		public static function rewards( $rewards, $max ) {
			$currency = html_entity_decode( get_woocommerce_currency_symbol() );
			$row      = function ( $index, $reward ) use ( $currency ) {
				$name = 'ddwccf_rewards[' . $index . ']';
				ob_start();
				?>
				<div class="ddwccf-repeater__row">
					<input type="hidden" name="<?php echo esc_attr( $name ); ?>[id]" value="<?php echo esc_attr( $reward['id'] ?? '' ); ?>" />
					<div class="ddwccf-repeater__head">
						<strong data-empty="<?php esc_attr_e( 'New reward', 'devdiggers-crowdfunding-for-woocommerce' ); ?>"><?php echo esc_html( ! empty( $reward['title'] ) ? $reward['title'] : __( 'New reward', 'devdiggers-crowdfunding-for-woocommerce' ) ); ?></strong>
						<?php if ( ! empty( $reward['claimed'] ) ) : ?>
							<span class="ddwccf-repeater__note">
								<?php
								/* translators: %d: claimed count. */
								echo esc_html( sprintf( _n( '%d backer chose this reward', '%d backers chose this reward', (int) $reward['claimed'], 'devdiggers-crowdfunding-for-woocommerce' ), (int) $reward['claimed'] ) );
								?>
							</span>
						<?php endif; ?>
						<?php echo self::remove_button( __( 'Remove reward', 'devdiggers-crowdfunding-for-woocommerce' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped when built. ?>
					</div>
					<div class="ddwccf-repeater__grid">
						<label><span><?php esc_html_e( 'Title', 'devdiggers-crowdfunding-for-woocommerce' ); ?></span><input type="text" name="<?php echo esc_attr( $name ); ?>[title]" value="<?php echo esc_attr( $reward['title'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Early bird', 'devdiggers-crowdfunding-for-woocommerce' ); ?>" /></label>
						<label>
							<span>
								<?php
								/* translators: %s: currency symbol. */
								printf( esc_html__( 'Minimum amount (%s)', 'devdiggers-crowdfunding-for-woocommerce' ), esc_html( $currency ) );
								?>
							</span>
							<input type="number" min="0" step="any" name="<?php echo esc_attr( $name ); ?>[amount]" value="<?php echo esc_attr( $reward['amount'] ?? '' ); ?>" />
						</label>
						<label><span><?php esc_html_e( 'Quantity limit', 'devdiggers-crowdfunding-for-woocommerce' ); ?></span><input type="number" min="0" step="1" name="<?php echo esc_attr( $name ); ?>[limit]" value="<?php echo esc_attr( ! empty( $reward['limit'] ) ? $reward['limit'] : '' ); ?>" placeholder="<?php esc_attr_e( 'Unlimited', 'devdiggers-crowdfunding-for-woocommerce' ); ?>" /></label>
						<label><span><?php esc_html_e( 'Estimated delivery', 'devdiggers-crowdfunding-for-woocommerce' ); ?></span><input type="text" name="<?php echo esc_attr( $name ); ?>[delivery]" value="<?php echo esc_attr( $reward['delivery'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'March 2027', 'devdiggers-crowdfunding-for-woocommerce' ); ?>" /></label>
						<label class="ddwccf-repeater__wide"><span><?php esc_html_e( 'What supporters get', 'devdiggers-crowdfunding-for-woocommerce' ); ?></span><textarea rows="2" name="<?php echo esc_attr( $name ); ?>[description]"><?php echo esc_textarea( $reward['description'] ?? '' ); ?></textarea></label>
					</div>
				</div>
				<?php
				return ob_get_clean();
			};

			return self::wrap( 'rewards', array_map( $row, array_keys( $rewards ), $rewards ), $row( '__INDEX__', [] ), __( 'Add reward', 'devdiggers-crowdfunding-for-woocommerce' ), $max );
		}

		/**
		 * Trash icon button that removes its row.
		 *
		 * @param string $label Accessible label.
		 * @return string
		 */
		protected static function remove_button( $label ) {
			return sprintf(
				'<button type="button" class="button-link ddwccf-repeater__remove" aria-label="%1$s" title="%1$s"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M9 2a1 1 0 0 0-.9.55L7.38 4H4a1 1 0 0 0 0 2h16a1 1 0 1 0 0-2h-3.38l-.72-1.45A1 1 0 0 0 15 2H9Z"/><path fill-rule="evenodd" d="M5 8h14l-.85 11.1A3 3 0 0 1 15.16 22H8.84a3 3 0 0 1-2.99-2.9L5 8Zm5 3a1 1 0 0 0-1 1v6a1 1 0 1 0 2 0v-6a1 1 0 0 0-1-1Zm4 0a1 1 0 0 0-1 1v6a1 1 0 1 0 2 0v-6a1 1 0 0 0-1-1Z" clip-rule="evenodd"/></svg></button>',
				esc_attr( $label )
			);
		}

		/**
		 * Repeater shell.
		 *
		 * @param string   $key      Key.
		 * @param string[] $rows     Row HTML.
		 * @param string   $template Blank row HTML.
		 * @param string   $label    Add button label.
		 * @param int      $max      Most rows allowed.
		 * @return string
		 */
		protected static function wrap( $key, $rows, $template, $label, $max ) {
			ob_start();
			?>
			<div class="ddwccf-repeater" data-repeater="<?php echo esc_attr( $key ); ?>" data-next="<?php echo esc_attr( count( $rows ) ); ?>" data-max="<?php echo esc_attr( $max ); ?>">
				<div class="ddwccf-repeater__rows">
					<?php echo implode( '', $rows ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Rows are escaped when built. ?>
				</div>
				<script type="text/html"><?php echo $template; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped when built. ?></script>
				<button type="button" class="button ddwccf-repeater__add">+ <?php echo esc_html( $label ); ?></button>
				<p class="ddwccf-repeater__limit">
					<?php
					/* translators: %d: number of reward tiers. */
					echo esc_html( sprintf( __( 'Free campaigns offer up to %d reward tiers.', 'devdiggers-crowdfunding-for-woocommerce' ), $max ) );
					?>
					<a href="<?php echo esc_url( \DDWCCrowdFunding\Includes\DDWCCF_Admin_Dashboard::UPGRADE_URL ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Get unlimited rewards with Pro', 'devdiggers-crowdfunding-for-woocommerce' ); ?></a>
				</p>
			</div>
			<?php
			return ob_get_clean();
		}
	}
}
