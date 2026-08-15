#!/usr/bin/env python3
"""Fase 16: Advanced AI Features - Evaluación de ensayos y reconocimiento de voz"""

def apply_f16_1_essay_evaluation():
    """F16-1: AI essay evaluation system"""
    essay_code = """<?php
/**
 * F16-1: AI Essay Evaluation System
 * Uses Ollama/Qwen3 to evaluate student essays
 */

if (!defined('ABSPATH')) exit;

function amsawal_evaluate_essay($essay_text, $lesson_id, $user_id) {
    // Validate input
    if (empty($essay_text) || strlen($essay_text) < 50) {
        return new WP_Error('too_short', 'El ensayo es demasiado corto (mínimo 50 caracteres)');
    }
    
    // Get lesson context
    $lesson = get_post($lesson_id);
    if (!$lesson) {
        return new WP_Error('invalid_lesson', 'Lección no válida');
    }
    
    // Prepare prompt for AI evaluation
    $prompt = sprintf(
        "Evalúa el siguiente ensayo de un estudiante de Tamazight (Tarifit).
        
        Tema de la lección: %s
        
        Ensayo del estudiante:
        %s
        
        Proporciona:
        1. Puntuación general (0-100)
        2. Gramática y ortografía (0-100)
        3. Vocabulario (0-100)
        4. Coherencia y estructura (0-100)
        5. Comentarios constructivos
        6. Sugerencias de mejora
        
        Responde en formato JSON:
        {
            \"overall_score\": 0-100,
            \"grammar_score\": 0-100,
            \"vocabulary_score\": 0-100,
            \"structure_score\": 0-100,
            \"feedback\": \"comentarios en español\",
            \"suggestions\": [\"sugerencia 1\", \"sugerencia 2\"]
        }",
        $lesson->post_title,
        $essay_text
    );
    
    // Call AI API
    $response = amsawal_call_ollama($prompt);
    
    if (is_wp_error($response)) {
        return $response;
    }
    
    // Parse JSON response
    $evaluation = json_decode($response, true);
    
    if (!$evaluation) {
        return new WP_Error('parse_error', 'Error al procesar la evaluación');
    }
    
    // Save evaluation to database
    global $wpdb;
    $table = $wpdb->prefix . 'amsawal_qualitative_analysis';
    
    $wpdb->insert($table, [
        'user_id' => $user_id,
        'analysis_type' => 'essay_evaluation',
        'content' => $essay_text,
        'ai_response' => json_encode($evaluation),
        'created_at' => current_time('mysql')
    ]);
    
    // Award XP based on score
    $xp_earned = intval($evaluation['overall_score'] / 10);
    amsawal_award_xp($user_id, $xp_earned, 'essay_evaluation');
    
    return $evaluation;
}

// AJAX handler
add_action('wp_ajax_amsawal_evaluate_essay', function() {
    check_ajax_referer('amsawal_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Debes iniciar sesión');
    }
    
    $essay = sanitize_textarea_field($_POST['essay'] ?? '');
    $lesson_id = absint($_POST['lesson_id'] ?? 0);
    $user_id = get_current_user_id();
    
    $evaluation = amsawal_evaluate_essay($essay, $lesson_id, $user_id);
    
    if (is_wp_error($evaluation)) {
        wp_send_json_error($evaluation->get_error_message());
    }
    
    wp_send_json_success($evaluation);
});
"""
    
    with open('wp-amsawal-essay-evaluation.php', 'w', encoding='utf-8') as f:
        f.write(essay_code)
    print("✅ F16-1: AI essay evaluation system created")
    return True

def apply_f16_2_speech_recognition():
    """F16-2: Speech recognition for pronunciation"""
    speech_code = """<?php
/**
 * F16-2: Speech Recognition for Pronunciation Practice
 * Uses Web Speech API for client-side recognition
 */

// Enqueue speech recognition script
add_action('wp_enqueue_scripts', function() {
    wp_add_inline_script('amsawal-pure-js', "
        // F16-2: Speech Recognition System
        const DuoSpeech = {
            recognition: null,
            isListening: false,
            lang: 'es-ES', // Default to Spanish, can be changed to Tamazight
            
            init() {
                if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
                    console.warn('Speech recognition not supported');
                    return false;
                }
                
                const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                this.recognition = new SpeechRecognition();
                this.recognition.continuous = false;
                this.recognition.interimResults = false;
                this.recognition.lang = this.lang;
                
                this.recognition.onstart = () => {
                    this.isListening = true;
                    document.dispatchEvent(new CustomEvent('duo-speech-start'));
                };
                
                this.recognition.onresult = (event) => {
                    const transcript = event.results[0][0].transcript;
                    document.dispatchEvent(new CustomEvent('duo-speech-result', {
                        detail: { transcript }
                    }));
                };
                
                this.recognition.onerror = (event) => {
                    console.error('Speech recognition error:', event.error);
                    document.dispatchEvent(new CustomEvent('duo-speech-error', {
                        detail: { error: event.error }
                    }));
                };
                
                this.recognition.onend = () => {
                    this.isListening = false;
                    document.dispatchEvent(new CustomEvent('duo-speech-end'));
                };
                
                return true;
            },
            
            start() {
                if (this.recognition && !this.isListening) {
                    this.recognition.start();
                }
            },
            
            stop() {
                if (this.recognition && this.isListening) {
                    this.recognition.stop();
                }
            },
            
            setLanguage(lang) {
                this.lang = lang;
                if (this.recognition) {
                    this.recognition.lang = lang;
                }
            }
        };
        
        // Initialize on load
        if (typeof DuoSpeech !== 'undefined') {
            DuoSpeech.init();
        }
    ");
});

// AJAX handler for pronunciation evaluation
add_action('wp_ajax_amsawal_evaluate_pronunciation', function() {
    check_ajax_referer('amsawal_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Debes iniciar sesión');
    }
    
    $spoken_text = sanitize_text_field($_POST['spoken_text'] ?? '');
    $target_text = sanitize_text_field($_POST['target_text'] ?? '');
    $lesson_id = absint($_POST['lesson_id'] ?? 0);
    
    if (empty($spoken_text) || empty($target_text)) {
        wp_send_json_error('Datos incompletos');
    }
    
    // Calculate similarity (simple Levenshtein distance)
    $similarity = similar_text($spoken_text, $target_text, $percent);
    
    // Evaluate pronunciation
    $evaluation = [
        'accuracy' => round($percent, 2),
        'spoken_text' => $spoken_text,
        'target_text' => $target_text,
        'feedback' => $percent >= 80 ? '¡Excelente pronunciación!' : 
                     ($percent >= 60 ? 'Buena pronunciación, sigue practicando.' : 'Necesitas más práctica.'),
        'score' => $percent >= 80 ? 100 : ($percent >= 60 ? 70 : 40)
    ];
    
    // Award XP based on accuracy
    if ($percent >= 60) {
        $xp = intval($percent / 10);
        amsawal_award_xp(get_current_user_id(), $xp, 'pronunciation_practice');
    }
    
    wp_send_json_success($evaluation);
});
"""
    
    with open('wp-amsawal-speech.php', 'w', encoding='utf-8') as f:
        f.write(speech_code)
    print("✅ F16-2: Speech recognition system created")
    return True

def apply_f16_3_adaptive_learning():
    """F16-3: Adaptive learning algorithm"""
    adaptive_code = """<?php
/**
 * F16-3: Adaptive Learning Algorithm
 * Adjusts difficulty based on user performance
 */

if (!defined('ABSPATH')) exit;

class Amsawal_Adaptive_Learning {
    private $user_id;
    private $lesson_id;
    
    public function __construct($user_id, $lesson_id) {
        $this->user_id = $user_id;
        $this->lesson_id = $lesson_id;
    }
    
    /**
     * Get user's performance history
     */
    public function get_performance_history() {
        global $wpdb;
        $table = $wpdb->prefix . 'amsawal_user_interactions';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT event_type, metadata, created_at 
             FROM $table 
             WHERE user_id = %d AND lesson_id = %d 
             ORDER BY created_at DESC 
             LIMIT 50",
            $this->user_id,
            $this->lesson_id
        ));
    }
    
    /**
     * Calculate difficulty level (0.0 - 1.0)
     */
    public function calculate_difficulty() {
        $history = $this->get_performance_history();
        
        if (empty($history)) {
            return 0.5; // Default medium difficulty
        }
        
        $total_attempts = count($history);
        $correct_answers = 0;
        $recent_performance = [];
        
        foreach ($history as $attempt) {
            $metadata = json_decode($attempt->metadata, true);
            
            if (isset($metadata['score'])) {
                $score = floatval($metadata['score']);
                $recent_performance[] = $score;
                
                if ($score >= 70) {
                    $correct_answers++;
                }
            }
        }
        
        // Calculate success rate
        $success_rate = $correct_answers / $total_attempts;
        
        // Calculate recent trend (last 5 attempts)
        $recent_trend = 0;
        if (count($recent_performance) >= 5) {
            $last_5 = array_slice($recent_performance, 0, 5);
            $recent_trend = array_sum($last_5) / count($last_5);
        }
        
        // Adjust difficulty based on performance
        $difficulty = 0.5;
        
        if ($success_rate >= 0.8 && $recent_trend >= 75) {
            // User is doing well, increase difficulty
            $difficulty = min(1.0, 0.5 + ($success_rate - 0.8) * 2);
        } elseif ($success_rate <= 0.4 || $recent_trend <= 50) {
            // User is struggling, decrease difficulty
            $difficulty = max(0.2, 0.5 - (0.4 - $success_rate) * 2);
        }
        
        return round($difficulty, 2);
    }
    
    /**
     * Get recommended next activity
     */
    public function get_recommended_activity() {
        $difficulty = $this->calculate_difficulty();
        $history = $this->get_performance_history();
        
        // Analyze weak areas
        $weak_areas = $this->identify_weak_areas($history);
        
        return [
            'difficulty' => $difficulty,
            'weak_areas' => $weak_areas,
            'recommended_type' => $this->recommend_activity_type($weak_areas),
            'estimated_time' => $this->estimate_time($difficulty)
        ];
    }
    
    /**
     * Identify areas where user struggles
     */
    private function identify_weak_areas($history) {
        $areas = [];
        
        foreach ($history as $attempt) {
            $metadata = json_decode($attempt->metadata, true);
            
            if (isset($metadata['area']) && isset($metadata['score'])) {
                $area = $metadata['area'];
                $score = floatval($metadata['score']);
                
                if (!isset($areas[$area])) {
                    $areas[$area] = ['total' => 0, 'correct' => 0];
                }
                
                $areas[$area]['total']++;
                if ($score >= 70) {
                    $areas[$area]['correct']++;
                }
            }
        }
        
        // Calculate success rate per area
        $weak_areas = [];
        foreach ($areas as $area => $stats) {
            $rate = $stats['correct'] / $stats['total'];
            if ($rate < 0.6) {
                $weak_areas[] = $area;
            }
        }
        
        return $weak_areas;
    }
    
    /**
     * Recommend activity type based on weak areas
     */
    private function recommend_activity_type($weak_areas) {
        if (empty($weak_areas)) {
            return 'challenge'; // User is doing well, give challenges
        }
        
        // Recommend practice for weak areas
        return 'practice';
    }
    
    /**
     * Estimate time needed for next activity
     */
    private function estimate_time($difficulty) {
        // Higher difficulty = more time
        return intval(5 + ($difficulty * 10)); // 5-15 minutes
    }
}

// AJAX handler
add_action('wp_ajax_amsawal_get_adaptive_recommendation', function() {
    check_ajax_referer('amsawal_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Debes iniciar sesión');
    }
    
    $lesson_id = absint($_POST['lesson_id'] ?? 0);
    $user_id = get_current_user_id();
    
    $adaptive = new Amsawal_Adaptive_Learning($user_id, $lesson_id);
    $recommendation = $adaptive->get_recommended_activity();
    
    wp_send_json_success($recommendation);
});
"""
    
    with open('wp-amsawal-adaptive.php', 'w', encoding='utf-8') as f:
        f.write(adaptive_code)
    print("✅ F16-3: Adaptive learning algorithm created")
    return True

def apply_f16_4_ai_content_generation():
    """F16-4: AI-powered content generation"""
    content_gen_code = """<?php
/**
 * F16-4: AI-Powered Content Generation
 * Generate exercises, quizzes, and lessons using Ollama/Qwen3
 */

if (!defined('ABSPATH')) exit;

function amsawal_generate_exercise($lesson_id, $exercise_type, $difficulty = 0.5) {
    $lesson = get_post($lesson_id);
    if (!$lesson) {
        return new WP_Error('invalid_lesson', 'Lección no válida');
    }
    
    $prompts = [
        'flashcards' => sprintf(
            "Genera 5 tarjetas de vocabulario para la lección: %s
        
        Tema: %s
        
        Formato JSON:
        [
            {\"front\": \"palabra en Tamazight\", \"back\": \"traducción en español\", \"example\": \"ejemplo de uso\"}
        ]",
            $lesson->post_title,
            $lesson->post_content
        ),
        'mcq' => sprintf(
            "Genera 5 preguntas de opción múltiple para: %s
        
        Tema: %s
        Dificultad: %s
        
        Formato JSON:
        [
            {
                \"question\": \"pregunta\",
                \"options\": [\"opción 1\", \"opción 2\", \"opción 3\", \"opción 4\"],
                \"correct\": 0,
                \"explanation\": \"explicación\"
            }
        ]",
            $lesson->post_title,
            $lesson->post_content,
            $difficulty < 0.4 ? 'fácil' : ($difficulty > 0.7 ? 'difícil' : 'media')
        ),
        'fill_blanks' => sprintf(
            "Genera 5 ejercicios de completar espacios para: %s
        
        Tema: %s
        
        Formato JSON:
        [
            {\"sentence\": \"oración con ___ espacio ___\", \"answer\": \"respuesta\", \"hint\": \"pista\"}
        ]",
            $lesson->post_title,
            $lesson->post_content
        ),
        'dictation' => sprintf(
            "Genera 5 ejercicios de dictado para: %s
        
        Tema: %s
        
        Formato JSON:
        [
            {\"text\": \"texto en Tamazight\", \"translation\": \"traducción\", \"audio_hint\": \"pronunciación aproximada\"}
        ]",
            $lesson->post_title,
            $lesson->post_content
        )
    ];
    
    $prompt = $prompts[$exercise_type] ?? $prompts['mcq'];
    
    // Call AI
    $response = amsawal_call_ollama($prompt);
    
    if (is_wp_error($response)) {
        return $response;
    }
    
    $exercises = json_decode($response, true);
    
    if (!$exercises || !is_array($exercises)) {
        return new WP_Error('parse_error', 'Error al generar ejercicios');
    }
    
    return $exercises;
}

// AJAX handler
add_action('wp_ajax_amsawal_generate_exercise', function() {
    check_ajax_referer('amsawal_nonce', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Acceso denegado');
    }
    
    $lesson_id = absint($_POST['lesson_id'] ?? 0);
    $exercise_type = sanitize_text_field($_POST['type'] ?? 'mcq');
    $difficulty = floatval($_POST['difficulty'] ?? 0.5);
    
    $exercises = amsawal_generate_exercise($lesson_id, $exercise_type, $difficulty);
    
    if (is_wp_error($exercises)) {
        wp_send_json_error($exercises->get_error_message());
    }
    
    wp_send_json_success($exercises);
});
"""
    
    with open('wp-amsawal-content-gen.php', 'w', encoding='utf-8') as f:
        f.write(content_gen_code)
    print("✅ F16-4: AI content generation system created")
    return True

def apply_f16_5_progress_prediction():
    """F16-5: Learning progress prediction"""
    prediction_code = """<?php
/**
 * F16-5: Learning Progress Prediction
 * Predicts when user will complete course based on current pace
 */

if (!defined('ABSPATH')) exit;

function amsawal_predict_completion($user_id, $course_id) {
    global $wpdb;
    
    // Get course structure
    $lessons = get_posts([
        'post_type' => 'page',
        'post_parent' => $course_id,
        'numberposts' => -1,
        'fields' => 'ids'
    ]);
    
    $total_lessons = count($lessons);
    
    if ($total_lessons === 0) {
        return new WP_Error('no_lessons', 'Curso sin lecciones');
    }
    
    // Get user's completed lessons
    $completed = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT lesson_id) 
         FROM {$wpdb->prefix}amsawal_user_interactions 
         WHERE user_id = %d AND event_type = 'lesson_complete' AND lesson_id IN (" . implode(',', array_map('absint', $lessons)) . ")",
        $user_id
    ));
    
    // Get user's activity history
    $activity = $wpdb->get_results($wpdb->prepare(
        "SELECT DATE(created_at) as date, COUNT(*) as count 
         FROM {$wpdb->prefix}amsawal_user_interactions 
         WHERE user_id = %d AND created_at >= DATE_SUB(%s, INTERVAL 30 DAY)
         GROUP BY DATE(created_at)
         ORDER BY date DESC",
        $user_id,
        current_time('Y-m-d')
    ));
    
    // Calculate average daily progress
    $total_days_active = count($activity);
    $total_lessons_completed = $completed;
    
    if ($total_days_active === 0) {
        return [
            'completion_percentage' => 0,
            'estimated_days_remaining' => null,
            'estimated_completion_date' => null,
            'daily_pace' => 0,
            'recommendation' => '¡Comienza tu primera lección hoy!'
        ];
    }
    
    $daily_pace = $total_lessons_completed / $total_days_active;
    $remaining_lessons = $total_lessons - $completed;
    $estimated_days = $daily_pace > 0 ? ceil($remaining_lessons / $daily_pace) : null;
    
    $completion_date = null;
    if ($estimated_days) {
        $completion_date = date('Y-m-d', strtotime("+{$estimated_days} days"));
    }
    
    // Generate recommendation
    $recommendation = '';
    if ($daily_pace < 1) {
        $recommendation = 'Intenta completar al menos 1 lección diaria para mantener el ritmo.';
    } elseif ($daily_pace < 2) {
        $recommendation = '¡Buen ritmo! Sigue así para completar el curso pronto.';
    } else {
        $recommendation = '¡Excelente progreso! Vas muy bien encaminado.';
    }
    
    return [
        'completion_percentage' => round(($completed / $total_lessons) * 100, 2),
        'completed_lessons' => $completed,
        'total_lessons' => $total_lessons,
        'estimated_days_remaining' => $estimated_days,
        'estimated_completion_date' => $completion_date,
        'daily_pace' => round($daily_pace, 2),
        'recommendation' => $recommendation
    ];
}

// AJAX handler
add_action('wp_ajax_amsawal_get_progress_prediction', function() {
    check_ajax_referer('amsawal_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Debes iniciar sesión');
    }
    
    $course_id = absint($_POST['course_id'] ?? 0);
    $user_id = get_current_user_id();
    
    $prediction = amsawal_predict_completion($user_id, $course_id);
    
    if (is_wp_error($prediction)) {
        wp_send_json_error($prediction->get_error_message());
    }
    
    wp_send_json_success($prediction);
});
"""
    
    with open('wp-amsawal-prediction.php', 'w', encoding='utf-8') as f:
        f.write(prediction_code)
    print("✅ F16-5: Learning progress prediction created")
    return True

# Ejecutar todas las mejoras de IA avanzada
if __name__ == '__main__':
    print(" Aplicando mejoras Fase 16 - Advanced AI Features...\n")
    
    apply_f16_1_essay_evaluation()
    apply_f16_2_speech_recognition()
    apply_f16_3_adaptive_learning()
    apply_f16_4_ai_content_generation()
    apply_f16_5_progress_prediction()
    
    print("\n✨ Mejoras de IA avanzada completadas")
