<?php
/**
 * Plugin Name: Mandala Proxy
 * Description: Proxies requests to sites like the KMaps Geoserver to bypass CORS.
 * Version: 1.0
 * Author: Than Grove (via ChatGPT)
 */

add_action('init', function () {
    add_rewrite_rule('^proxy/wfs/?$', 'index.php?geoserver_proxy=1', 'top');

    // TTT proxy
    add_rewrite_rule('^proxy/ttt/?$', 'index.php?ttt_proxy=1', 'top');

    // Solr proxy
    add_rewrite_rule('^proxy/solr/?$', 'index.php?solr_proxy=1', 'top');
});

add_filter('query_vars', function ($vars) {
    $vars[] = 'geoserver_proxy';
    $vars[] = 'ttt_proxy';
    $vars[] = 'solr_proxy';
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

    if (get_query_var('ttt_proxy')) {
        $target_url = 'https://ttt.thlib.org/'; // Replace with your target

        // Get headers only
        $headers = get_headers($target_url, true);

        // Output as JSON
        header('Content-Type: application/json');
        echo json_encode($headers);
        exit;
    }

    if (get_query_var('solr_proxy')) {
        $base_url = 'https://texts.thdl.org/solr/select';

        // Collect query string
        $query_string = $_SERVER['QUERY_STRING'];

        // Construct full target URL
        $url = $base_url . '?' . $query_string;

        // Use wp_remote_get to proxy the request
        $response = wp_remote_get($url, [
            'headers' => [
                'Accept' => 'application/json',
            ],
            'sslverify' => false,
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => 'Error fetching data from SOLR: ' . json_encode($response)]);
        }

        $body = wp_remote_retrieve_body($response);
        $status = wp_remote_retrieve_response_code($response);

        // Set CORS and content headers
        header('Access-Control-Allow-Origin: *');// ✅ Add CORS headers
        header('Access-Control-Allow-Methods: GET, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
        header('Content-Type: application/javascript; charset=UTF-8', true, $status);

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
