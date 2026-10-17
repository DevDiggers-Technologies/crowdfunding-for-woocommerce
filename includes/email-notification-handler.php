<?php
/**
 * Crowdfunding notification email.
 *
 * @package DevDiggers Crowdfunding for WooCommerce
 * @version 1.0.0
 */

namespace DDWCCrowdFunding\Includes;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCCF_Email_Notification_Handler' ) ) {
	/**
	 * Email notification class.
	 */
	class DDWCCF_Email_Notification_Handler extends \WC_Email {
		/**
		 * Message paragraphs.
		 *
		 * @var string[]
		 */
		public $email_message = [];

		/**
		 * Recipient display name.
		 *
		 * @var string
		 */
		public $display_name = '';

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id             = 'ddwccf_notification';
			$this->title          = __( 'Crowdfunding Notification', 'devdiggers-crowdfunding-for-woocommerce' );
			$this->description    = __( 'Delivers every Crowdfunding notification. Turn each one on or off under Crowdfunding > Configuration > Emails.', 'devdiggers-crowdfunding-for-woocommerce' );
			$this->template_html  = 'emails/crowd-funding.php';
			$this->template_plain = 'emails/plain/crowd-funding.php';
			$this->template_base  = DDWCCF_PLUGIN_FILE . 'templates/';
			$this->customer_email = true;

			add_action( 'ddwccf_mail_notification', [ $this, 'trigger' ] );

			parent::__construct();
		}

		/**
		 * Subject and heading of the email being sent.
		 *
		 * @var array
		 */
		protected $custom = [];

		/**
		 * Subject, preferring the per-notification one.
		 *
		 * @return string
		 */
		public function get_subject() {
			return ! empty( $this->custom['subject'] ) ? $this->format_string( $this->custom['subject'] ) : parent::get_subject();
		}

		/**
		 * Heading, preferring the per-notification one.
		 *
		 * @return string
		 */
		public function get_heading() {
			return ! empty( $this->custom['heading'] ) ? $this->format_string( $this->custom['heading'] ) : parent::get_heading();
		}

		/**
		 * Default subject.
		 *
		 * @return string
		 */
		public function get_default_subject() {
			return __( '[{site_title}] Crowdfunding update', 'devdiggers-crowdfunding-for-woocommerce' );
		}

		/**
		 * Default heading.
		 *
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'Crowdfunding update', 'devdiggers-crowdfunding-for-woocommerce' );
		}

		/**
		 * Send the email.
		 *
		 * @param array $data email, message[], display_name.
		 * @return void
		 */
		public function trigger( $data ) {
			if ( empty( $data['email'] ) || empty( $data['message'] ) ) {
				return;
			}

			$this->setup_locale();

			$this->recipient     = $data['email'];
			$this->email_message = array_filter( (array) $data['message'] );
			$this->custom        = [
				'subject' => $data['subject'] ?? '',
				'heading' => $data['heading'] ?? '',
			];

			if ( $this->is_enabled() && $this->get_recipient() ) {
				$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
			}

			$this->custom = [];

			$this->restore_locale();
		}

		/**
		 * Template args.
		 *
		 * @param bool $plain_text Plain text.
		 * @return array
		 */
		protected function get_template_args( $plain_text ) {
			return [
				'email_heading'      => $this->get_heading(),
				'email_message'      => $this->email_message,
				'additional_content' => $this->get_additional_content(),
				'display_name'       => $this->display_name,
				'customer_email'     => $this->get_recipient(),
				'blogname'           => $this->get_blogname(),
				'sent_to_admin'      => false,
				'plain_text'         => $plain_text,
				'email'              => $this,
			];
		}

		/**
		 * HTML content.
		 *
		 * @return string
		 */
		public function get_content_html() {
			return wc_get_template_html( $this->template_html, $this->get_template_args( false ), '', $this->template_base );
		}

		/**
		 * Plain content.
		 *
		 * @return string
		 */
		public function get_content_plain() {
			return wc_get_template_html( $this->template_plain, $this->get_template_args( true ), '', $this->template_base );
		}
	}
}
