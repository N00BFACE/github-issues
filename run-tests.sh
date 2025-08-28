#!/bin/bash

# GitHub Issues Plugin Test Runner
# This script runs PHPUnit tests for the plugin

echo "🚀 Starting GitHub Issues Plugin Tests..."
echo "=========================================="

# Check if composer is installed
if ! command -v composer &> /dev/null; then
    echo "❌ Composer is not installed. Please install Composer first."
    exit 1
fi

# Check if we're in the plugin directory
if [ ! -f "composer.json" ]; then
    echo "❌ Please run this script from the plugin root directory."
    exit 1
fi

# Install dependencies if vendor directory doesn't exist
if [ ! -d "vendor" ]; then
    echo "📦 Installing Composer dependencies..."
    composer install
fi

# Run tests
echo "🧪 Running PHPUnit tests..."
echo ""

# Check if PHPUnit is available
if [ ! -f "vendor/bin/phpunit" ]; then
    echo "❌ PHPUnit not found. Installing dependencies..."
    composer install
fi

# Run tests with coverage
echo "Running tests with coverage..."
vendor/bin/phpunit --coverage-html coverage

echo ""
echo "✅ Tests completed!"
echo "📊 Coverage report generated in: coverage/index.html"
echo ""
echo "To run tests without coverage:"
echo "  vendor/bin/phpunit"
echo ""
echo "To run specific test file:"
echo "  vendor/bin/phpunit tests/unit/test-gif-public.php"
echo ""
echo "To run specific test method:"
echo "  vendor/bin/phpunit --filter test_get_saved_issues tests/unit/test-gif-public.php"
