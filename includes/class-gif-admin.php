<?php

/**
 * GIF Admin
 * 
 * Admin page for GitHub Issue Fetcher with Settings API integration.
 */
class GifAdmin {
    
    private $options_group = 'gif_settings_group';
    private $option_name = 'gif_settings';
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        
        // Register AJAX handlers
        add_action('wp_ajax_gif_save_issues', array($this, 'ajax_save_issues'));
        
        // Add admin notice for debugging
        add_action('admin_notices', array($this, 'debug_admin_notice'));
    }

    public function add_admin_menu() {
        // Main menu page
        add_menu_page(
            'GitHub Issue Fetcher', 
            'GitHub Issues', 
            'manage_options', 
            'github-issues', 
            array($this, 'display_issues'),
            'dashicons-admin-site',
            30
        );
        
        // Settings submenu page
        add_submenu_page(
            'github-issues',
            'GitHub Issues Settings',
            'Settings',
            'manage_options',
            'github-issues-settings',
            array($this, 'display_settings')
        );
    }
    
    public function admin_init() {
        // Register settings
        register_setting(
            $this->options_group,
            $this->option_name,
            array($this, 'sanitize_settings')
        );
        
        // Add settings section
        add_settings_section(
            'gif_main_section',
            __('GitHub Repository Configuration', 'github-issues'),
            array($this, 'section_callback'),
            'gif-settings'
        );
        
        // Add repository URL field
        add_settings_field(
            'repository_url',
            __('Repository URL', 'github-issues'),
            array($this, 'repository_url_callback'),
            'gif-settings',
            'gif_main_section'
        );
        
        // Add access token field
        add_settings_field(
            'access_token',
            __('Personal Access Token', 'github-issues'),
            array($this, 'access_token_callback'),
            'gif-settings',
            'gif_main_section'
        );
        
        // Add per page field
        add_settings_field(
            'per_page',
            __('Issues Per Page', 'github-issues'),
            array($this, 'per_page_callback'),
            'gif-settings',
            'gif_main_section'
        );
    }

    public function admin_enqueue_scripts() {
        wp_enqueue_style('gif-admin', plugin_dir_url(__DIR__) . 'assets/css/gif-admin.css', array(), '1.0.0', 'all');
        
        // Enqueue our admin script (no external dependencies needed - using native fetch)
        wp_enqueue_script('gif-admin', plugin_dir_url(__DIR__) . 'assets/js/gif-admin.js', array('jquery'), '1.0.0', true);
        
        // Get saved settings
        $settings = get_option($this->option_name, array());
        
        // Localize script for AJAX and nonce
        wp_localize_script('gif-admin', 'gifAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gif_fetch_issues'),
            'saveIssuesNonce' => wp_create_nonce('gif_save_issues'),
            'savedSettings' => array(
                'repositoryUrl' => isset($settings['repository_url']) ? $settings['repository_url'] : '',
                'accessToken' => isset($settings['access_token']) ? $settings['access_token'] : '',
                'perPage' => isset($settings['per_page']) ? intval($settings['per_page']) : 10,
            ),
            'strings' => array(
                'fetchingIssues' => __('Fetching issues...', 'github-issues'),
                'errorFetching' => __('Error fetching issues. Please check your credentials and try again.', 'github-issues'),
                'successMessage' => __('Issues fetched successfully!', 'github-issues'),
                'savedToDatabase' => __('Issues saved to database successfully!', 'github-issues'),
                'invalidUrl' => __('Please enter a valid GitHub repository URL.', 'github-issues'),
            )
        ));
    }
    
    /**
     * Sanitize settings input
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        if (isset($input['repository_url'])) {
            $sanitized['repository_url'] = esc_url_raw($input['repository_url']);
        }
        
        if (isset($input['access_token'])) {
            $sanitized['access_token'] = sanitize_text_field($input['access_token']);
        }
        
        if (isset($input['per_page'])) {
            $per_page = intval($input['per_page']);
            $sanitized['per_page'] = ($per_page >= 1 && $per_page <= 100) ? $per_page : 10;
        }
        
        return $sanitized;
    }
    
    /**
     * Section callback
     */
    public function section_callback() {
        echo '<p>' . esc_html__('Configure your GitHub repository settings. These will be used as defaults when fetching issues.', 'github-issues') . '</p>';
    }
    
    /**
     * Repository URL field callback
     */
    public function repository_url_callback() {
        $settings = get_option($this->option_name, array());
        $value = isset($settings['repository_url']) ? $settings['repository_url'] : '';
        ?>
        <input 
            type="url" 
            id="repository_url" 
            name="<?php echo esc_attr($this->option_name); ?>[repository_url]" 
            value="<?php echo esc_attr($value); ?>" 
            class="regular-text"
            placeholder="https://github.com/username/repository"
        />
        <p class="description">
            <?php esc_html_e('Enter the full URL of your GitHub repository (e.g., https://github.com/octocat/Hello-World)', 'github-issues'); ?>
        </p>
        <?php
    }
    
    /**
     * Access token field callback
     */
    public function access_token_callback() {
        $settings = get_option($this->option_name, array());
        $value = isset($settings['access_token']) ? $settings['access_token'] : '';
        ?>
        <input 
            type="password" 
            id="access_token" 
            name="<?php echo esc_attr($this->option_name); ?>[access_token]" 
            value="<?php echo esc_attr($value); ?>" 
            class="regular-text"
            placeholder="ghp_xxxxxxxxxxxxxxxxxxxx"
        />
        <p class="description">
            <?php esc_html_e('Generate a personal access token in your GitHub settings.', 'github-issues'); ?>
            <a href="https://github.com/settings/tokens" target="_blank" rel="noopener">
                <?php esc_html_e('Create token', 'github-issues'); ?> <span class="dashicons dashicons-external"></span>
            </a>
        </p>
        <?php
    }
    
    /**
     * Per page field callback
     */
    public function per_page_callback() {
        $settings = get_option($this->option_name, array());
        $value = isset($settings['per_page']) ? intval($settings['per_page']) : 10;
        ?>
        <input 
            type="number" 
            id="per_page" 
            name="<?php echo esc_attr($this->option_name); ?>[per_page]" 
            value="<?php echo esc_attr($value); ?>" 
            min="1" 
            max="100" 
            class="small-text"
        />
        <p class="description">
            <?php esc_html_e('Number of issues to fetch per page (1-100). Default: 10', 'github-issues'); ?>
        </p>
        <?php
    }
    
    /**
     * AJAX handler for saving issues to database
     */
    public function ajax_save_issues() {
        // Check if request is AJAX
        if (!wp_doing_ajax()) {
            wp_die('Invalid request');
        }
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'gif_save_issues')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        // Debug: Log the received data
        error_log('AJAX Save Issues - POST data: ' . print_r($_POST, true));
        
        // Get the issues data
        $issues_json = isset($_POST['issues']) ? wp_unslash($_POST['issues']) : '';
        $repository_info = isset($_POST['repository']) ? sanitize_text_field($_POST['repository']) : '';
        
        if (empty($issues_json)) {
            wp_send_json_error('No issues data provided');
            return;
        }
        
        // Decode JSON data
        $issues_data = json_decode($issues_json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error('Invalid JSON data: ' . json_last_error_msg());
            return;
        }
        
        if (empty($issues_data) || !is_array($issues_data)) {
            wp_send_json_error('Invalid issues data format');
            return;
        }
        
        // Prepare the data to save
        $saved_issues = array(
            'repository' => $repository_info,
            'issues' => $issues_data,
            'last_updated' => current_time('mysql'),
            'total_count' => count($issues_data)
        );
        
        // Save to options table
        $result = update_option('gif_saved_issues', $saved_issues);
        
        if ($result !== false) {
            wp_send_json_success(array(
                'message' => 'Issues saved successfully',
                'count' => count($issues_data),
                'repository' => $repository_info
            ));
        } else {
            // Check if data is the same (update_option returns false if no change)
            $existing_data = get_option('gif_saved_issues', array());
            if (!empty($existing_data) && $existing_data['repository'] === $repository_info && 
                count($existing_data['issues']) === count($issues_data)) {
                wp_send_json_success(array(
                    'message' => 'Issues already up to date',
                    'count' => count($issues_data),
                    'repository' => $repository_info
                ));
            } else {
                wp_send_json_error('Failed to save issues to database');
            }
        }
    }
    
    /**
     * Debug admin notice
     */
    public function debug_admin_notice() {
        // Only show on GitHub Issues admin pages
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'github-issues') === false) {
            return;
        }
        
        $saved_issues = get_option('gif_saved_issues', array());
    }
    
    /**
     * Display settings page
     */
    public function display_settings() {
        // Check if settings were saved
        if (isset($_GET['settings-updated'])) {
            add_settings_error('gif_messages', 'gif_message', __('Settings saved successfully!', 'github-issues'), 'updated');
        }
        
        settings_errors('gif_messages');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields($this->options_group);
                do_settings_sections('gif-settings');
                submit_button(__('Save Settings', 'github-issues'));
                ?>
            </form>
        </div>
        <?php
    }

    public function display_issues() {
        // Get saved settings
        $settings = get_option($this->option_name, array());
        $saved_repo_url = isset($settings['repository_url']) ? $settings['repository_url'] : '';
        $saved_token = isset($settings['access_token']) ? $settings['access_token'] : '';
        
        // Check if we have saved settings
        $has_settings = !empty($saved_repo_url) && !empty($saved_token);
        
        // Show settings link if no settings are configured
        if (!$has_settings) {
            $settings_url = admin_url('admin.php?page=github-issues-settings');
        }
        ?>
        <div class="wrap gif-admin-wrap">
            <div class="gif-header">
                <h1 class="gif-title">
                    <span class="dashicons dashicons-admin-site"></span>
                    <?php echo esc_html('GitHub Issue Fetcher'); ?>
                </h1>
                <p class="gif-description"><?php echo esc_html('Fetch and display GitHub issues from your repository.'); ?></p>
                <?php if (!$has_settings): ?>
                    <div class="gif-settings-notice">
                        <p>
                            <span class="dashicons dashicons-info"></span>
                            <?php 
                            printf(
                                esc_html__('No default settings configured. %s to save your repository URL and access token.', 'github-issues'),
                                '<a href="' . esc_url($settings_url) . '">' . esc_html__('Go to Settings', 'github-issues') . '</a>'
                            ); 
                            ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="gif-form-container">
                <form method="post" action="" class="gif-form" id="gif-settings-form">
                    <?php wp_nonce_field('gif_fetch_issues', 'gif_nonce'); ?>
                    
                    <div class="gif-form-section">
                        <div class="gif-section-header">
                            <h3 class="gif-section-title"><?php echo esc_html('Repository Configuration'); ?></h3>
                            <?php if ($has_settings): ?>
                                <div class="gif-settings-actions">
                                    <button type="button" class="button button-secondary" id="use-saved-settings">
                                        <span class="dashicons dashicons-saved"></span>
                                        <?php echo esc_html('Use Saved Settings'); ?>
                                    </button>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=github-issues-settings')); ?>" class="button button-secondary">
                                        <span class="dashicons dashicons-admin-generic"></span>
                                        <?php echo esc_html('Edit Settings'); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="gif-form-group">
                            <label for="github_repository_url" class="gif-label">
                                <span class="gif-label-text"><?php echo esc_html('GitHub Repository URL'); ?></span>
                                <span class="gif-required">*</span>
                            </label>
                            <input 
                                type="url" 
                                id="github_repository_url" 
                                name="github_repository_url" 
                                class="gif-input"
                                value="<?php echo esc_attr($saved_repo_url); ?>"
                                placeholder="https://github.com/username/repository"
                                required
                                aria-describedby="repo-url-help"
                            />
                            <p class="gif-help-text" id="repo-url-help">
                                <?php echo esc_html('Enter the full URL of your GitHub repository (e.g., https://github.com/octocat/Hello-World)'); ?>
                            </p>
                        </div>

                        <div class="gif-form-group">
                            <label for="github_personal_access_token" class="gif-label">
                                <span class="gif-label-text"><?php echo esc_html('Personal Access Token'); ?></span>
                                <span class="gif-required">*</span>
                            </label>
                            <input 
                                type="password" 
                                id="github_personal_access_token" 
                                name="github_personal_access_token" 
                                class="gif-input"
                                value="<?php echo esc_attr($saved_token); ?>"
                                placeholder="ghp_xxxxxxxxxxxxxxxxxxxx"
                                required
                                aria-describedby="token-help"
                            />
                            <p class="gif-help-text" id="token-help">
                                <?php echo esc_html('Generate a personal access token in your GitHub settings.'); ?>
                                <a href="https://github.com/settings/tokens" target="_blank" rel="noopener">
                                    <?php echo esc_html('Create token'); ?> <span class="dashicons dashicons-external"></span>
                                </a>
                            </p>
                        </div>
                    </div>

                    <div class="gif-form-actions">
                        <button type="submit" class="gif-submit-btn">
                            <span class="dashicons dashicons-download"></span>
                            <?php echo esc_html('Fetch Issues'); ?>
                        </button>
                        <div class="gif-loading" id="gif-loading" style="display: none;">
                            <span class="spinner is-active"></span>
                            <?php echo esc_html('Fetching issues...'); ?>
                        </div>
                    </div>
                </form>
            </div>

            <?php if (!$has_settings): ?>
            <div class="gif-info-box">
                <h4><span class="dashicons dashicons-info"></span> <?php echo esc_html('Quick Setup Guide'); ?></h4>
                <ol>
                    <li><?php echo esc_html('Go to your GitHub repository'); ?></li>
                    <li><?php echo esc_html('Copy the repository URL from your browser'); ?></li>
                    <li><?php echo esc_html('Create a personal access token with \'repo\' permissions'); ?></li>
                    <li><?php printf(esc_html__('Save your settings in the %s page', 'github-issues'), '<a href="' . esc_url($settings_url) . '">' . esc_html__('Settings', 'github-issues') . '</a>'); ?></li>
                </ol>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
}