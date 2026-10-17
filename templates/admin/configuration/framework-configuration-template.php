<?php
/**
 * Configuration tabs rendered with the framework form layout.
 * Each tab posts to its own settings group (DDWCCF_Admin_Functions::get_settings_map()).
 *
 * @package DevDiggers Crowdfunding for WooCommerce
 * @version 1.0.0
 */

namespace DDWCCrowdFunding\Templates\Admin\Configuration;

use DDWCCrowdFunding\Includes\DDWCCF_File_Handler;
use DDWCCrowdFunding\Includes\DDWCCF_Admin_Dashboard;
use DDWCCrowdFunding\Helper\DDWCCF_Email_Helper;
use DevDiggers\Framework\Includes\DDFW_Layout;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCCF_Framework_Configuration_Template' ) ) {
	/**
	 * Configuration template class.
	 */
	class DDWCCF_Framework_Configuration_Template {
		/**
		 * Saved option or its quick-start default.
		 *
		 * @param string $key Option key without the prefix.
		 * @return mixed
		 */
		protected function value( $key ) {
			$defaults = DDWCCF_File_Handler::get_option_defaults();

			return get_option( '_ddwccf_' . $key, $defaults[ $key ] ?? '' );
		}

		/**
		 * Checkbox field.
		 *
		 * @param string $key         Option key without the prefix.
		 * @param string $label       Label.
		 * @param string $text        Checkbox text.
		 * @param string $description Description.
		 * @return array
		 */
		protected function checkbox( $key, $label, $text, $description ) {
			return [
				'type'           => 'checkbox',
				'name'           => '_ddwccf_' . $key,
				'id'             => 'ddwccf-' . str_replace( '_', '-', $key ),
				'label'          => $label,
				'checkbox_label' => $text,
				'description'    => $description,
				'value'          => $this->value( $key ),
			];
		}

		/**
		 * Text or number field.
		 *
		 * @param string $key         Option key without the prefix.
		 * @param string $label       Label.
		 * @param string $description Description.
		 * @param string $type        Field type.
		 * @param array  $extra       Extra field args.
		 * @return array
		 */
		protected function input( $key, $label, $description, $type = 'text', $extra = [] ) {
			return array_merge(
				[
					'type'        => $type,
					'name'        => '_ddwccf_' . $key,
					'id'          => 'ddwccf-' . str_replace( '_', '-', $key ),
					'label'       => $label,
					'description' => $description,
					'value'       => $this->value( $key ),
				],
				$extra
			);
		}

		/**
		 * Render sections into a settings form.
		 *
		 * @param array  $sections Sections.
		 * @param string $group    Settings group.
		 * @return void
		 */
		protected function render( $sections, $group ) {
			( new DDFW_Layout() )->get_form_section_layout( $sections, $group );
		}

		/**
		 * Disabled copy of a Pro setting.
		 *
		 * @param array $field Field arguments.
		 * @return array
		 */
		protected function locked( $field ) {
			return ddfw_locked_field( $field, 'ddwccf' );
		}

		/**
		 * Upgrade card markup for a section that is Pro from top to bottom.
		 *
		 * @param string $image    Screenshot name in assets/images/pro/.
		 * @param string $heading  Card heading.
		 * @param string $text     Card description.
		 * @param array  $features Feature bullets.
		 * @return string
		 */
		protected function upgrade_card( $image, $heading, $text, $features ) {
			return ddfw_get_upgrade_to_pro_section(
				[
					'image_url'     => DDWCCF_Admin_Dashboard::get_pro_image( $image ),
					'heading'       => $heading,
					'description'   => $text,
					'list_features' => $features,
					'upgrade_url'   => DDWCCF_Admin_Dashboard::UPGRADE_URL,
				]
			);
		}

		/**
		 * A configuration tab that is Pro only: section header with the upgrade card.
		 *
		 * @param string $title    Section heading.
		 * @param string $intro    Section description.
		 * @param string $image    Screenshot name.
		 * @param string $heading  Card heading.
		 * @param string $text     Card description.
		 * @param array  $features Feature bullets.
		 * @return void
		 */
		protected function pro_tab( $title, $intro, $image, $heading, $text, $features ) {
			( new DDFW_Layout() )->get_form_section_layout(
				[
					[
						'header'            => [
							'heading'     => $title,
							'description' => $intro,
						],
						'after_header_html' => $this->upgrade_card( $image, $heading, $text, $features ),
					],
				]
			);
		}

		/**
		 * General tab.
		 *
		 * @return void
		 */
		public function general() {
			$this->render(
				[
					[
						'header' => [
							'heading'     => __( 'Crowdfunding', 'devdiggers-crowdfunding-for-woocommerce' ),
							'description' => __( 'Turn crowdfunding on for your store.', 'devdiggers-crowdfunding-for-woocommerce' ),
						],
						'fields' => [
							$this->checkbox( 'enabled', __( 'Enable Crowdfunding', 'devdiggers-crowdfunding-for-woocommerce' ), __( 'Show campaigns and accept contributions', 'devdiggers-crowdfunding-for-woocommerce' ), __( 'When off, campaign pages stay visible but nobody can contribute, and the Backed Campaigns page is hidden.', 'devdiggers-crowdfunding-for-woocommerce' ) ),
							$this->input( 'admin_email', __( 'Notification email', 'devdiggers-crowdfunding-for-woocommerce' ), __( 'Gets a copy of the Goal Reached and Campaign Ended emails. Leave empty to use the site admin email.', 'devdiggers-crowdfunding-for-woocommerce' ), 'email', [ 'placeholder' => get_option( 'admin_email' ) ] ),
						],
					],
					[
						'header'            => [
							'heading'     => __( 'Who Can Start Campaigns', 'devdiggers-crowdfunding-for-woocommerce' ),
							'description' => __( 'You run every campaign from this dashboard. Pro also lets customers raise money for their own causes.', 'devdiggers-crowdfunding-for-woocommerce' ),
						],
						'after_header_html' => $this->upgrade_card(
							'customer-campaigns',
							__( 'Turn your store into a fundraising platform with Pro', 'devdiggers-crowdfunding-for-woocommerce' ),
							__( 'Customers start and run their own campaigns from My Account, and you approve them and pay them out.', 'devdiggers-crowdfunding-for-woocommerce' ),
							[
								__( 'Campaign editor in My Account with image upload', 'devdiggers-crowdfunding-for-woocommerce' ),
								__( 'Approve new campaigns before they go live', 'devdiggers-crowdfunding-for-woocommerce' ),
								__( 'Exclude roles and limit how long campaigns run', 'devdiggers-crowdfunding-for-woocommerce' ),
								__( 'Creators see their supporters and request payouts', 'devdiggers-crowdfunding-for-woocommerce' ),
							]
						),
					],
				],
				'ddwccf-general-fields'
			);
		}

		/**
		 * Campaigns tab.
		 *
		 * @return void
		 */
		public function campaigns() {
			$this->pro_tab(
				__( 'Campaign Defaults', 'devdiggers-crowdfunding-for-woocommerce' ),
				__( 'Free campaigns are flexible: every contribution counts as soon as it is paid.', 'devdiggers-crowdfunding-for-woocommerce' ),
				'campaign-defaults',
				__( 'Run Kickstarter style campaigns with Pro', 'devdiggers-crowdfunding-for-woocommerce' ),
				__( 'All or nothing funding, stretch goals and updates give supporters a reason to give more and share again.', 'devdiggers-crowdfunding-for-woocommerce' ),
				[
					__( 'All or nothing funding with one click refunds when a goal is missed', 'devdiggers-crowdfunding-for-woocommerce' ),
					__( 'Stretch goals marked on the progress bar', 'devdiggers-crowdfunding-for-woocommerce' ),
					__( 'Campaign updates emailed to every supporter in the background', 'devdiggers-crowdfunding-for-woocommerce' ),
					__( 'Unlimited reward tiers (Free has three per campaign)', 'devdiggers-crowdfunding-for-woocommerce' ),
					__( 'Campaign categories and featured campaigns', 'devdiggers-crowdfunding-for-woocommerce' ),
				]
			);
		}

		/**
		 * Contributions tab.
		 *
		 * @return void
		 */
		public function contributions() {
			$gateways = [];
			foreach ( WC()->payment_gateways()->payment_gateways() as $key => $gateway ) {
				if ( 'yes' === $gateway->enabled ) {
					$gateways[ $key ] = $gateway->get_method_title() ? $gateway->get_method_title() : $gateway->get_title();
				}
			}

			$currency = html_entity_decode( get_woocommerce_currency_symbol() );

			$this->render(
				[
					[
						'header'            => [
							'heading'     => __( 'Contribution Form', 'devdiggers-crowdfunding-for-woocommerce' ),
							'description' => __( 'How supporters choose and pay their contribution on the campaign page.', 'devdiggers-crowdfunding-for-woocommerce' ),
						],
						'after_header_html' => '<input type="hidden" name="_ddwccf_enabled_payment_gateways[]" value="" />',
						'fields'            => [
							$this->input( 'button_label', __( 'Button label', 'devdiggers-crowdfunding-for-woocommerce' ), __( 'Text of the contribute button, for example "Back this campaign", "Donate now" or "Pledge".', 'devdiggers-crowdfunding-for-woocommerce' ) ),
							/* translators: %s: currency symbol. */
							$this->input( 'preset_amounts', sprintf( __( 'Suggested amounts (%s)', 'devdiggers-crowdfunding-for-woocommerce' ), $currency ), __( 'Comma separated quick pick buttons, for example 10, 25, 50, 100. The first one is preselected. Amounts below a campaign minimum are hidden.', 'devdiggers-crowdfunding-for-woocommerce' ), 'text', [ 'placeholder' => '10, 25, 50, 100' ] ),
							$this->input(
								'max_contribution',
								/* translators: %s: currency symbol. */
								sprintf( __( 'Maximum contribution (%s)', 'devdiggers-crowdfunding-for-woocommerce' ), $currency ),
								__( 'Highest amount accepted in one contribution, a simple guard against typos and fraud. Leave empty for no limit.', 'devdiggers-crowdfunding-for-woocommerce' ),
								'number',
								[
									'custom_attributes' => [
										'min'  => '0',
										'step' => 'any',
									],
								]
							),
							[
								'type'              => 'select',
								'name'              => '_ddwccf_enabled_payment_gateways[]',
								'id'                => 'ddwccf-enabled-payment-gateways',
								'label'             => __( 'Payment gateways', 'devdiggers-crowdfunding-for-woocommerce' ),
								'description'       => __( 'Gateways offered when the cart holds a contribution. Leave empty to allow every enabled gateway.', 'devdiggers-crowdfunding-for-woocommerce' ),
								'options'           => $gateways,
								'custom_attributes' => [
									'multiple'         => 'multiple',
									'data-placeholder' => __( 'All enabled gateways', 'devdiggers-crowdfunding-for-woocommerce' ),
								],
								'value'             => (array) $this->value( 'enabled_payment_gateways' ),
							],
						],
					],
					[
						'header' => [
							'heading'     => __( 'Supporter Options', 'devdiggers-crowdfunding-for-woocommerce' ),
							'description' => __( 'Small touches that raise conversion and the average contribution.', 'devdiggers-crowdfunding-for-woocommerce' ),
						],
						'fields' => [
							$this->checkbox( 'allow_anonymous', __( 'Anonymous giving', 'devdiggers-crowdfunding-for-woocommerce' ), __( 'Let supporters hide their name from the public list', 'devdiggers-crowdfunding-for-woocommerce' ), __( 'You still see who they are.', 'devdiggers-crowdfunding-for-woocommerce' ) ),
							$this->checkbox( 'allow_messages', __( 'Messages', 'devdiggers-crowdfunding-for-woocommerce' ), __( 'Let supporters leave a public message', 'devdiggers-crowdfunding-for-woocommerce' ), __( 'Shown on the supporters wall and in the organiser email. Up to 300 characters.', 'devdiggers-crowdfunding-for-woocommerce' ) ),
							$this->locked(
								[
									'type'           => 'checkbox',
									'id'             => 'cover-fee',
									'label'          => __( 'Cover the fee', 'devdiggers-crowdfunding-for-woocommerce' ),
									'checkbox_label' => __( 'Offer supporters to add the platform fee on top', 'devdiggers-crowdfunding-for-woocommerce' ),
									'description'    => __( 'Supporters pay the platform fee so the creator receives the full gift.', 'devdiggers-crowdfunding-for-woocommerce' ),
									'value'          => 'yes',
								]
							),
						],
					],
					[
						'header' => [
							'heading'     => __( 'Goal Reached', 'devdiggers-crowdfunding-for-woocommerce' ),
							'description' => __( 'What happens when a campaign hits its goal.', 'devdiggers-crowdfunding-for-woocommerce' ),
						],
						'fields' => [
							$this->checkbox( 'close_on_goal', __( 'Close on goal', 'devdiggers-crowdfunding-for-woocommerce' ), __( 'Stop contributions once the goal is reached', 'devdiggers-crowdfunding-for-woocommerce' ), __( 'Turn off to let campaigns raise more than their goal until their end date.', 'devdiggers-crowdfunding-for-woocommerce' ) ),
						],
					],
				],
				'ddwccf-contributions-fields'
			);
		}

		/**
		 * Payouts and fees tab.
		 *
		 * @return void
		 */
		public function withdrawals() {
			$this->pro_tab(
				__( 'Payouts & Fees', 'devdiggers-crowdfunding-for-woocommerce' ),
				__( 'Earn a commission on every campaign and pay creators out from one screen.', 'devdiggers-crowdfunding-for-woocommerce' ),
				'payouts',
				__( 'Earn from every campaign with Pro', 'devdiggers-crowdfunding-for-woocommerce' ),
				__( 'Take a platform fee, let creators request their money once a campaign closes, and pay them in one click.', 'devdiggers-crowdfunding-for-woocommerce' ),
				[
					__( 'Platform fee as a percentage or fixed amount', 'devdiggers-crowdfunding-for-woocommerce' ),
					__( 'Fee override per campaign, for example to waive it for a charity', 'devdiggers-crowdfunding-for-woocommerce' ),
					__( 'Supporters can cover the fee so creators get the full gift', 'devdiggers-crowdfunding-for-woocommerce' ),
					__( 'Payout requests you mark as paid or reject', 'devdiggers-crowdfunding-for-woocommerce' ),
					__( 'One click payouts into the creator\'s DevDiggers Wallet', 'devdiggers-crowdfunding-for-woocommerce' ),
				]
			);
		}

		/**
		 * Display tab.
		 *
		 * @return void
		 */
		public function display() {
			$this->render(
				[
					[
						'header' => [
							'heading'     => __( 'Campaign Page', 'devdiggers-crowdfunding-for-woocommerce' ),
							'description' => __( 'Social proof and urgency shown on every campaign.', 'devdiggers-crowdfunding-for-woocommerce' ),
						],
						'fields' => [
							$this->input( 'accent_color', __( 'Accent color', 'devdiggers-crowdfunding-for-woocommerce' ), __( 'Used for the progress bar, selected amounts and highlights.', 'devdiggers-crowdfunding-for-woocommerce' ), 'colorpicker' ),
							$this->checkbox( 'campaign_tabs', __( 'Campaign tabs', 'devdiggers-crowdfunding-for-woocommerce' ), __( 'Show Story, Rewards and Supporters tabs', 'devdiggers-crowdfunding-for-woocommerce' ), __( 'Replaces the product tabs on campaign pages. Turn off to keep your theme tabs.', 'devdiggers-crowdfunding-for-woocommerce' ) ),
							$this->checkbox( 'show_countdown', __( 'Countdown', 'devdiggers-crowdfunding-for-woocommerce' ), __( 'Show a live countdown to the end date', 'devdiggers-crowdfunding-for-woocommerce' ), __( 'Shown on campaigns that have an end date.', 'devdiggers-crowdfunding-for-woocommerce' ) ),
							$this->checkbox( 'show_supporters', __( 'Supporters wall', 'devdiggers-crowdfunding-for-woocommerce' ), __( 'List recent supporters and their messages', 'devdiggers-crowdfunding-for-woocommerce' ), __( 'Shows first name and last initial only. Emails stay private.', 'devdiggers-crowdfunding-for-woocommerce' ) ),
							$this->input(
								'supporters_count',
								__( 'Supporters to show', 'devdiggers-crowdfunding-for-woocommerce' ),
								__( 'How many recent supporters to list, from 1 to 50.', 'devdiggers-crowdfunding-for-woocommerce' ),
								'number',
								[
									'custom_attributes' => [
										'min' => '1',
										'max' => '50',
									],
								]
							),
						],
					],
					[
						'header'            => [
							'heading'     => __( 'Sharing', 'devdiggers-crowdfunding-for-woocommerce' ),
							'description' => __( 'Most contributions come from people the creator shares with. Make it one click.', 'devdiggers-crowdfunding-for-woocommerce' ),
						],
						'after_header_html' => '<input type="hidden" name="_ddwccf_share_networks[]" value="" />',
						'fields'            => [
							$this->checkbox( 'show_share', __( 'Share buttons', 'devdiggers-crowdfunding-for-woocommerce' ), __( 'Show share buttons on campaign pages', 'devdiggers-crowdfunding-for-woocommerce' ), __( 'Opens the network share dialog in a new tab. No tracking scripts are loaded.', 'devdiggers-crowdfunding-for-woocommerce' ) ),
							[
								'type'              => 'select',
								'name'              => '_ddwccf_share_networks[]',
								'id'                => 'ddwccf-share-networks',
								'label'             => __( 'Networks', 'devdiggers-crowdfunding-for-woocommerce' ),
								'description'       => __( 'Buttons to show, in this order.', 'devdiggers-crowdfunding-for-woocommerce' ),
								'options'           => [
									'facebook' => __( 'Facebook', 'devdiggers-crowdfunding-for-woocommerce' ),
									'x'        => __( 'X', 'devdiggers-crowdfunding-for-woocommerce' ),
									'whatsapp' => __( 'WhatsApp', 'devdiggers-crowdfunding-for-woocommerce' ),
									'linkedin' => __( 'LinkedIn', 'devdiggers-crowdfunding-for-woocommerce' ),
									'email'    => __( 'Email', 'devdiggers-crowdfunding-for-woocommerce' ),
									'copy'     => __( 'Copy link', 'devdiggers-crowdfunding-for-woocommerce' ),
								],
								'custom_attributes' => [ 'multiple' => 'multiple' ],
								'value'             => (array) $this->value( 'share_networks' ),
							],
							$this->locked(
								[
									'type'           => 'checkbox',
									'id'             => 'show-embed',
									'label'          => __( 'Embed', 'devdiggers-crowdfunding-for-woocommerce' ),
									'checkbox_label' => __( 'Let visitors copy an embed code for other websites', 'devdiggers-crowdfunding-for-woocommerce' ),
									'description'    => __( 'Puts a live campaign card on blogs and partner sites.', 'devdiggers-crowdfunding-for-woocommerce' ),
									'value'          => 'yes',
								]
							),
							$this->locked(
								[
									'type'        => 'select',
									'id'          => 'share-shape',
									'label'       => __( 'Button shape', 'devdiggers-crowdfunding-for-woocommerce' ),
									'description' => __( 'Round icons suit most themes. Pick square to match sharp cornered designs.', 'devdiggers-crowdfunding-for-woocommerce' ),
									'options'     => [
										'circle'  => __( 'Circle', 'devdiggers-crowdfunding-for-woocommerce' ),
										'rounded' => __( 'Rounded square', 'devdiggers-crowdfunding-for-woocommerce' ),
										'square'  => __( 'Square', 'devdiggers-crowdfunding-for-woocommerce' ),
									],
									'value'       => 'circle',
								]
							),
							$this->locked(
								[
									'type'        => 'select',
									'id'          => 'share-style',
									'label'       => __( 'Button style', 'devdiggers-crowdfunding-for-woocommerce' ),
									'description' => __( 'Outline is quiet and neutral, Filled uses your accent color and Brand colors uses each network\'s own color.', 'devdiggers-crowdfunding-for-woocommerce' ),
									'options'     => [
										'outline' => __( 'Outline', 'devdiggers-crowdfunding-for-woocommerce' ),
										'filled'  => __( 'Filled with accent color', 'devdiggers-crowdfunding-for-woocommerce' ),
										'brand'   => __( 'Brand colors', 'devdiggers-crowdfunding-for-woocommerce' ),
									],
									'value'       => 'outline',
								]
							),
							$this->locked(
								[
									'type'        => 'select',
									'id'          => 'share-size',
									'label'       => __( 'Button size', 'devdiggers-crowdfunding-for-woocommerce' ),
									'description' => __( 'Larger buttons are easier to tap on phones.', 'devdiggers-crowdfunding-for-woocommerce' ),
									'options'     => [
										'small'  => __( 'Small', 'devdiggers-crowdfunding-for-woocommerce' ),
										'medium' => __( 'Medium', 'devdiggers-crowdfunding-for-woocommerce' ),
										'large'  => __( 'Large', 'devdiggers-crowdfunding-for-woocommerce' ),
									],
									'value'       => 'medium',
								]
							),
							$this->locked(
								[
									'type'           => 'checkbox',
									'id'             => 'share-labels',
									'label'          => __( 'Labels', 'devdiggers-crowdfunding-for-woocommerce' ),
									'checkbox_label' => __( 'Show the network name next to each icon', 'devdiggers-crowdfunding-for-woocommerce' ),
									'description'    => __( 'Clearer for visitors who do not recognise every icon, but takes more room.', 'devdiggers-crowdfunding-for-woocommerce' ),
									'value'          => '',
								]
							),
						],
					],
					[
						'header' => [
							'heading'     => __( 'My Account', 'devdiggers-crowdfunding-for-woocommerce' ),
							'description' => __( 'The page in My Account where supporters follow campaigns they backed.', 'devdiggers-crowdfunding-for-woocommerce' ),
						],
						'fields' => [
							$this->input( 'backed_endpoint_title', __( 'Backed campaigns title', 'devdiggers-crowdfunding-for-woocommerce' ), __( 'Menu name of the page where supporters follow campaigns they backed.', 'devdiggers-crowdfunding-for-woocommerce' ) ),
							$this->input( 'backed_endpoint', __( 'Backed campaigns URL', 'devdiggers-crowdfunding-for-woocommerce' ), __( 'URL slug of that page.', 'devdiggers-crowdfunding-for-woocommerce' ) ),
							$this->checkbox( 'enable_widgets_my_account_endpoint', __( 'Sidebar widgets', 'devdiggers-crowdfunding-for-woocommerce' ), __( 'Keep the theme sidebar on this page', 'devdiggers-crowdfunding-for-woocommerce' ), __( 'Leave off for a wider page.', 'devdiggers-crowdfunding-for-woocommerce' ) ),
							$this->locked(
								[
									'type'        => 'text',
									'id'          => 'my-account-endpoint-title',
									'label'       => __( 'Campaign manager title', 'devdiggers-crowdfunding-for-woocommerce' ),
									'description' => __( 'The My Account page where customers create and manage their own campaigns.', 'devdiggers-crowdfunding-for-woocommerce' ),
									'value'       => __( 'My Campaigns', 'devdiggers-crowdfunding-for-woocommerce' ),
								]
							),
						],
					],
				],
				'ddwccf-display-fields'
			);
		}

		/**
		 * Emails tab: the Free notifications with their fixed wording, then the Pro ones.
		 *
		 * @return void
		 */
		public function emails() {
			$name     = DDWCCF_Email_Helper::OPTION;
			$plain    = fn( $html ) => trim( wp_strip_all_tags( $html ) );
			$sections = [
				[
					'after_header_html' => '<div class="ddwccf-help-card"><p>' . sprintf(
						/* translators: %s: link to WooCommerce email settings. */
						esc_html__( 'Every notification uses your WooCommerce email design. Change the header image, colors and footer in %s.', 'devdiggers-crowdfunding-for-woocommerce' ),
						'<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=email' ) ) . '">' . esc_html__( 'WooCommerce > Settings > Emails', 'devdiggers-crowdfunding-for-woocommerce' ) . '</a>'
					) . '</p></div>',
				],
			];

			$recipients = [
				'creator'   => __( 'Organiser', 'devdiggers-crowdfunding-for-woocommerce' ),
				'supporter' => __( 'Supporter', 'devdiggers-crowdfunding-for-woocommerce' ),
			];

			foreach ( DDWCCF_Email_Helper::get_types() as $type => $email ) {
				$settings   = DDWCCF_Email_Helper::get_settings( $type );
				$id         = 'email-' . str_replace( '_', '-', $type );
				$sections[] = [
					'header' => [
						'heading'     => $email['title'],
						/* translators: 1: description, 2: recipient. */
						'description' => sprintf( __( '%1$s Recipient: %2$s.', 'devdiggers-crowdfunding-for-woocommerce' ), $email['description'], $recipients[ $email['recipient'] ] ),
					],
					'fields' => [
						[
							'type'           => 'checkbox',
							'name'           => $name . '[' . $type . '][enabled]',
							'id'             => 'ddwccf-' . $id . '-enabled',
							'label'          => __( 'Status', 'devdiggers-crowdfunding-for-woocommerce' ),
							'checkbox_label' => __( 'Send this email', 'devdiggers-crowdfunding-for-woocommerce' ),
							'value'          => $settings['enabled'],
						],
						$this->locked(
							[
								'type'  => 'text',
								'id'    => $id . '-subject',
								'label' => __( 'Subject', 'devdiggers-crowdfunding-for-woocommerce' ),
								'value' => $settings['subject'],
							]
						),
						$this->locked(
							[
								'type'  => 'text',
								'id'    => $id . '-heading',
								'label' => __( 'Heading', 'devdiggers-crowdfunding-for-woocommerce' ),
								'value' => $settings['heading'],
							]
						),
						$this->locked(
							[
								'type'              => 'textarea',
								'id'                => $id . '-message',
								'label'             => __( 'Email Message', 'devdiggers-crowdfunding-for-woocommerce' ),
								'description'       => __( 'Free sends this wording. Pro gives every email a full editor with placeholders.', 'devdiggers-crowdfunding-for-woocommerce' ),
								'value'             => $plain( $settings['message'] ),
								'custom_attributes' => [ 'rows' => 6 ],
							]
						),
					],
				];
			}

			$sections[] = [
				'header'            => [
					'heading'     => __( 'More Notifications', 'devdiggers-crowdfunding-for-woocommerce' ),
					'description' => __( 'Emails for customer campaigns, campaign updates and payouts.', 'devdiggers-crowdfunding-for-woocommerce' ),
				],
				'after_header_html' => $this->upgrade_card(
					'emails',
					__( 'Write every email your way with Pro', 'devdiggers-crowdfunding-for-woocommerce' ),
					__( 'A full editor and placeholders for all ten notifications, including six that only Pro sends.', 'devdiggers-crowdfunding-for-woocommerce' ),
					[
						__( 'Campaign submitted and campaign approved', 'devdiggers-crowdfunding-for-woocommerce' ),
						__( 'Campaign updates to every supporter', 'devdiggers-crowdfunding-for-woocommerce' ),
						__( 'Payout requested, paid and rejected', 'devdiggers-crowdfunding-for-woocommerce' ),
						__( 'Your own subject, heading and message on every email', 'devdiggers-crowdfunding-for-woocommerce' ),
					]
				),
			];

			$this->render( $sections, 'ddwccf-emails-fields' );
		}

		/**
		 * Shortcodes reference.
		 *
		 * @return void
		 */
		public function shortcodes() {
			$shortcodes = [
				[
					'title'       => __( 'Campaigns Grid', 'devdiggers-crowdfunding-for-woocommerce' ),
					'tag'         => 'ddwccf_campaigns',
					'description' => __( 'A grid of campaign cards. Campaigns are hidden from the shop, so put this on a page to give them a home.', 'devdiggers-crowdfunding-for-woocommerce' ),
					'example'     => '[ddwccf_campaigns columns="3" orderby="ending"]',
					'attributes'  => [
						[ 'limit', '12', __( 'Campaigns per page.', 'devdiggers-crowdfunding-for-woocommerce' ) ],
						[ 'columns', '3', __( 'Cards per row, 1 to 6.', 'devdiggers-crowdfunding-for-woocommerce' ) ],
						[ 'orderby', 'date', __( 'date, ending, popular, raised or funded.', 'devdiggers-crowdfunding-for-woocommerce' ) ],
						[ 'status', 'active', __( 'active, funded, ended or all. Combine with commas.', 'devdiggers-crowdfunding-for-woocommerce' ) ],
						[ 'ids', '', __( 'Hand picked campaign IDs, comma separated.', 'devdiggers-crowdfunding-for-woocommerce' ) ],
						[ 'paginate', 'yes', __( 'Show page links when there are more campaigns than the limit. yes or no.', 'devdiggers-crowdfunding-for-woocommerce' ) ],
						[ 'filters', 'no', __( 'Search, category, status and sort filters for visitors.', 'devdiggers-crowdfunding-for-woocommerce' ), true ],
						[ 'featured', 'no', __( 'Only campaigns marked as featured.', 'devdiggers-crowdfunding-for-woocommerce' ), true ],
						[ 'category', '', __( 'Campaigns of one category.', 'devdiggers-crowdfunding-for-woocommerce' ), true ],
						[ 'author', '', __( 'Campaigns of one creator.', 'devdiggers-crowdfunding-for-woocommerce' ), true ],
					],
				],
				[
					'title'       => __( 'Single Campaign Card', 'devdiggers-crowdfunding-for-woocommerce' ),
					'tag'         => 'ddwccf_campaign',
					'description' => __( 'The progress card of one campaign, for landing pages and blog posts.', 'devdiggers-crowdfunding-for-woocommerce' ),
					'example'     => '[ddwccf_campaign id="123"]',
					'attributes'  => [
						[ 'id', '', __( 'Campaign ID. Required.', 'devdiggers-crowdfunding-for-woocommerce' ) ],
					],
				],
			];

			ob_start();
			?>
			<div class="ddwccf-shortcodes">
				<?php foreach ( $shortcodes as $shortcode ) : ?>
					<div class="ddwccf-shortcode-card">
						<div class="ddwccf-shortcode-card__head">
							<h3><?php echo esc_html( $shortcode['title'] ); ?></h3>
							<div class="ddwccf-shortcode-card__copy">
								<code>[<?php echo esc_html( $shortcode['tag'] ); ?>]</code>
								<button type="button" class="button ddwccf-copy" data-copy="[<?php echo esc_attr( $shortcode['tag'] ); ?>]"><?php esc_html_e( 'Copy', 'devdiggers-crowdfunding-for-woocommerce' ); ?></button>
							</div>
						</div>
						<p><?php echo esc_html( $shortcode['description'] ); ?></p>
						<div class="ddfw-table-wrapper">
							<table class="widefat fixed striped ddfw-table">
								<thead>
									<tr>
										<th><?php esc_html_e( 'Attribute', 'devdiggers-crowdfunding-for-woocommerce' ); ?></th>
										<th><?php esc_html_e( 'Default', 'devdiggers-crowdfunding-for-woocommerce' ); ?></th>
										<th><?php esc_html_e( 'Description', 'devdiggers-crowdfunding-for-woocommerce' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $shortcode['attributes'] as $attr ) : ?>
										<tr>
											<td><code><?php echo esc_html( $attr[0] ); ?></code><?php echo empty( $attr[3] ) ? '' : ' ' . wp_kses_post( ddfw_get_pro_tag() ); ?></td>
											<td><?php echo '' !== $attr[1] ? esc_html( $attr[1] ) : '<span aria-hidden="true">&mdash;</span>'; ?></td>
											<td><?php echo esc_html( $attr[2] ); ?></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
						<p class="ddwccf-shortcode-card__example"><span><?php esc_html_e( 'Example:', 'devdiggers-crowdfunding-for-woocommerce' ); ?></span> <code><?php echo esc_html( $shortcode['example'] ); ?></code></p>
					</div>
				<?php endforeach; ?>
			</div>
			<?php
			$cards = ob_get_clean();

			$this->render(
				[
					[
						'header'            => [
							'heading'     => __( 'Shortcodes', 'devdiggers-crowdfunding-for-woocommerce' ),
							'description' => __( 'Show campaigns anywhere on your site. They work in the block editor Shortcode block and in page builders.', 'devdiggers-crowdfunding-for-woocommerce' ),
						],
						'after_header_html' => $cards,
					],
				],
				''
			);
		}
	}
}
