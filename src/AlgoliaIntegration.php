<?php

namespace TinyPixel\Platform\Search;

use Algolia\AlgoliaSearch\SearchClient;
use TinyPixel\Platform\Search\HTMLSplitter;

/**
 * Algolia Integration
 */
class AlgoliaIntegration
{
    public $algolia;

    public $splitter;

    public $synonyms;

    public $indexedMetaKeys = ['seo_description', 'seo_title'];

    /**
     * Class constructor.
     *
     * @param \Algolia\AlgoliaSearch\SearchClient
     */
    public function __construct(\Algolia\AlgoliaSearch\SearchClient $algolia)
    {
        $this->init($algolia);

        $this->hooks();
    }

    /**
     * Init
     *
     * @param \Algolia\AlgoliaSearch\SearchClient
     */
    public function init(\Algolia\AlgoliaSearch\SearchClient $algolia)
    {
        $this->algolia  = $algolia;
        $this->splitter = new HTMLSplitter;

        if (file_exists($synonyms = __DIR__ . '/synonyms.json')) {
            $this->synonyms = json_decode(file_get_contents($synonyms), true);
        }
    }

    /**
     * Hooks
     */
    public function hooks()
    {
        add_filter('get_post_settings', [$this, 'searchSettings']);
        add_filter('post_to_record', [$this, 'updateOnPublish']);
        add_filter('save_post', [$this, 'updateOnDelete'], 10, 3);
        add_filter('get_post_synonyms', [$this, 'synonyms']);
        add_action('update_post_meta', [$this, 'updatePostMeta'], 10, 4);
    }

    /**
     * Search settings
     */
    public function searchSettings()
    {
        return [
            'hitsPerPage' => 18,
            'searchableAttributes' => [
                'title',
                'content',
                'author.name'
            ],
        ];
    }

    /**
     * Update on Publish
     *
     * @param \WP_Post $post updated post
     * @return array $records
     */
    public function updateOnPublish(\WP_Post $post): array
    {
        $tags = array_map(function (\WP_Term $term) {
            return $term->name;
        }, wp_get_post_terms($post->ID, 'post_tag'));

        // Prepare all the common attributes
        // Add a new `distinct_key` (same value as the previous objectID)
        $common = [
            'distinct_key' => implode('#', [$post->post_type, $post->ID]),
            'title' => $post->post_title,
            'author' => [
                'id' => $post->post_author,
                'name' => get_user_by('ID', $post->post_author)->display_name,
            ],
            'excerpt' => $post->post_excerpt,
            'content' => strip_tags($post->post_content),
            'tags' => $tags,
            'url' => get_post_permalink($post->ID),
        ];

        // Split the records on the `post_content` attribute
        $records = $this->splitter->split($post);

        // Merge the common attributes into all split child
        // Add a unique objectID
        foreach ($records as $key => $split) {
            $records[$key] = array_merge($common, $split, [
                'objectID' => implode('-', [$post->post_type, $post->ID, $key]),
            ]);
        }

        return $records;
    }

    /**
     * Update Post Meta
     */
    public function updatePostMeta($meta_id, $object_id, $meta_key, $_meta_value)
    {
        if (in_array($meta_key, $this->indexedMetaKeys)) {
            $index = $this->algolia->initIndex(apply_filters('algolia_index_name', 'post'));

            $index->partialUpdateObject([
                'objectID' => 'post#' . $object_id,
                $meta_key => $_meta_value,
            ]);
        }
    }

    /**
     * Update on post deletion
     */
    public function updateOnDelete($id, \WP_Post $post, $update)
    {
        if (wp_is_post_revision($id) || wp_is_post_autosave($id)) {
            return $post;
        }

        $record = (array) apply_filters($post->post_type . '_to_record', $post);

        if (!isset($record['objectID'])) {
            $record['objectID'] = implode('#', [$post->post_type, $post->ID]);
        }

        $index = $this->algolia->initIndex(apply_filters('algolia_index_name', $post->post_type));

        // If the post is split, we always delete it
        // If it was deleted, we're good. It it's update, we'll push the new version
        if ($splitRecord = $this->isSplitRecord($record)) {
            $index->deleteBy([
                'filters' => 'distinct_key:' . $record['distinct_key']
            ]);
        }

        if ($post->status == 'trash') {
            // If the post was split, it's already deleted
            if (! $splitRecord) {
                $index->deleteObject($record['objectID']);
            }
        } else {
            $index->saveObjects($record);
        }

        return $post;
    }

    /**
     * Synonyms
     */
    public function synonyms($default)
    {
        return $this->synonyms;
    }

    /**
     * Is split record
     */
    protected function isSplitRecord($record)
    {
        return array_keys($record) == range(0, count($record) - 1);
    }
}
