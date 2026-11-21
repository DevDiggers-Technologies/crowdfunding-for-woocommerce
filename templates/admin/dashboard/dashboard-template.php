<?php
/**
 * Dashboard template: supplies data and config to the framework DDFW_Dashboard builder.
 *
 * @package DevDiggers Crowdfunding for WooCommerce
 * @version 1.0.0
 */

namespace DDWCCrowdFunding\Templates\Admin\Dashboard;

use DDWCCrowdFunding\Helper\Dashboard\DDWCCF_Dashboard_Helper;
use DDWCCrowdFunding\Helper\DDWCCF_Event_Helper;
use DDWCCrowdFunding\Includes\DDWCCF_Admin_Dashboard;
use DevDiggers\Framework\Includes\DDFW_Dashboard;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCCF_Dashboard_Template' ) ) {
	/**
	 * Dashboard template class.
	 */
	class DDWCCF_Dashboard_Template {
		/**
		 * Dashboard data.
		 *
		 * @var array
		 */
		protected $data;

		/**
		 * Construct.
		 */
		public function __construct() {
			if ( ! class_exists( DDFW_Dashboard::class ) ) {
				return;
			}

			$this->data = ( new DDWCCF_Dashboard_Helper() )->get_dashboard_data();
			$this->render();
		}

		/**
		 * Inline stroke icon.
		 *
		 * @param string $path SVG inner markup.
		 * @return string
		 */
		protected function icon( $path ) {
			return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
		}

		/**
		 * Campaign cover: the product image, or nothing for the placeholder tile.
		 *
		 * @param int $event_id Campaign product ID.
		 * @return string
		 */
		protected function image( $event_id ) {
			$product = wc_get_product( $event_id );

			return $product && $product->get_image_id() ? $product->get_image( [ 72, 72 ] ) : '';
		}

		/**
		 * Render.
		 *
		 * @return void
		 */
		protected function render() {
			$summary = $this->data['summary'];
			$events  = $this->data['events'];
			$states  = [];

			foreach ( $events['states'] as $state => $count ) {
				if ( $count ) {
					$states[] = [
						'label' => DDWCCF_Event_Helper::get_label( $state ),
						'value' => $count,
					];
				}
			}

			// Every card says how it compares: "↗ 12% from $50", "None in the previous period", or
			// "No change" when the two periods match.
			$card = fn( $title, $key, $icon, $money = false ) => [
				'title'       => $title,
				'value'       => $money ? wc_price( $summary[ $key ]['value'] ) : $summary[ $key ]['value'],
				'value_type'  => $money ? 'html' : 'text',
				'change'      => $summary[ $key ]['change'],
				'is_positive' => $summary[ $key ]['is_positive'],
				'previous'    => $money ? wc_price( $summary[ $key ]['previous'] ) : number_format_i18n( $summary[ $key ]['previous'] ),
				'note'        => 0.0 === (float) $summary[ $key ]['change'] && null !== $summary[ $key ]['change'] ? esc_html__( 'No change vs previous period', 'devdiggers-crowdfunding-for-woocommerce' ) : '',
				'icon'        => $this->icon( $icon ),
			];

			new DDFW_Dashboard(
				[
					'columns'       => 5,
					'header'        => [
						/* translators: %s: admin display name. */
						'welcome'  => esc_html__( 'Welcome back, %s!', 'devdiggers-crowdfunding-for-woocommerce' ),
						'subtitle' => esc_html__( 'Money raised, supporters and how every campaign is doing.', 'devdiggers-crowdfunding-for-woocommerce' ),
					],
					'summary_cards' => [
						$card( esc_html__( 'Amount Raised', 'devdiggers-crowdfunding-for-woocommerce' ), 'raised', '<line x1="12" y1="2" x2="12" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>', true ),
						$card( esc_html__( 'Contributions', 'devdiggers-crowdfunding-for-woocommerce' ), 'contributions', '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>' ),
						$card( esc_html__( 'Supporters', 'devdiggers-crowdfunding-for-woocommerce' ), 'supporters', '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>' ),
						$card( esc_html__( 'Average Contribution', 'devdiggers-crowdfunding-for-woocommerce' ), 'average', '<path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/>', true ),
						[
							'title'       => esc_html__( 'Success Rate', 'devdiggers-crowdfunding-for-woocommerce' ),
							'value'       => null === $events['success'] ? '&mdash;' : esc_html( $events['success'] . '%' ),
							'value_type'  => 'html',
							'note'        => $events['finished']
								/* translators: 1: funded campaigns, 2: finished campaigns. */
								? esc_html( sprintf( _n( '%1$d of %2$d campaign funded', '%1$d of %2$d campaigns funded', $events['finished'], 'devdiggers-crowdfunding-for-woocommerce' ), $events['succeeded'], $events['finished'] ) )
								: esc_html__( 'No finished campaigns yet', 'devdiggers-crowdfunding-for-woocommerce' ),
							'icon'        => $this->icon( '<circle cx="12" cy="8" r="6"/><path d="M15.5 13 17 22l-5-3-5 3 1.5-9"/>' ),
						],
					],
					'charts'        => [
						[
							'id'         => 'ddwccf-raised-trend',
							'title'      => esc_html__( 'Contributions Trend', 'devdiggers-crowdfunding-for-woocommerce' ),
							'date_label' => $this->data['date_range']['label'],
							'full_width' => true,
							'type'       => 'line',
							'data'       => $this->data['trend'],
							'x_key'      => 'date',
							'series'     => [
								[
									'key'          => 'raised',
									'label'        => esc_html__( 'Amount Raised', 'devdiggers-crowdfunding-for-woocommerce' ),
									'color'        => '#0256ff',
									'value_format' => 'currency',
								],
								[
									'key'   => 'contributions',
									'label' => esc_html__( 'Contributions', 'devdiggers-crowdfunding-for-woocommerce' ),
									'color' => '#10b981',
								],
							],
							'empty'      => [
								'title' => esc_html__( 'No contributions in this period', 'devdiggers-crowdfunding-for-woocommerce' ),
								'desc'  => esc_html__( 'Paid contributions appear here as soon as supporters back a campaign.', 'devdiggers-crowdfunding-for-woocommerce' ),
							],
						],
					],
					'widgets'       => [
						[
							'title'  => esc_html__( 'Top Campaigns', 'devdiggers-crowdfunding-for-woocommerce' ),
							'width'  => 'half',
							'render' => [ $this, 'render_top' ],
						],
						[
							'title' => esc_html__( 'Campaign Status', 'devdiggers-crowdfunding-for-woocommerce' ),
							'width' => 'half',
							'chart' => [
								'id'        => 'ddwccf-campaign-status',
								'type'      => 'doughnut',
								'data'      => $states,
								'label_key' => 'label',
								'value_key' => 'value',
								'empty'     => [
									'title' => esc_html__( 'No campaigns yet', 'devdiggers-crowdfunding-for-woocommerce' ),
									'desc'  => esc_html__( 'Create a campaign to see how your campaigns are doing.', 'devdiggers-crowdfunding-for-woocommerce' ),
								],
							],
						],
						[
							'title'  => esc_html__( 'Recent Contributions', 'devdiggers-crowdfunding-for-woocommerce' ),
							'width'  => 'half',
							'render' => [ $this, 'render_recent' ],
						],
						[
							'title'  => esc_html__( 'Ending Soon', 'devdiggers-crowdfunding-for-woocommerce' ),
							'width'  => 'half',
							'render' => [ $this, 'render_ending' ],
						],
						[
							'width'  => 'full',
							'render' => [ $this, 'render_pro_card' ],
						],
					],
				]
			);
		}

		/**
		 * Upgrade card under the figures.
		 *
		 * @return void
		 */
		public function render_pro_card() {
			ddfw_upgrade_to_pro_section(
				[
					'heading'       => esc_html__( 'Turn your store into a fundraising platform', 'devdiggers-crowdfunding-for-woocommerce' ),
					'description'   => esc_html__( 'Pro lets customers run their own campaigns, earns you a fee on every one and handles payouts and refunds for you.', 'devdiggers-crowdfunding-for-woocommerce' ),
					'list_features' => [
						esc_html__( 'Customer campaigns from My Account, with approval', 'devdiggers-crowdfunding-for-woocommerce' ),
						esc_html__( 'Platform fees, payout requests and DevDiggers Wallet payouts', 'devdiggers-crowdfunding-for-woocommerce' ),
						esc_html__( 'All or nothing funding with one click refunds', 'devdiggers-crowdfunding-for-woocommerce' ),
						esc_html__( 'Stretch goals, campaign updates and unlimited rewards', 'devdiggers-crowdfunding-for-woocommerce' ),
						esc_html__( 'Needs Attention queue for approvals, payouts and refunds', 'devdiggers-crowdfunding-for-woocommerce' ),
						esc_html__( 'CSV export of campaigns and contributions', 'devdiggers-crowdfunding-for-woocommerce' ),
					],
					'upgrade_url'   => DDWCCF_Admin_Dashboard::UPGRADE_URL,
				]
			);
		}

		/**
		 * Top campaigns by money raised.
		 *
		 * @return void
		 */
		public function render_top() {
			$items = [];

			foreach ( $this->data['events']['top'] as $row ) {
				$items[] = [
					'url'   => $row['url'],
					'title' => $row['name'],
					/* translators: %s: funded percentage. */
					'meta'  => $row['target'] ? sprintf( __( '%s%% funded', 'devdiggers-crowdfunding-for-woocommerce' ), $row['progress'] ) : __( 'No goal', 'devdiggers-crowdfunding-for-woocommerce' ),
					'image' => $this->image( $row['id'] ),
					'value' => wc_price( $row['collected'] ),
				];
			}

			DDFW_Dashboard::render_list( $items, __( 'No campaign has raised money yet.', 'devdiggers-crowdfunding-for-woocommerce' ) );
		}

		/**
		 * Latest paid contributions.
		 *
		 * @return void
		 */
		public function render_recent() {
			$items = array_map(
				fn( $row ) => [
					'url'   => $row['url'],
					'title' => $row['customer'],
					'meta'  => '#' . $row['number'] . ' · ' . $row['events'] . ' · ' . $row['date'],
					'image' => $row['email'] ? get_avatar_url( $row['email'], [ 'size' => 72 ] ) : '',
					'shape' => 'round',
					'value' => wc_price( $row['amount'] ),
				],
				$this->data['recent']
			);

			DDFW_Dashboard::render_list( $items, __( 'No contributions in this period.', 'devdiggers-crowdfunding-for-woocommerce' ) );
		}

		/**
		 * Live campaigns closing within two weeks.
		 *
		 * @return void
		 */
		public function render_ending() {
			$items = [];

			foreach ( $this->data['events']['ending'] as $row ) {
				$items[] = [
					'url'   => $row['url'],
					'title' => $row['name'],
					/* translators: %d: number of days. */
					'meta'  => sprintf( _n( '%d day left', '%d days left', $row['days_left'], 'devdiggers-crowdfunding-for-woocommerce' ), $row['days_left'] ),
					'image' => $this->image( $row['id'] ),
					'value' => $row['target'] ? esc_html( $row['progress'] . '%' ) : wc_price( $row['collected'] ),
				];
			}

			DDFW_Dashboard::render_list( $items, __( 'No live campaign ends in the next 14 days.', 'devdiggers-crowdfunding-for-woocommerce' ) );
		}
	}
}
