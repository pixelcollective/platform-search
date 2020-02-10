<?php

/**
 * Plugin Name: Platform Search
 * Description: WordPress search customization
 * Text Domain: tiny-pixel
 * Version:     1.0.0
 */

namespace TinyPixel\Platform\Search;

require_once __DIR__ . '/vendor/autoload.php';

use Algolia\AlgoliaSearch\SearchClient;
use TinyPixel\Platform\Search\AlgoliaCLI;
use TinyPixel\Platform\Search\AlgoliaIntegration;

/**
 * Use Algolia connect as a global
 */
global $algolia;

/**
 * Application credentials
 */
$credentials = (object) [
    'appId'     => defined('ALGOLIA_APP_ID') ? ALGOLIA_APP_ID : null,
    'adminKey'  => defined('ALGOLIA_ADMIN_API_KEY') ? ALGOLIA_ADMIN_API_KEY : null,
    'searchKey' => defined('ALGOLIA_SEARCH_API_KEY') ? ALGOLIA_SEARCH_API_KEY : null,
];

/**
 * Initialize Algolia
 */
new AlgoliaIntegration(
    $algolia = SearchClient::create(
        $credentials->appId,
        $credentials->adminKey
    )
);

/**
 * Add WP_CLI index command
 */
if (class_exists('WP_CLI')) {
    \WP_CLI::add_command('algolia', AlgoliaCLI::class);
}

/**
 * Enqueue search client assets
 */
add_action('wp_enqueue_scripts', function () use ($credentials) {
    $vendor = WPMU_PLUGIN_URL . '/platform-search/dist/vendor.js';
    $script = WPMU_PLUGIN_URL . '/platform-search/dist/client.js';
    $style  = WPMU_PLUGIN_URL . '/platform-search/dist/client.css';

    /**
     * Client scripts
     */
    wp_enqueue_script('algolia-client-vendor', $vendor, [], null, true);
    wp_enqueue_script('algolia-client-js', $script, ['algolia-client-vendor'], null, true);

    wp_localize_script('algolia-client-js', 'settings', [
        'id'        => $credentials->appId,
        'key'       => $credentials->searchKey,
        'indexName' => apply_filters('algolia_index_name', 'post'),
        'appName'   => get_bloginfo('name'),
    ]);

    /**
     * Deps manifest
     */
    if (file_exists($manifest = WPMU_PLUGIN_DIR . '/platform-search/dist/manifest.js')) {
        wp_add_inline_script('algolia-client-vendor', file_get_contents($manifest), 'before');
    }

    /**
     * Styles
     */
    wp_enqueue_style('algolia-client-css', $style, [], time());
});
