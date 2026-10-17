<?php
/**
 * Campaign card and share box.
 *
 * @package DevDiggers Crowdfunding for WooCommerce
 * @version 1.0.0
 */

namespace DDWCCrowdFunding\Templates\Front;

use DDWCCrowdFunding\Helper\DDWCCF_Event_Helper;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCCF_Campaign_Card_Template' ) ) {
	/**
	 * Campaign card template class.
	 */
	class DDWCCF_Campaign_Card_Template {
		/**
		 * Progress bar.
		 *
		 * @param array $data Campaign data.
		 * @return void
		 */
		public static function render_progress( $data ) {
			if ( ! $data['target_amount'] ) {
				return;
			}

			$progress = DDWCCF_Event_Helper::get_progress( $data );
			$target   = (float) $data['target_amount'];
			?>
			<div class="ddwccf-progress" role="progressbar" aria-valuenow="<?php echo esc_attr( min( 100, $progress ) ); ?>" aria-valuemin="0" aria-valuemax="100" aria-label="<?php esc_attr_e( 'Funding progress', 'devdiggers-crowdfunding-for-woocommerce' ); ?>">
				<span class="ddwccf-progress__bar" style="width: <?php echo esc_attr( min( 100, $progress ) ); ?>%"></span>
			</div>
			<?php
		}

		/**
		 * Render a campaign card.
		 *
		 * @param int    $event_id Event ID.
		 * @param array  $config   Configuration.
		 * @param string $context  single|grid.
		 * @return void
		 */
		public static function render( $event_id, $config, $context = 'single' ) {
			$data = DDWCCF_Event_Helper::get_event_data( $event_id );

			if ( ! $data ) {
				return;
			}

			$product    = wc_get_product( $event_id );
			$state      = DDWCCF_Event_Helper::get_state( $event_id, $data );
			$progress   = DDWCCF_Event_Helper::get_progress( $data );
			$days_left  = DDWCCF_Event_Helper::get_days_left( $data );
			$supporters = count( $data['contributors'] );
			$closed     = DDWCCF_Event_Helper::is_closed( $event_id, $data );
			$creator    = get_userdata( get_post_field( 'post_author', $event_id ) );
			$countdown  = 'single' === $context && ! $closed && $data['end_date'] && 'yes' === $config['show_countdown'];
			?>
			<div class="ddwccf-card ddwccf-card--<?php echo esc_attr( $context ); ?> ddwccf-state-<?php echo esc_attr( $state ); ?>">
				<?php if ( 'grid' === $context ) : ?>
					<a class="ddwccf-card__media" href="<?php echo esc_url( get_permalink( $event_id ) ); ?>" tabindex="-1">
						<?php echo wp_kses_post( $product->get_image( 'woocommerce_thumbnail' ) ); ?>
						<?php echo wp_kses_post( DDWCCF_Event_Helper::get_badge( $state ) ); ?>
					</a>
					<h3 class="ddwccf-card__title"><a href="<?php echo esc_url( get_permalink( $event_id ) ); ?>"><?php echo esc_html( $product->get_name() ); ?></a></h3>
					<?php if ( $product->get_short_description() ) : ?>
						<p class="ddwccf-card__excerpt"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $product->get_short_description() ), 18 ) ); ?></p>
					<?php endif; ?>
				<?php else : ?>
					<div class="ddwccf-card__badges">
						<?php echo wp_kses_post( DDWCCF_Event_Helper::get_badge( $state ) ); ?>
					</div>
				<?php endif; ?>

				<div class="ddwccf-card__raised">
					<strong><?php echo wp_kses_post( wc_price( $data['collected_amount'] ) ); ?></strong>
					<span>
						<?php
						if ( $data['target_amount'] ) {
							/* translators: %s: target amount. */
							printf( esc_html__( 'raised of %s goal', 'devdiggers-crowdfunding-for-woocommerce' ), wp_kses_post( wc_price( $data['target_amount'] ) ) );
						} else {
							esc_html_e( 'raised so far', 'devdiggers-crowdfunding-for-woocommerce' );
						}
						?>
					</span>
				</div>

				<?php self::render_progress( $data ); ?>

				<ul class="ddwccf-card__stats">
					<?php if ( $data['target_amount'] ) : ?>
						<li><strong><?php echo esc_html( $progress . '%' ); ?></strong><span><?php esc_html_e( 'Funded', 'devdiggers-crowdfunding-for-woocommerce' ); ?></span></li>
					<?php endif; ?>
					<li><strong><?php echo esc_html( number_format_i18n( $supporters ) ); ?></strong><span><?php echo esc_html( _n( 'Supporter', 'Supporters', $supporters, 'devdiggers-crowdfunding-for-woocommerce' ) ); ?></span></li>
					<?php if ( null !== $days_left && ! $closed ) : ?>
						<li><strong><?php echo esc_html( number_format_i18n( $days_left ) ); ?></strong><span><?php echo esc_html( _n( 'Day left', 'Days left', $days_left, 'devdiggers-crowdfunding-for-woocommerce' ) ); ?></span></li>
					<?php endif; ?>
				</ul>

				<?php if ( $countdown ) : ?>
					<div class="ddwccf-countdown" data-end="<?php echo esc_attr( gmdate( 'c', strtotime( $data['end_date'] . ' 23:59:59 ' . wp_timezone_string() ) ) ); ?>"></div>
				<?php endif; ?>

				<?php if ( 'single' === $context && $creator ) : ?>
					<p class="ddwccf-card__creator">
						<?php echo get_avatar( $creator->ID, 28 ); ?>
						<span>
							<?php
							/* translators: %s: creator name. */
							printf( esc_html__( 'Organised by %s', 'devdiggers-crowdfunding-for-woocommerce' ), '<strong>' . esc_html( $creator->display_name ) . '</strong>' );
							?>
						</span>
					</p>
				<?php endif; ?>

				<?php if ( 'single' === $context && $closed ) : ?>
					<p class="ddwccf-closed-notice">
						<?php
						if ( 'pending' === $state ) {
							esc_html_e( 'This campaign is waiting for approval.', 'devdiggers-crowdfunding-for-woocommerce' );
						} else {
							esc_html_e( 'This campaign is no longer accepting contributions. Thank you to everyone who supported it!', 'devdiggers-crowdfunding-for-woocommerce' );
						}
						?>
					</p>
				<?php elseif ( 'grid' === $context ) : ?>
					<a class="ddwccf-btn<?php echo $closed ? '' : ' ddwccf-btn--primary'; ?> ddwccf-card__cta" href="<?php echo esc_url( get_permalink( $event_id ) ); ?>"><?php echo esc_html( $closed ? __( 'View campaign', 'devdiggers-crowdfunding-for-woocommerce' ) : $config['button_label'] ); ?></a>
				<?php endif; ?>
			</div>
			<?php
		}

		/**
		 * Share links.
		 *
		 * @param int   $event_id Event ID.
		 * @param array $config   Configuration.
		 * @return void
		 */
		public static function render_share( $event_id, $config ) {
			$url      = get_permalink( $event_id );
			$title    = get_the_title( $event_id );
			$networks = [
				'facebook' => [ __( 'Facebook', 'devdiggers-crowdfunding-for-woocommerce' ), 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $url ), '<path d="M14 8h3V4h-3a4 4 0 0 0-4 4v2H8v4h2v8h4v-8h3l1-4h-4V8z"/>' ],
				'x'        => [ __( 'X', 'devdiggers-crowdfunding-for-woocommerce' ), 'https://twitter.com/intent/tweet?url=' . rawurlencode( $url ) . '&text=' . rawurlencode( $title ), '<path d="M4 4l16 16M20 4 4 20"/>' ],
				'whatsapp' => [ __( 'WhatsApp', 'devdiggers-crowdfunding-for-woocommerce' ), 'https://api.whatsapp.com/send?text=' . rawurlencode( $title . ' ' . $url ), '<path d="M3 21l1.6-4.7A9 9 0 1 1 8 19.4L3 21z"/>' ],
				'linkedin' => [ __( 'LinkedIn', 'devdiggers-crowdfunding-for-woocommerce' ), 'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode( $url ), '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M8 10v7M8 7v.01M12 17v-4a2 2 0 0 1 4 0v4M12 10v7"/>' ],
				'email'    => [ __( 'Email', 'devdiggers-crowdfunding-for-woocommerce' ), 'mailto:?subject=' . rawurlencode( $title ) . '&body=' . rawurlencode( $url ), '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/>' ],
			];
			$enabled  = (array) $config['share_networks'];
			$svg      = fn( $path ) => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
			$kses     = ddfw_kses_allowed_svg_tags();
			?>
			<div class="ddwccf-share ddwccf-share--circle ddwccf-share--outline ddwccf-share--medium">
				<span class="ddwccf-share__label"><?php esc_html_e( 'Share this campaign', 'devdiggers-crowdfunding-for-woocommerce' ); ?></span>
				<div class="ddwccf-share__buttons">
					<?php foreach ( $networks as $key => $network ) : ?>
						<?php if ( in_array( $key, $enabled, true ) ) : ?>
							<a class="ddwccf-share__button" data-network="<?php echo esc_attr( $key ); ?>" href="<?php echo esc_url( $network[1] ); ?>" target="_blank" rel="noopener nofollow" aria-label="<?php echo esc_attr( $network[0] ); ?>" title="<?php echo esc_attr( $network[0] ); ?>"><?php echo wp_kses( $svg( $network[2] ), $kses ); ?></a>
						<?php endif; ?>
					<?php endforeach; ?>
					<?php if ( in_array( 'copy', $enabled, true ) ) : ?>
						<button type="button" class="ddwccf-share__button ddwccf-copy" data-copy="<?php echo esc_attr( $url ); ?>" aria-label="<?php esc_attr_e( 'Copy link', 'devdiggers-crowdfunding-for-woocommerce' ); ?>" title="<?php esc_attr_e( 'Copy link', 'devdiggers-crowdfunding-for-woocommerce' ); ?>"><?php echo wp_kses( $svg( '<path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7"/><path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7"/>' ), $kses ); ?></button>
					<?php endif; ?>
				</div>
			</div>
			<?php
		}
	}
}
