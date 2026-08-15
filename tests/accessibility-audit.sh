#!/bin/bash
# F9-4: Accessibility Audit using pa11y
# Install: npm install -g pa11y

echo "♿ Accessibility Audit"
echo "======================"

PAGES=(
    "http://localhost:8080/"
    "http://localhost:8080/cursos-disponibles"
    "http://localhost:8080/liderazgos"
)

for page in "${PAGES[@]}"; do
    echo "🔍 Auditing: $page"
    pa11y "$page" --standard WCAG2AA --reporter cli
    echo ""
done

echo "✨ Accessibility audit complete"
