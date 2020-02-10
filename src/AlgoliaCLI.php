<?php

namespace TinyPixel\Platform\Search;

use \Exception;
use \WP_CLI;

class AlgoliaCLI
{
    public function __construct()
    {
        global $algolia;

        $this->algolia = $algolia;
    }

    public function reindex($args, $assocArgs)
    {
        $index = $this->algolia->initIndex(
            apply_filters('algolia_index_name', 'post')
        );

        $index->clearObjects()->wait();

        $paged = 1;
        $count = 0;

        do {
            $posts = new \WP_Query([
                'posts_per_page' => 100,
                'paged' => $paged,
                'post_type' => 'post',
                'post_status' => 'publish',
            ]);

            if (! $posts->have_posts()) {
                break;
            }

            $records = [];

            foreach ($posts->posts as $post) {
                if ($assocArgs['verbose']) {
                    \WP_CLI::line('Serializing [' . $post->post_title . ']');
                }

                $split = apply_filters('post_to_record', $post);

                $records = array_merge($records, $split);
                $count++;
            }

            if ($assocArgs['verbose']) {
                \WP_CLI::line('Sending batch');
            }

            $index->saveObjects($records);

            $paged++;
        } while (true);

        \WP_CLI::success("$count posts indexed in Algolia");
    }

    public function set_config($args, $assocArgs)
    {
        $canonicalIndexName = $assocArgs['index'];

        if (! $canonicalIndexName) {
            throw new Exception('--index argument is required');
        }

        $index = $this->algolia->initIndex(apply_filters('algolia_index_name', $canonicalIndexName));

        if ($assocArgs['settings']) {
            $settings = (array) apply_filters('get_' . $canonicalIndexName . '_settings', []);

            if ($settings) {
                $index->setSettings($settings);

                \WP_CLI::success('Push settings to ' . $index->getIndexName());
            }
        }

        if ($assocArgs['synonyms']) {
            $synonyms = (array) apply_filters('get_' . $canonicalIndexName . '_synonyms', []);

            if ($synonyms) {
                $index->replaceAllSynonyms($synonyms);

                \WP_CLI::success('Push synonyms to ' . $index->getIndexName());
            }
        }

        if ($assocArgs['rules']) {
            $rules = (array) apply_filters('get_' . $canonicalIndexName . '$rules', []);

            if ($rules) {
                $index->replaceAllRules($rules);

                \WP_CLI::success('Push rules to ' . $index->getIndexName());
            }
        }
    }
}
