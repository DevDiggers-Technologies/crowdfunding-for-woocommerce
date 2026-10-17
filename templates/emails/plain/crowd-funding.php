<?php
/**
 * Crowdfunding notification email (plain text).
 *
 * @package WooCommerce Crowdfunding
 * @version 2.0.0
 *
 * @var string   $email_heading      Heading.
 * @var string[] $email_message      Paragraphs.
 * @var string   $additional_content Additional content.
 */

defined( 'ABSPATH' ) || exit();

echo '= ' . esc_html( wp_strip_all_tags( $email_heading ) ) . " =\n\n";

foreach ( (array) $email_message as $ddwccf_message ) {
	echo esc_html( wp_strip_all_tags( str_replace( [ '</p>', '<br />', '<br>' ], "\n", $ddwccf_message ) ) ) . "\n\n";
}

if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ) . "\n\n";
}

echo "\n----------------------------------------\n\n";

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core hook.
