# Amsawal — Reproducibility & Deployment

> Companion technical document to the manuscript *"An AI-Augmented Open
> Architecture for Gamified and Adaptive Learning Platforms"* (IEEE TLT).
> See also [`ARCHITECTURE-SPECIFICATION.md`](ARCHITECTURE-SPECIFICATION.md)
> for the event model, subsystem mapping, and AI subsystem catalog.

## 1. Verified environment

| Component | Version | Role |
|-----------|---------|------|
| WordPress | 7.0 | Core CMS, authentication, content management, hook infrastructure |
| PHP | 8.2 | Runtime |
| MySQL | 8.0 | Persistence (`wp_*` tables + custom `wp_amsawal_*` tables) |
| H5P | 1.17.8 | Interactive learning activities |
| GamiPress | 7.9.2 | Points, ranks, achievements |
| BuddyPress | 14.4.0 | Profiles, groups, messaging, friends |

The plugin itself is a single WordPress plugin (`wp-amsawal`) composed of
61 service files following the `wp-amsawal-{feature}.php` convention, each
implementing one subsystem (see Section 4).

## 2. Local deployment (Docker)

```bash
# 1. Configure the AI backend (copy and edit)
cp .env.example .env
#    -> set AI_KEY, AI_URL, AI_MODEL

# 2. Start the stack
docker compose up -d

# 3. WordPress admin: http://localhost:8080/wp-admin
```

The plugin is bind-mounted into the container at
`/var/www/html/wp-content/plugins/wp-amsawal`, so source changes are picked
up immediately.

### 2.1 AI backend configuration

The AI layer is backend-agnostic. Any OpenAI-compatible endpoint works; the
backend is auto-detected from the URL (`wp_amsawal_ai_detect_backend()`).
Supported endpoints include:

- **ModelScope** (free tier): `https://api-inference.modelscope.ai/v1/chat/completions`
- **OpenAI / Groq / OpenRouter / Pioneer**

Configuration precedence: `wp_amsawal_ai_endpoint` / `wp_amsawal_ai_model`
options, falling back to the `WP_AMSAWAL_AI_URL` / `WP_AMSAWAL_AI_MODEL`
constants defined in `wp-config.php` (set from `AI_URL` / `AI_MODEL` env
vars in `docker-compose.yml`).

## 3. Data model

The platform stores learner activity in a custom analytics database
alongside the standard WordPress and H5P tables. The central table is
`wp_amsawal_user_interactions`:

```
interaction_type   varchar(50)   -- event type (e.g. 'lesson_complete', 'xp_awarded')
interaction_subtype varchar(50)  -- optional category
action             varchar(50)   -- specific action
content_id         bigint        -- lesson post ID or H5P content ID
content_type       varchar(50)   -- 'lesson' | 'h5p'
result_data        longtext      -- JSON payload
score / max_score  decimal       -- numeric outcomes (where applicable)
duration           int           -- time-on-task (seconds)
metadata           json          -- structured metadata
timestamp          datetime      -- event time
```

Other custom tables: `wp_amsawal_friends`, `wp_amsawal_messages`,
`wp_amsawal_notifications`, `wp_amsawal_qualitative_analysis`,
`wp_amsawal_league_history`, `wp_amsawal_challenges`,
`wp_amsawal_challenge_participants`, `wp_amsawal_content_versions`,
`wp_amsawal_aggregated_metrics`.

## 4. Subsystem inventory

Each subsystem is an independent module that communicates through the shared
event mechanism (WordPress action hooks), direct native-hook subscriptions,
and AJAX endpoints. Key files:

| Layer | Files |
|-------|-------|
| H5P integration | `wp-amsawal-h5p.php`, `wp-amsawal-h5p-authoring.php` |
| Learning path / routing | `wp-amsawal-router.php`, `wp-amsawal-view.php`, `wp-amsawal-syllabus.php` |
| Gamification bridge | `wp-amsawal-gamipress-bridge.php`, `wp-amsawal-gamification.php` |
| Achievements | `wp-amsawal-achievements-system.php`, `wp-amsawal-achievements-ui.php` |
| Leagues / streaks / quests | `wp-amsawal-leagues.php`, `wp-amsawal-streaks.php`, `wp-amsawal-quests.php` |
| Analytics | `wp-amsawal-analytics.php`, `wp-amsawal-analytics-class.php`, `wp-amsawal-data-collection.php`, `wp-amsawal-quantitative-analysis.php`, `wp-amsawal-qualitative-analysis.php` |
| Social | `wp-amsawal-buddypress.php`, `wp-amsawal-friends.php`, `wp-amsawal-messaging.php` |
| Real-time | `wp-amsawal-websocket.php`, `wp-amsawal-websocket-server.php` |
| AI (7 subsystems) | `wp-amsawal-ai.php`, `wp-amsawal-ai-tutor.php`, `wp-amsawal-ai-schemas.php`, `wp-amsawal-essay-evaluation.php`, `wp-amsawal-modelscope-images.php`, `wp-amsawal-translate.php`, `wp-amsawal-adaptive.php` |
| Review (SM-2) | `wp-amsawal-review.php`, `wp-amsawal-adaptive.php` |

## 5. Event model

The coordination model is documented in full in
[`ARCHITECTURE-SPECIFICATION.md`](ARCHITECTURE-SPECIFICATION.md#2-event-catalog)
(21 application events with payloads, producers, and consumers). The event
mechanism is WordPress action hooks (`do_action()` / `add_action()`), playing
the role of a lightweight event bus — deliberately no message-broker
infrastructure (no durable queues, delivery guarantees, or ordering
semantics).

## 6. License

[GNU GPL v3.0](../LICENSE)

## 7. Repository

Source code: <https://github.com/enacimie/wp-amsawal>

