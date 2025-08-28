<?php

/**
 * GIF Public
 * 
 * Frontend functionality for GitHub Issue Fetcher.
 */
class GifPublic {
    
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        wp_enqueue_style(
            'gif-public', 
            plugin_dir_url(__DIR__) . 'assets/css/gif-public.css', 
            array(), 
            '1.0.0', 
            'all'
        );
        
        wp_enqueue_script(
            'gif-public', 
            plugin_dir_url(__DIR__) . 'assets/js/gif-public.js', 
            array('jquery'), 
            '1.0.0', 
            true
        );
        
        // Localize script with data
        wp_localize_script('gif-public', 'gifPublic', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gif_public_nonce')
        ));
    }
    
    /**
     * Get saved issues from database
     */
    public function get_saved_issues() {
        $saved_issues = get_option('gif_saved_issues', array());
        
        if (empty($saved_issues) || !isset($saved_issues['issues'])) {
            return array(
                'repository' => '',
                'issues' => array(),
                'last_updated' => '',
                'total_count' => 0
            );
        }
        
        return $saved_issues;
    }
    
    /**
     * Render issues for frontend display
     */
    public function render_issues($attributes = array()) {
        $saved_data = $this->get_saved_issues();
        
        if (empty($saved_data['issues'])) {
            $no_issues_html = '<div class="gif-no-issues-message">
                <p>No GitHub issues available. Please fetch issues from the admin panel.</p>
            </div>';
            
            return $no_issues_html;
        }
        
        // Default attributes
        $defaults = array(
            'show_count' => 10,
            'show_state' => 'all', // all, open, closed
            'show_labels' => true,
            'show_author' => true,
            'show_date' => true,
            'show_body' => true,
            'layout' => 'list', // list, grid
            'page' => 1,
            'per_page' => 10,
            'project_filter' => '', // Filter by project name
            'column_filter' => '' // Filter by column name
        );
        
        $attributes = wp_parse_args($attributes, $defaults);
        
        // Filter issues based on state
        $issues = $saved_data['issues'];
        if ($attributes['show_state'] !== 'all') {
            $issues = array_filter($issues, function($issue) use ($attributes) {
                return $issue['state'] === $attributes['show_state'];
            });
        }
        
        // Filter issues based on project
        if (!empty($attributes['project_filter'])) {
            $issues = array_filter($issues, function($issue) use ($attributes) {
                if (isset($issue['project_data']['in_projects'])) {
                    foreach ($issue['project_data']['in_projects'] as $project) {
                        if (stripos($project['project_name'], $attributes['project_filter']) !== false) {
                            return true;
                        }
                    }
                }
                return false;
            });
        }
        
        // Filter issues based on column
        if (!empty($attributes['column_filter'])) {
            $issues = array_filter($issues, function($issue) use ($attributes) {
                if (isset($issue['project_data']['in_projects'])) {
                    foreach ($issue['project_data']['in_projects'] as $project) {
                        if (stripos($project['column_name'], $attributes['column_filter']) !== false) {
                            return true;
                        }
                    }
                }
                return false;
            });
        }
        
        // Pagination logic
        $total_issues = count($issues);
        $per_page = intval($attributes['per_page']);
        $current_page = intval($attributes['page']);
        $total_pages = ceil($total_issues / $per_page);
        
        // Ensure current page is within bounds
        if ($current_page < 1) $current_page = 1;
        if ($current_page > $total_pages) $current_page = $total_pages;
        
        // Get issues for current page
        $offset = ($current_page - 1) * $per_page;
        $page_issues = array_slice($issues, $offset, $per_page);
        
        // Start building HTML
        $html = '<div class="gif-issues-frontend gif-layout-' . esc_attr($attributes['layout']) . '">';
        
        // Header
        $html .= '<div class="gif-issues-frontend-header">';
        $html .= '<h3 class="gif-issues-title">GitHub Issues</h3>';
        if (!empty($saved_data['repository'])) {
            $html .= '<p class="gif-repository-name">';
            $html .= '<span class="dashicons dashicons-admin-site"></span>';
            $html .= 'Repository: <strong>' . esc_html($saved_data['repository']) . '</strong>';
            $html .= '</p>';
        }
        if (!empty($saved_data['last_updated'])) {
            $html .= '<p class="gif-last-updated">';
            $html .= 'Last updated: ' . esc_html(human_time_diff(strtotime($saved_data['last_updated']))) . ' ago';
            $html .= '</p>';
        }
        $html .= '</div>';
        
        // Issues list
        $html .= '<div class="gif-issues-frontend-list">';
        
        if (empty($page_issues)) {
            $html .= '<p class="gif-no-issues">No issues found matching the current filters.</p>';
        } else {
            foreach ($page_issues as $issue) {
                $html .= $this->render_single_issue($issue, $attributes);
            }
        }
        
        $html .= '</div>';
        
        // Add pagination if needed
        if ($total_pages > 1) {
            $html .= $this->render_pagination($current_page, $total_pages, $attributes);
        }
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render a single issue
     */
    private function render_single_issue($issue, $attributes) {
        $html = '<div class="gif-issue-item gif-issue-' . esc_attr($issue['state']) . '">';
        
        // Issue header
        $html .= '<div class="gif-issue-header">';
        $html .= '<h4 class="gif-issue-title">';
        $html .= '<a href="' . esc_url($issue['html_url']) . '" target="_blank" rel="noopener">';
        $html .= '#' . esc_html($issue['number']) . ' ' . esc_html($issue['title']);
        $html .= '</a>';
        $html .= '</h4>';
        $html .= '<span class="gif-issue-state gif-issue-state-' . esc_attr($issue['state']) . '">';
        $html .= esc_html(ucfirst($issue['state']));
        $html .= '</span>';
        $html .= '</div>';
        
        // Issue meta
        if ($attributes['show_author'] || $attributes['show_date'] || $attributes['show_labels']) {
            $html .= '<div class="gif-issue-meta">';
            
            if ($attributes['show_author'] && isset($issue['user']['login'])) {
                $html .= '<span class="gif-issue-author">by ' . esc_html($issue['user']['login']) . '</span>';
            }
            
            if ($attributes['show_date'] && isset($issue['created_at'])) {
                $created_date = date('M j, Y', strtotime($issue['created_at']));
                $html .= '<span class="gif-issue-date">on ' . esc_html($created_date) . '</span>';
            }
            
            if ($attributes['show_labels'] && !empty($issue['labels'])) {
                $html .= '<div class="gif-issue-labels">';
                foreach ($issue['labels'] as $label) {
                    $html .= '<span class="gif-issue-label" style="background-color: #' . esc_attr($label['color']) . '">';
                    $html .= esc_html($label['name']);
                    $html .= '</span>';
                }
                $html .= '</div>';
            }
            
            $html .= '</div>';
        }
        
        // Project information
        if (isset($issue['project_data']) && !empty($issue['project_data']['in_projects'])) {
            $html .= '<div class="gif-issue-projects">';
            $html .= '<span class="gif-project-label">📋 In Projects:</span>';
            
            foreach ($issue['project_data']['in_projects'] as $project) {
                $column_class = 'gif-project-column-' . sanitize_title($project['column_name']);
                $html .= '<div class="gif-project-item">';
                $html .= '<a href="' . esc_url($project['project_url']) . '" target="_blank" rel="noopener" class="gif-project-name">';
                $html .= esc_html($project['project_name']);
                $html .= '</a>';
                $html .= '<span class="gif-project-column ' . esc_attr($column_class) . '">';
                $html .= esc_html($project['column_name']);
                $html .= '</span>';
                $html .= '</div>';
            }
            
            $html .= '</div>';
        }
        
        // Issue body
        if ($attributes['show_body'] && !empty($issue['body'])) {
            $body = wp_trim_words(strip_tags($issue['body']), 30, '...');
            $html .= '<div class="gif-issue-body">';
            $html .= '<p>' . esc_html($body) . '</p>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Shortcode for displaying issues
     */
    public function shortcode_display_issues($atts) {
        $attributes = shortcode_atts(array(
            'count' => 10,
            'state' => 'all',
            'labels' => 'true',
            'author' => 'true',
            'date' => 'true',
            'body' => 'true',
            'layout' => 'list',
            'per_page' => 10,
            'project' => '', // Filter by project name
            'column' => '' // Filter by column name
        ), $atts);
        
        // Convert string booleans to actual booleans
        $attributes['show_labels'] = $attributes['labels'] === 'true';
        $attributes['show_author'] = $attributes['author'] === 'true';
        $attributes['show_date'] = $attributes['date'] === 'true';
        $attributes['show_body'] = $attributes['body'] === 'true';
        $attributes['show_count'] = intval($attributes['count']);
        $attributes['show_state'] = $attributes['state'];
        $attributes['per_page'] = intval($attributes['per_page']);
        $attributes['project_filter'] = $attributes['project'];
        $attributes['column_filter'] = $attributes['column'];
        
        // Get current page from query parameters
        $attributes['page'] = isset($_GET['gif_page']) ? intval($_GET['gif_page']) : 1;
        
        return $this->render_issues($attributes);
    }
    
    /**
     * Render pagination controls
     */
    private function render_pagination($current_page, $total_pages, $attributes) {
        $html = '<div class="gif-pagination">';
        $html .= '<div class="gif-pagination-info">';
        $html .= sprintf(
            'Page %d of %d',
            $current_page,
            $total_pages
        );
        $html .= '</div>';
        
        $html .= '<div class="gif-pagination-controls">';
        
        // Previous page
        if ($current_page > 1) {
            $prev_page = $current_page - 1;
            $html .= '<a href="' . $this->get_pagination_url($prev_page, $attributes) . '" class="gif-pagination-link gif-prev-page">';
            $html .= '<span class="dashicons dashicons-arrow-left-alt2"></span>';
            $html .= ' Previous';
            $html .= '</a>';
        }
        
        // Page numbers
        $html .= '<div class="gif-page-numbers">';
        
        $start_page = max(1, $current_page - 2);
        $end_page = min($total_pages, $current_page + 2);
        
        // First page
        if ($start_page > 1) {
            $html .= '<a href="' . $this->get_pagination_url(1, $attributes) . '" class="gif-pagination-link">1</a>';
            if ($start_page > 2) {
                $html .= '<span class="gif-pagination-ellipsis">...</span>';
            }
        }
        
        // Page range
        for ($i = $start_page; $i <= $end_page; $i++) {
            if ($i == $current_page) {
                $html .= '<span class="gif-pagination-current">' . $i . '</span>';
            } else {
                $html .= '<a href="' . $this->get_pagination_url($i, $attributes) . '" class="gif-pagination-link">' . $i . '</a>';
            }
        }
        
        // Last page
        if ($end_page < $total_pages) {
            if ($end_page < $total_pages - 1) {
                $html .= '<span class="gif-pagination-ellipsis">...</span>';
            }
            $html .= '<a href="' . $this->get_pagination_url($total_pages, $attributes) . '" class="gif-pagination-link">' . $total_pages . '</a>';
        }
        
        $html .= '</div>';
        
        // Next page
        if ($current_page < $total_pages) {
            $next_page = $current_page + 1;
            $html .= '<a href="' . $this->get_pagination_url($next_page, $attributes) . '" class="gif-pagination-link gif-next-page">';
            $html .= 'Next ';
            $html .= '<span class="dashicons dashicons-arrow-right-alt2"></span>';
            $html .= '</a>';
        }
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Get pagination URL with current attributes
     */
    private function get_pagination_url($page, $attributes) {
        $current_url = get_permalink();
        $query_args = array();
        
        // Add page parameter
        if ($page > 1) {
            $query_args['gif_page'] = $page;
        }
        
        // Add other attributes as query parameters
        if (isset($attributes['show_state']) && $attributes['show_state'] !== 'all') {
            $query_args['gif_state'] = $attributes['show_state'];
        }
        if (isset($attributes['per_page']) && $attributes['per_page'] != 10) {
            $query_args['gif_per_page'] = $attributes['per_page'];
        }
        if (isset($attributes['layout']) && $attributes['layout'] !== 'list') {
            $query_args['gif_layout'] = $attributes['layout'];
        }
        
        return add_query_arg($query_args, $current_url);
    }
}
