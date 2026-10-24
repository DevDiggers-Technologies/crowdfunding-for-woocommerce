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

			$card = fn( $title, $key, $icon, $money = false ) => [
				'title'       => $title,
				'value'       => $money ? wc_price( $summary[ $key ]['value'] ) : $summary[ $key ]['value'],
				'value_type'  => $money ? 'html' : 'text',
				'change'      => $summary[ $key ]['change'],
				'is_positive' => $summary[ $key ]['is_positive'],
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
							'change'      => 0,
							'is_positive' => true,
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
									'key'   => 'raised',
									'label' => esc_html__( 'Amount Raised', 'devdiggers-crowdfunding-for-woocommerce' ),
									'color' => '#0256ff',
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
		 * One linked list row.
		 *
		 * @param string $url   URL.
		 * @param string $title Title.
		 * @param string $meta  Meta line.
		 * @param string $value Right side HTML.
		 * @return void
		 */
		protected function row( $url, $title, $meta, $value ) {
			?>
			<a class="ddwccf-dash-item" href="<?php echo esc_url( $url ); ?>">
				<span class="ddwccf-dash-item__info">
					<strong><?php echo esc_html( $title ); ?></strong>
					<span><?php echo esc_html( $meta ); ?></span>
				</span>
				<span class="ddwccf-dash-item__value"><?php echo wp_kses_post( $value ); ?></span>
			</a>
			<?php
		}

		/**
		 * Empty widget message.
		 *
		 * @param string $message Message.
		 * @return void
		 */
		protected function empty_state( $message ) {
			?>
			<div class="ddfw-dash-no-data"><?php echo esc_html( $message ); ?></div>
			<?php
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
			if ( ! $this->data['events']['top'] ) {
				$this->empty_state( __( 'No campaign has raised money yet.', 'devdiggers-crowdfunding-for-woocommerce' ) );
				return;
			}

			foreach ( $this->data['events']['top'] as $row ) {
				/* translators: %s: funded percentage. */
				$meta = $row['target'] ? sprintf( __( '%s%% funded', 'devdiggers-crowdfunding-for-woocommerce' ), $row['progress'] ) : __( 'No goal', 'devdiggers-crowdfunding-for-woocommerce' );
				$this->row( $row['url'], $row['name'], $meta, wc_price( $row['collected'] ) );
			}
		}

		/**
		 * Latest paid contributions.
		 *
		 * @return void
		 */
		public function render_recent() {
			if ( ! $this->data['recent'] ) {
				$this->empty_state( __( 'No contributions in this period.', 'devdiggers-crowdfunding-for-woocommerce' ) );
				return;
			}

			foreach ( $this->data['recent'] as $row ) {
				$this->row( $row['url'], '#' . $row['number'] . ' ' . $row['customer'], $row['events'] . ' · ' . $row['date'], wc_price( $row['amount'] ) );
			}
		}

		/**
		 * Live campaigns closing within two weeks.
		 *
		 * @return void
		 */
		public function render_ending() {
			if ( ! $this->data['events']['ending'] ) {
				$this->empty_state( __( 'No live campaign ends in the next 14 days.', 'devdiggers-crowdfunding-for-woocommerce' ) );
				return;
			}

			foreach ( $this->data['events']['ending'] as $row ) {
				/* translators: %d: number of days. */
				$meta = sprintf( _n( '%d day left', '%d days left', $row['days_left'], 'devdiggers-crowdfunding-for-woocommerce' ), $row['days_left'] );
				$this->row( $row['url'], $row['name'], $meta, $row['target'] ? $row['progress'] . '%' : wc_price( $row['collected'] ) );
			}
		}
	}
}
