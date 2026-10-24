<?php
/**
 * Plugin Name: DevDiggers Crowdfunding for WooCommerce
 * Description: Run donation and crowdfunding campaigns in WooCommerce. Goals, end dates, reward tiers, a live progress card, a supporters wall and share buttons, paid through your normal checkout.
 * Plugin URI: https://github.com/DevDiggers-Technologies/crowdfunding-for-woocommerce
 * Author: DevDiggers
 * Author URI: https://devdiggers.com/
 * Version: 1.0.0
 * Text Domain: devdiggers-crowdfunding-for-woocommerce
 * Domain Path: /i18n
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * WC requires at least: 9.0
 * WC tested up to: 11.2
 * DevDiggersPrefix: ddwccf
 * Requires Plugins: woocommerce
 * License: GPLv3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package DevDiggers Crowdfunding for WooCommerce
 */

// ddwccf: DevDiggers Crowdfunding for WooCommerce.

use DDWCCrowdFunding\Includes\DDWCCF_File_Handler;

defined( 'ABSPATH' ) || exit();

/**
 * Point the plugin constants at this directory.
 *
 * @return void
 */
function ddwccf_free_define_constants() {
	defined( 'DDWCCF_PLUGIN_FILE' ) || define( 'DDWCCF_PLUGIN_FILE', plugin_dir_path( __FILE__ ) );
	defined( 'DDWCCF_PLUGIN_URL' ) || define( 'DDWCCF_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
	defined( 'DDWCCF_DASHBOARD_PAGE' ) || define( 'DDWCCF_DASHBOARD_PAGE', 'ddwccf-dashboard' );
}

if ( ! class_exists( 'DDWCCF_Free_Init' ) ) {
	/**
	 * Free Init class.
	 */
	final class DDWCCF_Free_Init {
		/**
		 * The single instance of this class.
		 *
		 * @var DDWCCF_Free_Init|null
		 */
		private static $instance = null;

		/**
		 * Class constructor.
		 */
		public function __construct() {
			add_action( 'init', [ $this, 'ddwccf_init' ], 0 );
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), [ $this, 'ddwccf_plugin_settings_link' ] );
			add_filter( 'plugin_row_meta', [ $this, 'ddwccf_plugin_row_meta' ], 10, 2 );
		}

		/**
		 * Create a plugin instance.
		 *
		 * @return static
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();

				/**
				 * Fires when the main plugin instance is loaded.
				 *
				 * @since 1.0.0
				 */
				do_action( 'ddwccf_loaded' );
			}

			return self::$instance;
		}

		/**
		 * Init function.
		 *
		 * @return void
		 */
		public function ddwccf_init() {
			// WordPress.org loads plugin translations automatically.
			if ( ! class_exists( 'WooCommerce' ) ) {
				add_action(
					'admin_notices',
					function () {
						?>
						<div class="notice notice-error">
							<p>
								<?php
								/* translators: %1$s: opening link tag, %2$s: closing link tag */
								printf( esc_html__( 'DevDiggers Crowdfunding for WooCommerce is activated but not effective. It requires %1$sWooCommerce%2$s in order to work.', 'devdiggers-crowdfunding-for-woocommerce' ), '<a href="' . esc_url( 'https://wordpress.org/plugins/woocommerce/' ) . '" target="_blank">', '</a>' );
								?>
							</p>
						</div>
						<?php
					}
				);

				return;
			}

			require_once DDWCCF_PLUGIN_FILE . 'autoload/autoload.php';
			new DDWCCF_File_Handler();

			// Only on the plugin's own screens (plus AJAX so the dismiss button works).
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin routing input.
			$on_plugin_page = is_admin() && isset( $_GET['page'] ) && DDWCCF_DASHBOARD_PAGE === sanitize_key( wp_unslash( $_GET['page'] ) );

			if ( ( $on_plugin_page || wp_doing_ajax() ) && class_exists( '\DevDiggers\Framework\Includes\DDFW_Review_Notice' ) ) {
				new \DevDiggers\Framework\Includes\DDFW_Review_Notice(
					[
						'plugin_name'   => esc_html__( 'DevDiggers Crowdfunding for WooCommerce', 'devdiggers-crowdfunding-for-woocommerce' ),
						'plugin_prefix' => 'ddwccf',
						'review_url'    => 'https://wordpress.org/support/plugin/devdiggers-crowdfunding-for-woocommerce/reviews/#new-post',
					]
				);
			}
		}

		/**
		 * Plugin settings link.
		 *
		 * @param array $links Links array.
		 * @return array
		 */
		public function ddwccf_plugin_settings_link( $links ) {
			ob_start();
			?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=ddwccf-dashboard' ) ); ?>"><?php esc_html_e( 'Dashboard', 'devdiggers-crowdfunding-for-woocommerce' ); ?></a>
			|
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=ddwccf-dashboard&menu=configuration' ) ); ?>"><?php esc_html_e( 'Configuration', 'devdiggers-crowdfunding-for-woocommerce' ); ?></a>
			|
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=ddwccf-dashboard&setup-wizard=true' ) ); ?>"><?php esc_html_e( 'Setup Wizard', 'devdiggers-crowdfunding-for-woocommerce' ); ?></a>
			|
			<a href="<?php echo esc_url( 'https://devdiggers.com/product/woocommerce-crowdfunding/' ); ?>" style="color: #0256ff; font-weight: bold;" target="_blank"><?php esc_html_e( 'Upgrade to Pro', 'devdiggers-crowdfunding-for-woocommerce' ); ?></a>
			<?php
			array_unshift( $links, ob_get_clean() );

			return $links;
		}

		/**
		 * Plugin row meta links.
		 *
		 * @param array  $links Links.
		 * @param string $file  Plugin file.
		 * @return array
		 */
		public function ddwccf_plugin_row_meta( $links, $file ) {
			if ( plugin_basename( __FILE__ ) === $file ) {
				$row_meta = [
					'support'       => '<a href="https://devdiggers.com/contact/" aria-label="' . esc_attr__( 'Support', 'devdiggers-crowdfunding-for-woocommerce' ) . '">' . esc_html__( 'Support', 'devdiggers-crowdfunding-for-woocommerce' ) . '</a>',
					'documentation' => '<a href="https://docs.devdiggers.com/woocommerce-crowdfunding/" target="_blank" aria-label="' . esc_attr__( 'Documentation', 'devdiggers-crowdfunding-for-woocommerce' ) . '">' . esc_html__( 'Documentation', 'devdiggers-crowdfunding-for-woocommerce' ) . '</a>',
					'review'        => '<a href="https://wordpress.org/support/plugin/devdiggers-crowdfunding-for-woocommerce/reviews/#new-post" target="_blank" title="' . esc_attr__( 'Review', 'devdiggers-crowdfunding-for-woocommerce' ) . '" aria-label="' . esc_attr__( 'Review', 'devdiggers-crowdfunding-for-woocommerce' ) . '"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 192 32" height="10"><path d="M16 26.534L6.111 32 8 20.422l-8-8.2 11.056-1.688L16 0l4.944 10.534L32 12.223l-8 8.2L25.889 32zm40 0L46.111 32 48 20.422l-8-8.2 11.056-1.688L56 0l4.944 10.534L72 12.223l-8 8.2L65.889 32zm40 0L86.111 32 88 20.422l-8-8.2 11.056-1.688L96 0l4.944 10.534L112 12.223l-8 8.2L105.889 32zm40 0L126.111 32 128 20.422l-8-8.2 11.056-1.688L136 0l4.944 10.534L152 12.223l-8 8.2L145.889 32zm40 0L166.111 32 168 20.422l-8-8.2 11.056-1.688L176 0l4.944 10.534L192 12.223l-8 8.2L185.889 32z" fill="#F5A623" fill-rule="evenodd"/></svg></a>',
				];

				$links = array_merge( $links, $row_meta );
			}

			return $links;
		}
	}
}

// Step aside when the premium edition of this plugin is active.
add_action(
	'plugins_loaded',
	function () {
		if ( class_exists( 'DDWCCF_Init' ) ) {
			return;
		}

		ddwccf_free_define_constants();

		DDWCCF_Free_Init::get_instance();

		// Load DevDiggers Framework if not loaded already.
		if ( ! defined( 'DDFW_LOADED' ) && file_exists( DDWCCF_PLUGIN_FILE . 'devdiggers-framework/init.php' ) ) {
			$should_load = true;

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin routing input.
			if ( ! empty( $_GET['page'] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin routing input.
				$current_page = sanitize_text_field( wp_unslash( $_GET['page'] ) );
				$prefix       = explode( '-', $current_page )[0];

				if ( 0 === strpos( $prefix, 'ddwc' ) || 0 === strpos( $prefix, 'ddwp' ) ) {
					$pro_class  = strtoupper( $prefix ) . '_Init';
					$free_class = strtoupper( $prefix ) . '_Free_Init';

					// Yield to a sibling DevDiggers plugin except on this plugin's own pages.
					if ( class_exists( $free_class ) && ! class_exists( $pro_class ) && 'ddwccf' !== $prefix ) {
						$should_load = false;
					}
				}
			}

			if ( $should_load ) {
				require DDWCCF_PLUGIN_FILE . 'devdiggers-framework/init.php';
			}
		}
	},
	10
);

register_activation_hook(
	__FILE__,
	function () {
		// The review notice counts its delay from here.
		add_option( 'ddwccf_installed_at', time() );

		// Register the Backed Campaigns endpoint on the next request.
		set_transient( 'ddwccf_flush_rewrite_rules', true, MINUTE_IN_SECONDS );

		// A store that already ran this plugin (or Pro) keeps its settings and skips the wizard.
		if ( false !== get_option( '_ddwccf_enabled', false ) ) {
			update_option( 'ddfw_setup_wizard_completed_devdiggers-crowdfunding-for-woocommerce', true );
			return;
		}

		set_transient( 'ddfw_activation_redirect_devdiggers-crowdfunding-for-woocommerce', true, 30 );
	}
);

// Free schedules one daily check for ended campaigns. Cleared by its literal name: the autoloader may not be loaded here.
register_deactivation_hook(
	__FILE__,
	function () {
		wp_clear_scheduled_hook( 'ddwccf_daily_check' );
	}
);

// HPOS compatibility.
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);
