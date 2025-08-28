# GitHub Issues WordPress Plugin

A comprehensive WordPress plugin for fetching, managing, and displaying GitHub issues from your repositories.

## 🚀 Features

### ✅ Core Functionality
- **GitHub API Integration**: Fetch issues directly from GitHub repositories
- **Settings Management**: Store repository URL and personal access token securely
- **Issue Storage**: Save fetched issues to WordPress database for offline access
- **Flexible Display**: Multiple display options with customizable attributes

### ✅ Display Options
- **Gutenberg Block**: Native WordPress block editor integration
- **Shortcode Support**: `[github_issues]` shortcode for any post/page
- **Multiple Layouts**: List and grid view options
- **Customizable Content**: Show/hide author, date, labels, and issue body
- **State Filtering**: Display all, open, or closed issues

### ✅ Pagination
- **Smart Pagination**: Automatic pagination for large issue lists
- **Configurable Per Page**: Set issues per page (5-50)
- **URL-based Navigation**: SEO-friendly pagination URLs
- **Responsive Design**: Mobile-optimized pagination controls

### ✅ Admin Features
- **Settings API**: WordPress-native settings management
- **AJAX Fetching**: Asynchronous issue fetching with progress indicators
- **Debug Information**: Built-in debugging and logging
- **User Permissions**: Role-based access control

## 📁 File Structure

```
github-issues/
├── assets/
│   ├── css/
│   │   ├── gif-admin.css      # Admin styles
│   │   └── gif-public.css     # Frontend styles
│   └── js/
│       ├── gif-admin.js       # Admin JavaScript
│       └── gif-public.js      # Frontend JavaScript
├── blocks/
│   └── github-issues-block/
│       ├── block.js           # Gutenberg block
│       ├── editor.css         # Editor styles
│       └── style.css          # Frontend block styles
├── includes/
│   ├── class-gif-admin.php    # Admin functionality
│   ├── class-gif-public.php   # Frontend display
│   ├── class-gif-block.php    # Block registration
│   └── class-gif-loader.php   # Plugin loader
├── tests/                     # PHPUnit tests
│   ├── bootstrap/
│   │   └── bootstrap.php      # Test bootstrap
│   └── unit/
│       ├── test-gif-admin.php # Admin tests
│       ├── test-gif-public.php # Public tests
│       └── test-gif-block.php # Block tests
├── composer.json              # PHP dependencies
├── phpunit.xml               # PHPUnit configuration
├── run-tests.sh              # Test runner script
└── github-issue-fetcher.php  # Main plugin file
```

## 🛠️ Installation

### 1. Manual Installation
1. Download the plugin files
2. Upload to `/wp-content/plugins/github-issues/`
3. Activate the plugin in WordPress admin
4. Go to **GitHub Issues > Settings** to configure

### 2. Composer Installation
```bash
composer require github-issues/github-issue-fetcher
```

## ⚙️ Configuration

### 1. GitHub Setup
1. **Repository URL**: Full GitHub repository URL (e.g., `https://github.com/username/repo`)
2. **Personal Access Token**: Generate with `repo` scope permissions
3. **Issues Per Page**: Set default pagination (1-100)

### 2. Settings Location
- **Admin Menu**: GitHub Issues > Settings
- **Settings API**: WordPress native settings management
- **Secure Storage**: Encrypted token storage

## 📖 Usage

### Gutenberg Block
1. Add "GitHub Issues" block to any post/page
2. Configure display options in block settings
3. Preview issues in real-time

### Shortcode
```php
// Basic usage
[github_issues]

// With custom attributes
[github_issues count="5" state="open" layout="grid" per_page="10"]

// Available attributes
[github_issues 
    count="10"           // Total issues to display
    state="all"          // all, open, or closed
    labels="true"        // Show/hide labels
    author="true"        // Show/hide author
    date="true"          // Show/hide date
    body="false"         // Show/hide issue body
    layout="list"        // list or grid
    per_page="10"        // Issues per page
]
```

### PHP Integration
```php
// Get saved issues
$gif_public = new GifPublic();
$issues = $gif_public->get_saved_issues();

// Render issues with custom attributes
$html = $gif_public->render_issues([
    'show_count' => 5,
    'show_state' => 'open',
    'per_page' => 5,
    'page' => 1
]);
```

## 🧪 Testing

### Running Tests
```bash
# Navigate to plugin directory
cd wp-content/plugins/github-issues

# Run all tests
./run-tests.sh

# Or manually
composer test

# With coverage
composer test:coverage
```

### Test Coverage
- **Unit Tests**: Individual class and method testing
- **Integration Tests**: WordPress integration testing
- **Mock Data**: GitHub API response simulation
- **Edge Cases**: Error handling and validation

## 🔧 Development

### Adding New Features
1. **Backend**: Extend classes in `includes/`
2. **Frontend**: Update CSS/JS in `assets/`
3. **Blocks**: Modify `blocks/` directory
4. **Tests**: Add tests in `tests/unit/`

### Code Standards
- **WordPress Coding Standards**: Follow WPCS guidelines
- **PHP 7.4+**: Modern PHP features and syntax
- **ES6+**: Modern JavaScript with WordPress compatibility
- **CSS**: BEM methodology and responsive design

### Debugging
```php
// Enable WordPress debugging
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Check debug log
tail -f wp-content/debug.log
```

## 📱 Responsive Design

### Mobile Optimization
- **Touch-friendly**: Optimized for mobile devices
- **Responsive Layout**: Adaptive grid and list views
- **Mobile Pagination**: Stacked pagination controls
- **Performance**: Optimized loading and rendering

### Browser Support
- **Modern Browsers**: Chrome, Firefox, Safari, Edge
- **Mobile Browsers**: iOS Safari, Chrome Mobile
- **Legacy Support**: IE11+ (with polyfills)

## 🔒 Security

### Data Protection
- **Nonce Verification**: CSRF protection for all forms
- **Capability Checks**: Role-based access control
- **Input Sanitization**: WordPress sanitization functions
- **Secure Storage**: Encrypted sensitive data

### API Security
- **Token Management**: Secure GitHub token storage
- **Rate Limiting**: Respect GitHub API limits
- **Error Handling**: Secure error messages
- **Logging**: Audit trail for debugging

## 🚀 Performance

### Optimization Features
- **Database Caching**: Store issues locally
- **Lazy Loading**: Load content as needed
- **Asset Optimization**: Minified CSS/JS
- **CDN Ready**: Compatible with CDN services

### Caching Strategy
- **Issue Caching**: Store fetched issues in database
- **Asset Caching**: WordPress asset versioning
- **Page Caching**: Compatible with caching plugins
- **API Caching**: Reduce GitHub API calls

## 📊 Monitoring

### Debug Information
- **Admin Notices**: Real-time status updates
- **Console Logging**: Browser developer tools
- **Error Logging**: WordPress debug log
- **Performance Metrics**: Load time and memory usage

### Health Checks
- **API Status**: GitHub API connectivity
- **Database Health**: Issue storage verification
- **Asset Loading**: CSS/JS file availability
- **Block Registration**: Gutenberg integration

## 🤝 Contributing

### Development Setup
1. **Fork Repository**: Create your own fork
2. **Local Development**: Set up WordPress development environment
3. **Run Tests**: Ensure all tests pass
4. **Submit PR**: Create pull request with description

### Code Review
- **Test Coverage**: Maintain high test coverage
- **Code Quality**: Follow WordPress standards
- **Documentation**: Update README and inline docs
- **Performance**: Optimize for speed and memory

## 📄 License

This plugin is licensed under the GPL v2 or later.

## 🆘 Support

### Documentation
- **Inline Comments**: Comprehensive code documentation
- **Usage Examples**: Practical implementation examples
- **Troubleshooting**: Common issues and solutions

### Community
- **WordPress.org**: Plugin support forum
- **GitHub Issues**: Bug reports and feature requests
- **Developer Chat**: Community discussions

---

**Made with ❤️ for the WordPress community**