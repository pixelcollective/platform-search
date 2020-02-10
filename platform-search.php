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
 * Credentials
 */
$credentials = (object) [
    'appId'     => defined('ALGOLIA_APP_ID')         ? ALGOLIA_APP_ID         : null,
    'adminKey'  => defined('ALGOLIA_ADMIN_API_KEY')  ? ALGOLIA_ADMIN_API_KEY  : null,
    'searchKey' => defined('ALGOLIA_SEARCH_API_KEY') ? ALGOLIA_SEARCH_API_KEY : null,
];

/**
 * Initialize Algolia
 */
$algolia = SearchClient::create(
    $credentials->appId,
    $credentials->adminKey
);

/**
 * Add WP_CLI index command
 */
if (class_exists('WP_CLI')) {
    \WP_CLI::add_command('algolia', AlgoliaCLI::class);
}

/**
 * Hook WordPress events
 */
new AlgoliaIntegration($algolia);

/**
 * Enqueue search client assets
 */
add_action('wp_enqueue_scripts', function () use ($credentials) {
    $script = WPMU_PLUGIN_URL . '/platform-search/dist/client.js';
    $style  = WPMU_PLUGIN_URL . '/platform-search/dist/client.css';

    wp_enqueue_script('algolia-client-js', $script, [], time(), true);
    wp_enqueue_style('algolia-client-css', $style, [], time());

    wp_localize_script('algolia-client-js', 'settings', [
        'id'        => $credentials->appId,
        'key'       => $credentials->searchKey,
        'indexName' => apply_filters('algolia_index_name', 'post'),
    ]);
});
