<?php

/**
 * Plugin Name: GitHub Issue Fetcher
 * Description: Fetches issues from a GitHub repository and displays them in a WordPress site.
 * Version: 1.0.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) exit;

// Load all dependencies.
require_once __DIR__ . '/includes/class-gif-loader.php';

new GifLoader();
