<?php

/**
 * GitHub Issue Fetcher
 * 
 * Fetches issues from a GitHub repository and displays them in a WordPress site.
 */
class GifLoader {
    public function __construct() {
        add_action('init', array($this, 'init'));
    }

    public function init() {
        // Load all dependencies.
        require_once __DIR__ . '/class-gif-admin.php';
        require_once __DIR__ . '/class-gif-public.php';
        require_once __DIR__ . '/class-gif-block.php';
        
        // Initialize classes
        $this->gif_admin = new GifAdmin();
        $this->gif_public = new GifPublic();
        $this->gif_block = new GifBlock($this->gif_public);
        
        // Register shortcode
        add_shortcode('github_issues', array($this->gif_public, 'shortcode_display_issues'));
    }
}