<?php
/**
 * File handler: builds the configuration and boots each request surface.
 *
 * @author DevDiggers
 * @package DevDiggers Crowdfunding for WooCommerce
 * @version 1.0.0
 */

namespace DDWCCrowdFunding\Includes;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCCF_File_Handler' ) ) {
	/**
	 * File handler class.
	 */
	class DDWCCF_File_Handler {
		/**
		 * Configuration.
		 *
		 * @var array
		 */
		protected $ddwccf_configuration;

		/**
		 * Constructor.
		 */
		public function __construct() {
			// Before anything that queries orders, the upgrade routine included.
			add_filter( 'woocommerce_order_query_args', [ Common\DDWCCF_Common_Functions::class, 'ddwccf_park_order_meta_query' ] );
			add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', [ Common\DDWCCF_Common_Functions::class, 'ddwccf_restore_order_meta_query' ], 10, 2 );

			$this->ddwccf_configuration = $this->ddwccf_set_globals();

			foreach ( [ 'update_option__ddwccf_backed_endpoint', 'add_option__ddwccf_backed_endpoint' ] as $hook ) {
				add_action(
					$hook,
					function () {
						set_transient( 'ddwccf_flush_rewrite_rules', true, MINUTE_IN_SECONDS );
					}
				);
			}

			if ( is_admin() ) {
				new DDWCCF_Admin_Dashboard( $this->ddwccf_configuration );
				new Admin\DDWCCF_Setup_Wizard();
				new Admin\DDWCCF_Admin_Hooks( $this->ddwccf_configuration );
			}

			// Front hooks also serve AJAX add to cart and Store API requests.
			if ( ! is_admin() || wp_doing_ajax() ) {
				new Front\DDWCCF_Front_Hooks( $this->ddwccf_configuration );
			}

			new Common\DDWCCF_Common_Hooks( $this->ddwccf_configuration );
		}

		/**
		 * Every option with its quick-start default.
		 * Defaults only apply until an option is saved, so unchecked boxes stay off.
		 *
		 * @return array
		 */
		public static function get_option_defaults() {
			return [
				'enabled'                            => 'yes',
				'button_label'                       => __( 'Back this campaign', 'devdiggers-crowdfunding-for-woocommerce' ),
				'enabled_payment_gateways'           => [],
				'preset_amounts'                     => '10, 25, 50, 100',
				'max_contribution'                   => '',
				'allow_anonymous'                    => 'yes',
				'allow_messages'                     => 'yes',
				'close_on_goal'                      => 'yes',
				'show_countdown'                     => 'yes',
				'show_supporters'                    => 'yes',
				'supporters_count'                   => 10,
				'show_share'                         => 'yes',
				'share_networks'                     => [ 'facebook', 'x', 'whatsapp', 'linkedin', 'email', 'copy' ],
				'campaign_tabs'                      => 'yes',
				'accent_color'                       => '#0256ff',
				'backed_endpoint'                    => 'ddwccf-backed',
				'backed_endpoint_title'              => __( 'Backed Campaigns', 'devdiggers-crowdfunding-for-woocommerce' ),
				'enable_widgets_my_account_endpoint' => '',
				'admin_email'                        => '',
			];
		}

		/**
		 * Build the configuration array from options.
		 *
		 * @return array
		 */
		public function ddwccf_set_globals() {
			global $ddwccf_configuration;

			$config = [];

			foreach ( self::get_option_defaults() as $key => $default ) {
				$value = get_option( '_ddwccf_' . $key, $default );

				// Text options fall back to the default when saved empty.
				if ( '' === $value && is_string( $default ) && in_array( $key, [ 'button_label', 'backed_endpoint', 'backed_endpoint_title', 'accent_color' ], true ) ) {
					$value = $default;
				}

				$config[ $key ] = is_array( $default ) ? array_filter( (array) $value ) : $value;
			}

			$config['max_contribution'] = (float) $config['max_contribution'];
			$config['supporters_count'] = max( 1, absint( $config['supporters_count'] ) );

			$ddwccf_configuration = apply_filters( 'ddwccf_configuration', $config );

			return $ddwccf_configuration;
		}
	}
}
