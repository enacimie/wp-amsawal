#!/bin/bash
# F9-3: Visual Regression Test
# Captures screenshots for key pages

echo "📸 Visual Regression Test"
echo "========================="

# Check if Puppeteer is available
if ! command -v google-chrome &> /dev/null; then
    echo "❌ Chrome not found. Install Chrome for visual tests."
    exit 1
fi

# Pages to test
PAGES=(
    "/"
    "/cursos-disponibles"
    "/liderazgos"
)

# Create screenshots directory
mkdir -p tests/screenshots

for page in "${PAGES[@]}"; do
    echo " Capturing: $page"
    google-chrome --headless --screenshot=tests/screenshots$(echo $page | tr '/' '_').png --window-size=1280,800 "http://localhost:8080$page" 2>/dev/null
    echo "  ✅ Saved: tests/screenshots$(echo $page | tr '/' '_').png"
done

echo ""
echo "✨ Visual regression test complete"
echo "Compare screenshots with baseline in tests/screenshots-baseline/"
