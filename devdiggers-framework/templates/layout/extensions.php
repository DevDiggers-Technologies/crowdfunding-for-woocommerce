<?php
/**
 * DevDiggers Extensions Page Template
 *
 * @author DevDiggers
 * @version 1.0.0
 * @package DevDiggers\Framework
 */

defined( 'ABSPATH' ) || exit(); // Exit if accessed directly.

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template scope variables are local include variables.
$plugins_api      = DDFW_Plugins_API::instance();
$has_consent      = $plugins_api->has_consent();
$website_plugins  = $plugins_api->get_website_plugins();
$featured_plugins = $plugins_api->get_featured_plugins();
$plugin_stats     = $plugins_api->get_plugin_statistics();

// Featured extensions are not repeated in the full list.
$featured_urls = wp_list_pluck( $featured_plugins, 'url' );
$other_plugins = array_filter(
	$website_plugins,
	function ( $plugin ) use ( $featured_urls ) {
		return ! in_array( $plugin['url'] ?? '', $featured_urls, true );
	}
);

$svg_open = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">';
$svg_tags = ddfw_kses_allowed_svg_tags();

$stats = [
	[
		'label' => esc_html__( 'Total Extensions', 'devdiggers-crowdfunding-for-woocommerce' ),
		'value' => $plugin_stats['total_plugins'] ?? 0,
		'icon'  => '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="M3.3 7 12 12l8.7-5"/><path d="M12 22V12"/>',
	],
	[
		'label' => esc_html__( 'Years Experience', 'devdiggers-crowdfunding-for-woocommerce' ),
		'value' => ( (int) gmdate( 'Y' ) - 2018 ) . '+',
		'icon'  => '<circle cx="12" cy="8" r="6"/><path d="M15.5 13 17 22l-5-3-5 3 1.5-9"/>',
	],
	[
		'label' => esc_html__( '5 Star Reviews', 'devdiggers-crowdfunding-for-woocommerce' ),
		'value' => esc_html__( '500+', 'devdiggers-crowdfunding-for-woocommerce' ),
		'icon'  => '<path d="M12 2l3.1 6.3 6.9 1-5 4.9 1.2 6.8L12 17.8 5.8 21l1.2-6.8-5-4.9 6.9-1z"/>',
	],
	[
		'label' => esc_html__( 'Support', 'devdiggers-crowdfunding-for-woocommerce' ),
		'value' => esc_html__( '24/7', 'devdiggers-crowdfunding-for-woocommerce' ),
		'icon'  => '<path d="M21 11.5a8.4 8.4 0 0 1-9 8.4 8.6 8.6 0 0 1-3.8-.9L3 21l1.9-5.2a8.4 8.4 0 0 1-.9-3.8 8.5 8.5 0 0 1 8.5-8.5h.5a8.5 8.5 0 0 1 8 8z"/>',
	],
];

/**
 * Print one extension card.
 *
 * @param array $plugin Extension data from the DevDiggers API.
 * @return void
 */
$render_card = function ( $plugin ) {
	?>
	<div class="ddfw-plugin-card ddfw-extension-card">
		<a class="ddfw-plugin-image" href="<?php echo esc_url( $plugin['url'] ); ?>" target="_blank" rel="noopener noreferrer" tabindex="-1">
			<img src="<?php echo esc_url( $plugin['image'] ); ?>" alt="<?php echo esc_attr( $plugin['name'] ); ?>" loading="lazy" />
		</a>
		<div class="ddfw-plugin-content">
			<h3><?php echo esc_html( $plugin['name'] ); ?></h3>
			<p class="ddfw-plugin-description"><?php echo esc_html( ! empty( $plugin['one_liner'] ) ? $plugin['one_liner'] : $plugin['description'] ); ?></p>
		</div>
		<div class="ddfw-plugin-footer">
			<a href="<?php echo esc_url( $plugin['url'] ); ?>" target="_blank" rel="noopener noreferrer" class="ddfw-button ddfw-button-primary"><?php esc_html_e( 'View Plugin', 'devdiggers-crowdfunding-for-woocommerce' ); ?></a>
			<div class="ddfw-extension-links">
				<?php if ( ! empty( $plugin['demo_url'] ) ) : ?>
					<a href="<?php echo esc_url( $plugin['demo_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Demo', 'devdiggers-crowdfunding-for-woocommerce' ); ?></a>
				<?php endif; ?>
				<?php if ( ! empty( $plugin['documentation_url'] ) ) : ?>
					<a href="<?php echo esc_url( $plugin['documentation_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Docs', 'devdiggers-crowdfunding-for-woocommerce' ); ?></a>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<?php
};
?>
<div class="wrap devdiggers-wrap ddfw-hub">
	<?php include DDFW_FILE . 'templates/layout/devdiggers-header.php'; ?>

	<div class="ddfw-extensions-page ddfw-dashboard-container">
		<div class="ddfw-dashboard-header">
			<div class="ddfw-dashboard-welcome">
				<h1><?php esc_html_e( 'Extensions', 'devdiggers-crowdfunding-for-woocommerce' ); ?></h1>
				<p><?php esc_html_e( 'Premium WooCommerce extensions, built and supported by DevDiggers.', 'devdiggers-crowdfunding-for-woocommerce' ); ?></p>
			</div>
		</div>

		<?php if ( ! $has_consent ) : ?>
			<div class="ddfw-dashboard-section ddfw-extensions-consent">
				<div class="ddfw-section-header">
					<h2><?php esc_html_e( 'Load the extensions list', 'devdiggers-crowdfunding-for-woocommerce' ); ?></h2>
					<p><?php esc_html_e( 'The list of extensions is kept on devdiggers.com. Nothing is loaded from there until you choose to.', 'devdiggers-crowdfunding-for-woocommerce' ); ?></p>
				</div>
				<p><?php esc_html_e( 'If you load it, your server requests the public list from devdiggers.com and your browser loads each extension\'s image from there. No personal data and no store data are sent, only the standard details of any web request, such as your IP address. The list is cached for a day and you can stop loading it at any time.', 'devdiggers-crowdfunding-for-woocommerce' ); ?></p>
				<form method="post">
					<?php wp_nonce_field( 'ddfw_extensions_consent_action', 'ddfw_extensions_nonce' ); ?>
					<input type="hidden" name="ddfw_extensions_consent" value="yes" />
					<button type="submit" class="ddfw-button ddfw-button-primary"><?php esc_html_e( 'Load extensions from devdiggers.com', 'devdiggers-crowdfunding-for-woocommerce' ); ?></button>
					<a class="ddfw-button ddfw-button-secondary" href="https://devdiggers.com/woocommerce-extensions/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Browse on devdiggers.com instead', 'devdiggers-crowdfunding-for-woocommerce' ); ?></a>
				</form>
			</div>
		<?php else : ?>
		<div class="ddfw-dashboard-stats">
			<?php foreach ( $stats as $stat ) : ?>
				<div class="ddfw-stat-card">
					<div class="ddfw-stat-icon"><?php echo wp_kses( $svg_open . $stat['icon'] . '</svg>', $svg_tags ); ?></div>
					<div class="ddfw-stat-content">
						<p><?php echo esc_html( $stat['label'] ); ?></p>
						<h3><?php echo esc_html( $stat['value'] ); ?></h3>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

		<?php if ( ! empty( $featured_plugins ) ) : ?>
			<div class="ddfw-dashboard-section">
				<div class="ddfw-section-header">
					<h2><?php esc_html_e( 'Featured', 'devdiggers-crowdfunding-for-woocommerce' ); ?></h2>
					<p><?php esc_html_e( 'Our most popular extensions.', 'devdiggers-crowdfunding-for-woocommerce' ); ?></p>
				</div>
				<div class="ddfw-plugins-grid">
					<?php array_map( $render_card, $featured_plugins ); ?>
				</div>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $other_plugins ) ) : ?>
			<div class="ddfw-dashboard-section">
				<div class="ddfw-section-header">
					<h2><?php esc_html_e( 'All Extensions', 'devdiggers-crowdfunding-for-woocommerce' ); ?></h2>
				</div>
				<div class="ddfw-plugins-grid" id="extensions-grid">
					<?php array_map( $render_card, $other_plugins ); ?>
				</div>
			</div>
		<?php endif; ?>

		<form method="post" class="ddfw-extensions-revoke">
			<?php wp_nonce_field( 'ddfw_extensions_consent_action', 'ddfw_extensions_nonce' ); ?>
			<input type="hidden" name="ddfw_extensions_consent" value="no" />
			<p>
				<?php esc_html_e( 'This list is loaded from devdiggers.com because you allowed it.', 'devdiggers-crowdfunding-for-woocommerce' ); ?>
				<button type="submit" class="button-link"><?php esc_html_e( 'Stop loading it', 'devdiggers-crowdfunding-for-woocommerce' ); ?></button>
			</p>
		</form>
		<?php endif; ?>
	</div>
</div>
