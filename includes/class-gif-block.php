<?php

/**
 * GIF Block
 * 
 * Gutenberg block for displaying GitHub issues.
 */
class GifBlock {
    
    private $public_instance;
    private $block_registered = false;
    
    public function __construct($public_instance) {
        $this->public_instance = $public_instance;
        
        add_action('init', array($this, 'register_block'), 10);
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_block_editor_assets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        
        add_action('plugins_loaded', array($this, 'register_block'), 15);
        add_action('wp_loaded', array($this, 'register_block'), 15);
    }
    
    /**
     * Register the Gutenberg block
     */
    public function register_block() {
        
        // Prevent duplicate registrations
        if ($this->block_registered) {
            return;
        }
        
        // Check if Gutenberg is available
        if (!function_exists('register_block_type')) {
            return;
        }
        
        $result = register_block_type('github-issues/github-issues', array(
            'editor_script' => 'gif-block-editor',
            'editor_style' => 'gif-block-editor-style',
            'style' => 'gif-block-style',
            'render_callback' => array($this, 'render_block'),
            'supports' => array(
                'html' => false,
                'align' => array('wide', 'full')
            ),
            'example' => array(
                'attributes' => array(
                    'showCount' => 5,
                    'showState' => 'all',
                    'title' => 'Sample GitHub Issues'
                )
            ),
            'attributes' => array(
                'showCount' => array(
                    'type' => 'number',
                    'default' => 10
                ),
                'showState' => array(
                    'type' => 'string',
                    'default' => 'all'
                ),
                'showLabels' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'showAuthor' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'showDate' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'showBody' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'layout' => array(
                    'type' => 'string',
                    'default' => 'list'
                ),
                'title' => array(
                    'type' => 'string',
                    'default' => 'GitHub Issues'
                ),
                'showHeader' => array(
                    'type' => 'boolean',
                    'default' => true
                )
            )
        ));
        
        // Mark as registered if successful
        if ($result) {
            $this->block_registered = true;
        }
    }
    
    /**
     * Enqueue block editor assets
     */
    public function enqueue_block_editor_assets() {
        $script_url = plugin_dir_url(__DIR__) . 'blocks/github-issues-block/block.js';
        
        wp_enqueue_script(
            'gif-block-editor',
            $script_url,
            array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n'),
            '1.0.0',
            true
        );
        
        wp_enqueue_style(
            'gif-block-editor-style',
            plugin_dir_url(__DIR__) . 'blocks/github-issues-block/editor.css',
            array('wp-edit-blocks'),
            '1.0.0'
        );
        
        wp_enqueue_style(
            'gif-block-style',
            plugin_dir_url(__DIR__) . 'blocks/github-issues-block/style.css',
            array(),
            '1.0.0'
        );
        
        // Localize script with saved issues data for preview
        $saved_issues = get_option('gif_saved_issues', array());
        wp_localize_script('gif-block-editor', 'gifBlockData', array(
            'savedIssues' => $saved_issues,
            'hasIssues' => !empty($saved_issues['issues']),
            'adminUrl' => admin_url('admin.php?page=github-issues')
        ));
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Always enqueue block styles on frontend to ensure they're available
        wp_enqueue_style(
            'gif-block-frontend',
            plugin_dir_url(__DIR__) . 'blocks/github-issues-block/style.css',
            array(),
            '1.0.0'
        );
        
        wp_enqueue_style(
            'gif-public',
            plugin_dir_url(__DIR__) . 'assets/css/gif-public.css',
            array(),
            '1.0.0'
        );
    }
    
    /**
     * Render the block on the frontend
     */
    public function render_block($attributes) {
        // Check if we have saved issues
        $saved_issues = get_option('gif_saved_issues', array());
        
        // Convert attributes to the format expected by the public class
        $render_attributes = array(
            'show_count' => isset($attributes['showCount']) ? intval($attributes['showCount']) : 10,
            'show_state' => isset($attributes['showState']) ? $attributes['showState'] : 'all',
            'show_labels' => isset($attributes['showLabels']) ? $attributes['showLabels'] : true,
            'show_author' => isset($attributes['showAuthor']) ? $attributes['showAuthor'] : true,
            'show_date' => isset($attributes['showDate']) ? $attributes['showDate'] : true,
            'show_body' => isset($attributes['showBody']) ? $attributes['showBody'] : true,
            'layout' => isset($attributes['layout']) ? $attributes['layout'] : 'list',
            'per_page' => isset($attributes['perPage']) ? intval($attributes['perPage']) : 10,
            'page' => 1,
            'project_filter' => isset($attributes['projectFilter']) ? $attributes['projectFilter'] : '',
            'column_filter' => isset($attributes['columnFilter']) ? $attributes['columnFilter'] : ''
        );
        
        // Custom title handling
        $custom_title = isset($attributes['title']) ? $attributes['title'] : 'GitHub Issues';
        $show_header = isset($attributes['showHeader']) ? $attributes['showHeader'] : true;
        
        // Get the rendered issues
        try {
            if (is_object($this->public_instance) && method_exists($this->public_instance, 'render_issues')) {
                $content = $this->public_instance->render_issues($render_attributes);
            } else {
                // Fallback if public instance is not available
                $content = $this->render_fallback_issues($render_attributes);
            }
        } catch (Exception $e) {
            $content = $this->render_fallback_issues($render_attributes);
        }
        
        // If custom title or header settings, modify the content
        if (!$show_header) {
            // Remove the header section
            $content = preg_replace('/<div class="gif-issues-frontend-header">.*?<\/div>/s', '', $content);
        } elseif ($custom_title !== 'GitHub Issues') {
            // Replace the default title
            $content = str_replace(
                '<h3 class="gif-issues-title">GitHub Issues</h3>',
                '<h3 class="gif-issues-title">' . esc_html($custom_title) . '</h3>',
                $content
            );
        }
        
        // Add block wrapper class
        $content = str_replace(
            'class="gif-issues-frontend',
            'class="gif-issues-frontend gif-gutenberg-block',
            $content
        );
        
        // Wrap in block container
        $content = '<div class="wp-block-gif-github-issues">' . $content . '</div>';
        
        return $content;
    }
    
    /**
     * Fallback render method if public instance fails
     */
    private function render_fallback_issues($attributes) {
        $saved_issues = get_option('gif_saved_issues', array());
        
        if (empty($saved_issues['issues'])) {
            return '<div class="gif-no-issues-message">
                <p>No GitHub issues available. Please fetch issues from the admin panel.</p>
            </div>';
        }
        
        $issues = $saved_issues['issues'];
        $count = isset($attributes['show_count']) ? min(intval($attributes['show_count']), count($issues)) : count($issues);
        $issues = array_slice($issues, 0, $count);
        
        $html = '<div class="gif-issues-frontend">';
        $html .= '<div class="gif-issues-frontend-header">';
        $html .= '<h3 class="gif-issues-title">GitHub Issues</h3>';
        if (!empty($saved_issues['repository'])) {
            $html .= '<p class="gif-repository-name">Repository: <strong>' . esc_html($saved_issues['repository']) . '</strong></p>';
        }
        $html .= '</div>';
        
        $html .= '<div class="gif-issues-frontend-list">';
        foreach ($issues as $issue) {
            $html .= '<div class="gif-issue-item gif-issue-' . esc_attr($issue['state']) . '">';
            $html .= '<div class="gif-issue-header">';
            $html .= '<h4 class="gif-issue-title">';
            $html .= '<a href="' . esc_url($issue['html_url']) . '" target="_blank" rel="noopener">';
            $html .= '#' . esc_html($issue['number']) . ' ' . esc_html($issue['title']);
            $html .= '</a>';
            $html .= '</h4>';
            $html .= '<span class="gif-issue-state gif-issue-state-' . esc_attr($issue['state']) . '">' . esc_html(ucfirst($issue['state'])) . '</span>';
            $html .= '</div>';
            $html .= '</div>';
        }
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Register a simple test block to verify the system works
     */
    public function register_test_block() {
        if (!function_exists('register_block_type')) {
            return;
        }
        
        $result = register_block_type('github-issues/test-block', array(
            'render_callback' => function($attributes) {
                return '<div style="background: #f0f0f0; padding: 20px; border: 1px solid #ccc;">
                    <h3>Test Block Working!</h3>
                    <p>This is a simple test block to verify the registration system is working.</p>
                </div>';
            }
        ));
    }
}
