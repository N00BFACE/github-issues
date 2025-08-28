<?php
/**
 * PHPUnit Bootstrap for GitHub Issues Plugin Tests
 */

// Check if WordPress is already loaded
if (!defined('ABSPATH')) {
    // Load WordPress test environment
    $_tests_dir = getenv('WP_TESTS_DIR');
    
    if (!$_tests_dir) {
        $_tests_dir = rtrim(sys_get_temp_dir(), '/\\') . '/wordpress-tests-lib';
    }
    
    if (!file_exists($_tests_dir . '/wp-tests-config.php')) {
        echo "Could not find wp-tests-config.php in {$_tests_dir}\n";
        exit(1);
    }
    
    // Give access to tests_add_filter() function.
    require_once $_tests_dir . '/includes/functions.php';
    
    /**
     * Manually load the plugin being tested.
     */
    function _manually_load_plugin() {
        // Load the main plugin file
        require dirname(dirname(__DIR__)) . '/github-issue-fetcher.php';
    }
    
    tests_add_filter('muplugins_loaded', '_manually_load_plugin');
    
    // Start up the WP testing environment.
    require $_tests_dir . '/includes/bootstrap.php';
} else {
    // WordPress is already loaded, just include the plugin
    require_once dirname(dirname(__DIR__)) . '/github-issue-fetcher.php';
}

// Set up test data
function gif_setup_test_data() {
    // Create test options
    $test_issues = array(
        'repository' => 'test-user/test-repo',
        'issues' => array(
            array(
                'id' => 1,
                'number' => 1,
                'title' => 'Test Issue 1',
                'state' => 'open',
                'html_url' => 'https://github.com/test-user/test-repo/issues/1',
                'user' => array('login' => 'testuser'),
                'created_at' => '2023-01-01T00:00:00Z',
                'labels' => array(
                    array('name' => 'bug', 'color' => 'd73a4a')
                ),
                'body' => 'This is a test issue for testing purposes.'
            ),
            array(
                'id' => 2,
                'number' => 2,
                'title' => 'Test Issue 2',
                'state' => 'closed',
                'html_url' => 'https://github.com/test-user/test-repo/issues/2',
                'user' => array('login' => 'testuser2'),
                'created_at' => '2023-01-02T00:00:00Z',
                'labels' => array(
                    array('name' => 'enhancement', 'color' => 'a2eeef')
                ),
                'body' => 'This is another test issue.'
            )
        ),
        'last_updated' => current_time('mysql'),
        'total_count' => 2
    );
    
    update_option('gif_saved_issues', $test_issues);
    
    // Create test settings
    $test_settings = array(
        'repository_url' => 'https://github.com/test-user/test-repo',
        'access_token' => 'test_token_123',
        'per_page' => 5
    );
    
    update_option('gif_settings', $test_settings);
}

// Clean up test data
function gif_cleanup_test_data() {
    delete_option('gif_saved_issues');
    delete_option('gif_settings');
}

// Helper function to create mock GitHub API response
function gif_create_mock_github_response($issues_count = 10) {
    $issues = array();
    
    for ($i = 1; $i <= $issues_count; $i++) {
        $issues[] = array(
            'id' => $i,
            'number' => $i,
            'title' => "Test Issue {$i}",
            'state' => ($i % 2 == 0) ? 'closed' : 'open',
            'html_url' => "https://github.com/test-user/test-repo/issues/{$i}",
            'user' => array('login' => "user{$i}"),
            'created_at' => date('c', strtotime("-{$i} days")),
            'labels' => array(
                array('name' => 'test', 'color' => '000000')
            ),
            'body' => "This is test issue number {$i}."
        );
    }
    
    return $issues;
}
