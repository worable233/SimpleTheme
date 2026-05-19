<?php
// Quick opcache reset
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "opcache reset\n";
}

// Test the resolve function
require_once __DIR__ . '/inc/rest/misc.php';

$test_path = '/shuoshuo/test-说说/';
echo "Testing path: " . $test_path . "\n";
$post = simple_theme_find_post_by_path($test_path, array('post', 'page', 'shuoshuo'));
if ($post) {
    echo "Found: ID=" . $post->ID . " type=" . $post->post_type . " name=" . $post->post_name . "\n";
} else {
    echo "Not found!\n";
}

// Also test with the raw URL-encoded form
$test_path2 = '/shuoshuo/test-%e8%af%b4%e8%af%b4/';
echo "\nTesting path: " . $test_path2 . "\n";
$post2 = simple_theme_find_post_by_path($test_path2, array('post', 'page', 'shuoshuo'));
if ($post2) {
    echo "Found: ID=" . $post2->ID . " type=" . $post2->post_type . " name=" . $post2->post_name . "\n";
} else {
    echo "Not found!\n";
}

// Check actual DB
global $wpdb;
$row = $wpdb->get_row("SELECT ID, post_name, post_type FROM {$wpdb->posts} WHERE post_type='shuoshuo' AND post_status='publish' LIMIT 5");
if ($row) {
    echo "\nDB check: ID=" . $row->ID . " name='" . $row->post_name . "' type=" . $row->post_type . "\n";
} else {
    echo "\nNo shuoshuo posts in DB.\n";
}