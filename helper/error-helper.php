<?php
/**
 * Notice helper.
 *
 * @package WooCommerce Crowdfunding
 * @version 1.2.0
 */

namespace DDWCCrowdFunding\Helper;

defined( 'ABSPATH' ) || exit();

if ( ! trait_exists( 'DDWCCF_Error_Helper' ) ) {
	/**
	 * Notice helper trait.
	 */
	trait DDWCCF_Error_Helper {
		/**
		 * Print an admin notice or a WooCommerce notice.
		 *
		 * @param string $message    Message (HTML allowed).
		 * @param string $error_type success|error|warning|info.
		 * @return void
		 */
		public function ddwccf_print_notification( $message, $error_type = 'success' ) {
			if ( is_admin() ) {
				?>
				<div class="notice notice-<?php echo esc_attr( $error_type ); ?> is-dismissible">
					<p><?php echo wp_kses_post( $message ); ?></p>
				</div>
				<?php
			} else {
				wc_print_notice( wp_kses_post( $message ), $error_type );
			}
		}
	}
}
