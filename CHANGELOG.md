# Changelog

All notable changes to WP Amsawal will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added - Fase 11: Duolingo-Style Repetition & Guide
- Guide button always clickable for completed sections (reopen theory modal anytime)
- Repeat attempt support: completed exercises can be redone with reduced XP (1-3 XP)
- Repeat attempts tracked in user meta for statistics
- Section completion detection with celebratory feedback bar
- Section completion header styling (green border, check icon)
- Guide theory modal with focus trap, ARIA, ESC close, animations
- CSP worker-src fix (`worker-src 'self' blob:;`) for H5P workers
- Feedback bar with CONTINUAR/REINTENTAR actions based on score (≥70%/<70%)

### Added - Fase 10: Learning Path & Navigation
- Zigzag learning path with progress tracking via user_meta
- next_lesson_url resolved from page containing H5P content
- GamiPress rank fallback: user_meta `_amsawal_completed_items` + H5P→lesson mapping
- Module boundaries with guide content (alphabet, greetings, numbers, family, adjectives)
- PWA manifest icon updated to `images/yaz_icon.png`

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
