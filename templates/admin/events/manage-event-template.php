<?php
/**
 * Admin campaign editor on the framework edit screen.
 * Saving happens on admin_init (DDWCCF_Admin_Functions::ddwccf_handle_event_save()).
 *
 * @package DevDiggers Crowdfunding for WooCommerce
 * @version 1.0.0
 */

namespace DDWCCrowdFunding\Templates\Admin\Events;

use DDWCCrowdFunding\Helper\DDWCCF_Event_Helper;
use DDWCCrowdFunding\Helper\DDWCCF_Event_Form_Helper;
use DDWCCrowdFunding\Includes\DDWCCF_Admin_Dashboard;
use DDWCCrowdFunding\Templates\Common\DDWCCF_Repeater_Template;
use DevDiggers\Framework\Includes\DDFW_Layout;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCCF_Manage_Event_Template' ) ) {
	/**
	 * Admin campaign editor class.
	 */
	class DDWCCF_Manage_Event_Template {
		/**
		 * Construct.
		 *
		 * @param array $config   Configuration.
		 * @param int   $event_id Event ID.
		 */
		public function __construct( $config, $event_id = 0 ) {
			$event_id = absint( $event_id );
			$data     = $event_id ? DDWCCF_Event_Helper::get_event_data( $event_id ) : false;
			$product  = $data ? wc_get_product( $event_id ) : null;
			$event_id = $data ? $event_id : 0;
			$data     = $data ? $data : DDWCCF_Event_Helper::get_defaults();
			$currency = html_entity_decode( get_woocommerce_currency_symbol() );
			$locked   = fn( $field ) => ddfw_locked_field( $field, 'ddwccf' );
			$card     = fn( $image, $heading, $text, $features ) => ddfw_get_upgrade_to_pro_section(
				[
					'image_url'     => DDWCCF_Admin_Dashboard::get_pro_image( $image ),
					'heading'       => $heading,
					'description'   => $text,
					'list_features' => $features,
					'upgrade_url'   => DDWCCF_Admin_Dashboard::UPGRADE_URL,
				]
			);

			$sections = [
				[
					'header'            => [
						'heading'     => __( 'Story', 'devdiggers-crowdfunding-for-woocommerce' ),
						'description' => __( 'What supporters see on the campaign page.', 'devdiggers-crowdfunding-for-woocommerce' ),
					],
					'after_header_html' => '<input type="hidden" name="ddwccf_event_id" value="' . esc_attr( $event_id ) . '" />',
					'fields'            => [
						[
							'type'              => 'text',
							'name'              => 'ddwccf_name',
							'id'                => 'ddwccf-name',
							'label'             => __( 'Campaign name', 'devdiggers-crowdfunding-for-woocommerce' ),
							'required'          => true,
							'value'             => $product ? $product->get_name() : '',
							'custom_attributes' => [ 'required' => 'required' ],
						],
						[
							'type'        => 'textarea',
							'name'        => 'ddwccf_excerpt',
							'id'          => 'ddwccf-excerpt',
							'label'       => __( 'Short summary', 'devdiggers-crowdfunding-for-woocommerce' ),
							'description' => __( 'One or two sentences shown on campaign cards and the campaign page.', 'devdiggers-crowdfunding-for-woocommerce' ),
							'value'       => $product ? $product->get_short_description() : '',
						],
						[
							'type'          => 'editor',
							'name'          => 'ddwccf_description',
							'id'            => 'ddwccf-description',
							'label'         => __( 'Story', 'devdiggers-crowdfunding-for-woocommerce' ),
							'description'   => __( 'Why the campaign matters, how the money will be used and who it helps. Shown in the Story tab.', 'devdiggers-crowdfunding-for-woocommerce' ),
							'value'         => $product ? $product->get_description() : '',
							'textarea_rows' => 10,
						],
						[
							'type'  => 'image',
							'name'  => 'ddwccf_image',
							'id'    => 'ddwccf-image',
							'label' => __( 'Cover image', 'devdiggers-crowdfunding-for-woocommerce' ),
							'value' => $product ? $product->get_image_id() : '',
						],
					],
				],
				[
					'header' => [
						'heading'     => __( 'Funding', 'devdiggers-crowdfunding-for-woocommerce' ),
						'description' => __( 'Set a goal, an end date, or both. Every paid contribution counts toward the goal.', 'devdiggers-crowdfunding-for-woocommerce' ),
					],
					'fields' => [
						$locked(
							[
								'type'        => 'select',
								'id'          => 'funding-mode',
								'label'       => __( 'Funding model', 'devdiggers-crowdfunding-for-woocommerce' ),
								'description' => __( 'Flexible keeps whatever is raised. All or nothing only releases the money if the goal is met by the end date, otherwise every supporter is refunded.', 'devdiggers-crowdfunding-for-woocommerce' ),
								'options'     => [
									'flexible'       => __( 'Flexible (keep what you raise)', 'devdiggers-crowdfunding-for-woocommerce' ),
									'all_or_nothing' => __( 'All or nothing', 'devdiggers-crowdfunding-for-woocommerce' ),
								],
								'value'       => 'flexible',
							]
						),
						[
							'type'        => 'select',
							'name'        => 'ddwccf_contribution_type',
							'id'          => 'ddwccf-contribution-type',
							'label'       => __( 'Contribution', 'devdiggers-crowdfunding-for-woocommerce' ),
							'description' => __( 'Let supporters choose any amount above a minimum, or charge everyone the same fixed amount.', 'devdiggers-crowdfunding-for-woocommerce' ),
							'options'     => [
								'dynamic' => __( 'Supporters choose the amount', 'devdiggers-crowdfunding-for-woocommerce' ),
								'fixed'   => __( 'Fixed amount', 'devdiggers-crowdfunding-for-woocommerce' ),
							],
							'value'       => $data['contribution_type'],
						],
						[
							'type'              => 'number',
							'name'              => 'ddwccf_contribution_amount',
							'id'                => 'ddwccf-contribution-amount',
							/* translators: %s: currency symbol. */
							'label'             => sprintf( __( 'Fixed or minimum amount (%s)', 'devdiggers-crowdfunding-for-woocommerce' ), $currency ),
							'description'       => __( 'The fixed price, or the smallest contribution accepted. Use 0 for no minimum.', 'devdiggers-crowdfunding-for-woocommerce' ),
							'custom_attributes' => [
								'min'  => '0',
								'step' => 'any',
							],
							'value'             => $product ? (float) $product->get_regular_price() : 1,
						],
						[
							'type'              => 'number',
							'name'              => 'ddwccf_target_amount',
							'id'                => 'ddwccf-target-amount',
							/* translators: %s: currency symbol. */
							'label'             => sprintf( __( 'Goal (%s)', 'devdiggers-crowdfunding-for-woocommerce' ), $currency ),
							'description'       => __( 'Shown on the progress bar. Leave empty for an open ended campaign with an end date.', 'devdiggers-crowdfunding-for-woocommerce' ),
							'custom_attributes' => [
								'min'  => '0',
								'step' => 'any',
							],
							'value'             => $data['target_amount'],
						],
						[
							'type'        => 'date',
							'name'        => 'ddwccf_end_date',
							'id'          => 'ddwccf-end-date',
							'label'       => __( 'End date', 'devdiggers-crowdfunding-for-woocommerce' ),
							'description' => __( 'Contributions close at the end of this day, in the store time zone.', 'devdiggers-crowdfunding-for-woocommerce' ),
							'value'       => $data['end_date'],
						],
					],
				],
				[
					'header'            => [
						'heading'     => __( 'Rewards', 'devdiggers-crowdfunding-for-woocommerce' ),
						/* translators: %d: number of reward tiers. */
						'description' => sprintf( __( 'Optional perks for supporters who give at least a set amount. Limited rewards sell out automatically. Up to %d tiers per campaign.', 'devdiggers-crowdfunding-for-woocommerce' ), DDWCCF_Event_Form_Helper::get_max_rewards() ),
					],
					'after_header_html' => DDWCCF_Repeater_Template::rewards( $data['rewards'], max( DDWCCF_Event_Form_Helper::get_max_rewards(), count( $data['rewards'] ) ) ),
				],
				[
					'header'            => [
						'heading'     => __( 'Stretch Goals & Updates', 'devdiggers-crowdfunding-for-woocommerce' ),
						'description' => __( 'Keep supporters coming back after they give.', 'devdiggers-crowdfunding-for-woocommerce' ),
					],
					'after_header_html' => $card(
						'stretch-goals',
						__( 'Give supporters a reason to give again with Pro', 'devdiggers-crowdfunding-for-woocommerce' ),
						__( 'Show what extra money unlocks and post news that lands in every supporter\'s inbox.', 'devdiggers-crowdfunding-for-woocommerce' ),
						[
							__( 'Stretch goals marked on the progress bar and listed on the campaign', 'devdiggers-crowdfunding-for-woocommerce' ),
							__( 'Updates timeline in an Updates tab on the campaign page', 'devdiggers-crowdfunding-for-woocommerce' ),
							__( 'Updates emailed to every supporter in the background', 'devdiggers-crowdfunding-for-woocommerce' ),
							__( 'Unlimited reward tiers', 'devdiggers-crowdfunding-for-woocommerce' ),
						]
					),
				],
			];

			$sections[] = [
				'column' => 'side',
				'header' => [ 'heading' => __( 'Publish', 'devdiggers-crowdfunding-for-woocommerce' ) ],
				'fields' => [
					[
						'type'    => 'select',
						'name'    => 'ddwccf_status',
						'id'      => 'ddwccf-status',
						'label'   => __( 'Status', 'devdiggers-crowdfunding-for-woocommerce' ),
						'options' => [
							'publish' => __( 'Live', 'devdiggers-crowdfunding-for-woocommerce' ),
							'draft'   => __( 'Disabled', 'devdiggers-crowdfunding-for-woocommerce' ),
						],
						'value'   => $product ? $product->get_status() : 'publish',
					],
					[
						'type'        => 'users',
						'name'        => 'ddwccf_creator',
						'id'          => 'ddwccf-creator',
						'label'       => __( 'Organiser', 'devdiggers-crowdfunding-for-woocommerce' ),
						'description' => __( 'Shown on the campaign page and receives the contribution emails. Leave empty for yourself.', 'devdiggers-crowdfunding-for-woocommerce' ),
						'value'       => $event_id ? (int) get_post_field( 'post_author', $event_id ) : '',
					],
				],
			];

			( new DDFW_Layout() )->get_edit_screen_layout(
				$sections,
				'',
				[
					'name'  => 'ddwccf_save_event',
					'value' => __( 'Save Campaign', 'devdiggers-crowdfunding-for-woocommerce' ),
				],
				'ddwccf-manage-event',
				[
					'heading'         => $event_id ? __( 'Edit Campaign', 'devdiggers-crowdfunding-for-woocommerce' ) : __( 'Add Campaign', 'devdiggers-crowdfunding-for-woocommerce' ),
					'description'     => $event_id ? wp_kses_post( DDWCCF_Event_Helper::get_badge( DDWCCF_Event_Helper::get_state( $event_id, $data ) ) ) . ' ' . sprintf(
						/* translators: 1: raised amount, 2: supporters. */
						esc_html__( '%1$s raised from %2$s supporters', 'devdiggers-crowdfunding-for-woocommerce' ),
						wp_kses_post( wc_price( $data['collected_amount'] ) ),
						esc_html( number_format_i18n( count( $data['contributors'] ) ) )
					) : '',
					'back_button_url' => admin_url( 'admin.php?page=' . DDWCCF_DASHBOARD_PAGE . '&menu=events' ),
					'header_buttons'  => $event_id ? [
						[
							'label'  => __( 'View Campaign', 'devdiggers-crowdfunding-for-woocommerce' ),
							'url'    => get_permalink( $event_id ),
							'target' => '_blank',
							'icon'   => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg>',
						],
						[
							'label' => __( 'Contributions', 'devdiggers-crowdfunding-for-woocommerce' ),
							'url'   => admin_url( 'admin.php?page=' . DDWCCF_DASHBOARD_PAGE . '&menu=orders&ddwccf-event=' . $event_id ),
						],
					] : [],
				]
			);
		}
	}
}
