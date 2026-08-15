# WP Amsawal API Documentation

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
    "content": "[h5p id="123"]",
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
