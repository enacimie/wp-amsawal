#!/bin/bash
# F9-6: Test Runner - Execute all tests

echo "🧪 WP Amsawal Test Suite"
echo "========================"
echo ""

cd "$(dirname "$0")/.."

# Run unit tests
echo "1️  Unit Tests"
echo "--------------"
php tests/test-ui-ux.php
echo ""

# Run integration tests
echo "2️⃣  Integration Tests"
echo "--------------------"
php tests/test-integration.php
echo ""

# Run performance budget
echo "3️⃣  Performance Budget"
echo "---------------------"
php tests/test-performance-budget.php
echo ""

# Optional: Visual regression (requires Chrome)
if command -v google-chrome &> /dev/null; then
    echo "4️⃣  Visual Regression"
    echo "--------------------"
    bash tests/visual-regression.sh
    echo ""
else
    echo "4️⃣  Visual Regression - SKIPPED (Chrome not installed)"
    echo ""
fi

# Optional: Accessibility audit (requires pa11y)
if command -v pa11y &> /dev/null; then
    echo "5️⃣  Accessibility Audit"
    echo "----------------------"
    bash tests/accessibility-audit.sh
    echo ""
else
    echo "5️⃣  Accessibility Audit - SKIPPED (pa11y not installed)"
    echo ""
fi

echo "✨ All tests complete"
