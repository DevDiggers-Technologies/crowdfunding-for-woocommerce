<?php
/**
 * Saves a campaign from the admin editor.
 *
 * @package DevDiggers Crowdfunding for WooCommerce
 * @version 1.0.0
 */

namespace DDWCCrowdFunding\Helper;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCCF_Event_Form_Helper' ) ) {
	/**
	 * Campaign form helper class.
	 */
	class DDWCCF_Event_Form_Helper {
		/**
		 * Error Helper Trait.
		 */
		use DDWCCF_Error_Helper;

		/**
		 * Reward tiers a campaign can offer.
		 *
		 * @return int
		 */
		public static function get_max_rewards() {
			/**
			 * Filters how many reward tiers a campaign can offer.
			 *
			 * @param int $max Reward tiers per campaign.
			 */
			return max( 1, absint( apply_filters( 'ddwccf_max_rewards', 3 ) ) );
		}

		/**
		 * Read a posted scalar.
		 *
		 * @param string $key       Field name.
		 * @param string $sanitizer Sanitizer callback.
		 * @return string
		 */
		protected function posted( $key, $sanitizer = 'sanitize_text_field' ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verified by the caller; sanitized through the given callback.
			return isset( $_POST[ $key ] ) && ! is_array( $_POST[ $key ] ) ? call_user_func( $sanitizer, wp_unslash( $_POST[ $key ] ) ) : '';
		}

		/**
		 * Read a posted list of rows.
		 *
		 * @param string $key Field name.
		 * @return array
		 */
		protected function posted_rows( $key ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verified by the caller; every value is sanitized by the row parsers.
			return isset( $_POST[ $key ] ) && is_array( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : [];
		}

		/**
		 * Show an error with the page notices. Saving runs on admin_init.
		 *
		 * @param string $message Message.
		 * @return void
		 */
		protected function fail( $message ) {
			add_action(
				'admin_notices',
				function () use ( $message ) {
					$this->ddwccf_print_notification( esc_html( $message ), 'error' );
				}
			);
		}

		/**
		 * Sanitize reward rows, keeping claimed counts of existing rewards.
		 * Keeps up to get_max_rewards() tiers, or as many as the campaign already had.
		 *
		 * @param array $rows     Posted rows.
		 * @param array $existing Current rewards.
		 * @return array
		 */
		public static function parse_rewards( $rows, $existing = [] ) {
			$claimed = array_change_key_case( array_column( $existing, 'claimed', 'id' ) );
			$limit   = max( self::get_max_rewards(), count( $existing ) );
			$rewards = [];

			foreach ( (array) $rows as $row ) {
				$title  = sanitize_text_field( $row['title'] ?? '' );
				$amount = (float) wc_format_decimal( $row['amount'] ?? 0 );

				if ( ! $title || $amount <= 0 ) {
					continue;
				}

				$id        = sanitize_key( $row['id'] ?? '' );
				$id        = $id ? $id : 'r' . strtolower( wp_generate_password( 8, false, false ) );
				$rewards[] = [
					'id'          => $id,
					'title'       => $title,
					'amount'      => $amount,
					'description' => sanitize_textarea_field( $row['description'] ?? '' ),
					'limit'       => absint( $row['limit'] ?? 0 ),
					'delivery'    => sanitize_text_field( $row['delivery'] ?? '' ),
					'claimed'     => absint( $claimed[ strtolower( $id ) ] ?? 0 ),
				];

				if ( count( $rewards ) >= $limit ) {
					break;
				}
			}

			usort( $rewards, fn( $a, $b ) => $a['amount'] <=> $b['amount'] );

			return $rewards;
		}

		/**
		 * Save a campaign. Caller must verify the nonce and capability first.
		 *
		 * @return void
		 */
		public function save() {
			$event_id = absint( $this->posted( 'ddwccf_event_id' ) );

			if ( $event_id && ! DDWCCF_Event_Helper::is_event( $event_id ) ) {
				$this->fail( __( 'You are not allowed to manage this campaign.', 'devdiggers-crowdfunding-for-woocommerce' ) );
				return;
			}

			$current             = $event_id ? DDWCCF_Event_Helper::get_event_data( $event_id ) : DDWCCF_Event_Helper::get_defaults();
			$name                = $this->posted( 'ddwccf_name' );
			$description         = $this->posted( 'ddwccf_description', 'wp_kses_post' );
			$excerpt             = $this->posted( 'ddwccf_excerpt', 'sanitize_textarea_field' );
			$contribution_type   = 'fixed' === $this->posted( 'ddwccf_contribution_type' ) ? 'fixed' : 'dynamic';
			$contribution_amount = max( 0, (float) wc_format_decimal( $this->posted( 'ddwccf_contribution_amount' ) ) );
			$target_amount       = (float) wc_format_decimal( $this->posted( 'ddwccf_target_amount' ) );
			$target_amount       = $target_amount > 0 ? wc_format_decimal( $target_amount ) : '';
			$end_date            = $this->posted( 'ddwccf_end_date' );
			$end_date            = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $end_date ) ? $end_date : '';
			$image               = absint( $this->posted( 'ddwccf_image' ) );
			$status              = 'draft' === $this->posted( 'ddwccf_status' ) ? 'draft' : 'publish';
			$creator             = absint( $this->posted( 'ddwccf_creator' ) );
			$creator             = $creator ? $creator : ( $event_id ? (int) get_post_field( 'post_author', $event_id ) : get_current_user_id() );

			if ( ! $name || ( ! $target_amount && ! $end_date ) || ( 'fixed' === $contribution_type && ! $contribution_amount ) ) {
				$this->fail( __( 'Please add a name, a contribution amount and a target amount or an end date.', 'devdiggers-crowdfunding-for-woocommerce' ) );
				return;
			}

			global $wpdb;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-off uniqueness check on save.
			$name_exists = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type = 'product' AND post_status != 'trash' AND ID != %d LIMIT 1", $name, $event_id ) );

			if ( $name_exists ) {
				$this->fail( __( 'A product or campaign with this name already exists.', 'devdiggers-crowdfunding-for-woocommerce' ) );
				return;
			}

			$postarr = [
				'post_author'  => $creator,
				'post_title'   => $name,
				'post_content' => $description,
				'post_excerpt' => $excerpt ? $excerpt : wp_trim_words( wp_strip_all_tags( $description ), 30 ),
				'post_status'  => $status,
				'post_type'    => 'product',
			];

			if ( $event_id ) {
				$postarr['ID'] = $event_id;
				$id            = wp_update_post( $postarr, true );
			} else {
				$id = wp_insert_post( $postarr, true );
			}

			if ( is_wp_error( $id ) || ! $id ) {
				$this->fail( __( 'The campaign could not be saved. Please try again.', 'devdiggers-crowdfunding-for-woocommerce' ) );
				return;
			}

			// Keys Free does not edit (saved by Pro) stay as they are.
			$data                      = $current;
			$data['contribution_type'] = $contribution_type;
			$data['target_amount']     = $target_amount;
			$data['end_date']          = $end_date;
			$data['rewards']           = self::parse_rewards( $this->posted_rows( 'ddwccf_rewards' ), $current['rewards'] );

			DDWCCF_Event_Helper::update_event_data( $id, apply_filters( 'ddwccf_event_data_before_save', $data, $id ) );

			$product = wc_get_product( $id );

			if ( ! $event_id ) {
				$product->set_virtual( true );
				$product->set_sold_individually( true );
				$product->set_tax_status( 'none' );
				$product->set_catalog_visibility( 'hidden' );
				$product->set_manage_stock( false );
				$product->set_stock_status( 'instock' );
			}

			$product->set_regular_price( $contribution_amount );
			$product->set_price( $contribution_amount );
			$product->set_image_id( $image );
			$product->save();

			/**
			 * Fires after a campaign is saved.
			 *
			 * @param int  $id     Event ID.
			 * @param bool $is_new Whether it was created.
			 */
			do_action( 'ddwccf_event_saved', $id, ! $event_id );

			wp_safe_redirect( admin_url( 'admin.php?page=' . DDWCCF_DASHBOARD_PAGE . '&menu=events&action=edit&id=' . $id . '&status=event_saved' ) );
			exit();
		}
	}
}
