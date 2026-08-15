# Contributing to WP Amsawal

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
