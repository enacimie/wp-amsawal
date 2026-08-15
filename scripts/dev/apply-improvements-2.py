#!/usr/bin/env python3
"""Aplica mejoras UI/UX Fase 7 - Batch 2"""
import re

def apply_f7_4_consolidate_tokens():
    """F7-4: Consolidar sistema de tokens - añadir comentarios de mapeo"""
    with open('css/modules/_variables.css', 'r', encoding='utf-8') as f:
        css = f.read()
    
    # Añadir mapa de tokens consolidado
    token_map = """
  /* ═══════════════════════════════════════════════════════════════
   * F7-4: TOKEN CONSOLIDATION MAP
   * Primary: --amsawal-* (use these in new code)
   * Legacy: --duo-* (aliases, keep for backward compatibility)
   * RGB: --color-* (for rgba() usage, H5P styles)
   * ═══════════════════════════════════════════════════════════════
   * --amsawal-primary (#2c5f8d) = --duo-green = rgb(--color-macaw: 52,152,219)
   * --amsawal-accent (#3498db) = --duo-blue = rgb(--color-whale: 41,128,185)
   * --amsawal-success (#27ae60) = --duo-green (legacy) = rgb(--color-owl: 39,174,96)
   * --amsawal-warning (#f39c12) = --duo-orange = rgb(--color-fox: 243,156,18)
   * --amsawal-error (#e74c3c) = --duo-red = rgb(--color-fire-ant: 231,76,60)
   * --amsawal-tifinagh (#d4af37) = --duo-gold = rgb(--color-camel: 212,175,55)
   * ═══════════════════════════════════════════════════════════════ */
"""
    
    # Insertar después del bloque de legacy aliases
    if 'F7-4: TOKEN CONSOLIDATION MAP' not in css:
        css = css.replace(
            "  --duo-font: 'Quicksand', 'Inter', system-ui, sans-serif;\n}",
            "  --duo-font: 'Quicksand', 'Inter', system-ui, sans-serif;\n" + token_map + "}"
        )
    
    with open('css/modules/_variables.css', 'w', encoding='utf-8') as f:
        f.write(css)
    print("✅ F7-4: Token consolidation map añadido")
    return True

def apply_f7_5_dark_mode():
    """F7-5: Dark mode completo para módulos faltantes"""
    
    # _gamification.css
    with open('css/modules/_gamification.css', 'r', encoding='utf-8') as f:
        css = f.read()
    
    dark_gamification = """
/* F7-5: Dark mode para gamificación */
[data-theme="dark"] .duo-gamification-bar,
[data-theme="dark"] .duo-achievement-case,
[data-theme="dark"] .duo-quest-card {
  background: var(--duo-card);
  border-color: var(--duo-border);
}

[data-theme="dark"] .duo-level-badge {
  box-shadow: 0 4px 12px rgba(0,0,0,0.4);
}

[data-theme="dark"] .duo-xp-bar,
[data-theme="dark"] .duo-quest-progress-bar-wrapper {
  background: var(--duo-border);
}

[data-theme="dark"] .duo-gamification-streak,
[data-theme="dark"] .duo-gamification-lives {
  background: rgba(255,255,255,0.05);
  border-color: var(--duo-border);
}

[data-theme="dark"] .duo-streak-panel {
  box-shadow: 0 8px 24px rgba(0,0,0,0.4);
}

[data-theme="dark"] .duo-achievement-case.is-earned {
  box-shadow: 0 0 0 3px rgba(212,175,55,0.3);
}
"""
    
    if '[data-theme="dark"] .duo-gamification-bar' not in css:
        css += dark_gamification
    
    with open('css/modules/_gamification.css', 'w', encoding='utf-8') as f:
        f.write(css)
    
    # _learning-path.css
    with open('css/modules/_learning-path.css', 'r', encoding='utf-8') as f:
        css = f.read()
    
    dark_learning = """
/* F7-5: Dark mode para learning path */
[data-theme="dark"] .duo-node--current {
  background: var(--duo-card);
  box-shadow: 0 0 0 6px rgba(52,152,219,0.3);
}

[data-theme="dark"] .duo-node--current .duo-node-circle {
  background: linear-gradient(135deg, var(--duo-card), #1a2332);
  border-color: var(--amsawal-accent);
  color: var(--amsawal-accent);
  box-shadow: 0 8px 0 var(--amsawal-primary-dark),
              inset 0 -4px 0 rgba(0,0,0,0.2),
              0 0 0 6px rgba(52,152,219,0.2);
}

[data-theme="dark"] .duo-node--locked .duo-node-circle {
  background: linear-gradient(135deg, #2a2e36, #1a1d24);
  color: #5a6a6b;
  box-shadow: 0 8px 0 #1a1d24,
              inset 0 -4px 0 rgba(0,0,0,0.2);
}

[data-theme="dark"] .duo-connector-path {
  stroke: var(--duo-border) !important;
}

[data-theme="dark"] .duo-connector--completed .duo-connector-path,
[data-theme="dark"] .duo-connector--current .duo-connector-path {
  stroke: var(--amsawal-primary) !important;
}

[data-theme="dark"] .duo-unit-header {
  background: rgba(52,152,219,0.1);
  border-color: var(--duo-border);
}

[data-theme="dark"] .duo-treasure-chest--locked {
  filter: grayscale(0.8) brightness(0.5);
}
"""
    
    if '[data-theme="dark"] .duo-node--current' not in css:
        css += dark_learning
    
    with open('css/modules/_learning-path.css', 'w', encoding='utf-8') as f:
        f.write(css)
    
    # _ai-components.css
    with open('css/modules/_ai-components.css', 'r', encoding='utf-8') as f:
        css = f.read()
    
    dark_ai = """
/* F7-5: Dark mode para AI components */
[data-theme="dark"] .duo-ai-card-front {
  background: var(--duo-card);
  border-color: var(--duo-border);
  color: var(--duo-text);
}

[data-theme="dark"] .duo-ai-input {
  background: var(--duo-card);
  border-color: var(--duo-border);
  color: var(--duo-text);
}

[data-theme="dark"] .duo-ai-memory-card {
  background: var(--duo-card);
  border-color: var(--duo-border);
}

[data-theme="dark"] .duo-ai-option {
  background: var(--duo-card);
  border-color: var(--duo-border);
  color: var(--duo-text);
}

[data-theme="dark"] .duo-ai-dropzone {
  background: rgba(255,255,255,0.05);
  border-color: var(--duo-border);
}

[data-theme="dark"] .duo-ai-regenerate-wrap {
  background: rgba(206,130,255,0.1);
  border-color: rgba(206,130,255,0.3);
}

[data-theme="dark"] .duo-ai-essay,
[data-theme="dark"] .duo-adaptest {
  background: var(--duo-card);
  border-color: var(--duo-border);
}

[data-theme="dark"] .duo-ai-essay textarea {
  background: var(--duo-bg);
  border-color: var(--duo-border);
  color: var(--duo-text);
}

[data-theme="dark"] .duo-adaptest-card {
  background: var(--duo-bg);
}

[data-theme="dark"] .duo-adaptest-option {
  background: var(--duo-card);
  border-color: var(--duo-border);
}

[data-theme="dark"] .duo-adaptest-option:hover {
  border-color: var(--amsawal-accent);
}

[data-theme="dark"] .duo-adaptest-progress-bar[role="progressbar"] {
  background: var(--duo-border);
}
"""
    
    if '[data-theme="dark"] .duo-ai-card-front' not in css:
        css += dark_ai
    
    with open('css/modules/_ai-components.css', 'w', encoding='utf-8') as f:
        f.write(css)
    
    # _breadcrumbs.css
    with open('css/modules/_breadcrumbs.css', 'r', encoding='utf-8') as f:
        css = f.read()
    
    dark_breadcrumbs = """
/* F7-5: Dark mode para breadcrumbs */
[data-theme="dark"] .duo-breadcrumbs {
  color: var(--duo-text-light);
}

[data-theme="dark"] .duo-crumb a {
  color: var(--duo-text-light);
}

[data-theme="dark"] .duo-crumb a:hover {
  background: rgba(52,152,219,0.15);
}

[data-theme="dark"] .duo-crumb span[aria-current] {
  color: var(--duo-text);
}

[data-theme="dark"] .duo-crumb-sep {
  color: var(--duo-text-light);
}
"""
    
    if '[data-theme="dark"] .duo-breadcrumbs' not in css:
        css += dark_breadcrumbs
    
    with open('css/modules/_breadcrumbs.css', 'w', encoding='utf-8') as f:
        f.write(css)
    
    # _leaderboard.css
    with open('css/modules/_leaderboard.css', 'r', encoding='utf-8') as f:
        css = f.read()
    
    dark_leaderboard = """
/* F7-5: Dark mode para leaderboard */
[data-theme="dark"] .duo-leader-toggle,
[data-theme="dark"] .duo-leaderboard {
  background: var(--duo-card);
  border-color: var(--duo-border);
}

[data-theme="dark"] .duo-leader-card {
  background: var(--duo-card);
  border-color: var(--duo-border);
}

[data-theme="dark"] .duo-leader-card--me {
  background: rgba(52,152,219,0.15);
  border-color: var(--amsawal-accent);
}

[data-theme="dark"] .duo-leader-card--far {
  background: var(--duo-bg);
  border-color: var(--duo-border);
}

[data-theme="dark"] .duo-tab {
  border-bottom-color: var(--duo-border);
}

[data-theme="dark"] .duo-tab:hover {
  background: rgba(255,255,255,0.05);
}

[data-theme="dark"] .duo-quest-section-header h2 {
  color: var(--duo-text);
}

[data-theme="dark"] .duo-quest-progress-bar-wrapper {
  background: var(--duo-border);
}

[data-theme="dark"] .duo-streak-ring__track {
  stroke: var(--duo-border);
}
"""
    
    if '[data-theme="dark"] .duo-leader-toggle' not in css:
        css += dark_leaderboard
    
    with open('css/modules/_leaderboard.css', 'w', encoding='utf-8') as f:
        f.write(css)
    
    print("✅ F7-5: Dark mode añadido a 5 módulos")
    return True

def apply_f7_6_focus_visible():
    """F7-6: Focus-visible en elementos interactivos"""
    
    # _learning-path.css
    with open('css/modules/_learning-path.css', 'r', encoding='utf-8') as f:
        css = f.read()
    
    focus_learning = """
/* F7-6: Focus-visible para nodos */
.duo-node-circle:focus-visible {
  outline: 3px solid var(--amsawal-tifinagh);
  outline-offset: 4px;
}

.duo-node-badge:focus-visible {
  outline: 3px solid var(--amsawal-tifinagh);
  outline-offset: 2px;
}
"""
    
    if '.duo-node-circle:focus-visible' not in css:
        css += focus_learning
    
    with open('css/modules/_learning-path.css', 'w', encoding='utf-8') as f:
        f.write(css)
    
    # _gamification.css
    with open('css/modules/_gamification.css', 'r', encoding='utf-8') as f:
        css = f.read()
    
    focus_gamification = """
/* F7-6: Focus-visible para quest cards y achievements */
.duo-quest-card:focus-visible {
  outline: 3px solid var(--amsawal-tifinagh);
  outline-offset: 2px;
}

.duo-achievement-case:focus-visible {
  outline: 3px solid var(--amsawal-tifinagh);
  outline-offset: 2px;
}

.duo-leader-toggle:focus-visible {
  outline: 3px solid var(--amsawal-tifinagh);
  outline-offset: 2px;
}

.duo-tab:focus-visible {
  outline: 3px solid var(--amsawal-tifinagh);
  outline-offset: -3px;
}
"""
    
    if '.duo-quest-card:focus-visible' not in css:
        css += focus_gamification
    
    with open('css/modules/_gamification.css', 'w', encoding='utf-8') as f:
        f.write(css)
    
    # _activities.css
    with open('css/modules/_activities.css', 'r', encoding='utf-8') as f:
        css = f.read()
    
    focus_activities = """
/* F7-6: Focus-visible para course cards */
.duo-course-card:focus-visible {
  outline: 3px solid var(--amsawal-tifinagh);
  outline-offset: 2px;
}

.duo-activity:focus-visible {
  outline: 3px solid var(--amsawal-tifinagh);
  outline-offset: 2px;
}

.duo-test-btn:focus-visible,
.duo-info-btn:focus-visible,
.duo-course-btn:focus-visible {
  outline: 3px solid var(--amsawal-tifinagh);
  outline-offset: 2px;
}
"""
    
    if '.duo-course-card:focus-visible' not in css:
        css += focus_activities
    
    with open('css/modules/_activities.css', 'w', encoding='utf-8') as f:
        f.write(css)
    
    # _ai-components.css
    with open('css/modules/_ai-components.css', 'r', encoding='utf-8') as f:
        css = f.read()
    
    focus_ai = """
/* F7-6: Focus-visible para adaptive test options */
.duo-adaptest-option:focus-visible {
  outline: 3px solid var(--amsawal-tifinagh);
  outline-offset: 2px;
}

.duo-ai-card:focus-visible {
  outline: 3px solid var(--amsawal-tifinagh);
  outline-offset: 2px;
}

.duo-ai-regenerate-btn:focus-visible {
  outline: 3px solid var(--amsawal-tifinagh);
  outline-offset: 2px;
}
"""
    
    if '.duo-adaptest-option:focus-visible' not in css:
        css += focus_ai
    
    with open('css/modules/_ai-components.css', 'w', encoding='utf-8') as f:
        f.write(css)
    
    print("✅ F7-6: Focus-visible añadido a elementos interactivos")
    return True

def apply_f7_8_spacing_tokens():
    """F7-8: Adoptar spacing tokens en módulos clave"""
    
    # _learning-path.css - reemplazar algunos px por tokens
    with open('css/modules/_learning-path.css', 'r', encoding='utf-8') as f:
        css = f.read()
    
    # Reemplazar valores específicos (con cuidado de no romper layouts)
    replacements = [
        ('padding: 12px 0 16px;', 'padding: var(--space-3) 0 var(--space-4);'),
        ('gap: 12px;', 'gap: var(--space-3);'),
        ('margin-bottom: 28px;', 'margin-bottom: var(--space-7, 28px);'),
        ('margin-bottom: 8px;', 'margin-bottom: var(--space-2);'),
        ('gap: 8px;', 'gap: var(--space-2);'),
        ('padding: 8px 16px;', 'padding: var(--space-2) var(--space-4);'),
        ('margin-top: 12px;', 'margin-top: var(--space-3);'),
        ('padding-left: 64px;', 'padding-left: var(--space-16);'),
    ]
    
    for old, new in replacements:
        css = css.replace(old, new)
    
    with open('css/modules/_learning-path.css', 'w', encoding='utf-8') as f:
        f.write(css)
    
    print("✅ F7-8: Spacing tokens adoptados en learning-path")
    return True

def apply_f7_9_high_contrast():
    """F7-9: High contrast mode para módulos"""
    
    # _learning-path.css
    with open('css/modules/_learning-path.css', 'r', encoding='utf-8') as f:
        css = f.read()
    
    hc_learning = """
/* F7-9: High contrast mode */
@media (prefers-contrast: more) {
  .duo-node-circle {
    border-width: 3px;
  }
  
  .duo-node--current .duo-node-circle {
    border-width: 5px;
  }
  
  .duo-connector-path {
    stroke-width: 20 !important;
  }
  
  .duo-unit-header {
    border-width: 3px;
  }
}
"""
    
    if 'prefers-contrast: more' not in css:
        css += hc_learning
    
    with open('css/modules/_learning-path.css', 'w', encoding='utf-8') as f:
        f.write(css)
    
    # _gamification.css
    with open('css/modules/_gamification.css', 'r', encoding='utf-8') as f:
        css = f.read()
    
    hc_gamification = """
/* F7-9: High contrast mode */
@media (prefers-contrast: more) {
  .duo-quest-card,
  .duo-achievement-case,
  .duo-leader-card {
    border-width: 3px;
  }
  
  .duo-level-badge {
    border-width: 4px;
  }
}
"""
    
    if 'prefers-contrast: more' not in css:
        css += hc_gamification
    
    with open('css/modules/_gamification.css', 'w', encoding='utf-8') as f:
        f.write(css)
    
    # _activities.css
    with open('css/modules/_activities.css', 'r', encoding='utf-8') as f:
        css = f.read()
    
    hc_activities = """
/* F7-9: High contrast mode */
@media (prefers-contrast: more) {
  .duo-activity,
  .duo-course-card {
    border-width: 3px;
  }
  
  .duo-test-btn,
  .duo-info-btn,
  .duo-course-btn {
    border-width: 3px;
  }
}
"""
    
    if 'prefers-contrast: more' not in css:
        css += hc_activities
    
    with open('css/modules/_activities.css', 'w', encoding='utf-8') as f:
        f.write(css)
    
    print("✅ F7-9: High contrast mode añadido a 3 módulos")
    return True

def apply_f7_12_loading_state():
    """F7-12: Loading state en node popover"""
    with open('css/modules/_learning-path.css', 'r', encoding='utf-8') as f:
        css = f.read()
    
    loading_css = """
/* F7-12: Loading state para node popover */
.duo-node-popover--loading {
  position: relative;
  min-height: 120px;
}

.duo-node-popover--loading::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(90deg,
    var(--amsawal-border) 0%,
    var(--amsawal-bg) 50%,
    var(--amsawal-border) 100%);
  background-size: 200% 100%;
  animation: duo-shimmer 1.5s ease-in-out infinite;
  border-radius: var(--amsawal-radius);
}

@keyframes duo-shimmer {
  0%   { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}
"""
    
    if '.duo-node-popover--loading' not in css:
        css += loading_css
    
    with open('css/modules/_learning-path.css', 'w', encoding='utf-8') as f:
        f.write(css)
    
    print("✅ F7-12: Loading state para node popover añadido")
    return True

def apply_f7_13_microinteractions():
    """F7-13: Micro-interacciones en section headers"""
    with open('css/modules/_learning-path.css', 'r', encoding='utf-8') as f:
        css = f.read()
    
    micro_css = """
/* F7-13: Micro-interacciones en section headers */
.duo-unit-header {
  transition: transform var(--amsawal-duration-fast) var(--amsawal-ease-out),
              box-shadow var(--amsawal-duration-fast) var(--amsawal-ease-out);
}

.duo-unit-header:hover {
  transform: translateY(-2px);
  box-shadow: var(--amsawal-shadow);
}

.duo-unit-guide-btn {
  position: relative;
  overflow: hidden;
}

.duo-unit-guide-btn::after {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  width: 0;
  height: 0;
  background: rgba(255,255,255,0.3);
  border-radius: 50%;
  transform: translate(-50%, -50%);
  transition: width 0.4s ease, height 0.4s ease, opacity 0.4s ease;
  opacity: 0;
}

.duo-unit-guide-btn:active::after {
  width: 200px;
  height: 200px;
  opacity: 0;
  transition: 0s;
}
"""
    
    if 'F7-13: Micro-interacciones' not in css:
        css += micro_css
    
    with open('css/modules/_learning-path.css', 'w', encoding='utf-8') as f:
        f.write(css)
    
    print("✅ F7-13: Micro-interacciones en section headers añadidas")
    return True

def apply_f7_14_emoji_labels():
    """F7-14: Text labels para emoji status en PHP"""
    with open('wp-amsawal-view.php', 'r', encoding='utf-8') as f:
        php = f.read()
    
    # Añadir aria-label a los emojis de status
    php = php.replace(
        "aria-hidden=\"true\">🔥</div>",
        'aria-hidden="true" role="img">🔥</div><span class="screen-reader-text">Racha</span>'
    )
    
    php = php.replace(
        "aria-hidden=\"true\">⭐</div>",
        'aria-hidden="true" role="img">⭐</div><span class="screen-reader-text">Estrella</span>'
    )
    
    php = php.replace(
        "aria-hidden=\"true\">✓</span>",
        'aria-hidden="true" role="img">✓</span><span class="screen-reader-text">Completado</span>'
    )
    
    php = php.replace(
        "aria-hidden=\"true\">🔒</span>",
        'aria-hidden="true" role="img">🔒</span><span class="screen-reader-text">Bloqueado</span>'
    )
    
    with open('wp-amsawal-view.php', 'w', encoding='utf-8') as f:
        f.write(php)
    
    print("✅ F7-14: Text labels para emoji status añadidos")
    return True

# Ejecutar todas las mejoras
if __name__ == '__main__':
    print("🚀 Aplicando mejoras Fase 7 - Batch 2...\n")
    
    apply_f7_4_consolidate_tokens()
    apply_f7_5_dark_mode()
    apply_f7_6_focus_visible()
    apply_f7_8_spacing_tokens()
    apply_f7_9_high_contrast()
    apply_f7_12_loading_state()
    apply_f7_13_microinteractions()
    apply_f7_14_emoji_labels()
    
    print("\n✨ Mejoras P0-P3 completadas")
