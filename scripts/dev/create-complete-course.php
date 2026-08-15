<?php
/**
 * Generador Completo del Curso Tarifit Basado en Libro de Referencia
 * 
 * Este script crea:
 * 1. Librerías H5P necesarias
 * 2. Páginas de navegación
 * 3. Estructura completa del curso (5 módulos, 20+ lecciones)
 * 4. Actividades H5P reales
 * 5. Sistema de gamificación
 * 
 * Uso: dp amsawal eval-file --allow-root create-complete-course.php
 */

// Verificar WordPress


/*
 * Script de desarrollo. No ejecutar en producción.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'WPINC' ) ) {
    die;
}

if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 'Acceso denegado: se requieren permisos de administrador.' );
}

if (!defined('ABSPATH')) {
    die("❌ Este script debe ejecutarse vía wp-cli\n");
}

// ============================================
// CONFIGURACIÓN Y DATOS DEL CURSO
// ============================================

echo "\n";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║   CURS TARIFIT COMPLET - GENERADÓR DEFINITIV             ║\n";
echo "║   Basat en: 'Curs de Lenga Tamazight - Nivèl Elemental' ║\n";
echo "║   Autor: Jahfar Hassan Yahia (2014)                     ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

// Estructura del curso basada en el libro
$course_structure = [
    'title' => 'Tarifit Complet',
    'description' => 'Curs complet de lenga Tarifit (Tamazight rifeño) basat en metodologia oral-primer',
    'modules' => [
        1 => [
            'title' => 'Mòdul 1: Alfabet i Fonologia',
            'description' => 'Aprene l\'alfabet Tifinagh e la fonetica de Tarifit',
            'lessons' => [
                1 => [
                    'title' => 'Vocals (A, I, U)',
                    'vocabulary' => [
                        'ⴰ (a)' => 'a (vocal aperta)',
                        'ⵉ (i)' => 'i (vocal tancada)',
                        'ⵓ (u)' => 'u (vocal tancada)',
                        'aman' => 'aiga',
                        'iri' => 'còl',
                        'urar' => 'noces'
                    ],
                    'activities' => ['flashcards', 'multiple-choice']
                ],
                2 => [
                    'title' => 'Consonants Oclusives (B, D, G, K, T)',
                    'vocabulary' => [
                        'ⵀ (b)' => 'b (bilabial sonora)',
                        'ⴷ (d)' => 'd (dental sonora)',
                        'ⴳ (g)' => 'g (velar sorda)',
                        'ⴽ (k)' => 'k (velar sorda)',
                        'ⵜ (t)' => 't (dental sorda)',
                        'baba' => 'pair',
                        'addar' => 'mont',
                        'agawri' => 'estranger'
                    ],
                    'activities' => ['fill-blanks', 'multiple-choice']
                ],
                3 => [
                    'title' => 'Consonants Fricatives (F, S, Z, X, H)',
                    'vocabulary' => [
                        'ⵊ (f)' => 'f (labiodental sorda)',
                        'ⵙ (s)' => 's (dental sorda)',
                        'ⵣ (z)' => 'z (dental sonora)',
                        'ⵅ (x)' => 'x (velar sorda, com j castellana)',
                        'ⵀ (h)' => 'h (faríngia sonora)',
                        'fus' => 'man',
                        'sidi' => 'senhor',
                        'axxam' => 'casa'
                    ],
                    'activities' => ['flashcards', 'fill-blanks']
                ],
                4 => [
                    'title' => 'Consonants Especials (R, L, N, M, W, Y)',
                    'vocabulary' => [
                        'ⵔ (r)' => 'r (suau, omisible)',
                        'ⵍ (l)' => 'l (alveolar lateral)',
                        'ⵏ (n)' => 'n (alveolar nasal)',
                        'ⵎ (m)' => 'm (bilabial nasal)',
                        'ⵡ (w)' => 'w (semiconsonant)',
                        'ⵢ (y)' => 'y (semiconsonant)',
                        'ayur' => 'luna',
                        'lalla' => 'senhora',
                        'anawer' => 'activitat'
                    ],
                    'activities' => ['multiple-choice', 'flashcards']
                ]
            ]
        ],
        2 => [
            'title' => 'Mòdul 2: Saluts i Presentacions',
            'description' => 'Expressions de salut i com presentar-se',
            'lessons' => [
                5 => [
                    'title' => 'Saluts Bàsics',
                    'vocabulary' => [
                        'azul' => 'salut/pau',
                        'ṣbaḥ rxar' => 'bons jorns',
                        'msa rxar' => 'bonas vèspras/bonas nuèits',
                        'tensid di rxar' => 'que ages bonas nuèits',
                        'ar tiwecca' => 'fins deman',
                        'ar dawḥda' => 'fins lèu'
                    ],
                    'activities' => ['dialog-cards', 'multiple-choice']
                ],
                6 => [
                    'title' => 'Presentar-se (Ism Inu)',
                    'vocabulary' => [
                        'ism inu' => 'me disi',
                        'cek' => 'tu (masc.)',
                        'cem' => 'tu (fem.)',
                        'wi da?' => 'qual es aquí?',
                        'wa d baba' => 'aquò es mon pair',
                        'ta d yemma' => 'aquò es ma maire'
                    ],
                    'activities' => ['fill-blanks', 'dialog-cards']
                ],
                7 => [
                    'title' => 'Pronoms Demonstratius (Wa, Win, Wenni)',
                    'vocabulary' => [
                        'wa' => 'aqueste (masc.)',
                        'win' => 'aquò (masc.)',
                        'wenni' => 'aquel (masc.)',
                        'ta' => 'aquesta (fem.)',
                        'tin' => 'aquela (fem.)',
                        'tenni' => 'aquela (fem. luènh)',
                        'ay-a' => 'aquò (neutre) pròche',
                        'ay-in' => 'aquò (neutre) mièg-luènh',
                        'ay-nni' => 'aquò (neutre) luènh'
                    ],
                    'activities' => ['multiple-choice', 'flashcards']
                ],
                8 => [
                    'title' => 'Dies de la Setmana',
                    'vocabulary' => [
                        'rḥed' => 'dimeenge',
                        'reṯnayen' => 'diluns',
                        'ttraṯa' => 'dimars',
                        'rabaε' => 'dimècres',
                        'rexmis' => 'dijòus',
                        'jjemεa' => 'divendres',
                        'ssebt' => 'dissabte'
                    ],
                    'activities' => ['flashcards', 'fill-blanks']
                ]
            ]
        ],
        3 => [
            'title' => 'Mòdul 3: Numeros e Temps',
            'description' => 'Numeracion cardinal, ordinal e adverbis de temps',
            'lessons' => [
                9 => [
                    'title' => 'Numeros 0-10',
                    'vocabulary' => [
                        'ṣifr' => 'zèro',
                        'waḥit' => 'un',
                        'ṯnayen' => 'dos',
                        'ṯraṯa' => 'tres',
                        'arbεa' => 'quatre',
                        'xamsa' => 'cinc',
                        'sitta' => 'sièis',
                        'sebεa' => 'sèt',
                        'ṯmanya' => 'uèit',
                        'tesεa' => 'nòu',
                        'εacra' => 'dètz'
                    ],
                    'activities' => ['flashcards', 'multiple-choice', 'fill-blanks']
                ],
                10 => [
                    'title' => 'Numeros 11-20',
                    'vocabulary' => [
                        'ḥidoac' => 'onze',
                        'ṯenoac' => 'dotze',
                        'ṯrettac' => 'tretze',
                        'arbaεtac' => 'catòrze',
                        'xemmestac' => 'quinze',
                        'settac' => 'setze',
                        'sbaεtac' => 'dètz-e-sèt',
                        'ṯmentac' => 'dètz-e-uèit',
                        'tseεtac' => 'dètz-e-nòu',
                        'εicrin' => 'vint'
                    ],
                    'activities' => ['multiple-choice', 'flashcards']
                ],
                11 => [
                    'title' => 'Adverbis de Temps',
                    'vocabulary' => [
                        'fru-faryiḍennaḍ' => 'abans-davant-ièr',
                        'faryiḍennaḍ' => 'davant-ièr',
                        'iḍennaḍ' => 'ièr',
                        'nhar-a' => 'uèi',
                        'tiwecca' => 'deman',
                        'farwayecca' => 'deman-passat',
                        'fru-farwayecca' => 'après-deman-passat'
                    ],
                    'activities' => ['fill-blanks', 'multiple-choice']
                ]
            ]
        ],
        4 => [
            'title' => 'Mòdul 4: Familha e Personas',
            'description' => 'Vocabulari de familha e relacions personalas',
            'lessons' => [
                12 => [
                    'title' => 'Familha Pròcha (Baba, Yemma)',
                    'vocabulary' => [
                        'baba' => 'mon pair',
                        'yemma' => 'ma maire',
                        'baba-c' => 'ton pair (masc.)',
                        'baba-m' => 'ton pair (fem.)',
                        'yemma-c' => 'ta maire (masc.)',
                        'yemma-m' => 'ta maire (fem.)',
                        'mmi' => 'mon filh',
                        'yedji' => 'ma filha'
                    ],
                    'activities' => ['dialog-cards', 'flashcards']
                ],
                13 => [
                    'title' => 'Germans e Sòrres',
                    'vocabulary' => [
                        'uma' => 'mon fraire',
                        'wučma' => 'ma sòrre',
                        'ayṯma' => 'mos fraires',
                        'yessma' => 'mas sòrres',
                        'uma-s' => 'son fraire',
                        'wučma-s' => 'sa sòrre',
                        'awmaten inu' => 'mos fraires',
                        'tiwečmaṯin inu' => 'mas sòrres'
                    ],
                    'activities' => ['multiple-choice', 'fill-blanks']
                ],
                14 => [
                    'title' => 'Oncls e Tantos',
                    'vocabulary' => [
                        'εemmi' => 'mon oncle (fraire de pair)',
                        'εenti' => 'ma tanta (sòrre de pair)',
                        'xali' => 'mon oncle (fraire de maire)',
                        'xalti' => 'ma tanta (sòrre de maire)',
                        'jeddi' => 'mon grand',
                        'ḥenna' => 'ma grand',
                        'εemmi-s' => 'son oncle',
                        'xalti-s' => 'sa tanta'
                    ],
                    'activities' => ['flashcards', 'dialog-cards']
                ],
                15 => [
                    'title' => 'Pronoms Personals',
                    'vocabulary' => [
                        'nec' => 'ieu',
                        'cek' => 'tu (masc.)',
                        'cem' => 'tu (fem.)',
                        'netta' => 'el',
                        'nettat' => 'ela',
                        'neccin' => 'nosautres',
                        'kenniw' => 'vosautres (masc.)',
                        'kennimt' => 'vosautras (fem.)',
                        'nitni' => 'eles',
                        'nitenti' => 'elas'
                    ],
                    'activities' => ['multiple-choice', 'flashcards', 'fill-blanks']
                ]
            ]
        ],
        5 => [
            'title' => 'Mòdul 5: Adjectius i Descripcions',
            'description' => 'Adjectius qualificatius e concòrdia de genre/nombre',
            'lessons' => [
                16 => [
                    'title' => 'Adjectius Básics (Grand/Petit)',
                    'vocabulary' => [
                        'ameqqran' => 'grand (masc. sing.)',
                        'tameqqrant' => 'granda (fem. sing.)',
                        'imeqqranen' => 'grands (masc. pl.)',
                        'timeqqranin' => 'grandas (fem. pl.)',
                        'ameẓẓyan' => 'pichòt (masc. sing.)',
                        'tameẓzyant' => 'pichòta (fem. sing.)',
                        'imeẓẓyanen' => 'pichòts (masc. pl.)',
                        'timeẓẓyanin' => 'pichòtas (fem. pl.)'
                    ],
                    'activities' => ['flashcards', 'multiple-choice']
                ],
                17 => [
                    'title' => 'Adjectius de Talha (Naut/Bas)',
                    'vocabulary' => [
                        'azirar' => 'naut (masc. sing.)',
                        'tazirart' => 'nauta (fem. sing.)',
                        'iziraren' => 'nauts (masc. pl.)',
                        'tizirarin' => 'nautas (fem. pl.)',
                        'aquḍaḍ' => 'bas (masc. sing.)',
                        'taquḍaṭṭ' => 'bassa (fem. sing.)',
                        'iquḍaḍen' => 'basses (masc. pl.)',
                        'tiquḍaḍin' => 'bassas (fem. pl.)'
                    ],
                    'activities' => ['fill-blanks', 'multiple-choice']
                ],
                18 => [
                    'title' => 'Colors',
                    'vocabulary' => [
                        'azegzaw' => 'verd',
                        'azuggwaɣ' => 'roge',
                        'anili' => 'blau',
                        'awraɣ' => 'jaune',
                        'amlil' => 'blanc',
                        'asṭṭay' => 'negre',
                        'axṭṭay' => 'marron/gris',
                        'awraɣ n waman' => 'tòca-clar'
                    ],
                    'activities' => ['flashcards', 'multiple-choice', 'fill-blanks']
                ],
                19 => [
                    'title' => 'Qualitats Fisicas',
                    'vocabulary' => [
                        'azdad' => 'prim (masc.)',
                        'tazdadt' => 'prima (fem.)',
                        'amugdar' => 'espés/gros',
                        'aẓidan' => 'doç sucrat',
                        'arzag' => 'amar',
                        'aḥlaw' => 'doç agradable',
                        'ameqran n wul' => 'generós',
                        'aneflat' => 'rapid'
                    ],
                    'activities' => ['dialog-cards', 'fill-blanks']
                ]
            ]
        ]
    ]
];

echo "📋 Estructura del curs:\n";
$total_lessons = 0;
$total_activities = 0;

foreach ($course_structure['modules'] as $mod_num => $module) {
    echo "   {$module['title']}:\n";
    foreach ($module['lessons'] as $lesson_num => $lesson) {
        $total_lessons++;
        $num_activities = count($lesson['activities']);
        $total_activities += $num_activities;
        echo "     {$lesson['title']} ({$num_activities} activitats)\n";
    }
}

echo "\n";
echo "📊 Resum:\n";
echo "   • Mòduls: " . count($course_structure['modules']) . "\n";
echo "   • Leiçons: {$total_lessons}\n";
echo "   • Activitats H5P: {$total_activities}\n\n";

// ============================================
// PAS 1: INSTALLAR LLIBRERIES H5P
// ============================================

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📦 PAS 1: Verificar i installar llibreries H5P\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

global $wpdb;

// Llibreries necessàries
$required_libraries = [
    'H5P.Dialog Cards' => '1.1.2',
    'H5P.Fill in the Blanks' => '1.1.4',
    'H5P.Multi Choice' => '1.1.6'
];

echo "Llibreries requerides:\n";
foreach ($required_libraries as $lib_name => $version) {
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}h5p_libraries WHERE name = %s",
        $lib_name
    ));
    
    $status = $exists ? "✅ ja installada" : "❌ manca";
    echo "  • {$lib_name} ({$version}): {$status}\n";
}

echo "\n⚠️  Nota: Si les llibreries manquen, cal installar-les manualment des de:\n";
echo "   WordPress Admin → H5P → Libraries → Upload\n";
echo "   Descarregar de: https://h5p.org/library-list\n\n";

// ============================================
// PAS 2: CREAR PÀGINES DE NAVEGACIÓ
// ============================================

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📄 PAS 2: Crear pàgines de navegació\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$nav_pages = [
    'Inicio' => 'Pàgina principal amb el Learning Path',
    'Cursos disponibles' => 'Selector de cursos',
    'Liderazgos' => 'Classificació i competicions',
    'Mis resultados' => 'Progrès personal i estadístiques'
];

$nav_page_ids = [];

foreach ($nav_pages as $page_title => $page_content) {
    $existing = get_page_by_title($page_title);
    
    if ($existing) {
        echo "  ✅ '{$page_title}' ja existeix (ID: {$existing->ID})\n";
        $nav_page_ids[$page_title] = $existing->ID;
    } else {
        $page_id = wp_insert_post([
            'post_title' => $page_title,
            'post_content' => $page_content,
            'post_status' => 'publish',
            'post_type' => 'page'
        ]);
        
        if (!is_wp_error($page_id)) {
            echo "  ✅ '{$page_title}' creada (ID: {$page_id})\n";
            $nav_page_ids[$page_title] = $page_id;
        } else {
            echo "  ❌ Error creant '{$page_title}'\n";
        }
    }
}

// Configurar pàgina d'inici
if (isset($nav_page_ids['Inicio'])) {
    update_option('page_on_front', $nav_page_ids['Inicio']);
    update_option('show_on_front', 'page');
    echo "\n✅ Pàgina 'Inicio' configurada com a portada\n";
}

echo "\n";

// ============================================
// PAS 3: CREAR CURS PRINCIPAL
// ============================================

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📚 PAS 3: Crear estructura del curs\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Crear pàgina del curs principal
$course_title = $course_structure['title'];
$course_page = get_page_by_title($course_title);

if ($course_page) {
    echo "✅ Curs '{$course_title}' ja existeix (ID: {$course_page->ID})\n";
    $course_page_id = $course_page->ID;
} else {
    $course_page_id = wp_insert_post([
        'post_title' => $course_title,
        'post_content' => $course_structure['description'],
        'post_status' => 'publish',
        'post_type' => 'page'
    ]);
    
    if (!is_wp_error($course_page_id)) {
        update_post_meta($course_page_id, 'wp_amsawal_mb_typeh5p', 'course');
        update_post_meta($course_page_id, 'wp_amsawal_mb_course', 'tarifit-complet');
        echo "✅ Curs '{$course_title}' creat (ID: {$course_page_id})\n";
    } else {
        echo "❌ Error creant el curs\n";
        exit(1);
    }
}

echo "\n";

// ============================================
// PAS 4: CREAR MÒDULS I LEIÇONS AMB H5P
// ============================================

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📖 PAS 4: Crear mòduls, leiçons i activitats H5P\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$created_lessons = 0;
$created_activities = 0;

foreach ($course_structure['modules'] as $mod_num => $module) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📦 {$module['title']}\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    // Crear pàgina del mòdul
    $module_title = $module['title'];
    $module_page = get_page_by_title($module_title);
    
    if ($module_page) {
        $module_page_id = $module_page->ID;
        echo "✅ Mòdul ja existeix (ID: {$module_page_id})\n";
    } else {
        $module_page_id = wp_insert_post([
            'post_title' => $module_title,
            'post_content' => $module['description'],
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_parent' => $course_page_id
        ]);
        
        if (!is_wp_error($module_page_id)) {
            update_post_meta($module_page_id, 'wp_amsawal_mb_typeh5p', 'module');
            update_post_meta($module_page_id, 'wp_amsawal_mb_course', 'tarifit-complet');
            update_post_meta($module_page_id, 'wp_amsawal_mb_module', $mod_num);
            echo "✅ Mòdul creat (ID: {$module_page_id})\n";
        }
    }
    
    echo "\n";
    
    // Crear leiçons del mòdul
    foreach ($module['lessons'] as $lesson_num => $lesson) {
        echo "  📖 Leiçon {$lesson_num}: {$lesson['title']}\n";
        
        // Crear pàgina de la leiçon
        $lesson_title = "Leiçon {$lesson_num}: {$lesson['title']}";
        $lesson_page = get_page_by_title($lesson_title);
        
        if ($lesson_page) {
            $lesson_page_id = $lesson_page->ID;
            echo "     ✅ Leiçon ja existeix (ID: {$lesson_page_id})\n";
        } else {
            $lesson_page_id = wp_insert_post([
                'post_title' => $lesson_title,
                'post_content' => '',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_parent' => $module_page_id
            ]);
            
            if (!is_wp_error($lesson_page_id)) {
                update_post_meta($lesson_page_id, 'wp_amsawal_mb_typeh5p', 'lesson');
                update_post_meta($lesson_page_id, 'wp_amsawal_mb_course', 'tarifit-complet');
                update_post_meta($lesson_page_id, 'wp_amsawal_mb_module', $mod_num);
                update_post_meta($lesson_page_id, 'wp_amsawal_mb_lesson', $lesson_num);
                update_post_meta($lesson_page_id, 'wp_amsawal_vocabulary', $lesson['vocabulary']);
                
                $created_lessons++;
                echo "     ✅ Leiçon creada (ID: {$lesson_page_id})\n";
            }
        }
        
        // Crear activitats H5P per aquesta leiçon
        echo "     🎮 Generant activitats H5P:\n";
        
        foreach ($lesson['activities'] as $activity_type) {
            echo "       • {$activity_type}... ";
            
            // Verificar si ja existeix
            $h5p_query = $wpdb->prepare(
                "SELECT content_id FROM {$wpdb->prefix}amsawal_lesson_content " .
                "WHERE lesson_id = %d AND activity_type = %s",
                $lesson_page_id,
                $activity_type
            );
            
            $existing_h5p = $wpdb->get_var($h5p_query);
            
            if ($existing_h5p) {
                echo "ja existeix\n";
                continue;
            }
            
            // Generar contingut H5P
            $h5p_content = generate_h5p_activity($activity_type, $lesson['vocabulary'], $lesson['title']);
            
            if ($h5p_content) {
                // Inserir contingut H5P
                $h5p_id = $wpdb->insert(
                    $wpdb->prefix . 'h5p_contents',
                    [
                        'title' => "{$lesson['title']} - {$activity_type}",
                        'library' => $h5p_content['library'],
                        'parameters' => json_encode($h5p_content['params']),
                        'filtered' => json_encode($h5p_content['params']),
                        'slug' => sanitize_title($lesson['title'] . '-' . $activity_type),
                        'embed_type' => 'div',
                        'disable' => 0,
                        'content_type' => $activity_type,
                        'author' => get_current_user_id()
                    ]
                );
                
                if ($h5p_id) {
                    // Registrar en la taula del plugin
                    $wpdb->insert(
                        $wpdb->prefix . 'amsawal_lesson_content',
                        [
                            'lesson_id' => $lesson_page_id,
                            'content_id' => $h5p_id,
                            'activity_type' => $activity_type,
                            'display_order' => array_search($activity_type, $lesson['activities']) + 1
                        ]
                    );
                    
                    $created_activities++;
                    echo "✅ creada (H5P ID: {$h5p_id})\n";
                } else {
                    echo "❌ error inserint\n";
                }
            } else {
                echo "❌ error generant\n";
            }
        }
        
        echo "\n";
    }
}

// ============================================
// PAS 5: CONFIGURAR GAMIFICACIÓ
// ============================================

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "🏆 PAS 5: Configurar sistema de gamificació\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Configurar punts per activitat
$points_config = [
    'flashcards' => 10,
    'multiple-choice' => 15,
    'fill-blanks' => 20,
    'dialog-cards' => 15
];

echo "Configuració de punts:\n";
foreach ($points_config as $activity => $points) {
    update_option("wp_amsawal_points_{$activity}", $points);
    echo "  • {$activity}: {$points} punts\n";
}

// Crear rangs (nivells)
$ranks = [
    1 => 'Principiant',
    2 => 'Apreneu',
    3 => 'Avançat',
    4 => 'Mèstre',
    5 => 'Expert'
];

echo "\nRangs del curs:\n";
foreach ($ranks as $rank_num => $rank_name) {
    echo "  • Nivell {$rank_num}: {$rank_name}\n";
}

update_option('wp_amsawal_ranks', $ranks);

echo "\n✅ Gamificació configurada\n\n";

// ============================================
// RESUM FINAL
// ============================================

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✨ GENERACIÓ COMPLETADA\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "📊 Estadístiques:\n";
echo "  • Mòduls creats: " . count($course_structure['modules']) . "\n";
echo "  • Leiçons creades: {$created_lessons}\n";
echo "  • Activitats H5P creades: {$created_activities}\n";
echo "  • Pàgines de navegació: " . count($nav_page_ids) . "\n";

echo "\n🔗 URL d'accès:\n";
echo "  • Portada: " . get_permalink($nav_page_ids['Inicio']) . "\n";
echo "  • Curs principal: " . get_permalink($course_page_id) . "\n";
echo "  • Cursos disponibles: " . get_permalink($nav_page_ids['Cursos disponibles']) . "\n";

echo "\n🎯 Pròxims passos:\n";
echo "  1. Accedir a WordPress Admin\n";
echo "  2. Verificar que les llibreries H5P estiguin installades\n";
echo "  3. Provar el Learning Path a la portada\n";
echo "  4. Configurar BuddyPress i GamiPress si cal\n";

echo "\n✅ Curs Tarifit Complet generat amb èxit!\n\n";

// ============================================
// FUNCIONS AUXILIARS
// ============================================

/**
 * Genera el contingut H5P per a una activitat
 */
function generate_h5p_activity($type, $vocabulary, $title) {
    $params = [];
    $library = '';
    
    switch ($type) {
        case 'flashcards':
            $library = 'H5P.Dialog Cards 1.1.2';
            $cards = [];
            
            foreach ($vocabulary as $word => $translation) {
                $cards[] = [
                    'text' => "<p>{$word}</p>",
                    'answer' => "<p>{$translation}</p>"
                ];
            }
            
            $params = [
                'title' => $title,
                'description' => "Flashcards de {$title}",
                'cards' => $cards,
                'retry' => true,
                'randomCards' => true,
                'maxCards' => count($cards),
                'l10n' => [
                    'retry' => 'Tornar a provar',
                    'correctText' => 'Correcte!',
                    'incorrectText' => 'Incorrecte'
                ]
            ];
            break;
            
        case 'multiple-choice':
            $library = 'H5P.Multi Choice 1.1.6';
            $questions = [];
            
            foreach ($vocabulary as $word => $translation) {
                // Crear pregunta de traducció
                $wrong_answers = array_diff(array_values($vocabulary), [$translation]);
                $wrong_answers = array_slice($wrong_answers, 0, 3);
                
                $options = array_merge([$translation], $wrong_answers);
                shuffle($options);
                
                $question_options = [];
                foreach ($options as $option) {
                    $question_options[] = [
                        'text' => $option,
                        'correct' => ($option === $translation)
                    ];
                }

                $questions[] = [
                    'task' => "<p>Què significa \"{$word}\"?</p>",
                    'answers' => $question_options
                ];
            }
            
            $params = [
                'title' => $title,
                'questions' => $questions,
                'randomQuestions' => true,
                'showSolutions' => true,
                'retry' => true
            ];
            break;
            
        case 'fill-blanks':
            $library = 'H5P.Fill in the Blanks 1.1.4';
            $sentences = [];
            
            foreach ($vocabulary as $word => $translation) {
                // Crear frase amb espai buit
                $sentence = "\"{$word}\" significa *{$translation}* en castellà";
                $sentences[] = $sentence;
            }
            
            $params = [
                'title' => $title,
                'questions' => [
                    [
                        'task' => implode("\n", $sentences),
                        'solutions' => array_values($vocabulary)
                    ]
                ],
                'showSolutions' => true,
                'retry' => true
            ];
            break;
            
        case 'dialog-cards':
            $library = 'H5P.Dialog Cards 1.1.2';
            $dialogs = [];
            
            foreach ($vocabulary as $word => $translation) {
                $dialogs[] = [
                    'text' => "<p>Què significa \"{$word}\"?</p>",
                    'answer' => "<p>Significa \"{$translation}\"</p>"
                ];
            }
            
            $params = [
                'title' => $title,
                'dialogs' => $dialogs,
                'randomCards' => true
            ];
            break;
    }
    
    if (empty($library) || empty($params)) {
        return null;
    }
    
    return [
        'library' => $library,
        'params' => $params
    ];
}
