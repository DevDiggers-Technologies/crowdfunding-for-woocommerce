<?php
/**
 * Crowdfunding setup wizard.
 *
 * @package DevDiggers Crowdfunding for WooCommerce
 * @version 1.0.0
 */

namespace DDWCCrowdFunding\Includes\Admin;

use DDWCCrowdFunding\Includes\DDWCCF_File_Handler;
use DDWCCrowdFunding\Helper\DDWCCF_Event_Helper;
use DevDiggers\Framework\Includes\DDFW_Form_Field;
use DevDiggers\Framework\Includes\DDFW_Setup_Wizard;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCCF_Setup_Wizard' ) ) {
	/**
	 * Setup wizard class.
	 */
	class DDWCCF_Setup_Wizard {
		/**
		 * Register wizard.
		 */
		public function __construct() {
			new DDFW_Setup_Wizard( $this->get_wizard_config() );
		}

		/**
		 * Saved option or its quick-start default.
		 *
		 * @param string $key Option key without prefix.
		 * @return mixed
		 */
		protected function value( $key ) {
			$defaults = DDWCCF_File_Handler::get_option_defaults();
			return get_option( '_ddwccf_' . $key, $defaults[ $key ] ?? '' );
		}

		/**
		 * Wizard settings.
		 *
		 * @return array
		 */
		public function get_wizard_config() {
			return [
				'plugin_slug'    => 'devdiggers-crowdfunding-for-woocommerce',
				'plugin_file'    => 'devdiggers-crowdfunding-for-woocommerce/functions.php',
				'dashboard_page' => DDWCCF_DASHBOARD_PAGE,
				'redirect_url'   => admin_url( 'admin.php?page=' . DDWCCF_DASHBOARD_PAGE ),
				'brand'          => [
					'name'        => __( 'Crowdfunding', 'devdiggers-crowdfunding-for-woocommerce' ),
					'description' => __( 'Welcome to Crowdfunding for WooCommerce! A few quick steps and your store is ready to run fundraising campaigns.', 'devdiggers-crowdfunding-for-woocommerce' ),
					'logo'        => '',
				],
				'steps'          => [
					'welcome'   => [
						'label'         => __( 'Welcome', 'devdiggers-crowdfunding-for-woocommerce' ),
						'view_callback' => [ $this, 'welcome' ],
					],
					'campaigns' => [
						'label'         => __( 'Campaigns', 'devdiggers-crowdfunding-for-woocommerce' ),
						'title'         => __( 'Campaign basics', 'devdiggers-crowdfunding-for-woocommerce' ),
						'description'   => __( 'Turn crowdfunding on and choose what happens when a campaign reaches its goal.', 'devdiggers-crowdfunding-for-woocommerce' ),
						'view_callback' => [ $this, 'campaigns' ],
						'save_callback' => [ $this, 'save_campaigns' ],
					],
					'giving'    => [
						'label'         => __( 'Contributions', 'devdiggers-crowdfunding-for-woocommerce' ),
						'title'         => __( 'How do supporters give?', 'devdiggers-crowdfunding-for-woocommerce' ),
						'description'   => __( 'The button, suggested amounts and supporter options. You can fine tune everything later.', 'devdiggers-crowdfunding-for-woocommerce' ),
						'view_callback' => [ $this, 'giving' ],
						'save_callback' => [ $this, 'save_giving' ],
					],
					'ready'     => [
						'label'             => __( 'Ready!', 'devdiggers-crowdfunding-for-woocommerce' ),
						'ready_title'       => __( 'Congratulations! Crowdfunding is ready.', 'devdiggers-crowdfunding-for-woocommerce' ),
						'ready_description' => __( 'Create your first campaign, add [ddwccf_campaigns] to a page, and follow every contribution from the Dashboard.', 'devdiggers-crowdfunding-for-woocommerce' ),
					],
				],
			];
		}

		/**
		 * Welcome step.
		 *
		 * @return void
		 */
		public function welcome() {
			?>
			<div class="ddfw-setup-wizard-ready ddfw-setup-wizard-onboarding">
				<div class="ddfw-success-icon-wrap">
					<?php echo wp_kses( str_replace( '<svg ', '<svg class="ddfw-success-svg" ', DDWCCF_Event_Helper::get_brand_icon( 100 ) ), ddfw_kses_allowed_svg_tags() ); ?>
				</div>
				<h2 class="ddfw-setup-wizard-ready-title"><?php esc_html_e( 'Welcome to Crowdfunding!', 'devdiggers-crowdfunding-for-woocommerce' ); ?></h2>
				<p class="ddfw-setup-wizard-ready-desc"><?php esc_html_e( 'Launch goal based campaigns with rewards, a live progress card and a supporters wall, and collect contributions through your normal WooCommerce checkout.', 'devdiggers-crowdfunding-for-woocommerce' ); ?></p>
			</div>
			<?php
		}

		/**
		 * Render fields.
		 *
		 * @param array $fields Field definitions.
		 * @return void
		 */
		private function render_fields( $fields ) {
			?>
			<div class="ddfw-fields-section"><table class="form-table"><tbody>
			<?php
			foreach ( $fields as $field ) {
				DDFW_Form_Field::display_form_field( $field );
			}
			?>
			</tbody></table></div>
			<?php
		}

		/**
		 * Campaigns step.
		 *
		 * @return void
		 */
		public function campaigns() {
			$this->render_fields(
				[
					[
						'type'           => 'checkbox',
						'name'           => '_ddwccf_enabled',
						'label'          => __( 'Status', 'devdiggers-crowdfunding-for-woocommerce' ),
						'checkbox_label' => __( 'Enable Crowdfunding', 'devdiggers-crowdfunding-for-woocommerce' ),
						'description'    => __( 'Show campaigns and accept contributions.', 'devdiggers-crowdfunding-for-woocommerce' ),
						'value'          => $this->value( 'enabled' ),
					],
					[
						'type'           => 'checkbox',
						'name'           => '_ddwccf_close_on_goal',
						'label'          => __( 'Close on goal', 'devdiggers-crowdfunding-for-woocommerce' ),
						'checkbox_label' => __( 'Stop contributions once the goal is reached', 'devdiggers-crowdfunding-for-woocommerce' ),
						'description'    => __( 'Turn off to let campaigns raise more than their goal until the end date.', 'devdiggers-crowdfunding-for-woocommerce' ),
						'value'          => $this->value( 'close_on_goal' ),
					],
				]
			);
		}

		/**
		 * Contributions step.
		 *
		 * @return void
		 */
		public function giving() {
			$this->render_fields(
				[
					[
						'type'        => 'text',
						'name'        => '_ddwccf_button_label',
						'label'       => __( 'Button label', 'devdiggers-crowdfunding-for-woocommerce' ),
						'description' => __( 'For example "Back this campaign" or "Donate now".', 'devdiggers-crowdfunding-for-woocommerce' ),
						'value'       => $this->value( 'button_label' ),
					],
					[
						'type'        => 'text',
						'name'        => '_ddwccf_preset_amounts',
						'label'       => __( 'Suggested amounts', 'devdiggers-crowdfunding-for-woocommerce' ),
						'description' => __( 'Comma separated quick pick amounts, for example 10, 25, 50, 100.', 'devdiggers-crowdfunding-for-woocommerce' ),
						'value'       => $this->value( 'preset_amounts' ),
					],
					[
						'type'           => 'checkbox',
						'name'           => '_ddwccf_allow_anonymous',
						'label'          => __( 'Anonymous giving', 'devdiggers-crowdfunding-for-woocommerce' ),
						'checkbox_label' => __( 'Let supporters hide their name from the public list', 'devdiggers-crowdfunding-for-woocommerce' ),
						'value'          => $this->value( 'allow_anonymous' ),
					],
					[
						'type'           => 'checkbox',
						'name'           => '_ddwccf_allow_messages',
						'label'          => __( 'Messages', 'devdiggers-crowdfunding-for-woocommerce' ),
						'checkbox_label' => __( 'Let supporters leave a public message', 'devdiggers-crowdfunding-for-woocommerce' ),
						'value'          => $this->value( 'allow_messages' ),
					],
				]
			);
		}

		/**
		 * Save campaigns step.
		 *
		 * @param array $form_data Submitted fields.
		 * @return true
		 */
		public function save_campaigns( $form_data ) {
			$values = array_column( (array) $form_data, 'value', 'name' );
			$admin  = new DDWCCF_Admin_Functions( [] );

			foreach ( [ '_ddwccf_enabled', '_ddwccf_close_on_goal' ] as $option ) {
				update_option( $option, $admin->sanitize_checkbox( $values[ $option ] ?? '' ) );
			}

			return true;
		}

		/**
		 * Save contributions step.
		 *
		 * @param array $form_data Submitted fields.
		 * @return true
		 */
		public function save_giving( $form_data ) {
			$values = array_column( (array) $form_data, 'value', 'name' );
			$admin  = new DDWCCF_Admin_Functions( [] );

			if ( isset( $values['_ddwccf_button_label'] ) ) {
				update_option( '_ddwccf_button_label', sanitize_text_field( $values['_ddwccf_button_label'] ) );
			}
			if ( isset( $values['_ddwccf_preset_amounts'] ) ) {
				update_option( '_ddwccf_preset_amounts', $admin->sanitize_preset_amounts( $values['_ddwccf_preset_amounts'] ) );
			}

			foreach ( [ '_ddwccf_allow_anonymous', '_ddwccf_allow_messages' ] as $option ) {
				update_option( $option, $admin->sanitize_checkbox( $values[ $option ] ?? '' ) );
			}

			return true;
		}
	}
}
