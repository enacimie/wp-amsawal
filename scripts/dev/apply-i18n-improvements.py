#!/usr/bin/env python3
"""Fase 11: Internacionalización - Soporte multilingüe completo"""

def apply_f11_1_translation_files():
    """F11-1: Crear archivos de traducción"""
    import os
    os.makedirs('languages', exist_ok=True)
    
    # Spanish (default)
    es_po = """# WP Amsawal - Spanish Translations
# Copyright (C) 2026 Amsawal Project
# This file is distributed under the same license as the WP Amsawal plugin.
msgid ""
msgstr ""
"Project-Id-Version: WP Amsawal 1.0.0\\n"
"Report-Msgid-Bugs-To: \\n"
"POT-Creation-Date: 2026-06-09 12:00+0000\\n"
"PO-Revision-Date: 2026-06-09 12:00+0000\\n"
"Last-Translator: Amsawal Team\\n"
"Language-Team: Spanish\\n"
"Language: es_ES\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"Plural-Forms: nplurals=2; plural=(n != 1);\\n"

#: wp-amsawal-view.php
msgid "Sección"
msgstr "Sección"

#: wp-amsawal-view.php
msgid "Lección"
msgstr "Lección"

#: wp-amsawal-view.php
msgid "EMPEZAR"
msgstr "EMPEZAR"

#: wp-amsawal-view.php
msgid "COMPLETADO"
msgstr "COMPLETADO"

#: wp-amsawal-view.php
msgid "BLOQUEADO"
msgstr "BLOQUEADO"

#: wp-amsawal-view.php
msgid "GUÍA"
msgstr "GUÍA"

#: wp-amsawal-view.php
msgid "TEST"
msgstr "TEST"

#: wp-amsawal-view.php
msgid "INFO"
msgstr "INFO"

#: wp-amsawal-view.php
msgid "Racha"
msgstr "Racha"

#: wp-amsawal-view.php
msgid "Estrella"
msgstr "Estrella"

#: wp-amsawal-view.php
msgid "Completado"
msgstr "Completado"

#: wp-amsawal-view.php
msgid "Bloqueado"
msgstr "Bloqueado"

#: wp-amsawal-view.php
msgid "días"
msgstr "días"

#: wp-amsawal-view.php
msgid "Nivel"
msgstr "Nivel"

#: wp-amsawal-view.php
msgid "XP"
msgstr "XP"

#: wp-amsawal-view.php
msgid "Monedas"
msgstr "Monedas"

#: wp-amsawal-view.php
msgid "Vidas"
msgstr "Vidas"

#: wp-amsawal-view.php
msgid "Ligas"
msgstr "Ligas"

#: wp-amsawal-view.php
msgid "Perfil"
msgstr "Perfil"

#: wp-amsawal-view.php
msgid "Aprender"
msgstr "Aprender"

#: wp-amsawal-view.php
msgid "Notas"
msgstr "Notas"

#: wp-amsawal-view.php
msgid "Top Monedas"
msgstr "Top Monedas"

#: wp-amsawal-view.php
msgid "Ver ligas"
msgstr "Ver ligas"

#: wp-amsawal-view.php
msgid "Gana"
msgstr "Gana"

#: wp-amsawal-view.php
msgid "hoy"
msgstr "hoy"

#: wp-amsawal-view.php
msgid "¡Azul, admin!"
msgstr "¡Azul, admin!"

#: wp-amsawal-view.php
msgid "Primeros pasos: El alfabeto"
msgstr "Primeros pasos: El alfabeto"

#: wp-amsawal-view.php
msgid "Saludos y Presentaciones"
msgstr "Saludos y Presentaciones"

#: wp-amsawal-view.php
msgid "Números y Tiempo"
msgstr "Números y Tiempo"

#: wp-amsawal-view.php
msgid "Familia y Personas"
msgstr "Familia y Personas"

#: wp-amsawal-view.php
msgid "Adjetivos y Descripciones"
msgstr "Adjetivos y Descripciones"
"""
    
    with open('languages/wp-amsawal-es_ES.po', 'w', encoding='utf-8') as f:
        f.write(es_po)
    
    # Tamazight (Tarifit)
    tzg_po = es_po.replace('Language: es_ES', 'Language: tzg')
    tzg_po = tzg_po.replace('Spanish', 'Tamazight (Tarifit)')
    tzg_po = tzg_po.replace('Amsawal Team', 'Amsawal Team - Tarifit')
    
    # Basic translations (would need native speaker review)
    translations = {
        'Sección': 'Tasekkurt',
        'Lección': 'Tamsirt',
        'EMPEZAR': 'BDU',
        'COMPLETADO': 'YEMMA',
        'BLOQUEADO': 'YEGDEL',
        'GUÍA': 'AMNIR',
        'TEST': 'AKTAR',
        'INFO': 'TALQIT',
        'Racha': 'Tallunt',
        'Estrella': 'Itri',
        'Completado': 'Yemma',
        'Bloqueado': 'Yegdel',
        'días': 'ussan',
        'Nivel': 'Aswir',
        'XP': 'XP',
        'Monedas': 'Tibḥirin',
        'Vidas': 'Tudrin',
        'Ligas': 'Tiliga',
        'Perfil': 'Amaḍal',
        'Aprender': 'Ɛlem',
        'Notas': 'Tizmilin',
        'Top Monedas': 'Tibḥirin n ufella',
        'Ver ligas': 'Ẓer tiliga',
        'Gana': 'Erbe',
        'hoy': 'ass-a',
        '¡Azul, admin!': 'Azul, admin!',
        'Primeros pasos: El alfabeto': 'Isekka imezwura: Alfabet',
        'Saludos y Presentaciones': 'Tisellafin d Tiseggamin',
        'Números y Tiempo': 'Imḍanen d Wemḍiq',
        'Familia y Personas': 'Tawacult d Medden',
        'Adjetivos y Descripciones': 'Ismawen d Tifawin'
    }
    
    for es, tzg in translations.items():
        tzg_po = tzg_po.replace(f'msgstr "{es}"', f'msgstr "{tzg}"')
    
    with open('languages/wp-amsawal-tzg.po', 'w', encoding='utf-8') as f:
        f.write(tzg_po)
    
    # English
    en_po = es_po.replace('Language: es_ES', 'Language: en_US')
    en_po = en_po.replace('Spanish', 'English')
    en_po = en_po.replace('Amsawal Team', 'Amsawal Team')
    
    en_translations = {
        'Sección': 'Section',
        'Lección': 'Lesson',
        'EMPEZAR': 'START',
        'COMPLETADO': 'COMPLETED',
        'BLOQUEADO': 'LOCKED',
        'GUÍA': 'GUIDE',
        'TEST': 'TEST',
        'INFO': 'INFO',
        'Racha': 'Streak',
        'Estrella': 'Star',
        'Completado': 'Completed',
        'Bloqueado': 'Locked',
        'días': 'days',
        'Nivel': 'Level',
        'XP': 'XP',
        'Monedas': 'Coins',
        'Vidas': 'Lives',
        'Ligas': 'Leagues',
        'Perfil': 'Profile',
        'Aprender': 'Learn',
        'Notas': 'Notes',
        'Top Monedas': 'Top Coins',
        'Ver ligas': 'View leagues',
        'Gana': 'Earn',
        'hoy': 'today',
        '¡Azul, admin!': 'Blue, admin!',
        'Primeros pasos: El alfabeto': 'First steps: The alphabet',
        'Saludos y Presentaciones': 'Greetings and Introductions',
        'Números y Tiempo': 'Numbers and Time',
        'Familia y Personas': 'Family and People',
        'Adjetivos y Descripciones': 'Adjectives and Descriptions'
    }
    
    for es, en in en_translations.items():
        en_po = en_po.replace(f'msgstr "{es}"', f'msgstr "{en}"')
    
    with open('languages/wp-amsawal-en_US.po', 'w', encoding='utf-8') as f:
        f.write(en_po)
    
    print("✅ F11-1: Translation files created (es_ES, tzg, en_US)")
    return True

def apply_f11_2_language_switcher():
    """F11-2: Mejorar language switcher en PHP"""
    with open('wp-amsawal-ai.php', 'r', encoding='utf-8') as f:
        php = f.read()
    
    # Añadir función de traducción mejorada
    i18n_code = """
// F11-2: Enhanced i18n support
function amsawal_get_available_languages() {
    return [
        'es_ES' => ['name' => 'Español', 'flag' => '🇪🇸', 'dir' => 'ltr'],
        'tzg' => ['name' => 'Tamazight (Tarifit)', 'flag' => '', 'dir' => 'ltr'],
        'en_US' => ['name' => 'English', 'flag' => '🇺🇸', 'dir' => 'ltr'],
    ];
}

function amsawal_get_current_language() {
    $lang = get_user_meta(get_current_user_id(), 'amsawal_language', true);
    if (!$lang) {
        $lang = get_locale();
    }
    return $lang ?: 'es_ES';
}

function amsawal_set_language($lang) {
    $available = array_keys(amsawal_get_available_languages());
    if (in_array($lang, $available)) {
        update_user_meta(get_current_user_id(), 'amsawal_language', $lang);
        return true;
    }
    return false;
}

// AJAX handler for language switch
add_action('wp_ajax_amsawal_switch_language', function() {
    check_ajax_referer('amsawal_nonce', 'nonce');
    
    $lang = sanitize_text_field($_POST['language'] ?? '');
    if (amsawal_set_language($lang)) {
        wp_send_json_success(['language' => $lang]);
    } else {
        wp_send_json_error('Invalid language');
    }
});
"""
    
    if 'amsawal_get_available_languages' not in php:
        php += i18n_code
    
    with open('wp-amsawal-ai.php', 'w', encoding='utf-8') as f:
        f.write(php)
    print("✅ F11-2: Language switcher functions added")
    return True

def apply_f11_3_js_translations():
    """F11-3: Translation system in JS"""
    with open('js/pure-js-script.js', 'r', encoding='utf-8') as f:
        js = f.read()
    
    # Añadir sistema de traducciones en JS
    i18n_js = """
// F11-3: Client-side translation system
const DuoI18n = {
    currentLang: 'es_ES',
    translations: {},
    
    async load(lang) {
        this.currentLang = lang;
        try {
            const response = await fetch(`/wp-content/plugins/wp-amsawal/languages/${lang}.json`);
            this.translations = await response.json();
        } catch (e) {
            console.warn('Translation file not found:', lang);
            this.translations = {};
        }
    },
    
    t(key, params = {}) {
        let text = this.translations[key] || key;
        for (const [param, value] of Object.entries(params)) {
            text = text.replace(`{${param}}`, value);
        }
        return text;
    },
    
    updateDOM() {
        document.querySelectorAll('[data-i18n]').forEach(el => {
            const key = el.getAttribute('data-i18n');
            el.textContent = this.t(key);
        });
        
        document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
            const key = el.getAttribute('data-i18n-placeholder');
            el.placeholder = this.t(key);
        });
        
        document.querySelectorAll('[data-i18n-aria]').forEach(el => {
            const key = el.getAttribute('data-i18n-aria');
            el.setAttribute('aria-label', this.t(key));
        });
    }
};

// Load translations on init
if (typeof DuoI18n !== 'undefined') {
    DuoI18n.load(DuoI18n.currentLang).then(() => {
        DuoI18n.updateDOM();
    });
}
"""
    
    if 'DuoI18n' not in js:
        js = js.replace(
            "document.addEventListener('DOMContentLoaded', function() {",
            "document.addEventListener('DOMContentLoaded', function() {\n" + i18n_js
        )
    
    with open('js/pure-js-script.js', 'w', encoding='utf-8') as f:
        f.write(js)
    print("✅ F11-3: JS translation system added")
    return True

def apply_f11_4_json_translation_files():
    """F11-4: Create JSON translation files for JS"""
    import json
    import os
    
    os.makedirs('languages', exist_ok=True)
    
    # Common translations
    common = {
        "Sección": "Section",
        "Lección": "Lesson",
        "EMPEZAR": "START",
        "COMPLETADO": "COMPLETED",
        "BLOQUEADO": "LOCKED",
        "GUÍA": "GUIDE",
        "TEST": "TEST",
        "INFO": "INFO",
        "Racha": "Streak",
        "Estrella": "Star",
        "Completado": "Completed",
        "Bloqueado": "Locked",
        "días": "days",
        "Nivel": "Level",
        "XP": "XP",
        "Monedas": "Coins",
        "Vidas": "Lives",
        "Ligas": "Leagues",
        "Perfil": "Profile",
        "Aprender": "Learn",
        "Notas": "Notes",
        "Top Monedas": "Top Coins",
        "Ver ligas": "View leagues",
        "Gana": "Earn",
        "hoy": "today",
        "Primeros pasos: El alfabeto": "First steps: The alphabet",
        "Saludos y Presentaciones": "Greetings and Introductions",
        "Números y Tiempo": "Numbers and Time",
        "Familia y Personas": "Family and People",
        "Adjetivos y Descripciones": "Adjectives and Descriptions",
        "Cargando...": "Loading...",
        "Error": "Error",
        "Reintentar": "Retry",
        "Cancelar": "Cancel",
        "Aceptar": "Accept",
        "Cerrar": "Close",
        "Enviar": "Send",
        "Respuesta": "Answer",
        "Correcto": "Correct",
        "Incorrecto": "Incorrect",
        "Siguiente": "Next",
        "Anterior": "Previous",
        "Puntuación": "Score",
        "Tiempo": "Time",
        "Intentos": "Attempts",
        "Mejor puntuación": "Best score",
        "Nueva lección": "New lesson",
        "Continuar": "Continue",
        "Volver al mapa": "Back to map",
        "Tutor virtual": "Virtual tutor",
        "Escribe tu pregunta...": "Type your question...",
        "El tutor está pensando": "The tutor is thinking",
        "Logros": "Achievements",
        "Misiones": "Quests",
        "Tienda": "Shop",
        "Ajustes": "Settings",
        "Ayuda": "Help",
        "Acerca de": "About"
    }
    
    # English
    with open('languages/en_US.json', 'w', encoding='utf-8') as f:
        json.dump(common, f, indent=2, ensure_ascii=False)
    
    # Tamazight (Tarifit) - basic translations
    tzg = {
        "Sección": "Tasekkurt",
        "Lección": "Tamsirt",
        "EMPEZAR": "BDU",
        "COMPLETADO": "YEMMA",
        "BLOQUEADO": "YEGDEL",
        "GUÍA": "AMNIR",
        "TEST": "AKTAR",
        "INFO": "TALQIT",
        "Racha": "Tallunt",
        "Estrella": "Itri",
        "Completado": "Yemma",
        "Bloqueado": "Yegdel",
        "días": "ussan",
        "Nivel": "Aswir",
        "XP": "XP",
        "Monedas": "Tibḥirin",
        "Vidas": "Tudrin",
        "Ligas": "Tiliga",
        "Perfil": "Amaḍal",
        "Aprender": "Ɛlem",
        "Notas": "Tizmilin",
        "Top Monedas": "Tibḥirin n ufella",
        "Ver ligas": "Ẓer tiliga",
        "Gana": "Erbeḥ",
        "hoy": "ass-a",
        "Primeros pasos: El alfabeto": "Isekka imezwura: Alfabet",
        "Saludos y Presentaciones": "Tisellafin d Tiseggamin",
        "Números y Tiempo": "Imḍanen d Wemḍiq",
        "Familia y Personas": "Tawacult d Medden",
        "Adjetivos y Descripciones": "Ismawen d Tifawin",
        "Cargando...": "Yettawit...",
        "Error": "Tuccḍa",
        "Reintentar": "Ɛreḍ tikelt nniḍen",
        "Cancelar": "Sefsex",
        "Aceptar": "Qbel",
        "Cerrar": "Mdel",
        "Enviar": "Azen",
        "Respuesta": "Tiririt",
        "Correcto": "Yemmet",
        "Incorrecto": "Ur yemmi ara",
        "Siguiente": "Uḍfir",
        "Anterior": "Uqbel",
        "Puntuación": "Tasqamt",
        "Tiempo": "Akud",
        "Intentos": "Iɛerḍen",
        "Mejor puntuación": "Tasqamt tafellayt",
        "Nueva lección": "Tamsirt tamaynut",
        "Continuar": "Kemmel",
        "Volver al mapa": "Uɣal ɣer tkarḍa",
        "Tutor virtual": "Amesli n umesli",
        "Escribe tu pregunta...": "Aru asteqsi nnek...",
        "El tutor está pensando": "Amesli yettxemmem",
        "Logros": "Tilisa",
        "Misiones": "Tiwuriwin",
        "Tienda": "Taḥanut",
        "Ajustes": "Iɣewwaṛen",
        "Ayuda": "Tallalt",
        "Acerca de": "ef"
    }
    
    with open('languages/tzg.json', 'w', encoding='utf-8') as f:
        json.dump(tzg, f, indent=2, ensure_ascii=False)
    
    # Spanish (default)
    es = {k: k for k in common.keys()}
    with open('languages/es_ES.json', 'w', encoding='utf-8') as f:
        json.dump(es, f, indent=2, ensure_ascii=False)
    
    print("✅ F11-4: JSON translation files created (es_ES, tzg, en_US)")
    return True

def apply_f11_5_rtl_support():
    """F11-5: RTL support infrastructure"""
    with open('css/modules/_variables.css', 'r', encoding='utf-8') as f:
        css = f.read()
    
    # RTL is not needed for Tamazight (uses Latin script) but good to have infrastructure
    rtl_css = """
/* F11-5: RTL support infrastructure */
/* Note: Tamazight (Tarifit) uses Latin script, so RTL is not currently needed */
/* This is prepared for future Arabic script support */

[dir="rtl"] .duo-container {
  direction: rtl;
}

[dir="rtl"] .duo-node {
  flex-direction: row-reverse;
}

[dir="rtl"] .duo-breadcrumbs {
  direction: rtl;
}

[dir="rtl"] .duo-crumb-sep {
  transform: rotate(180deg);
}

[dir="rtl"] .duo-sidebar {
  left: auto;
  right: 0;
}

[dir="rtl"] .duo-toast-stack {
  right: auto;
  left: 24px;
}
"""
    
    if '[dir="rtl"]' not in css:
        css += rtl_css
    
    with open('css/modules/_variables.css', 'w', encoding='utf-8') as f:
        f.write(css)
    print("✅ F11-5: RTL support infrastructure added")
    return True

def apply_f11_6_language_detection():
    """F11-6: Automatic language detection"""
    with open('wp-amsawal-ai.php', 'r', encoding='utf-8') as f:
        php = f.read()
    
    detection_code = """
// F11-6: Automatic language detection
function amsawal_detect_user_language() {
    // Check browser language
    $browser_lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'es', 0, 2);
    
    $lang_map = [
        'es' => 'es_ES',
        'en' => 'en_US',
        'fr' => 'es_ES', // French speakers in Morocco often use Spanish
        'ar' => 'tzg',  // Arabic speakers might prefer Tamazight
    ];
    
    return $lang_map[$browser_lang] ?? 'es_ES';
}

// Set language on first visit
add_action('init', function() {
    if (is_user_logged_in()) {
        $user_lang = get_user_meta(get_current_user_id(), 'amsawal_language', true);
        if (!$user_lang) {
            $detected = amsawal_detect_user_language();
            update_user_meta(get_current_user_id(), 'amsawal_language', $detected);
        }
    }
});
"""
    
    if 'amsawal_detect_user_language' not in php:
        php += detection_code
    
    with open('wp-amsawal-ai.php', 'w', encoding='utf-8') as f:
        f.write(php)
    print("✅ F11-6: Automatic language detection added")
    return True

# Ejecutar todas las mejoras de i18n
if __name__ == '__main__':
    print(" Aplicando mejoras Fase 11 - Internacionalización...\n")
    
    apply_f11_1_translation_files()
    apply_f11_2_language_switcher()
    apply_f11_3_js_translations()
    apply_f11_4_json_translation_files()
    apply_f11_5_rtl_support()
    apply_f11_6_language_detection()
    
    print("\n✨ Mejoras de internacionalización completadas")
