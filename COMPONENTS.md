# WP Amsawal Component Documentation

## Overview
WP Amsawal is a Duolingo-style educational platform for learning Tamazight (Tarifit/Rif).

## Architecture

### File Structure
```
wp-amsawal/
├── css/
│   ├── modules/           # Modular CSS components
│   │   ├── _variables.css      # Design tokens (colors, spacing, typography)
│   │   ├── _learning-path.css  # Learning path nodes and connectors
│   │   ├── _activities.css     # Activity cards and buttons
│   │   ├── _ai-components.css  # AI-generated content styles
│   │   ├── _gamification.css   # XP, levels, streaks, achievements
│   │   ├── _leaderboard.css    # League tables and rankings
│   │   ├── _feedback-toast.css # Notifications and modals
│   │   ├── _tutor.css          # Virtual tutor chat interface
│   │   ├── _mobile-nav.css     # Bottom navigation (mobile)
│   │   ├── _breadcrumbs.css    # Navigation breadcrumbs
│   │   └── _print.css          # Print stylesheet
│   └── wp-amsawal-style-h5p.css  # H5P content overrides
├── js/
│   └── pure-js-script.js    # Main application logic (2500+ lines)
├── tests/                   # Automated test suite
── wp-amsawal-ai.php        # AI integration (Ollama, Qwen3)
├── wp-amsawal-view.php      # Main view rendering
├── wp-amsawal-courses.php   # Course management
├── wp-amsawal-gamification.php  # Gamification logic
└── ROADMAP.md              # Project roadmap
```

## Design Tokens

### Color Palette
Located in `css/modules/_variables.css`

**Primary Colors:**
- `--amsawal-primary` (#2c5f8d) - Atlas sky blue
- `--amsawal-accent` (#3498db) - Mediterranean blue
- `--amsawal-success` (#27ae60) - Success green
- `--amsawal-warning` (#f39c12) - Warning gold
- `--amsawal-error` (#e74c3c) - Error red
- `--amsawal-tifinagh` (#d4af37) - Traditional gold (Amazigh cultural accent)

**Text Colors (WCAG AAA compliant):**
- `--amsawal-text` (#2c3e50) - Primary text
- `--amsawal-text-light` (#5a6a6b) - Secondary text (7.1:1 contrast)
- `--amsawal-text-muted` (#5a6a6b) - Muted text (7.1:1 contrast)

**Legacy Aliases:**
- `--duo-green` → `--amsawal-primary` (for backward compatibility)
- `--duo-blue` → `--amsawal-accent`
- `--duo-gold` → `--amsawal-tifinagh`

**RGB Tokens (for rgba() usage):**
- `--color-macaw` (52,152,219) - Accent blue
- `--color-owl` (39,174,96) - Success green
- `--color-fox` (243,156,18) - Warning orange

### Spacing Scale
4px base unit:
- `--space-1`: 4px
- `--space-2`: 8px
- `--space-3`: 12px
- `--space-4`: 16px
- `--space-5`: 20px
- `--space-6`: 24px
- `--space-8`: 32px
- `--space-10`: 40px
- `--space-12`: 48px
- `--space-16`: 64px

### Border Radius
- `--amsawal-radius-sm`: 8px
- `--amsawal-radius`: 12px (default)
- `--amsawal-radius-lg`: 20px

## Components

### Learning Path Nodes
**File:** `css/modules/_learning-path.css`

**States:**
- `.duo-node--completed` - Green checkmark, solid background
- `.duo-node--current` - White with blue border, glow effect
- `.duo-node--locked` - Gray, pointer-events: none

**Accessibility:**
- Focus-visible: 3px gold outline
- aria-haspopup="true" on node circles
- aria-current="step" on current node

### Activity Buttons
**File:** `css/modules/_activities.css`

**Types:**
- `.duo-activity--start` - Primary CTA (blue, 3D shadow)
- `.duo-test-btn` - Gold gradient (test/quiz)
- `.duo-info-btn` - Secondary action

**3D Effect:**
```css
box-shadow: 0 6px 0 var(--amsawal-primary-dark);
```
Press effect: translateY(6px) + box-shadow: 0 0 0

### Gamification Elements
**File:** `css/modules/_gamification.css`

**Components:**
- Level badge (circular, gold border)
- XP progress bar (animated shimmer)
- Streak panel (orange gradient)
- Achievement cases (grid layout)

**Dark Mode:**
All components have `[data-theme="dark"]` variants.

## Accessibility Features

### WCAG Compliance
- **Color Contrast:** AAA (7.1:1) for all text
- **Focus Indicators:** 3px gold outline on :focus-visible
- **High Contrast Mode:** @media (prefers-contrast: more)
- **Reduced Motion:** @media (prefers-reduced-motion: reduce)
- **Screen Reader Support:** aria-live regions, aria-labels

### Keyboard Navigation
- Tab cycling through all interactive elements
- Focus trap in mobile drawer (DuoFocusTrap)
- Escape key closes modals/drawers
- Enter/Space activates buttons

### ARIA Attributes
- `aria-live="polite"` - Toast notifications, adaptive test
- `aria-modal="true"` - Dialog overlays
- `aria-expanded` - Drawer toggle
- `aria-current="step"` - Current lesson node
- `aria-disabled="true"` - Locked nodes

## Performance Optimizations

### Critical CSS
Inline above-the-fold styles in `<head>` for fast FCP.

### Lazy Loading
- `loading="lazy"` on images
- `decoding="async"` for non-critical images

### Caching
- Service worker for offline support
- .htaccess cache headers (1 year for images, 1 month for CSS/JS)
- WordPress object cache flush on updates

### Resource Hints
- preconnect to Google Fonts
- dns-prefetch for localhost
- preload for critical fonts

## Testing

### Run Tests
```bash
bash tests/run-tests.sh
```

### Test Types
1. **Unit Tests** (`tests/test-ui-ux.php`)
   - Color tokens, dark mode, focus trap, aria-live, etc.

2. **Integration Tests** (`tests/test-integration.php`)
   - Learning path, H5P content, gamification data

3. **Visual Regression** (`tests/visual-regression.sh`)
   - Puppeteer screenshots (requires Chrome)

4. **Accessibility Audit** (`tests/accessibility-audit.sh`)
   - pa11y WCAG2AA (requires pa11y)

5. **Performance Budget** (`tests/test-performance-budget.php`)
   - Asset size limits

## Development Workflow

### Local Development
```bash
# Start Docker
docker compose up -d

# Access site
http://localhost:8080

# Admin panel
http://localhost:8080/wp-admin
Username: admin
Password: password123

# View logs
docker compose logs -f wordpress
```

### Making Changes
1. Edit files in project directory
2. Copy to Docker: `docker compose cp <file> wordpress:/var/www/html/wp-content/plugins/wp-amsawal/`
3. Flush cache: `docker compose exec -T wordpress bash -c 'cd /var/www/html && php -r "define("WP_USE_THEMES", false); require "wp-load.php"; wp_cache_flush();"'`
4. Test changes in browser

### Committing
```bash
git add <files>
git commit -m "feat: <description>"
```

## Browser Support
- Chrome/Edge 90+
- Firefox 88+
- Safari 14+
- Mobile Safari iOS 14+
- Chrome Android 90+

## License
Proprietary - Amsawal Project
