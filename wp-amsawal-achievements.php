<?php
/**
 * F20-3: Advanced Gamification
 * Badges, achievements, and rewards
 */

if (!defined('ABSPATH')) exit;

// Achievement definitions
function amsawal_get_achievements() {
    return [
        'first_lesson' => [
            'name' => 'Primera Lección',
            'description' => 'Completa tu primera lección',
            'icon' => '🎓',
            'xp' => 50
        ],
        'streak_7' => [
            'name' => 'Racha de 7 días',
            'description' => 'Practica 7 días seguidos',
            'icon' => '⭐',
            'xp' => 100
        ],
        'streak_30' => [
            'name' => 'Racha de 30 días',
            'description' => 'Practica 30 días seguidos',
            'icon' => '⚡',
            'xp' => 500
        ],
        'perfect_quiz' => [
            'name' => 'Quiz Perfecto',
            'description' => 'Obtén 100% en un quiz',
            'icon' => '💯',
            'xp' => 75
        ],
        'speed_learner' => [
            'name' => 'Aprendiz Rápido',
            'description' => 'Completa 5 lecciones en un día',
            'icon' => '⚡',
            'xp' => 150
        ],
        'social_butterfly' => [
            'name' => 'Mariposa Social',
            'description' => 'Añade 10 amigos',
            'icon' => '🦋',
            'xp' => 200
        ],
        'challenge_master' => [
            'name' => 'Maestro de Desafíos',
            'description' => 'Completa 10 desafíos',
            'icon' => '🏆',
            'xp' => 300
        ],
        'content_creator' => [
            'name' => 'Creador de Contenido',
            'description' => 'Crea tu primer ejercicio H5P',
            'icon' => '✏️',
            'xp' => 100
        ]
    ];
}

// Unlock achievement
function amsawal_unlock_achievement($user_id, $achievement_id) {
    $achievements = amsawal_get_achievements();
    
    if (!isset($achievements[$achievement_id])) {
        return new WP_Error('invalid_achievement', 'Logro no válido');
    }
    
    $unlocked = get_user_meta($user_id, 'amsawal_achievements', true) ?: [];
    
    if (in_array($achievement_id, $unlocked)) {
        return false; // Already unlocked
    }
    
    $unlocked[] = $achievement_id;
    update_user_meta($user_id, 'amsawal_achievements', $unlocked);
    
    // Award XP
    $xp = $achievements[$achievement_id]['xp'];
    amsawal_award_xp($user_id, $xp, 'achievement_' . $achievement_id);
    
    // Send notification
    amsawal_send_notification($user_id, 'achievement_unlocked', [
        'achievement_id' => $achievement_id,
        'achievement_name' => $achievements[$achievement_id]['name'],
        'xp_earned' => $xp
    ]);
    
    return true;
}

// Check and unlock achievements based on activity
function amsawal_check_achievements($user_id, $event_type, $metadata = []) {
    $unlocked = get_user_meta($user_id, 'amsawal_achievements', true) ?: [];
    
    // First lesson
    if ($event_type === 'lesson_complete' && !in_array('first_lesson', $unlocked)) {
        $total_lessons = intval(get_user_meta($user_id, 'amsawal_lessons_completed', true));
        if ($total_lessons >= 1) {
            amsawal_unlock_achievement($user_id, 'first_lesson');
        }
    }
    
    // Perfect quiz: check both quiz_complete and lesson_complete with 100% score
    if (($event_type === 'quiz_complete' || $event_type === 'lesson_complete') && !in_array('perfect_quiz', $unlocked)) {
        if (isset($metadata['score']) && $metadata['score'] >= 100) {
            amsawal_unlock_achievement($user_id, 'perfect_quiz');
        }
    }
    
    // Speed learner
    if ($event_type === 'lesson_complete' && !in_array('speed_learner', $unlocked)) {
        $today_lessons = intval(get_user_meta($user_id, 'amsawal_lessons_today', true));
        if ($today_lessons >= 5) {
            amsawal_unlock_achievement($user_id, 'speed_learner');
        }
    }
}

add_action('amsawal_activity_tracked', 'amsawal_check_achievements', 10, 3);
