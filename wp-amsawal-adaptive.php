<?php
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
            "SELECT interaction_type, result_data, created_at 
             FROM $table 
             WHERE user_id = %d AND CAST(JSON_EXTRACT(result_data, '$.content_id') AS UNSIGNED) = %d 
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
            $result_data = json_decode($attempt->result_data, true);
            
            if (isset($result_data['score'])) {
                $score = floatval($result_data['score']);
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
            $result_data = json_decode($attempt->result_data, true);
            
            if (isset($result_data['area']) && isset($result_data['score'])) {
                $area = $result_data['area'];
                $score = floatval($result_data['score']);
                
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
