<?php
/**
 * Run with: php -d zend.assertions=1 tests/framework-smoke.php
 *
 * @package DevDiggers Crowdfunding for WooCommerce
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'DAY_IN_SECONDS', 86400 );
function trailingslashit( $value ) {
	return rtrim( $value, '/' ) . '/';
}
function wc_format_decimal( $value, $dp = false ) {
	return false === $dp ? trim( (string) $value ) : (string) round( (float) $value, $dp );
}
function wp_parse_args( $args, $defaults ) {
	return array_merge( $defaults, (array) $args );
}
function absint( $value ) {
	return abs( (int) $value );
}
function wc_get_price_decimals() {
	return 2;
}
function sanitize_text_field( $value ) {
	return trim( (string) $value );
}
function sanitize_textarea_field( $value ) {
	return trim( (string) $value );
}
function sanitize_key( $value ) {
	return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $value ) );
}
function apply_filters( $hook, $value ) {
	return $value;
}
function current_time( $format ) {
	return 'Y-m-d' === $format ? '2026-10-16' : '2026-10-16 12:00:00';
}

require dirname( __DIR__ ) . '/autoload/autoload.php';

$admin = new DDWCCrowdFunding\Includes\Admin\DDWCCF_Admin_Functions( [] );
assert( $admin->sanitize_amount( '-3' ) === '' );
assert( $admin->sanitize_amount( '4.50' ) === '4.50' );
assert( $admin->sanitize_supporters_count( 500 ) === 50 );
assert( $admin->sanitize_checkbox( 'on' ) === '' && $admin->sanitize_checkbox( 'yes' ) === 'yes' );
assert( class_exists( 'DDWCCrowdFunding\\Includes\\DDWCCF_Admin_Dashboard' ) );

$helper = 'DDWCCrowdFunding\\Helper\\DDWCCF_Event_Helper';
assert( $helper::parse_preset_amounts( '50, 10,abc,-5,10 , 25' ) === [ 10.0, 25.0, 50.0 ] );
assert( $helper::get_progress( [ 'target_amount' => '200', 'collected_amount' => 250 ] ) === 125 );
assert( $helper::get_progress( [ 'target_amount' => '', 'collected_amount' => 250 ] ) === 0 );
assert( $helper::is_goal_reached( [ 'target_amount' => '100', 'collected_amount' => 100 ] ) );
assert( $helper::is_expired( [ 'end_date' => '2026-10-15' ] ) );
assert( ! $helper::is_expired( [ 'end_date' => '2026-10-16' ] ) );

// Rewards.
$data = [ 'rewards' => [ [ 'id' => 'r1', 'limit' => 2, 'claimed' => 2 ], [ 'id' => 'r2', 'limit' => 0, 'claimed' => 9 ] ] ];
assert( ! $helper::is_reward_available( $helper::get_reward( $data, 'r1' ) ) );
assert( $helper::is_reward_available( $helper::get_reward( $data, 'r2' ) ) );
assert( null === $helper::get_reward( $data, 'nope' ) );

// Free caps reward tiers at three, but never drops tiers a campaign already has.
$form = 'DDWCCrowdFunding\\Helper\\DDWCCF_Event_Form_Helper';
$rows = array_map( fn( $i ) => [ 'id' => 'r' . $i, 'title' => 'T' . $i, 'amount' => $i * 10 ], range( 1, 5 ) );
assert( 3 === count( $form::parse_rewards( $rows ) ) );
assert( 4 === count( $form::parse_rewards( $rows, array_slice( $rows, 0, 4 ) ) ) );

// Boundary: Pro-only logic must not exist in Free.
foreach ( [ 'is_failed', 'get_fee', 'get_withdrawal_charges', 'get_cover_fee_rate', 'get_milestones', 'can_request_withdrawal', 'get_backer_emails' ] as $method ) {
	assert( ! method_exists( $helper, $method ), $method );
}
assert( ! class_exists( 'DDWCCrowdFunding\\Includes\\Admin\\DDWCCF_Admin_Ajax_Functions' ) );

$root  = dirname( __DIR__ );
$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ) );
foreach ( $files as $file ) {
	$path = substr( $file->getPathname(), strlen( $root ) + 1 );
	if ( preg_match( '#^(node_modules|devdiggers-framework|tests|bin|i18n)/#', $path ) || ! preg_match( '/\.(php|js)$/', $path ) ) {
		continue;
	}
	$code = file_get_contents( $file->getPathname() );
	foreach ( [ 'wp_ajax_', 'register_rest_route', 'wc_create_refund', 'as_enqueue_async_action', 'media_handle_upload', '_ddwccf_license', 'ddwccf_export', 'DDWCWM_', 'wp_unschedule_hook', 'ddwccf_embed' ] as $banned ) {
		assert( false === strpos( $code, $banned ), "$banned in $path" );
	}
}

echo "Crowdfunding Free smoke check passed.\n";
