<?php
/**
 * Admin hooks.
 *
 * @author DevDiggers
 * @package DevDiggers Crowdfunding for WooCommerce
 * @version 1.0.0
 */

namespace DDWCCrowdFunding\Includes\Admin;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCCF_Admin_Hooks' ) ) {
	/**
	 * Admin hooks class.
	 */
	class DDWCCF_Admin_Hooks extends DDWCCF_Admin_Functions {
		/**
		 * Construct.
		 *
		 * @param array $ddwccf_configuration Configuration.
		 */
		public function __construct( $ddwccf_configuration ) {
			parent::__construct( $ddwccf_configuration );

			add_filter( 'set-screen-option', [ $this, 'ddwccf_set_options' ], 10, 3 );
			add_action( 'admin_init', [ $this, 'ddwccf_register_settings' ] );
			add_action( 'admin_init', [ $this, 'ddwccf_handle_event_save' ] );
			add_action( 'admin_init', [ $this, 'ddwccf_handle_events_bulk_action' ] );
			add_filter( 'display_post_states', [ $this, 'ddwccf_add_post_state' ], 10, 2 );
			add_filter( 'get_edit_post_link', [ $this, 'ddwccf_edit_post_link' ], 10, 2 );
		}
	}
}
