<?php
/**
 * Crowdfunding notification email (HTML).
 *
 * @package WooCommerce Crowdfunding
 * @version 2.0.0
 *
 * @var string    $email_heading      Heading.
 * @var string[]  $email_message      Paragraphs (HTML).
 * @var string    $additional_content Additional content.
 * @var \WC_Email $email              Email object.
 */

defined( 'ABSPATH' ) || exit();

do_action( 'woocommerce_email_header', $email_heading, $email ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core hook.

foreach ( (array) $email_message as $ddwccf_message ) {
	echo wp_kses_post( $ddwccf_message );
}

if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core hook.
