#!/usr/bin/env python3
"""Fase 10: Documentation & Developer Experience"""

def apply_f10_1_component_docs():
    """F10-1: Component documentation"""
    docs = """# WP Amsawal Component Documentation

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
3. Flush cache: `docker compose exec -T wordpress bash -c 'cd /var/www/html && php -r "define(\"WP_USE_THEMES\", false); require \"wp-load.php\"; wp_cache_flush();"'`
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
"""
    
    with open('COMPONENTS.md', 'w', encoding='utf-8') as f:
        f.write(docs)
    print("✅ F10-1: Component documentation created (COMPONENTS.md)")
    return True

def apply_f10_2_contributing_guide():
    """F10-2: Contributing guidelines"""
    contributing = """# Contributing to WP Amsawal

Thank you for your interest in contributing! This document provides guidelines for contributing to the project.

## Code of Conduct
- Be respectful and inclusive
- Focus on what is best for the community
- Show empathy towards other community members

## How to Contribute

### Reporting Bugs
1. Check if the bug is already reported in Issues
2. Use the bug report template
3. Include:
   - Steps to reproduce
   - Expected behavior
   - Actual behavior
   - Screenshots if applicable
   - Browser/OS information

### Suggesting Features
1. Open an Issue with the feature request template
2. Describe the problem the feature solves
3. Provide examples of how it would work
4. Wait for maintainer feedback before implementing

### Pull Requests
1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Run tests: `bash tests/run-tests.sh`
5. Commit with descriptive message
6. Push to your fork
7. Open a Pull Request

## Coding Standards

### PHP
- Follow WordPress Coding Standards
- Use PHPDoc comments for functions
- Sanitize all inputs
- Escape all outputs
- Use nonces for form submissions

### CSS
- Use CSS custom properties (variables) for colors, spacing, etc.
- Follow BEM-like naming: `.duo-component--modifier`
- Mobile-first responsive design
- Include dark mode variants: `[data-theme="dark"]`
- Respect `prefers-reduced-motion` and `prefers-contrast`

### JavaScript
- No jQuery (vanilla JS only)
- Use `const`/`let` (no `var`)
- Arrow functions for callbacks
- Async/await for asynchronous code
- Add JSDoc comments for complex functions

### Accessibility
- All interactive elements must be keyboard accessible
- Use semantic HTML elements
- Add ARIA attributes where needed
- Maintain WCAG AA contrast minimum (AAA preferred)
- Test with screen readers

## Testing

### Before Submitting PR
```bash
# Run all tests
bash tests/run-tests.sh

# Check for accessibility issues
bash tests/accessibility-audit.sh

# Verify performance budget
php tests/test-performance-budget.php
```

### Test Coverage
- Unit tests for utility functions
- Integration tests for key workflows
- Visual regression tests for UI changes
- Accessibility audit for all new components

## Commit Messages
Follow [Conventional Commits](https://www.conventionalcommits.org/):
- `feat:` New feature
- `fix:` Bug fix
- `docs:` Documentation changes
- `style:` Code style changes (formatting, etc.)
- `refactor:` Code refactoring
- `test:` Adding/updating tests
- `chore:` Maintenance tasks

Example:
```
feat: add focus trap to mobile drawer

Implement DuoFocusTrap in initDrawer() to prevent keyboard users
from tabbing outside the drawer when it's open.

Closes #123
```

## Review Process
1. Maintainer reviews code within 48 hours
2. Automated tests must pass
3. Accessibility audit must pass
4. Performance budget must not be exceeded
5. At least one approval required for merge

## Questions?
Open a Discussion or contact the maintainers.
"""
    
    with open('CONTRIBUTING.md', 'w', encoding='utf-8') as f:
        f.write(contributing)
    print("✅ F10-2: Contributing guidelines created (CONTRIBUTING.md)")
    return True

def apply_f10_3_changelog():
    """F10-3: Changelog"""
    changelog = """# Changelog

All notable changes to WP Amsawal will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added - Fase 9: Testing & QA
- Unit test framework with 10 test suites
- Integration tests for learning path, H5P, gamification
- Visual regression test script (Puppeteer)
- Accessibility audit script (pa11y)
- Performance budget tests
- Unified test runner

### Added - Fase 8: Performance
- Lazy loading for images (loading="lazy")
- Critical CSS inline for above-the-fold content
- Defer non-critical JavaScript
- Resource hints (preconnect, dns-prefetch)
- Image optimization with sizes attribute
- Cache headers in .htaccess
- Service worker for offline support
- Prefetch critical fonts

### Added - Fase 7: UI/UX & Accessibility
- WCAG AAA color contrast (7.1:1) for all text
- Focus trap in mobile drawer
- ARIA live regions for dynamic content
- Token consolidation map
- Dark mode for 5 modules (gamification, learning-path, ai-components, breadcrumbs, leaderboard)
- Focus-visible styles on all interactive elements
- Streak panel with brand tokens
- Spacing tokens adoption
- High contrast mode support
- Confetti colors from CSS tokens
- Toast position fix for mobile
- Loading state for node popover
- Micro-interactions on section headers
- Text labels for emoji status indicators
- Print stylesheet
- Border radius standardization

## [1.0.0] - 2026-06-09

### Added
- Complete Duolingo-style learning path
- H5P integration with 10 activity types
- AI tutor with Ollama/Qwen3 backend
- Gamification system (XP, levels, streaks, achievements)
- Leaderboard with leagues
- Adaptive testing
- Course management
- User analytics and data collection
- Qualitative AI analysis
- Virtual tutor chat interface
- Mobile-responsive design
- Dark mode support
- Accessibility features (WCAG AA)

### Security
- Nonce verification on all forms
- Input sanitization
- Output escaping
- Capability checks

### Performance
- Critical CSS
- Lazy loading
- Cache optimization
- Service worker

[Unreleased]: https://gitlab.com/amsawal/wp-amsawal/compare/v1.0.0...genai
"""
    
    with open('CHANGELOG.md', 'w', encoding='utf-8') as f:
        f.write(changelog)
    print("✅ F10-3: Changelog created (CHANGELOG.md)")
    return True

def apply_f10_4_api_docs():
    """F10-4: API documentation"""
    api_docs = """# WP Amsawal API Documentation

## AI Endpoints

### Generate H5P Content
```
POST /wp-admin/admin-ajax.php
Action: amsawal_generate_h5p
```

**Parameters:**
- `lesson_id` (int) - Lesson post ID
- `activity_type` (string) - Type: flashcards, mcq, dictation, etc.
- `nonce` (string) - WordPress nonce

**Response:**
```json
{
  "success": true,
  "data": {
    "h5p_id": 123,
    "content": "[h5p id=\"123\"]",
    "type": "Multi Choice"
  }
}
```

### Tutor Chat
```
POST /wp-admin/admin-ajax.php
Action: amsawal_tutor_chat
```

**Parameters:**
- `message` (string) - User message
- `lesson_id` (int) - Current lesson context
- `history` (array) - Previous messages (max 20)
- `nonce` (string) - WordPress nonce

**Response:**
```json
{
  "success": true,
  "data": {
    "reply": "AI response text",
    "context": "Lesson context used"
  }
}
```

### Adaptive Test
```
POST /wp-admin/admin-ajax.php
Action: amsawal_adaptive_test
```

**Parameters:**
- `lesson_id` (int) - Lesson ID
- `answers` (array) - Previous answers with scores
- `nonce` (string) - WordPress nonce

**Response:**
```json
{
  "success": true,
  "data": {
    "question": "Next question text",
    "options": ["Option 1", "Option 2", "Option 3", "Option 4"],
    "difficulty": 0.75,
    "question_number": 5
  }
}
```

## Gamification Endpoints

### Award XP
```
POST /wp-admin/admin-ajax.php
Action: amsawal_award_xp
```

**Parameters:**
- `user_id` (int) - User ID
- `amount` (int) - XP amount
- `reason` (string) - Reason for XP award
- `nonce` (string) - WordPress nonce

### Update Streak
```
POST /wp-admin/admin-ajax.php
Action: amsawal_update_streak
```

**Parameters:**
- `user_id` (int) - User ID
- `nonce` (string) - WordPress nonce

## Data Collection Endpoints

### Track Interaction
```
POST /wp-admin/admin-ajax.php
Action: amsawal_track_interaction
```

**Parameters:**
- `event_type` (string) - Event type
- `user_id` (int) - User ID
- `lesson_id` (int) - Lesson ID
- `metadata` (json) - Additional data
- `nonce` (string) - WordPress nonce

## WordPress Hooks

### Actions
- `amsawal_lesson_complete` - Fired when lesson is completed
- `amsawal_xp_awarded` - Fired when XP is awarded
- `amsawal_level_up` - Fired when user levels up
- `amsawal_streak_updated` - Fired when streak changes
- `amsawal_achievement_unlocked` - Fired when achievement earned

### Filters
- `amsawal_h5p_parameters` - Modify H5P content parameters
- `amsawal_tutor_response` - Modify tutor AI response
- `amsawal_adaptive_difficulty` - Modify test difficulty
- `amsawal_xp_multiplier` - Modify XP multiplier

## Database Schema

### wp_amsawal_user_interactions
```sql
CREATE TABLE wp_amsawal_user_interactions (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT NOT NULL,
  event_type VARCHAR(50) NOT NULL,
  lesson_id BIGINT,
  h5p_id BIGINT,
  metadata JSON,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

### wp_amsawal_qualitative_analysis
```sql
CREATE TABLE wp_amsawal_qualitative_analysis (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT NOT NULL,
  analysis_type VARCHAR(50) NOT NULL,
  content TEXT,
  ai_response TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

### wp_amsawal_aggregated_metrics
```sql
CREATE TABLE wp_amsawal_aggregated_metrics (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  metric_name VARCHAR(100) NOT NULL,
  metric_value DECIMAL(10,2),
  period_start DATE,
  period_end DATE,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```
"""
    
    with open('API.md', 'w', encoding='utf-8') as f:
        f.write(api_docs)
    print("✅ F10-4: API documentation created (API.md)")
    return True

def apply_f10_5_readme_update():
    """F10-5: Update README with comprehensive info"""
    readme = """# WP Amsawal - Tamazight Learning Platform

[![License](https://img.shields.io/badge/license-Proprietary-red.svg)](LICENSE)
[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net)
[![WCAG](https://img.shields.io/badge/WCAG-AAA-green.svg)](https://www.w3.org/WAI/WCAG21/quickref/)

A Duolingo-style educational platform for learning **Tamazight (Tarifit/Rif)** - the Amazigh language of North Africa.

## Features

### Learning Experience
- **Interactive Learning Path** - Duolingo-style node-based progression
- **H5P Activities** - 10+ activity types (flashcards, quizzes, dictation, drag-drop, etc.)
- **AI-Powered Content** - Auto-generated exercises using Ollama/Qwen3
- **Adaptive Testing** - Difficulty adjusts to learner performance
- **Virtual Tutor** - AI chat assistant for questions and guidance

### Gamification
- **XP & Levels** - Earn experience points and level up
- **Streaks** - Daily practice tracking with freeze options
- **Achievements** - Unlock badges for milestones
- **Leaderboards** - Compete in weekly leagues
- **Quests** - Daily challenges for bonus rewards

### Accessibility
- **WCAG AAA** - 7.1:1 contrast ratio for all text
- **Keyboard Navigation** - Full keyboard accessibility
- **Screen Reader Support** - ARIA labels and live regions
- **High Contrast Mode** - Enhanced visibility
- **Reduced Motion** - Respects user preferences
- **Focus Indicators** - Clear focus states on all interactive elements

### Performance
- **Critical CSS** - Inline above-the-fold styles
- **Lazy Loading** - Images load on demand
- **Service Worker** - Offline support
- **Cache Optimization** - Browser and server-side caching
- **Resource Hints** - Preconnect and prefetch

### Technology
- **WordPress Plugin** - Extends WordPress with custom functionality
- **H5P Integration** - Interactive content framework
- **AI Backend** - Ollama with Qwen3.5-4B (Tamazight fine-tuned)
- **Vanilla JavaScript** - No jQuery dependency
- **Modular CSS** - Component-based architecture
- **Docker Support** - Easy local development

## Quick Start

### Prerequisites
- Docker & Docker Compose
- WordPress 6.0+
- PHP 7.4+
- MySQL 5.7+

### Installation
```bash
# Clone repository
git clone https://gitlab.com/amsawal/wp-amsawal.git
cd wp-amsawal

# Start Docker environment
docker compose up -d

# Access site
open http://localhost:8080

# Admin panel
open http://localhost:8080/wp-admin
# Username: admin
# Password: password123
```

### Development
```bash
# View logs
docker compose logs -f wordpress

# Run tests
bash tests/run-tests.sh

# Make changes
# 1. Edit files in project directory
# 2. Copy to Docker:
docker compose cp <file> wordpress:/var/www/html/wp-content/plugins/wp-amsawal/
# 3. Flush cache:
docker compose exec -T wordpress bash -c 'cd /var/www/html && php -r "define(\"WP_USE_THEMES\", false); require \"wp-load.php\"; wp_cache_flush();"'
```

## Documentation

- [Component Documentation](COMPONENTS.md) - Design tokens, components, accessibility
- [API Documentation](API.md) - Endpoints, hooks, database schema
- [Contributing Guide](CONTRIBUTING.md) - How to contribute
- [Changelog](CHANGELOG.md) - Version history
- [Roadmap](ROADMAP.md) - Project roadmap

## Testing

```bash
# Run all tests
bash tests/run-tests.sh

# Individual tests
php tests/test-ui-ux.php           # Unit tests
php tests/test-integration.php     # Integration tests
php tests/test-performance-budget.php  # Performance budget
bash tests/visual-regression.sh    # Visual regression (requires Chrome)
bash tests/accessibility-audit.sh  # Accessibility audit (requires pa11y)
```

## Browser Support

| Browser | Version |
|---------|---------|
| Chrome/Edge | 90+ |
| Firefox | 88+ |
| Safari | 14+ |
| Mobile Safari iOS | 14+ |
| Chrome Android | 90+ |

## License

Proprietary - Amsawal Project

## Acknowledgments

- **Amazigh Culture** - Inspired by Atlas Mountains and Tifinagh script
- **Duolingo** - UI/UX inspiration for learning path design
- **H5P** - Interactive content framework
- **Ollama** - Local AI inference
- **Qwen3.5-4B** - Tamazight fine-tuned language model

## Contact

Project: [GitLab](https://gitlab.com/amsawal/wp-amsawal)
"""
    
    with open('README.md', 'w', encoding='utf-8') as f:
        f.write(readme)
    print("✅ F10-5: README.md updated with comprehensive info")
    return True

# Ejecutar todas las mejoras de documentación
if __name__ == '__main__':
    print("🚀 Aplicando mejoras Fase 10 - Documentation & DX...\n")
    
    apply_f10_1_component_docs()
    apply_f10_2_contributing_guide()
    apply_f10_3_changelog()
    apply_f10_4_api_docs()
    apply_f10_5_readme_update()
    
    print("\n✨ Mejoras de documentación completadas")
