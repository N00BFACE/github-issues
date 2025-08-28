<?php
/**
 * Test GifAdmin Class
 */

class TestGifAdmin extends WP_UnitTestCase {
    
    private $gif_admin;
    
    public function setUp(): void {
        parent::setUp();
        
        // Set up test data
        gif_setup_test_data();
        
        // Create instance of GifAdmin
        $this->gif_admin = new GifAdmin();
    }
    
    public function tearDown(): void {
        // Clean up test data
        gif_cleanup_test_data();
        
        parent::tearDown();
    }
    
    /**
     * Test admin_init method
     */
    public function test_admin_init() {
        // Mock the admin_init method call
        $this->gif_admin->admin_init();
        
        // Check if settings are registered
        global $wp_settings_sections, $wp_settings_fields;
        
        $this->assertArrayHasKey('gif-settings', $wp_settings_sections);
        $this->assertArrayHasKey('gif-settings', $wp_settings_fields);
    }
    
    /**
     * Test sanitize_settings method
     */
    public function test_sanitize_settings() {
        $input = array(
            'repository_url' => 'https://github.com/test/repo',
            'access_token' => 'test_token_123',
            'per_page' => '25'
        );
        
        $sanitized = $this->gif_admin->sanitize_settings($input);
        
        $this->assertIsArray($sanitized);
        $this->assertEquals('https://github.com/test/repo', $sanitized['repository_url']);
        $this->assertEquals('test_token_123', $sanitized['access_token']);
        $this->assertEquals(25, $sanitized['per_page']);
    }
    
    /**
     * Test sanitize_settings with invalid data
     */
    public function test_sanitize_settings_invalid() {
        $input = array(
            'repository_url' => 'not-a-url',
            'access_token' => '',
            'per_page' => '999' // Invalid per_page
        );
        
        $sanitized = $this->gif_admin->sanitize_settings($input);
        
        $this->assertIsArray($sanitized);
        $this->assertEquals('not-a-url', $sanitized['repository_url']); // esc_url_raw doesn't validate
        $this->assertEquals('', $sanitized['access_token']);
        $this->assertEquals(10, $sanitized['per_page']); // Should default to 10
    }
    
    /**
     * Test sanitize_settings with missing data
     */
    public function test_sanitize_settings_missing() {
        $input = array(
            'repository_url' => 'https://github.com/test/repo'
            // Missing other fields
        );
        
        $sanitized = $this->gif_admin->sanitize_settings($input);
        
        $this->assertIsArray($sanitized);
        $this->assertEquals('https://github.com/test/repo', $sanitized['repository_url']);
        $this->assertArrayNotHasKey('access_token', $sanitized);
        $this->assertArrayNotHasKey('per_page', $sanitized);
    }
    
    /**
     * Test ajax_save_issues method
     */
    public function test_ajax_save_issues() {
        // Mock POST data
        $_POST['nonce'] = wp_create_nonce('gif_save_issues');
        $_POST['issues'] = json_encode(gif_create_mock_github_response(5));
        $_POST['repository'] = 'test-user/test-repo';
        
        // Mock current user capabilities
        wp_set_current_user(1);
        $user = wp_get_current_user();
        $user->set_role('administrator');
        
        // Capture output
        ob_start();
        $this->gif_admin->ajax_save_issues();
        $output = ob_get_clean();
        
        // Check if issues were saved
        $saved_issues = get_option('gif_saved_issues');
        $this->assertIsArray($saved_issues);
        $this->assertEquals('test-user/test-repo', $saved_issues['repository']);
        $this->assertEquals(5, $saved_issues['total_count']);
    }
    
    /**
     * Test ajax_save_issues with invalid nonce
     */
    public function test_ajax_save_issues_invalid_nonce() {
        $_POST['nonce'] = 'invalid_nonce';
        $_POST['issues'] = json_encode(gif_create_mock_github_response(1));
        $_POST['repository'] = 'test-user/test-repo';
        
        // This should die with an error
        $this->expectException('WPDieException');
        $this->gif_admin->ajax_save_issues();
    }
    
    /**
     * Test ajax_save_issues with no data
     */
    public function test_ajax_save_issues_no_data() {
        $_POST['nonce'] = wp_create_nonce('gif_save_issues');
        $_POST['issues'] = '';
        $_POST['repository'] = '';
        
        wp_set_current_user(1);
        $user = wp_get_current_user();
        $user->set_role('administrator');
        
        // This should send JSON error
        $this->expectOutputString('');
        $this->gif_admin->ajax_save_issues();
    }
    
    /**
     * Test display_settings method
     */
    public function test_display_settings() {
        // Mock settings updated
        $_GET['settings-updated'] = 'true';
        
        ob_start();
        $this->gif_admin->display_settings();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Settings saved successfully', $output);
        $this->assertStringContainsString('GitHub Repository Configuration', $output);
    }
    
    /**
     * Test repository_url_callback method
     */
    public function test_repository_url_callback() {
        ob_start();
        $this->gif_admin->repository_url_callback();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('name="gif_settings[repository_url]"', $output);
        $this->assertStringContainsString('value="https://github.com/test-user/test-repo"', $output);
    }
    
    /**
     * Test access_token_callback method
     */
    public function test_access_token_callback() {
        ob_start();
        $this->gif_admin->access_token_callback();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('name="gif_settings[access_token]"', $output);
        $this->assertStringContainsString('value="test_token_123"', $output);
    }
    
    /**
     * Test per_page_callback method
     */
    public function test_per_page_callback() {
        ob_start();
        $this->gif_admin->per_page_callback();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('name="gif_settings[per_page]"', $output);
        $this->assertStringContainsString('value="5"', $output);
    }
}
