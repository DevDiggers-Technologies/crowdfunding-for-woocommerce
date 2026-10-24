<?php
/**
 * Admin callbacks: settings, list screens, campaign editor save and bulk actions.
 *
 * @author DevDiggers
 * @package DevDiggers Crowdfunding for WooCommerce
 * @version 1.0.0
 */

namespace DDWCCrowdFunding\Includes\Admin;

use DDWCCrowdFunding\Templates\Admin;
use DDWCCrowdFunding\Helper\DDWCCF_Error_Helper;
use DDWCCrowdFunding\Helper\DDWCCF_Event_Helper;
use DDWCCrowdFunding\Helper\DDWCCF_Event_Form_Helper;
use DDWCCrowdFunding\Helper\DDWCCF_Email_Helper;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCCF_Admin_Functions' ) ) {
	/**
	 * Admin functions class.
	 */
	class DDWCCF_Admin_Functions {
		/**
		 * Error Helper Trait.
		 */
		use DDWCCF_Error_Helper;

		/**
		 * Configuration.
		 *
		 * @var array
		 */
		protected $ddwccf_configuration;

		/**
		 * Construct.
		 *
		 * @param array $ddwccf_configuration Configuration.
		 */
		public function __construct( $ddwccf_configuration ) {
			$this->ddwccf_configuration = $ddwccf_configuration;
		}

		/**
		 * Capability for the plugin screens.
		 *
		 * @return string
		 */
		public static function get_capability() {
			return apply_filters( 'ddwccf_modify_admin_menu_capability', 'manage_woocommerce' );
		}

		/**
		 * Settings group per configuration tab => option => sanitize callback.
		 * One group per tab so saving one tab never blanks the others.
		 *
		 * @return array
		 */
		public function get_settings_map() {
			$checkbox = [ $this, 'sanitize_checkbox' ];
			$text     = 'sanitize_text_field';

			return [
				'ddwccf-general-fields'       => [
					'_ddwccf_enabled'     => $checkbox,
					'_ddwccf_admin_email' => 'sanitize_email',
				],
				'ddwccf-contributions-fields' => [
					'_ddwccf_button_label'             => $text,
					'_ddwccf_preset_amounts'           => [ $this, 'sanitize_preset_amounts' ],
					'_ddwccf_max_contribution'         => [ $this, 'sanitize_amount' ],
					'_ddwccf_allow_anonymous'          => $checkbox,
					'_ddwccf_allow_messages'           => $checkbox,
					'_ddwccf_close_on_goal'            => $checkbox,
					'_ddwccf_enabled_payment_gateways' => [ $this, 'sanitize_gateways' ],
				],
				'ddwccf-display-fields'       => [
					'_ddwccf_campaign_tabs'         => $checkbox,
					'_ddwccf_show_countdown'        => $checkbox,
					'_ddwccf_show_supporters'       => $checkbox,
					'_ddwccf_supporters_count'      => [ $this, 'sanitize_supporters_count' ],
					'_ddwccf_show_share'            => $checkbox,
					'_ddwccf_share_networks'        => [ $this, 'sanitize_networks' ],
					'_ddwccf_accent_color'          => [ $this, 'sanitize_color' ],
					'_ddwccf_backed_endpoint'       => [ $this, 'sanitize_backed_endpoint' ],
					'_ddwccf_backed_endpoint_title' => $text,
					'_ddwccf_enable_widgets_my_account_endpoint' => $checkbox,
				],
				'ddwccf-emails-fields'        => [
					DDWCCF_Email_Helper::OPTION => [ DDWCCF_Email_Helper::class, 'sanitize' ],
				],
			];
		}

		/**
		 * Register settings.
		 *
		 * @return void
		 */
		public function ddwccf_register_settings() {
			foreach ( $this->get_settings_map() as $group => $options ) {
				foreach ( $options as $option => $callback ) {
					register_setting( $group, $option, [ 'sanitize_callback' => $callback ] );
				}
			}
		}

		/**
		 * Normalize checkbox values.
		 *
		 * @param mixed $value Submitted value.
		 * @return string
		 */
		public function sanitize_checkbox( $value ) {
			return 'yes' === $value ? 'yes' : '';
		}

		/**
		 * Keep enabled gateway IDs only.
		 *
		 * @param mixed $value Submitted gateway IDs.
		 * @return array
		 */
		public function sanitize_gateways( $value ) {
			$allowed = [];
			foreach ( WC()->payment_gateways()->payment_gateways() as $key => $gateway ) {
				if ( 'yes' === $gateway->enabled ) {
					$allowed[] = $key;
				}
			}
			return array_values( array_unique( array_intersect( (array) $value, $allowed ) ) );
		}

		/**
		 * Share networks.
		 *
		 * @param mixed $value Submitted networks.
		 * @return array
		 */
		public function sanitize_networks( $value ) {
			return array_values( array_intersect( (array) $value, [ 'facebook', 'x', 'whatsapp', 'linkedin', 'email', 'copy' ] ) );
		}

		/**
		 * Sanitize a nonnegative amount.
		 *
		 * @param mixed $value Submitted value.
		 * @return string
		 */
		public function sanitize_amount( $value ) {
			$amount = wc_format_decimal( $value );
			return is_numeric( $amount ) && (float) $amount >= 0 ? $amount : '';
		}

		/**
		 * Normalize preset amounts to a sorted comma list.
		 *
		 * @param mixed $value Submitted value.
		 * @return string
		 */
		public function sanitize_preset_amounts( $value ) {
			return implode( ', ', array_map( 'wc_format_decimal', DDWCCF_Event_Helper::parse_preset_amounts( sanitize_text_field( (string) $value ) ) ) );
		}

		/**
		 * Backed campaigns endpoint slug, never empty.
		 *
		 * @param mixed $value Submitted value.
		 * @return string
		 */
		public function sanitize_backed_endpoint( $value ) {
			$value = sanitize_title( (string) $value );
			return $value ? $value : 'ddwccf-backed';
		}

		/**
		 * Hex colour, falling back to the default accent.
		 *
		 * @param mixed $value Submitted value.
		 * @return string
		 */
		public function sanitize_color( $value ) {
			$color = sanitize_hex_color( (string) $value );
			return $color ? $color : '#0256ff';
		}

		/**
		 * Supporters count between 1 and 50.
		 *
		 * @param mixed $value Submitted value.
		 * @return int
		 */
		public function sanitize_supporters_count( $value ) {
			return min( 50, max( 1, absint( $value ) ) );
		}

		/**
		 * Screen options for the campaigns list.
		 *
		 * @return void
		 */
		public function ddwccf_add_screen_options_in_events_list() {
			global $myListTable; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- WordPress list table convention.

			add_screen_option(
				'per_page',
				[
					'label'   => esc_html__( 'Campaigns per page', 'devdiggers-crowdfunding-for-woocommerce' ),
					'default' => 20,
					'option'  => 'events_per_page',
				]
			);

			$myListTable = new Admin\Events\DDWCCF_Events_List_Template( $this->ddwccf_configuration ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- WordPress list table convention.
		}

		/**
		 * Screen options for the contributions list.
		 *
		 * @return void
		 */
		public function ddwccf_add_screen_options_in_orders_list() {
			global $myListTable; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- WordPress list table convention.

			add_screen_option(
				'per_page',
				[
					'label'   => esc_html__( 'Contributions per page', 'devdiggers-crowdfunding-for-woocommerce' ),
					'default' => 20,
					'option'  => 'orders_per_page',
				]
			);

			$myListTable = new Admin\Orders\DDWCCF_Orders_List_Template(); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- WordPress list table convention.
		}

		/**
		 * Persist per page screen options.
		 *
		 * @param mixed  $status Status.
		 * @param string $option Option.
		 * @param mixed  $value  Value.
		 * @return mixed
		 */
		public function ddwccf_set_options( $status, $option, $value ) {
			return in_array( $option, [ 'events_per_page', 'orders_per_page' ], true ) ? $value : $status;
		}

		/**
		 * Whether the request targets one of our menus.
		 *
		 * @param string $menu Menu key.
		 * @return bool
		 */
		protected function is_menu( $menu ) {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only routing.
			$page    = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
			$current = isset( $_GET['menu'] ) ? sanitize_key( wp_unslash( $_GET['menu'] ) ) : 'dashboard';
			// phpcs:enable WordPress.Security.NonceVerification.Recommended

			return DDWCCF_DASHBOARD_PAGE === $page && $menu === $current;
		}

		/**
		 * Save the campaign editor before any output so redirects work.
		 *
		 * @return void
		 */
		public function ddwccf_handle_event_save() {
			if ( ! $this->is_menu( 'events' ) || empty( $_POST['ddwccf_save_event'] ) ) {
				return;
			}

			if ( empty( $_POST['ddwccf_save_event_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ddwccf_save_event_nonce'] ) ), 'ddwccf_save_event_nonce_action' ) ) {
				wp_die( esc_html__( 'Security check failed.', 'devdiggers-crowdfunding-for-woocommerce' ) );
			}

			if ( ! current_user_can( self::get_capability() ) ) {
				wp_die( esc_html__( 'You are not allowed to manage campaigns.', 'devdiggers-crowdfunding-for-woocommerce' ) );
			}

			( new DDWCCF_Event_Form_Helper( $this->ddwccf_configuration ) )->save();
		}

		/**
		 * Row and bulk actions of the campaigns list, before output.
		 *
		 * @return void
		 */
		public function ddwccf_handle_events_bulk_action() {
			if ( ! $this->is_menu( 'events' ) || empty( $_REQUEST['ddwccf_nonce'] ) ) {
				return;
			}

			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['ddwccf_nonce'] ) ), 'ddwccf_nonce_action' ) ) {
				return;
			}

			if ( ! current_user_can( self::get_capability() ) ) {
				return;
			}

			$action = isset( $_REQUEST['action'] ) && '-1' !== $_REQUEST['action'] ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : ( isset( $_REQUEST['action2'] ) ? sanitize_key( wp_unslash( $_REQUEST['action2'] ) ) : '' );
			$ids    = isset( $_REQUEST['ddwccf-product-id'] ) ? array_map( 'absint', (array) wp_unslash( $_REQUEST['ddwccf-product-id'] ) ) : [];
			$ids    = array_filter( $ids, [ DDWCCF_Event_Helper::class, 'is_event' ] );

			if ( ! $ids || ! in_array( $action, [ 'delete', 'enable', 'disable' ], true ) ) {
				return;
			}

			foreach ( $ids as $id ) {
				if ( 'delete' === $action ) {
					wp_trash_post( $id );
				} else {
					wp_update_post(
						[
							'ID'          => $id,
							'post_status' => 'disable' === $action ? 'draft' : 'publish',
						]
					);
				}
			}

			wp_safe_redirect(
				add_query_arg(
					[
						'page'    => DDWCCF_DASHBOARD_PAGE,
						'menu'    => 'events',
						'status'  => 'delete' === $action ? 'event_deleted' : 'event_updated',
						'updated' => count( $ids ),
					],
					admin_url( 'admin.php' )
				)
			);
			exit();
		}

		/**
		 * Disabled CSV export button, a Pro feature.
		 *
		 * @return void
		 */
		protected static function locked_export_button() {
			?>
			<span class="ddfw-upgrade-to-pro-tag-wrapper ddwccf-locked-action">
				<button type="button" class="page-title-action button" disabled="disabled"><?php esc_html_e( 'Export CSV', 'devdiggers-crowdfunding-for-woocommerce' ); ?></button>
				<?php echo wp_kses_post( ddfw_get_pro_tag() ); ?>
			</span>
			<?php
		}

		/**
		 * Mark campaigns in the WooCommerce products list.
		 *
		 * @param array    $states States.
		 * @param \WP_Post $post   Post.
		 * @return array
		 */
		public function ddwccf_add_post_state( $states, $post ) {
			if ( 'product' === $post->post_type && DDWCCF_Event_Helper::is_event( $post->ID ) ) {
				$states['ddwccf'] = esc_html__( 'Crowdfunding Campaign', 'devdiggers-crowdfunding-for-woocommerce' );
			}

			return $states;
		}

		/**
		 * Send product edit links of campaigns to the campaign editor.
		 *
		 * @param string $link    Link.
		 * @param int    $post_id Post ID.
		 * @return string
		 */
		public function ddwccf_edit_post_link( $link, $post_id ) {
			return 'product' === get_post_type( $post_id ) && DDWCCF_Event_Helper::is_event( $post_id ) ? admin_url( 'admin.php?page=' . DDWCCF_DASHBOARD_PAGE . '&menu=events&action=edit&id=' . $post_id ) : $link;
		}

		/**
		 * Campaigns menu: list or editor.
		 *
		 * @return void
		 */
		public function ddwccf_get_events_list() {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only routing and status messages.
			$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
			$status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
			$id     = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
			// phpcs:enable WordPress.Security.NonceVerification.Recommended

			$messages = [
				'event_saved'   => __( 'Campaign saved.', 'devdiggers-crowdfunding-for-woocommerce' ),
				'event_deleted' => __( 'Campaign(s) moved to trash.', 'devdiggers-crowdfunding-for-woocommerce' ),
				'event_updated' => __( 'Campaign(s) updated.', 'devdiggers-crowdfunding-for-woocommerce' ),
			];

			if ( isset( $messages[ $status ] ) ) {
				$this->ddwccf_print_notification( esc_html( $messages[ $status ] ) );
			}

			if ( in_array( $action, [ 'add', 'edit' ], true ) ) {
				new Admin\Events\DDWCCF_Manage_Event_Template( $this->ddwccf_configuration, 'edit' === $action ? $id : 0 );
				return;
			}

			$list = new Admin\Events\DDWCCF_Events_List_Template( $this->ddwccf_configuration );
			?>
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Campaigns', 'devdiggers-crowdfunding-for-woocommerce' ); ?></h1>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . DDWCCF_DASHBOARD_PAGE . '&menu=events&action=add' ) ); ?>" class="page-title-action button button-primary"><?php esc_html_e( 'Add Campaign', 'devdiggers-crowdfunding-for-woocommerce' ); ?></a>
			<?php self::locked_export_button(); ?>
			<hr class="wp-header-end" />
			<?php $list->views(); ?>
			<form method="get">
				<input type="hidden" name="page" value="<?php echo esc_attr( DDWCCF_DASHBOARD_PAGE ); ?>" />
				<input type="hidden" name="menu" value="events" />
				<?php
				wp_nonce_field( 'ddwccf_nonce_action', 'ddwccf_nonce', false );
				$list->prepare_items();
				$list->search_box( esc_html__( 'Search', 'devdiggers-crowdfunding-for-woocommerce' ), 'ddwccf-events-search' );
				$list->display();
				?>
			</form>
			<?php
		}

		/**
		 * Contributions menu.
		 *
		 * @return void
		 */
		public function ddwccf_get_orders_list() {
			$list = new Admin\Orders\DDWCCF_Orders_List_Template();
			?>
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Contributions', 'devdiggers-crowdfunding-for-woocommerce' ); ?></h1>
			<?php self::locked_export_button(); ?>
			<hr class="wp-header-end" />
			<form method="get">
				<input type="hidden" name="page" value="<?php echo esc_attr( DDWCCF_DASHBOARD_PAGE ); ?>" />
				<input type="hidden" name="menu" value="orders" />
				<?php
				$list->prepare_items();
				$list->search_box( esc_html__( 'Search', 'devdiggers-crowdfunding-for-woocommerce' ), 'ddwccf-orders-search' );
				$list->display();
				?>
			</form>
			<?php
		}
	}
}
