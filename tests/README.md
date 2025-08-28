# GitHub Issues Plugin Tests

This directory contains PHPUnit tests for the GitHub Issues WordPress plugin.

## Test Structure

- `bootstrap/` - Test bootstrap and helper functions
- `unit/` - Unit tests for individual classes
  - `test-gif-public.php` - Tests for GifPublic class
  - `test-gif-admin.php` - Tests for GifAdmin class
  - `test-gif-block.php` - Tests for GifBlock class

## Running Tests

### Prerequisites

1. Install Composer dependencies:
   ```bash
   composer install
   ```

2. Set up WordPress test environment (optional, for full integration tests):
   ```bash
   # Download WordPress test library
   bash bin/install-wp-tests.sh wordpress_test root '' localhost latest
   ```

### Running Tests

1. **Run all tests:**
   ```bash
   composer test
   ```

2. **Run with coverage:**
   ```bash
   composer test:coverage
   ```

3. **Run specific test file:**
   ```bash
   vendor/bin/phpunit tests/unit/test-gif-public.php
   ```

4. **Run specific test method:**
   ```bash
   vendor/bin/phpunit --filter test_get_saved_issues tests/unit/test-gif-public.php
   ```

## Test Data

The tests use mock data that includes:
- Sample GitHub issues
- Repository information
- Plugin settings

Test data is automatically set up in `setUp()` and cleaned up in `tearDown()`.

## Writing New Tests

1. Create a new test file in `tests/unit/`
2. Extend `WP_UnitTestCase`
3. Use the helper functions from `bootstrap/bootstrap.php`
4. Follow the existing test patterns

## Test Coverage

The tests cover:
- ✅ Data retrieval and validation
- ✅ HTML rendering
- ✅ Settings management
- ✅ AJAX handlers
- ✅ Block registration and rendering
- ✅ Asset enqueuing
- ✅ Error handling
- ✅ Edge cases

## Continuous Integration

Tests can be integrated with CI/CD pipelines using the provided composer scripts.
