<?php
/**
 * Plugin Name: Mandala Proxy
 * Description: Proxies requests to sites like the KMaps Geoserver to bypass CORS.
 * Version: 1.0
 * Author: Than Grove (via ChatGPT)
 */

add_action('init', function () {
    add_rewrite_rule('^proxy/wfs/?$', 'index.php?geoserver_proxy=1', 'top');
});

add_filter('query_vars', function ($vars) {
    $vars[] = 'geoserver_proxy';
    return $vars;
});

add_action('template_redirect', function () {
    if (get_query_var('geoserver_proxy')) {
        $base_url = 'https://places.kmaps.virginia.edu/geoserver/wfs';

        // Collect query string
        $query_string = $_SERVER['QUERY_STRING'];

        // Construct full target URL
        $url = $base_url . '?' . $query_string;

        // Use wp_remote_get to proxy the request
        $response = wp_remote_get($url, [
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => 'Error fetching data from Geoserver.']);
        }

        $body = wp_remote_retrieve_body($response);
        $status = wp_remote_retrieve_response_code($response);

        // Set CORS and content headers
        header('Access-Control-Allow-Origin: *');// ✅ Add CORS headers
        header('Access-Control-Allow-Methods: GET, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
        header('Content-Type: application/json', true, $status);

        echo $body;
        exit;
    }
});

register_activation_hook(__FILE__, function () {
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function () {
    flush_rewrite_rules();
});
