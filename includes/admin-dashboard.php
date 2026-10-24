<?php
/**
 * Framework admin shell: Dashboard, Campaigns, Contributions and Configuration.
 *
 * @package DevDiggers Crowdfunding for WooCommerce
 * @version 1.0.0
 */

namespace DDWCCrowdFunding\Includes;

use DDWCCrowdFunding\Includes\Admin\DDWCCF_Admin_Functions;
use DDWCCrowdFunding\Helper\DDWCCF_Event_Helper;
use DDWCCrowdFunding\Templates\Admin\Configuration\DDWCCF_Framework_Configuration_Template;
use DDWCCrowdFunding\Templates\Admin\Dashboard\DDWCCF_Dashboard_Template;
use DevDiggers\Framework\Includes\DDFW_Assets;
use DevDiggers\Framework\Includes\DDFW_Plugin_Dashboard;
use DevDiggers\Framework\Includes\DDFW_SVG;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCCF_Admin_Dashboard' ) ) {
	/**
	 * Admin dashboard class.
	 */
	class DDWCCF_Admin_Dashboard {
		/**
		 * Pro product page.
		 */
		const UPGRADE_URL = 'https://devdiggers.com/product/woocommerce-crowdfunding/';

		/**
		 * Dashboard renderer.
		 *
		 * @var DDFW_Plugin_Dashboard
		 */
		private $dashboard;

		/**
		 * List screens.
		 *
		 * @var DDWCCF_Admin_Functions
		 */
		private $admin;

		/**
		 * Register pages.
		 *
		 * @param array $configuration Configuration.
		 */
		public function __construct( $configuration ) {
			$this->admin = new DDWCCF_Admin_Functions( $configuration );
			$settings    = new DDWCCF_Framework_Configuration_Template();
			$tabs        = [
				'general'       => [ __( 'General', 'devdiggers-crowdfunding-for-woocommerce' ), DDFW_SVG::get_svg_icon( 'general', true, [ 'size' => 18 ] ) ],
				'campaigns'     => [ __( 'Campaigns', 'devdiggers-crowdfunding-for-woocommerce' ), $this->icon( '<path d="m3 11 18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/>' ) ],
				'contributions' => [ __( 'Contributions', 'devdiggers-crowdfunding-for-woocommerce' ), $this->icon( '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>' ) ],
				'withdrawals'   => [ __( 'Payouts & Fees', 'devdiggers-crowdfunding-for-woocommerce' ), $this->icon( '<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/>' ) ],
				'display'       => [ __( 'Display', 'devdiggers-crowdfunding-for-woocommerce' ), $this->icon( '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/>' ) ],
				'emails'        => [ __( 'Emails', 'devdiggers-crowdfunding-for-woocommerce' ), $this->icon( '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/>' ) ],
				'shortcodes'    => [ __( 'Shortcodes', 'devdiggers-crowdfunding-for-woocommerce' ), $this->icon( '<path d="m16 18 6-6-6-6M8 6l-6 6 6 6"/>' ) ],
			];

			$config_tabs = [];
			foreach ( $tabs as $key => $tab ) {
				$config_tabs[ $key ] = [
					'label'    => $tab[0],
					'icon'     => $tab[1],
					'callback' => [ $settings, $key ],
				];
			}

			$this->dashboard = new DDFW_Plugin_Dashboard(
				[
					'page_title'              => __( 'Crowdfunding', 'devdiggers-crowdfunding-for-woocommerce' ),
					'menu_title'              => __( 'Crowdfunding', 'devdiggers-crowdfunding-for-woocommerce' ),
					'plugin_name'             => DDWCCF_Event_Helper::get_brand_icon() . ' ' . esc_html__( 'Crowdfunding', 'devdiggers-crowdfunding-for-woocommerce' ),
					'slug'                    => DDWCCF_DASHBOARD_PAGE,
					'capability'              => DDWCCF_Admin_Functions::get_capability(),
					'upgrade_url'             => self::UPGRADE_URL,
					'screen_options_callback' => [ $this, 'add_screen_options' ],
					'menus'                   => [
						'dashboard'     => [
							'label'    => __( 'Dashboard', 'devdiggers-crowdfunding-for-woocommerce' ),
							'layout'   => 'full-width',
							'callback' => [ $this, 'get_dashboard_template' ],
						],
						'events'        => [
							'label'    => __( 'Campaigns', 'devdiggers-crowdfunding-for-woocommerce' ),
							'layout'   => 'full-width',
							'callback' => [ $this->admin, 'ddwccf_get_events_list' ],
						],
						'orders'        => [
							'label'    => __( 'Contributions', 'devdiggers-crowdfunding-for-woocommerce' ),
							'layout'   => 'full-width',
							'callback' => [ $this->admin, 'ddwccf_get_orders_list' ],
						],
						'configuration' => [
							'label'  => __( 'Configuration', 'devdiggers-crowdfunding-for-woocommerce' ),
							'layout' => 'sidebar',
							'tabs'   => $config_tabs,
						],
					],
				]
			);

			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
			add_filter( 'admin_footer_text', [ $this, 'set_admin_footer_text' ], 99 );
		}

		/**
		 * Stroke icon for configuration tabs.
		 *
		 * @param string $path SVG inner markup.
		 * @return string
		 */
		private function icon( $path ) {
			return '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
		}

		/**
		 * Screenshot behind a Pro upgrade card, or '' until one is added to assets/images/pro/.
		 *
		 * @param string $name File name without extension.
		 * @return string
		 */
		public static function get_pro_image( $name ) {
			$file = 'assets/images/pro/' . $name . '.webp';

			return file_exists( DDWCCF_PLUGIN_FILE . $file ) ? DDWCCF_PLUGIN_URL . $file : '';
		}

		/**
		 * Dashboard menu.
		 *
		 * @return void
		 */
		public function get_dashboard_template() {
			new DDWCCF_Dashboard_Template();
		}

		/**
		 * Screen options for list menus.
		 *
		 * @return void
		 */
		public function add_screen_options() {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin routing.
			$menu = isset( $_GET['menu'] ) ? sanitize_key( wp_unslash( $_GET['menu'] ) ) : 'dashboard';

			if ( 'orders' === $menu ) {
				$this->admin->ddwccf_add_screen_options_in_orders_list();
			} elseif ( 'events' === $menu ) {
				$this->admin->ddwccf_add_screen_options_in_events_list();
			}
		}

		/**
		 * Plugin page assets.
		 *
		 * @return void
		 */
		public function enqueue_assets() {
			if ( ! $this->dashboard->is_a_plugin_page() ) {
				return;
			}

			wp_enqueue_style( 'ddwccf-admin-style', DDWCCF_PLUGIN_URL . 'assets/css/admin.css', [ DDFW_Assets::$framework_css_handle ], filemtime( DDWCCF_PLUGIN_FILE . 'assets/css/admin.css' ) );
			wp_enqueue_script( 'ddwccf-admin-script', DDWCCF_PLUGIN_URL . 'assets/js/admin.js', [ DDFW_Assets::$framework_js_handle ], filemtime( DDWCCF_PLUGIN_FILE . 'assets/js/admin.js' ), true );
			wp_localize_script(
				'ddwccf-admin-script',
				'ddwccfAdminObject',
				[
					'i18n' => [
						'copied' => __( 'Copied', 'devdiggers-crowdfunding-for-woocommerce' ),
					],
				]
			);

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin routing.
			if ( isset( $_GET['action'] ) && in_array( $_GET['action'], [ 'add', 'edit' ], true ) ) {
				wp_enqueue_media();
				wp_enqueue_editor();
			}
		}

		/**
		 * Review request in the admin footer of plugin pages.
		 *
		 * @param string $footer_text Footer text.
		 * @return string
		 */
		public function set_admin_footer_text( $footer_text ) {
			if ( ! $this->dashboard->is_a_plugin_page() ) {
				return $footer_text;
			}

			return sprintf(
				/* translators: 1: plugin name, 2: five-star rating link. */
				esc_html__( 'Thank you for using %1$s. If you like it, please leave us a %2$s rating. It means a lot to us!', 'devdiggers-crowdfunding-for-woocommerce' ),
				'<strong>' . esc_html__( 'DevDiggers Crowdfunding for WooCommerce', 'devdiggers-crowdfunding-for-woocommerce' ) . '</strong>',
				'<a href="https://wordpress.org/support/plugin/devdiggers-crowdfunding-for-woocommerce/reviews/#new-post" target="_blank" rel="noopener" aria-label="' . esc_attr__( 'five star', 'devdiggers-crowdfunding-for-woocommerce' ) . '"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 192 32" height="10" aria-hidden="true"><path d="M16 26.534L6.111 32 8 20.422l-8-8.2 11.056-1.688L16 0l4.944 10.534L32 12.223l-8 8.2L25.889 32zm40 0L46.111 32 48 20.422l-8-8.2 11.056-1.688L56 0l4.944 10.534L72 12.223l-8 8.2L65.889 32zm40 0L86.111 32 88 20.422l-8-8.2 11.056-1.688L96 0l4.944 10.534L112 12.223l-8 8.2L105.889 32zm40 0L126.111 32 128 20.422l-8-8.2 11.056-1.688L136 0l4.944 10.534L152 12.223l-8 8.2L145.889 32zm40 0L166.111 32 168 20.422l-8-8.2 11.056-1.688L176 0l4.944 10.534L192 12.223l-8 8.2L185.889 32z" fill="#F5A623" fill-rule="evenodd"/></svg></a>'
			);
		}
	}
}
