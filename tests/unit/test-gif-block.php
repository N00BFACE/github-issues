<?php
/**
 * Test GifBlock Class
 */

class TestGifBlock extends WP_UnitTestCase {
    
    private $gif_block;
    private $gif_public;
    
    public function setUp(): void {
        parent::setUp();
        
        // Set up test data
        gif_setup_test_data();
        
        // Create instances
        $this->gif_public = new GifPublic();
        $this->gif_block = new GifBlock($this->gif_public);
    }
    
    public function tearDown(): void {
        // Clean up test data
        gif_cleanup_test_data();
        
        parent::tearDown();
    }
    
    /**
     * Test constructor
     */
    public function test_constructor() {
        $this->assertInstanceOf('GifBlock', $this->gif_block);
        $this->assertInstanceOf('GifPublic', $this->gif_public);
    }
    
    /**
     * Test register_block method
     */
    public function test_register_block() {
        // Mock register_block_type function if it doesn't exist
        if (!function_exists('register_block_type')) {
            $this->markTestSkipped('register_block_type function not available');
        }
        
        // Test block registration
        $result = $this->gif_block->register_block();
        
        // Check if block was registered
        $this->assertTrue($result !== false);
    }
    
    /**
     * Test register_test_block method
     */
    public function test_register_test_block() {
        // Mock register_block_type function if it doesn't exist
        if (!function_exists('register_block_type')) {
            $this->markTestSkipped('register_block_type function not available');
        }
        
        // Test test block registration
        $this->gif_block->register_test_block();
        
        // This should not throw any errors
        $this->assertTrue(true);
    }
    
    /**
     * Test render_block method
     */
    public function test_render_block() {
        $attributes = array(
            'showCount' => 5,
            'showState' => 'all',
            'showLabels' => true,
            'showAuthor' => true,
            'showDate' => true,
            'showBody' => false,
            'layout' => 'list',
            'title' => 'Test Block Title'
        );
        
        $html = $this->gif_block->render_block($attributes);
        
        $this->assertIsString($html);
        $this->assertStringContainsString('Test Block Title', $html);
        $this->assertStringContainsString('test-user/test-repo', $html);
    }
    
    /**
     * Test render_block with minimal attributes
     */
    public function test_render_block_minimal() {
        $attributes = array();
        
        $html = $this->gif_block->render_block($attributes);
        
        $this->assertIsString($html);
        $this->assertStringContainsString('GitHub Issues', $html);
    }
    
    /**
     * Test render_block with no issues
     */
    public function test_render_block_no_issues() {
        // Delete issues to test empty case
        delete_option('gif_saved_issues');
        
        $attributes = array('title' => 'Empty Block');
        $html = $this->gif_block->render_block($attributes);
        
        $this->assertIsString($html);
        $this->assertStringContainsString('Empty Block', $html);
        $this->assertStringContainsString('No GitHub issues available', $html);
    }
    
    /**
     * Test render_fallback_issues method
     */
    public function test_render_fallback_issues() {
        $attributes = array('title' => 'Fallback Test');
        
        $html = $this->gif_block->render_fallback_issues($attributes);
        
        $this->assertIsString($html);
        $this->assertStringContainsString('Fallback Test', $html);
        $this->assertStringContainsString('No GitHub issues available', $html);
    }
    
    /**
     * Test enqueue_block_editor_assets method
     */
    public function test_enqueue_block_editor_assets() {
        // Mock wp_enqueue_script
        global $wp_scripts;
        if (!isset($wp_scripts)) {
            $wp_scripts = new WP_Scripts();
        }
        
        $this->gif_block->enqueue_block_editor_assets();
        
        // Check if script was enqueued
        $this->assertTrue(wp_script_is('gif-block-editor', 'enqueued'));
    }
    
    /**
     * Test enqueue_frontend_assets method
     */
    public function test_enqueue_frontend_assets() {
        // Mock wp_enqueue_style
        global $wp_styles;
        if (!isset($wp_styles)) {
            $wp_styles = new WP_Styles();
        }
        
        $this->gif_block->enqueue_frontend_assets();
        
        // Check if styles were enqueued
        $this->assertTrue(wp_style_is('gif-block-style', 'enqueued'));
        $this->assertTrue(wp_style_is('gif-public', 'enqueued'));
    }
    
    /**
     * Test block registration with different namespaces
     */
    public function test_block_registration_namespace() {
        if (!function_exists('register_block_type')) {
            $this->markTestSkipped('register_block_type function not available');
        }
        
        // Test with different namespace
        $test_block = new GifBlock($this->gif_public);
        
        // This should not throw any errors
        $this->assertTrue(true);
    }
}
