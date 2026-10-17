<?php
/**
 * Front end callbacks.
 *
 * @author DevDiggers
 * @package DevDiggers Crowdfunding for WooCommerce
 * @version 1.0.0
 */

namespace DDWCCrowdFunding\Includes\Front;

use DDWCCrowdFunding\Templates\Front;
use DDWCCrowdFunding\Helper\DDWCCF_Event_Helper;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCCF_Front_Functions' ) ) {
	/**
	 * Front functions class.
	 */
	class DDWCCF_Front_Functions {
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
		 * Add Backed Campaigns before logout.
		 *
		 * @param array $items Items.
		 * @return array
		 */
		public function ddwccf_add_woocommerce_menu( $items ) {
			if ( ! apply_filters( 'ddwccf_display_myaccounts_menu', true ) ) {
				return $items;
			}

			$logout_key = get_option( 'woocommerce_logout_endpoint', 'customer-logout' );
			$logout     = isset( $items[ $logout_key ] ) ? $items[ $logout_key ] : null;
			unset( $items[ $logout_key ] );

			$items[ $this->ddwccf_configuration['backed_endpoint'] ] = $this->ddwccf_configuration['backed_endpoint_title'];

			if ( null !== $logout ) {
				$items[ $logout_key ] = $logout;
			}

			return $items;
		}

		/**
		 * Register the endpoint as a WooCommerce account query var.
		 *
		 * @param array $vars Query vars.
		 * @return array
		 */
		public function ddwccf_add_query_vars( $vars ) {
			$vars[ $this->ddwccf_configuration['backed_endpoint'] ] = $this->ddwccf_configuration['backed_endpoint'];

			return $vars;
		}

		/**
		 * Backed endpoint title.
		 *
		 * @return string
		 */
		public function ddwccf_backed_endpoint_title() {
			return $this->ddwccf_configuration['backed_endpoint_title'];
		}

		/**
		 * Backed campaigns page.
		 *
		 * @return void
		 */
		public function ddwccf_add_backed_content_on_my_account_page() {
			$this->ddwccf_enqueue_front_scripts();
			new Front\MyAccount\DDWCCF_Backed_List_Template( $this->ddwccf_configuration, max( 1, absint( get_query_var( $this->ddwccf_configuration['backed_endpoint'] ) ) ) );
		}

		/**
		 * Hide sidebar widgets on the Backed Campaigns page unless enabled.
		 *
		 * @param array $sidebars_widgets Widgets.
		 * @return array
		 */
		public function ddwccf_remove_sidebar_from_custom_menu_page( $sidebars_widgets ) {
			if ( 'yes' === $this->ddwccf_configuration['enable_widgets_my_account_endpoint'] || is_admin() || ! function_exists( 'is_wc_endpoint_url' ) || ! did_action( 'wp' ) ) {
				return $sidebars_widgets;
			}

			return is_wc_endpoint_url( $this->ddwccf_configuration['backed_endpoint'] ) ? [ false ] : $sidebars_widgets;
		}

		/**
		 * Enqueue registered front assets.
		 *
		 * @return void
		 */
		public function ddwccf_enqueue_front_scripts() {
			wp_enqueue_style( 'ddwccf-front-style' );
			wp_enqueue_script( 'ddwccf-front-script' );
		}

		/**
		 * Register front assets.
		 *
		 * @return void
		 */
		public function ddwccf_front_scripts() {
			wp_register_style( 'ddwccf-front-style', DDWCCF_PLUGIN_URL . 'assets/css/front.css', [], filemtime( DDWCCF_PLUGIN_FILE . 'assets/css/front.css' ) );
			wp_add_inline_style( 'ddwccf-front-style', ':root{--ddwccf-accent:' . ( sanitize_hex_color( $this->ddwccf_configuration['accent_color'] ) ? sanitize_hex_color( $this->ddwccf_configuration['accent_color'] ) : '#0256ff' ) . ';}' );
			wp_register_script( 'ddwccf-front-script', DDWCCF_PLUGIN_URL . 'assets/js/front.js', [], filemtime( DDWCCF_PLUGIN_FILE . 'assets/js/front.js' ), true );
			wp_localize_script(
				'ddwccf-front-script',
				'ddwccfFrontObj',
				[
					'i18n' => [
						'days'   => esc_html__( 'Days', 'devdiggers-crowdfunding-for-woocommerce' ),
						'hours'  => esc_html__( 'Hours', 'devdiggers-crowdfunding-for-woocommerce' ),
						'mins'   => esc_html__( 'Mins', 'devdiggers-crowdfunding-for-woocommerce' ),
						'secs'   => esc_html__( 'Secs', 'devdiggers-crowdfunding-for-woocommerce' ),
						'ended'  => esc_html__( 'Campaign ended', 'devdiggers-crowdfunding-for-woocommerce' ),
						'copied' => esc_html__( 'Copied!', 'devdiggers-crowdfunding-for-woocommerce' ),
					],
				]
			);
		}

		/**
		 * Campaign add to cart label.
		 *
		 * @param string      $label   Label.
		 * @param \WC_Product $product Product.
		 * @return string
		 */
		public function ddwccf_modify_product_single_add_to_cart_text( $label, $product ) {
			return DDWCCF_Event_Helper::is_event( $product->get_id() ) ? $this->ddwccf_configuration['button_label'] : $label;
		}

		/**
		 * Loop buttons go to the campaign page, where the amount is chosen.
		 *
		 * @param string      $url     URL.
		 * @param \WC_Product $product Product.
		 * @return string
		 */
		public function ddwccf_loop_add_to_cart_url( $url, $product ) {
			return DDWCCF_Event_Helper::is_event( $product->get_id() ) ? get_permalink( $product->get_id() ) : $url;
		}

		/**
		 * No AJAX add to cart from loops for campaigns.
		 *
		 * @param bool        $supports Supports.
		 * @param string      $feature  Feature.
		 * @param \WC_Product $product  Product.
		 * @return bool
		 */
		public function ddwccf_disable_ajax_add_to_cart( $supports, $feature, $product ) {
			return 'ajax_add_to_cart' === $feature && DDWCCF_Event_Helper::is_event( $product->get_id() ) ? false : $supports;
		}

		/**
		 * Replace the default price, meta and sharing on campaign pages.
		 *
		 * @return void
		 */
		public function ddwccf_handle_remove_default_hooks() {
			global $product;

			if ( $product && DDWCCF_Event_Helper::is_event( $product->get_id() ) ) {
				remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
				remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
				remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50 );
			}
		}

		/**
		 * Campaign progress card on the product page.
		 *
		 * @return void
		 */
		public function ddwccf_add_content_on_single_product_page() {
			global $product;

			if ( $product && DDWCCF_Event_Helper::is_event( $product->get_id() ) ) {
				$this->ddwccf_enqueue_front_scripts();
				Front\DDWCCF_Campaign_Card_Template::render( $product->get_id(), $this->ddwccf_configuration, 'single' );
			}
		}

		/**
		 * Reward picker and contribution fields inside the add to cart form.
		 *
		 * @return void
		 */
		public function ddwccf_add_content_before_add_to_cart_button() {
			global $product;

			if ( $product && DDWCCF_Event_Helper::is_event( $product->get_id() ) ) {
				Front\DDWCCF_Contribution_Form_Template::render( $product, $this->ddwccf_configuration );
			}
		}

		/**
		 * Share buttons.
		 *
		 * @return void
		 */
		public function ddwccf_add_share_box() {
			global $product;

			if ( $product && 'yes' === $this->ddwccf_configuration['show_share'] && DDWCCF_Event_Helper::is_event( $product->get_id() ) && 'publish' === $product->get_status() ) {
				Front\DDWCCF_Campaign_Card_Template::render_share( $product->get_id(), $this->ddwccf_configuration );
			}
		}

		/**
		 * Story, Rewards and Supporters tabs for campaigns.
		 *
		 * @param array $tabs Tabs.
		 * @return array
		 */
		public function ddwccf_product_tabs( $tabs ) {
			global $product;

			$data = $product ? DDWCCF_Event_Helper::get_event_data( $product->get_id() ) : false;

			if ( ! $data || 'yes' !== $this->ddwccf_configuration['campaign_tabs'] ) {
				return $tabs;
			}

			$event_id = $product->get_id();
			$count    = absint( $this->ddwccf_configuration['supporters_count'] );
			$new      = [];

			if ( $product->get_description() ) {
				// The tab label already says Story, so drop WooCommerce's "Description" heading.
				add_filter( 'woocommerce_product_description_heading', '__return_empty_string' );

				$new['description'] = [
					'title'    => __( 'Story', 'devdiggers-crowdfunding-for-woocommerce' ),
					'priority' => 10,
					'callback' => 'woocommerce_product_description_tab',
				];
			}

			if ( $data['rewards'] ) {
				$new['ddwccf_rewards'] = [
					/* translators: %d: number of rewards. */
					'title'    => sprintf( __( 'Rewards (%d)', 'devdiggers-crowdfunding-for-woocommerce' ), count( $data['rewards'] ) ),
					'priority' => 20,
					'callback' => function () use ( $event_id ) {
						Front\DDWCCF_Campaign_Tabs_Template::rewards( $event_id );
					},
				];
			}

			if ( 'yes' === $this->ddwccf_configuration['show_supporters'] && $data['contributors'] ) {
				$new['ddwccf_supporters'] = [
					/* translators: %d: number of supporters. */
					'title'    => sprintf( __( 'Supporters (%d)', 'devdiggers-crowdfunding-for-woocommerce' ), count( $data['contributors'] ) ),
					'priority' => 40,
					'callback' => function () use ( $event_id, $count ) {
						Front\DDWCCF_Campaign_Tabs_Template::supporters( $event_id, $count );
					},
				];
			}

			return apply_filters( 'ddwccf_campaign_tabs', $new, $event_id );
		}

		/**
		 * Supporters below the summary when campaign tabs are off.
		 *
		 * @return void
		 */
		public function ddwccf_add_supporters_list() {
			global $product;

			if ( $product && 'yes' !== $this->ddwccf_configuration['campaign_tabs'] && 'yes' === $this->ddwccf_configuration['show_supporters'] && DDWCCF_Event_Helper::is_event( $product->get_id() ) ) {
				Front\DDWCCF_Campaign_Tabs_Template::supporters( $product->get_id(), $this->ddwccf_configuration['supporters_count'], true );
			}
		}

		/**
		 * Posted contribution fields.
		 *
		 * @return array
		 */
		protected function ddwccf_get_posted_contribution() {
			// phpcs:disable WordPress.Security.NonceVerification.Missing -- WooCommerce add to cart form has no nonce; values are validated.
			$posted = [
				'amount'    => isset( $_POST['ddwccf_contribution_amount'] ) ? (float) wc_format_decimal( sanitize_text_field( wp_unslash( $_POST['ddwccf_contribution_amount'] ) ) ) : 0.0,
				'reward_id' => isset( $_POST['ddwccf_reward_id'] ) ? sanitize_key( wp_unslash( $_POST['ddwccf_reward_id'] ) ) : '',
				'anonymous' => ! empty( $_POST['ddwccf_anonymous'] ) && 'yes' === $this->ddwccf_configuration['allow_anonymous'],
				'message'   => isset( $_POST['ddwccf_message'] ) && 'yes' === $this->ddwccf_configuration['allow_messages'] ? mb_substr( sanitize_textarea_field( wp_unslash( $_POST['ddwccf_message'] ) ), 0, 300 ) : '',
			];
			// phpcs:enable WordPress.Security.NonceVerification.Missing

			return $posted;
		}

		/**
		 * Resolve the contribution amount from the posted fields.
		 *
		 * @param \WC_Product $product Product.
		 * @param array       $data    Campaign data.
		 * @param array       $posted  Posted fields.
		 * @return array{base:float,minimum:float,reward:array|null}
		 */
		protected function ddwccf_resolve_amount( $product, $data, $posted ) {
			$reward  = $posted['reward_id'] ? DDWCCF_Event_Helper::get_reward( $data, $posted['reward_id'] ) : null;
			$minimum = max( (float) $product->get_regular_price(), $reward ? (float) $reward['amount'] : 0 );

			if ( 'fixed' === $data['contribution_type'] ) {
				$base = $reward ? (float) $reward['amount'] : (float) $product->get_regular_price();
			} else {
				$base = $posted['amount'];
			}

			return [
				'base'    => $base,
				'minimum' => $minimum,
				'reward'  => $reward,
			];
		}

		/**
		 * Validate the amount, reward and campaign state.
		 *
		 * @param bool $passed     Passed.
		 * @param int  $product_id Product ID.
		 * @return bool
		 */
		public function ddwccf_validate_contribution( $passed, $product_id ) {
			$data = DDWCCF_Event_Helper::get_event_data( $product_id );

			if ( ! $data ) {
				return $passed;
			}

			if ( DDWCCF_Event_Helper::is_closed( $product_id, $data ) ) {
				wc_add_notice( esc_html__( 'This campaign is no longer accepting contributions.', 'devdiggers-crowdfunding-for-woocommerce' ), 'error' );
				return false;
			}

			$posted   = $this->ddwccf_get_posted_contribution();
			$resolved = $this->ddwccf_resolve_amount( wc_get_product( $product_id ), $data, $posted );

			if ( $posted['reward_id'] && ( ! $resolved['reward'] || ! DDWCCF_Event_Helper::is_reward_available( $resolved['reward'] ) ) ) {
				wc_add_notice( esc_html__( 'This reward is no longer available. Please choose another one.', 'devdiggers-crowdfunding-for-woocommerce' ), 'error' );
				return false;
			}

			$maximum = (float) $this->ddwccf_configuration['max_contribution'];

			if ( $resolved['base'] <= 0 || $resolved['base'] < $resolved['minimum'] ) {
				/* translators: %s: minimum amount. */
				wc_add_notice( sprintf( esc_html__( 'Contribution amount must be at least %s.', 'devdiggers-crowdfunding-for-woocommerce' ), wp_kses_post( wc_price( max( $resolved['minimum'], 0.01 ) ) ) ), 'error' );
				return false;
			}

			if ( $maximum && $resolved['base'] > $maximum ) {
				/* translators: %s: maximum amount. */
				wc_add_notice( sprintf( esc_html__( 'Contribution amount cannot be more than %s.', 'devdiggers-crowdfunding-for-woocommerce' ), wp_kses_post( wc_price( $maximum ) ) ), 'error' );
				return false;
			}

			return $passed;
		}

		/**
		 * Keep the contribution details on the cart item.
		 *
		 * @param array $cart_item_data Cart item data.
		 * @param int   $product_id     Product ID.
		 * @return array
		 */
		public function ddwccf_add_cart_item_data( $cart_item_data, $product_id ) {
			$data = DDWCCF_Event_Helper::get_event_data( $product_id );

			if ( ! $data ) {
				return $cart_item_data;
			}

			$posted   = $this->ddwccf_get_posted_contribution();
			$resolved = $this->ddwccf_resolve_amount( wc_get_product( $product_id ), $data, $posted );

			return array_merge(
				$cart_item_data,
				array_filter(
					[
						'ddwccf_contribution_amount' => $resolved['base'],
						'ddwccf_reward_id'           => $resolved['reward'] ? $resolved['reward']['id'] : '',
						'ddwccf_anonymous'           => $posted['anonymous'],
						'ddwccf_message'             => $posted['message'],
					]
				)
			);
		}

		/**
		 * Show reward, anonymity and message in the cart.
		 *
		 * @param array $item_data Item data.
		 * @param array $cart_item Cart item.
		 * @return array
		 */
		public function ddwccf_display_cart_item_data( $item_data, $cart_item ) {
			$data = DDWCCF_Event_Helper::get_event_data( $cart_item['product_id'] );

			if ( ! $data ) {
				return $item_data;
			}

			if ( ! empty( $cart_item['ddwccf_reward_id'] ) ) {
				$reward = DDWCCF_Event_Helper::get_reward( $data, $cart_item['ddwccf_reward_id'] );
				if ( $reward ) {
					$item_data[] = [
						'key'   => __( 'Reward', 'devdiggers-crowdfunding-for-woocommerce' ),
						'value' => esc_html( $reward['title'] ),
					];
				}
			}
			if ( ! empty( $cart_item['ddwccf_anonymous'] ) ) {
				$item_data[] = [
					'key'   => __( 'Shown as', 'devdiggers-crowdfunding-for-woocommerce' ),
					'value' => __( 'Anonymous', 'devdiggers-crowdfunding-for-woocommerce' ),
				];
			}
			if ( ! empty( $cart_item['ddwccf_message'] ) ) {
				$item_data[] = [
					'key'   => __( 'Message', 'devdiggers-crowdfunding-for-woocommerce' ),
					'value' => esc_html( $cart_item['ddwccf_message'] ),
				];
			}

			return $item_data;
		}

		/**
		 * Apply the chosen amount to the cart item price.
		 *
		 * @param \WC_Cart $cart_object Cart.
		 * @return void
		 */
		public function ddwccf_change_event_product_price( $cart_object ) {
			foreach ( $cart_object->get_cart() as $cart_item ) {
				if ( ! empty( $cart_item['ddwccf_contribution_amount'] ) ) {
					$cart_item['data']->set_price( (float) $cart_item['ddwccf_contribution_amount'] );
				}
			}
		}

		/**
		 * Whether the cart holds a campaign.
		 *
		 * @return bool
		 */
		protected function ddwccf_cart_has_event() {
			if ( ! WC()->cart ) {
				return false;
			}

			foreach ( WC()->cart->get_cart() as $cart_item ) {
				if ( DDWCCF_Event_Helper::is_event( $cart_item['product_id'] ) ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Explain why other products cannot be added next to a contribution.
		 *
		 * @return void
		 */
		public function ddwccf_add_notice_for_event_product() {
			global $post;

			$on_campaign = is_product() && $post && DDWCCF_Event_Helper::is_event( $post->ID );

			if ( ( is_shop() || is_product_taxonomy() || ( is_product() && ! $on_campaign ) ) && $this->ddwccf_cart_has_event() ) {
				wc_add_notice( esc_html__( 'Your cart holds a contribution. Complete or remove it before adding other products.', 'devdiggers-crowdfunding-for-woocommerce' ), 'notice' );
			}
		}

		/**
		 * Contributions are checked out alone, so drop other products.
		 *
		 * @return void
		 */
		public function ddwccf_check_event_product_in_cart() {
			if ( ! $this->ddwccf_cart_has_event() ) {
				return;
			}

			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				if ( ! DDWCCF_Event_Helper::is_event( $cart_item['product_id'] ) ) {
					WC()->cart->remove_cart_item( $cart_item_key );
				}
			}
		}

		/**
		 * Limit gateways for contributions.
		 *
		 * @param array $available_gateways Gateways.
		 * @return array
		 */
		public function ddwccf_remove_payment_gateway( $available_gateways ) {
			$allowed = $this->ddwccf_configuration['enabled_payment_gateways'];

			if ( empty( $allowed ) || ! is_array( $available_gateways ) ) {
				return $available_gateways;
			}

			global $wp;

			if ( ! empty( $wp->query_vars['order-pay'] ) ) {
				$order     = wc_get_order( absint( $wp->query_vars['order-pay'] ) );
				$has_event = $order && DDWCCF_Event_Helper::get_event_items( $order );
			} else {
				$has_event = $this->ddwccf_cart_has_event();
			}

			return $has_event ? array_intersect_key( $available_gateways, array_flip( $allowed ) ) : $available_gateways;
		}

		/**
		 * [ddwccf_campaigns] grid with pagination.
		 *
		 * @param array $atts Attributes.
		 * @return string
		 */
		public function ddwccf_campaigns_shortcode( $atts ) {
			$this->ddwccf_enqueue_front_scripts();

			ob_start();
			new Front\DDWCCF_Campaigns_Grid_Template( (array) $atts, $this->ddwccf_configuration );
			return ob_get_clean();
		}

		/**
		 * [ddwccf_campaign id="123"] progress card for one campaign.
		 *
		 * @param array $atts Attributes.
		 * @return string
		 */
		public function ddwccf_campaign_shortcode( $atts ) {
			$atts     = shortcode_atts( [ 'id' => 0 ], $atts, 'ddwccf_campaign' );
			$event_id = absint( $atts['id'] );

			if ( ! DDWCCF_Event_Helper::is_event( $event_id ) || 'publish' !== get_post_status( $event_id ) ) {
				return '';
			}

			$this->ddwccf_enqueue_front_scripts();

			ob_start();
			echo '<div class="ddwccf-single-card">';
			Front\DDWCCF_Campaign_Card_Template::render( $event_id, $this->ddwccf_configuration, 'grid' );
			echo '</div>';
			return ob_get_clean();
		}
	}
}
