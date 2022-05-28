
<?php

/**
* @package Post filters
* Plugin Name: Post filters
* Plugin URI: https://www.blackthorn.ai/
* Description: This plugin add REST API for projects, research, careers and blog posts filtering.
* Version: 2022.05.28
* Author: Pavlo Tymoshenko, Anastasiia Hrynyshyn
**/

include_once 'text-utils.php';

/*

This code registers new API endpoints at address:
blackthorn.ai/wo-json/posts-api/v1/<ROUTE>

*/

const API_NAMESPACE = 'posts-api';

function pf_get_multiple_labels(WP_REST_Request $request, string $filter_name) {
    return $request->has_param($filter_name)
        ? explode(';', $request->get_param($filter_name))
        : array();
}

function pf_meta_query_params(WP_REST_Request $request, array $filter_names) {
    $params = [];

    foreach ($filter_names as $filter_name) {
        foreach(pf_get_multiple_labels($request, $filter_name) as $filter_label) {
            $params[] = array(
                'key'      => $filter_name,
                'value'    => $filter_label,
                'compare'  => 'LIKE'
            );
        }
    }

    return $params;
}

function pf_get_meta_query(WP_REST_Request $request, array $filter_names) {
    $params = pf_meta_query_params($request, $filter_names);
    if (count($params) == 0) return array();

    if (count($params) == 1) return array(
        'meta_query' => $params
    );

    else /* count($params) > 1 */ return array(
        'meta_query' => array(
            'relation' => 'AND',
            ...$params
        )
    );
}

function pf_list_query_project_pages(WP_Query $query): array {
    $extract_page = function($page): array {
        $profile = array_map(
            'pf_remove_a_tag',
            get_field('customer_profile', $page->ID)
        );

        $description = '';

        if (count($profile) == 1)
            $description = $profile[0];

        else for ($i=1; $i < count($profile); $i++)
            $description .= "{$i}. {$profile[$i]} ";

        return array(
            'description' => pf_cut_words($description, 240),
            'keywords'    => get_field('keyword_labels', $page->ID),
            'link'        => $page->post_name,
            'title'       => $page->post_title,
            'thumbnail'   => get_the_post_thumbnail_url($page->ID),
            'filtering'   => array(
                'keyword_labels'  => get_field('keyword_labels', $page->ID),
                'service_labels'  => get_field('service_labels', $page->ID),
                'industry_labels' => get_field('industry_labels', $page->ID)
            )
        );
    };

    return array_map($extract_page, $query->posts);
}

function pf_list_query_research_pages(WP_Query $query): array {
    $extract_page = function($page): array {
        return array(
            'description' => pf_cut_words(get_field('description', $page->ID), 240),
            'article'     => get_field('article_link', $page->ID),
            'keywords'    => get_field('keyword_labels', $page->ID),
            'pdf'         => get_field('pdf_link', $page->ID),
            'publication_date'=> get_field('publication_date', $page->ID),
            'publisher'   => get_field('publisher', $page->ID),
            'title'       => $page->post_title,
            'thumbnail'   => get_the_post_thumbnail_url($page->ID),
            'filtering'   => array(
                'keyword_labels'  => get_field('keyword_labels', $page->ID),
                'industry_labels' => get_field('industry_labels', $page->ID)
            )
        );
    };

    return array_map($extract_page, $query->posts);
}

function pf_list_query_blog_pages(WP_Query $query): array {
    $extract_page = function($page): array {
        return array(
            'description' => pf_cut_words(get_field('description', $page->ID), 240),
            'keywords'    => get_field('keyword_labels', $page->ID),
            'publication_date'=> get_field('publication_date', $page->ID),
            'theme'       => get_field('theme', $page->ID),
            'time_to_read'=> get_field('time_to_read', $page->ID),
            'link'        => get_field('link', $page->ID),
            'title'       => $page->post_title,
            'thumbnail'   => get_the_post_thumbnail_url($page->ID),
            'filtering'   => array(
                'keyword_labels'  => get_field('keyword_labels', $page->ID)
            )
        );
    };

    return array_map($extract_page, $query->posts);
}

function pf_list_query_careers_pages(WP_Query $query): array {
    $extract_page = function($page): array {
        return array(
            'description' => pf_cut_words(get_field('description', $page->ID), 240),
            'skills'    => get_field('skill_labels', $page->ID),
            'title'       => $page->post_title,
            'link'        => $page->post_name,
            'thumbnail'   => get_the_post_thumbnail_url($page->ID),
            'filtering'   => array(
                'skill_labels'  => get_field('skill_labels', $page->ID)
            )
        );
    };

    return array_map($extract_page, $query->posts);
}

const PROJECTS_PAGE_ID   = 755;
const BLOG_PAGE_ID       = 1651;
const RESEARCHES_PAGE_ID = 1720;
const CAREERS_PAGE_ID    = 1796;

const SEARCH_QUERY_BASE = array(
    'numberposts'  => -1,
    'nopaging' => true,
    'post_type'    => 'page',
    'post_status'  => 'publish',
    'meta_key'     => 'display_order',
    'orderby'      => 'meta_value_num',
    'order'        => 'ASC'
);

/**
 * pf_filter_project_pages
 * @return WP_REST_Response
 */
function pf_filter_project_pages(WP_REST_Request $request) {
    $query = new WP_Query(array_merge(
        SEARCH_QUERY_BASE,
        array('post_parent' => PROJECTS_PAGE_ID),
        pf_get_meta_query(
            $request,
            ['industry_labels', 'service_labels', 'keyword_labels']
        )));

    return new WP_REST_Response(pf_list_query_project_pages($query));
}

/**
 * pf_filter_blog_pages
 * @return WP_REST_Response
 */
function pf_filter_blog_pages(WP_REST_Request $request) {
    $query = new WP_Query(array_merge(
        SEARCH_QUERY_BASE,
        array('post_parent' => BLOG_PAGE_ID),
        pf_get_meta_query(
            $request,
            ['keyword_labels']
        )));

    return new WP_REST_Response(pf_list_query_blog_pages($query));
}

/**
 * pf_filter_researches
 * @return WP_REST_Response
 */
function pf_filter_researches(WP_REST_Request $request) {
    $query = new WP_Query(array_merge(
        SEARCH_QUERY_BASE,
        array('post_parent' => RESEARCHES_PAGE_ID),
        pf_get_meta_query(
            $request,
            ['industry_labels', 'keyword_labels']
        )));

    return new WP_REST_Response(pf_list_query_research_pages($query));
}

/**
 * pf_filter_careers_pages
 * @return WP_REST_Response
 */
function pf_filter_careers_pages(WP_REST_Request $request) {
    $query = new WP_Query(array_merge(
        SEARCH_QUERY_BASE,
        array('post_parent' => CAREERS_PAGE_ID),
        pf_get_meta_query(
            $request,
            ['skill_labels']
        )));

    return new WP_REST_Response(pf_list_query_careers_pages($query));
}

function pf_reg_methods_v1() {
    $v         = '1';

    register_rest_route(
        API_NAMESPACE . "/v${v}",  // pages-api/v1
        'projects',                // route
        array(
            'methods'   => 'GET',
            'callback'  => 'pf_filter_project_pages',
            'permission_callback' => '__return_true'
        )
    );

    register_rest_route(
        API_NAMESPACE . "/v${v}",  // pages-api/v1
        'blog',                    // route
        array(
            'methods'   => 'GET',
            'callback'  => 'pf_filter_blog_pages',
            'permission_callback' => '__return_true'
        )
    );

    register_rest_route(
        API_NAMESPACE . "/v${v}",  // pages-api/v1
        'researches',              // route
        array(
            'methods'   => 'GET',
            'callback'  => 'pf_filter_researches',
            'permission_callback' => '__return_true'
        )
    );

    register_rest_route(
        API_NAMESPACE . "/v${v}",  // pages-api/v1
        'careers',                    // route
        array(
            'methods'   => 'GET',
            'callback'  => 'pf_filter_careers_pages',
            'permission_callback' => '__return_true'
        )
    );
}

add_action('rest_api_init', 'pf_reg_methods_v1');
