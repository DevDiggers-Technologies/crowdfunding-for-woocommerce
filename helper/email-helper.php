<?php
/**
 * Email types, editable defaults, placeholders and sending.
 *
 * @package DevDiggers Crowdfunding for WooCommerce
 * @version 1.0.0
 */

namespace DDWCCrowdFunding\Helper;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCCF_Email_Helper' ) ) {
	/**
	 * Email helper class.
	 */
	class DDWCCF_Email_Helper {
		/**
		 * Option that stores every email's settings.
		 */
		const OPTION = '_ddwccf_email_settings';

		/**
		 * Email types with recipient and default copy.
		 *
		 * @return array
		 */
		public static function get_types() {
			return [
				'contribution_received' => [
					'title'       => __( 'Thank You to Supporter', 'devdiggers-crowdfunding-for-woocommerce' ),
					'description' => __( 'Sent to the supporter when their contribution is paid.', 'devdiggers-crowdfunding-for-woocommerce' ),
					'recipient'   => 'supporter',
					'subject'     => __( 'Thank you for backing {campaign_name}', 'devdiggers-crowdfunding-for-woocommerce' ),
					'heading'     => __( 'Thank you for your support!', 'devdiggers-crowdfunding-for-woocommerce' ),
					'message'     => __( "Hi {supporter_name},\n\nYour contribution of {amount} to <strong>{campaign_name}</strong> has been received. {reward_line}\n\nThe campaign has now raised {collected_amount}{target_line}. Share it with friends to help it reach its goal: {campaign_url}", 'devdiggers-crowdfunding-for-woocommerce' ),
				],
				'new_contribution'      => [
					'title'       => __( 'New Contribution to Organiser', 'devdiggers-crowdfunding-for-woocommerce' ),
					'description' => __( 'Sent to the campaign organiser for every paid contribution.', 'devdiggers-crowdfunding-for-woocommerce' ),
					'recipient'   => 'creator',
					'subject'     => __( 'New contribution to {campaign_name}', 'devdiggers-crowdfunding-for-woocommerce' ),
					'heading'     => __( 'You received a new contribution', 'devdiggers-crowdfunding-for-woocommerce' ),
					'message'     => __( "Hi {creator_name},\n\n{supporter_name} contributed {amount} to <strong>{campaign_name}</strong>. {reward_line}\n\n{message_line}\n\nThe campaign has raised {collected_amount}{target_line}.", 'devdiggers-crowdfunding-for-woocommerce' ),
				],
				'goal_reached'          => [
					'title'       => __( 'Goal Reached', 'devdiggers-crowdfunding-for-woocommerce' ),
					'description' => __( 'Sent to the organiser, with a copy to the admin, when a campaign reaches its goal.', 'devdiggers-crowdfunding-for-woocommerce' ),
					'recipient'   => 'creator',
					'admin_copy'  => true,
					'subject'     => __( '{campaign_name} reached its goal!', 'devdiggers-crowdfunding-for-woocommerce' ),
					'heading'     => __( 'Goal reached!', 'devdiggers-crowdfunding-for-woocommerce' ),
					'message'     => __( "Congratulations! <strong>{campaign_name}</strong> reached its goal of {target_amount} with {collected_amount} raised from {supporters_count} supporters.\n\nSee the campaign: {campaign_url}", 'devdiggers-crowdfunding-for-woocommerce' ),
				],
				'campaign_ended'        => [
					'title'       => __( 'Campaign Ended', 'devdiggers-crowdfunding-for-woocommerce' ),
					'description' => __( 'Sent to the organiser, with a copy to the admin, the day after the end date passes.', 'devdiggers-crowdfunding-for-woocommerce' ),
					'recipient'   => 'creator',
					'admin_copy'  => true,
					'subject'     => __( '{campaign_name} has ended', 'devdiggers-crowdfunding-for-woocommerce' ),
					'heading'     => __( 'Your campaign has ended', 'devdiggers-crowdfunding-for-woocommerce' ),
					'message'     => __( "Hi {creator_name},\n\n<strong>{campaign_name}</strong> ended with {collected_amount} raised{target_line}. Supporters: {supporters_count}.\n\nThank you for raising funds with us!", 'devdiggers-crowdfunding-for-woocommerce' ),
				],
			];
		}

		/**
		 * Saved settings of one type merged over its defaults.
		 *
		 * @param string $type Email type.
		 * @return array
		 */
		public static function get_settings( $type ) {
			$types = self::get_types();

			if ( ! isset( $types[ $type ] ) ) {
				return [];
			}

			// Free sends fixed wording: only the on/off switch is read from the saved settings.
			$saved = (array) get_option( self::OPTION, [] );

			return array_merge( $types[ $type ], [ 'enabled' => isset( $saved[ $type ]['enabled'] ) && 'no' === $saved[ $type ]['enabled'] ? 'no' : 'yes' ] );
		}

		/**
		 * Sanitize the email settings option. Only the switches are saved; any other
		 * saved values (for example wording written in Pro) are kept as they are.
		 *
		 * @param mixed $value Submitted value.
		 * @return array
		 */
		public static function sanitize( $value ) {
			$clean = (array) get_option( self::OPTION, [] );

			foreach ( array_keys( self::get_types() ) as $type ) {
				$row                       = isset( $value[ $type ] ) && is_array( $value[ $type ] ) ? $value[ $type ] : [];
				$clean[ $type ]            = isset( $clean[ $type ] ) && is_array( $clean[ $type ] ) ? $clean[ $type ] : [];
				$clean[ $type ]['enabled'] = isset( $row['enabled'] ) && 'yes' === $row['enabled'] ? 'yes' : 'no';
			}

			return $clean;
		}

		/**
		 * Build the placeholder map for a campaign (and optional order/update).
		 *
		 * @param int   $event_id Event ID.
		 * @param array $extra    Extra values: order, amount, reward, message.
		 * @return array
		 */
		public static function get_replacements( $event_id, $extra = [] ) {
			$data    = DDWCCF_Event_Helper::get_event_data( $event_id );
			$data    = $data ? $data : DDWCCF_Event_Helper::get_defaults();
			$creator = get_userdata( get_post_field( 'post_author', $event_id ) );
			$order   = $extra['order'] ?? null;
			$link    = fn( $url ) => '<a href="' . esc_url( $url ) . '">' . esc_html( $url ) . '</a>';

			return [
				'{campaign_name}'    => esc_html( get_the_title( $event_id ) ),
				'{campaign_url}'     => $link( get_permalink( $event_id ) ),
				'{creator_name}'     => esc_html( $creator ? $creator->display_name : '' ),
				// Emails are private, so use the real name even for anonymous supporters.
				'{supporter_name}'   => esc_html( $order ? ( trim( $order->get_formatted_billing_full_name() ) ? trim( $order->get_formatted_billing_full_name() ) : __( 'A supporter', 'devdiggers-crowdfunding-for-woocommerce' ) ) : '' ),
				'{amount}'           => isset( $extra['amount'] ) ? wc_price( $extra['amount'] ) : '',
				'{reward}'           => esc_html( $extra['reward'] ?? '' ),
				/* translators: %s: reward title. */
				'{reward_line}'      => ! empty( $extra['reward'] ) ? sprintf( esc_html__( 'Reward: %s.', 'devdiggers-crowdfunding-for-woocommerce' ), esc_html( $extra['reward'] ) ) : '',
				/* translators: %s: supporter message. */
				'{message_line}'     => ! empty( $extra['message'] ) ? sprintf( esc_html__( 'Their message: "%s"', 'devdiggers-crowdfunding-for-woocommerce' ), esc_html( $extra['message'] ) ) : '',
				'{collected_amount}' => wc_price( $data['collected_amount'] ),
				'{target_amount}'    => $data['target_amount'] ? wc_price( $data['target_amount'] ) : '',
				/* translators: %s: target amount. */
				'{target_line}'      => $data['target_amount'] ? sprintf( esc_html__( ' of its %s goal', 'devdiggers-crowdfunding-for-woocommerce' ), wc_price( $data['target_amount'] ) ) : '',
				'{supporters_count}' => esc_html( number_format_i18n( count( $data['contributors'] ) ) ),
				'{site_title}'       => esc_html( get_bloginfo( 'name' ) ),
			];
		}

		/**
		 * Send an email type for a campaign.
		 *
		 * @param string $type     Email type.
		 * @param int    $event_id Event ID.
		 * @param array  $extra    Extra placeholder values.
		 * @return void
		 */
		public static function send( $type, $event_id, $extra = [] ) {
			$settings = self::get_settings( $type );

			if ( ! $settings || 'yes' !== $settings['enabled'] ) {
				return;
			}

			$creator = get_userdata( get_post_field( 'post_author', $event_id ) );
			$admin   = get_option( '_ddwccf_admin_email' ) ? get_option( '_ddwccf_admin_email' ) : get_option( 'admin_email' );
			$map     = [
				'admin'     => $admin,
				'creator'   => $creator ? $creator->user_email : '',
				'supporter' => isset( $extra['order'] ) ? $extra['order']->get_billing_email() : '',
			];
			$to      = [ $map[ $settings['recipient'] ] ];

			if ( ! empty( $settings['admin_copy'] ) ) {
				$to[] = $admin;
			}

			$replace = self::get_replacements( $event_id, $extra );
			$fill    = fn( $text ) => strtr( $text, $replace );

			foreach ( array_unique( array_filter( $to ) ) as $email ) {
				do_action(
					'ddwccf_mail',
					[
						'email'   => $email,
						'subject' => wp_strip_all_tags( $fill( $settings['subject'] ) ),
						'heading' => wp_strip_all_tags( $fill( $settings['heading'] ) ),
						'message' => [ wpautop( $fill( wp_kses_post( $settings['message'] ) ) ) ],
						'type'    => $type,
					]
				);
			}
		}
	}
}
