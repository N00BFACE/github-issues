<?php
/**
 * Test GifPublic Class
 */

class TestGifPublic extends WP_UnitTestCase {
    
    private $gif_public;
    
    public function setUp(): void {
        parent::setUp();
        
        // Set up test data
        gif_setup_test_data();
        
        // Create instance of GifPublic
        $this->gif_public = new GifPublic();
    }
    
    public function tearDown(): void {
        // Clean up test data
        gif_cleanup_test_data();
        
        parent::tearDown();
    }
    
    /**
     * Test get_saved_issues method
     */
    public function test_get_saved_issues() {
        $saved_issues = $this->gif_public->get_saved_issues();
        
        $this->assertIsArray($saved_issues);
        $this->assertArrayHasKey('repository', $saved_issues);
        $this->assertArrayHasKey('issues', $saved_issues);
        $this->assertArrayHasKey('last_updated', $saved_issues);
        $this->assertArrayHasKey('total_count', $saved_issues);
        
        $this->assertEquals('test-user/test-repo', $saved_issues['repository']);
        $this->assertEquals(2, $saved_issues['total_count']);
        $this->assertCount(2, $saved_issues['issues']);
    }
    
    /**
     * Test get_saved_issues with no data
     */
    public function test_get_saved_issues_empty() {
        // Delete the option to test empty case
        delete_option('gif_saved_issues');
        
        $saved_issues = $this->gif_public->get_saved_issues();
        
        $this->assertIsArray($saved_issues);
        $this->assertEquals('', $saved_issues['repository']);
        $this->assertEmpty($saved_issues['issues']);
        $this->assertEquals(0, $saved_issues['total_count']);
    }
    
    /**
     * Test render_issues method with default attributes
     */
    public function test_render_issues_default() {
        $html = $this->gif_public->render_issues();
        
        $this->assertIsString($html);
        $this->assertStringContainsString('GitHub Issues', $html);
        $this->assertStringContainsString('test-user/test-repo', $html);
        $this->assertStringContainsString('Test Issue 1', $html);
        $this->assertStringContainsString('Test Issue 2', $html);
    }
    
    /**
     * Test render_issues with custom attributes
     */
    public function test_render_issues_custom_attributes() {
        $attributes = array(
            'show_count' => 1,
            'show_state' => 'open',
            'show_labels' => false,
            'show_author' => false,
            'show_date' => false,
            'show_body' => false,
            'layout' => 'grid'
        );
        
        $html = $this->gif_public->render_issues($attributes);
        
        $this->assertIsString($html);
        $this->assertStringContainsString('gif-layout-grid', $html);
        $this->assertStringContainsString('Test Issue 1', $html);
        $this->assertStringNotContainsString('Test Issue 2', $html); // Should be filtered out (closed)
    }
    
    /**
     * Test render_issues with state filtering
     */
    public function test_render_issues_state_filtering() {
        // Test open issues only
        $attributes = array('show_state' => 'open');
        $html = $this->gif_public->render_issues($attributes);
        
        $this->assertStringContainsString('Test Issue 1', $html);
        $this->assertStringNotContainsString('Test Issue 2', $html);
        
        // Test closed issues only
        $attributes = array('show_state' => 'closed');
        $html = $this->gif_public->render_issues($attributes);
        
        $this->assertStringContainsString('Test Issue 2', $html);
        $this->assertStringNotContainsString('Test Issue 1', $html);
    }
    
    /**
     * Test render_issues with count limiting
     */
    public function test_render_issues_count_limiting() {
        // Create more test issues
        $many_issues = gif_create_mock_github_response(15);
        $test_data = array(
            'repository' => 'test-user/test-repo',
            'issues' => $many_issues,
            'last_updated' => current_time('mysql'),
            'total_count' => 15
        );
        update_option('gif_saved_issues', $test_data);
        
        $attributes = array('show_count' => 5);
        $html = $this->gif_public->render_issues($attributes);
        
        // Should only show 5 issues
        $this->assertStringContainsString('Test Issue 1', $html);
        $this->assertStringContainsString('Test Issue 5', $html);
        $this->assertStringNotContainsString('Test Issue 6', $html);
    }
    
    /**
     * Test render_issues with no issues
     */
    public function test_render_issues_no_issues() {
        // Delete issues to test empty case
        delete_option('gif_saved_issues');
        
        $html = $this->gif_public->render_issues();
        
        $this->assertStringContainsString('No GitHub issues available', $html);
    }
    
    /**
     * Test shortcode functionality
     */
    public function test_shortcode_display_issues() {
        $html = $this->gif_public->shortcode_display_issues(array('count' => '1'));
        
        $this->assertIsString($html);
        $this->assertStringContainsString('GitHub Issues', $html);
        $this->assertStringContainsString('Test Issue 1', $html);
    }
    
    /**
     * Test shortcode with invalid parameters
     */
    public function test_shortcode_invalid_parameters() {
        $html = $this->gif_public->shortcode_display_issues(array('count' => 'invalid'));
        
        $this->assertIsString($html);
        $this->assertStringContainsString('GitHub Issues', $html);
    }
    
    /**
     * Test render_single_issue method
     */
    public function test_render_single_issue() {
        $issue = array(
            'id' => 999,
            'number' => 999,
            'title' => 'Test Single Issue',
            'state' => 'open',
            'html_url' => 'https://github.com/test/repo/issues/999',
            'user' => array('login' => 'testuser'),
            'created_at' => '2023-01-01T00:00:00Z',
            'labels' => array(
                array('name' => 'test', 'color' => 'ffffff')
            ),
            'body' => 'Test issue body content'
        );
        
        $attributes = array(
            'show_labels' => true,
            'show_author' => true,
            'show_date' => true,
            'show_body' => true
        );
        
        $html = $this->gif_public->render_single_issue($issue, $attributes);
        
        $this->assertIsString($html);
        $this->assertStringContainsString('Test Single Issue', $html);
        $this->assertStringContainsString('#999', $html);
        $this->assertStringContainsString('testuser', $html);
        $this->assertStringContainsString('test', $html); // label
        $this->assertStringContainsString('Test issue body content', $html);
    }
}
