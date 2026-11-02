# Phase 1 — Clients, Library, Activities and the First Two Games

**Goal:** a clinician can add a client (by reference only), build an activity from a Malay or English word library, play it with the child in session, and see the child's accuracy over time.

## Scope

- Clients with privacy protection
- Word library (starter words + clinician's own words and pictures)
- Activity builder
- Two games: **Sound card drill** and **Minimal pairs**
- Play screen with answer recording and end-of-session summary
- Progress chart on the client page

Out of scope: goals, clinical hours link, PDF reports (Phase 2); other games, rewards, offline (Phase 3).

## 1. Privacy protection

- **Warning banner** on every client page and form:
    > Do not enter real patient details. Use a reference only (e.g. RN no. or your own code like C-01). No names, IC numbers, phone numbers, addresses or photos of patients.
- **Confirmation checkbox** on client creation (`accepted` validation):
    > I confirm this contains no information that identifies the patient.
- **`NoPersonalData` validation rule** applied to `reference_code`, `diagnosis` and `notes`. It rejects:
    - IC numbers: `\d{6}-?\d{2}-?\d{4}`
    - Malaysian phone numbers: `(\+?60|0)1\d-?\d{7,8}`
    - Email addresses
- **Upload warning** on picture uploads: _"Do not upload photos of patients."_
- The play screen never shows the client reference.
- Deleting a client cascades to assignments, play sessions and trials.

## 2. Data model

### Enums (`app/Enums/Therapy/`)

| Enum           | Cases                                                          |
| -------------- | -------------------------------------------------------------- |
| `GameType`     | `SoundDrill` (`sound_drill`), `MinimalPairs` (`minimal_pairs`) |
| `Language`     | `Malay` (`ms`), `English` (`en`)                               |
| `WordPosition` | `Initial`, `Medial`, `Final`                                   |
| `TrialResult`  | `Correct`, `Prompted`, `Incorrect`                             |

All use the existing `HasEnumOptions` concern. Age group reuses `App\Enums\ClinicalHours\AgeGroup`.

### Tables

**`clients`**

| Column         | Type                | Notes                                  |
| -------------- | ------------------- | -------------------------------------- |
| id             | id                  |                                        |
| user_id        | foreignId           | cascade on delete                      |
| reference_code | string(30)          | unique per `user_id`                   |
| age_group      | string              | `AgeGroup` enum                        |
| language       | string              | `Language` enum                        |
| diagnosis      | string, nullable    | general term only                      |
| notes          | text, nullable      |                                        |
| archived_at    | timestamp, nullable | hide from active list without deleting |
| timestamps     |                     |                                        |

**`content_items`**

| Column        | Type                 | Notes                                |
| ------------- | -------------------- | ------------------------------------ |
| id            | id                   |                                      |
| user_id       | foreignId, nullable  | `null` = starter library (read-only) |
| language      | string               | `Language`                           |
| word          | string(100)          |                                      |
| emoji         | string(16), nullable | used when there is no image          |
| image_path    | string, nullable     | uploaded picture (public disk)       |
| target_sound  | string(10), nullable | e.g. `s`, `k`, `ng`                  |
| word_position | string, nullable     | `WordPosition`                       |
| category      | string(50), nullable | e.g. `animals`, `food`               |
| timestamps    |                      |                                      |

**`activities`**

| Column     | Type      | Notes                                      |
| ---------- | --------- | ------------------------------------------ |
| id         | id        |                                            |
| user_id    | foreignId | cascade                                    |
| game_type  | string    | `GameType`                                 |
| title      | string    |                                            |
| language   | string    | `Language`                                 |
| settings   | json      | `{ trial_count, shuffle, show_word_text }` |
| timestamps |           |                                            |

**`activity_items`**

| Column          | Type                 | Notes                        |
| --------------- | -------------------- | ---------------------------- |
| id              | id                   |                              |
| activity_id     | foreignId            | cascade                      |
| content_item_id | foreignId            | restrict delete while in use |
| paired_item_id  | foreignId, nullable  | minimal pairs only           |
| position        | unsignedSmallInteger | order in the activity        |

**`assignments`** — activities on a client's therapy plan

| Column      | Type           | Notes                               |
| ----------- | -------------- | ----------------------------------- |
| id          | id             |                                     |
| client_id   | foreignId      | cascade                             |
| activity_id | foreignId      | cascade                             |
| is_active   | boolean        | default true                        |
| notes       | text, nullable |                                     |
| timestamps  |                | unique (`client_id`, `activity_id`) |

**`play_sessions`**

| Column      | Type                | Notes                      |
| ----------- | ------------------- | -------------------------- |
| id          | id                  |                            |
| client_id   | foreignId           | cascade                    |
| activity_id | foreignId           | cascade                    |
| started_at  | timestamp           |                            |
| ended_at    | timestamp, nullable | `null` = still in progress |
| notes       | text, nullable      |                            |
| timestamps  |                     |                            |

**`trials`**

| Column          | Type                      | Notes                                       |
| --------------- | ------------------------- | ------------------------------------------- |
| id              | id                        |                                             |
| play_session_id | foreignId                 | cascade                                     |
| content_item_id | foreignId                 | the target word                             |
| result          | string                    | `TrialResult`                               |
| response_ms     | unsignedInteger, nullable | time from showing to answer                 |
| client_uuid     | uuid                      | unique — makes retries safe (no duplicates) |
| created_at      | timestamp                 |                                             |

## 3. Starter word library

- Seeder `TherapyContentSeeder` with Malay and English words, tagged by target sound, word position and category.
- Pictures use **emoji** (no licensing, works offline, child-friendly), e.g. 🐍 _ular_, 🍎 _epal_, 🔑 _kunci_, 🐱 _kucing_, ☀️ _sun_.
- Cover the most common targets first: `s`, `k`, `g`, `t`, `d`, `l`, `r`, `ng`, `sy` in initial and final positions.
- Starter minimal pairs for common contrasts: `k/t`, `g/d`, `s/t`, `r/l`.
- Clinicians can add their own words and upload pictures (jpg/png/webp, max 2 MB, resized to 512 px).

## 4. Games

### Sound card drill (articulation)

- Child area: one large picture (and the word, if `show_word_text` is on).
- Clinician strip: **✓ Correct**, **🤝 Prompted**, **✗ Incorrect** — keyboard `1`, `2`, `3`.
- Counter, e.g. `7 / 20`. Next card appears after scoring.
- Clinician-scored, so no speech recognition is needed.

### Minimal pairs (phonology)

- Child area: two large pictures side by side (random left/right).
- Clinician strip shows _"Say: kunci"_ — only the clinician sees it.
- The clinician says the word; the child taps a picture; the app scores it automatically.
- Clinician can override the last answer (e.g. the child tapped by accident).
- No text-to-speech needed, because the clinician says the words.

### Shared play behaviour

- Full-screen, tablet-friendly, large touch targets.
- Small celebration animation every few answers.
- Each answer is sent immediately in the background (`useHttp`). Failed sends are queued in `localStorage` and retried; `client_uuid` prevents duplicates.
- **End session** button → summary: accuracy, result per word, notes field, _Back to client_.

## 5. Pages

| Page                                   | Purpose                                                                                                                      |
| -------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------- |
| `clients/Index`                        | Active clients, archived toggle, privacy banner                                                                              |
| `clients/Create`, `clients/Edit`       | Reference, age group, language, diagnosis, notes; privacy banner + checkbox                                                  |
| `clients/Show`                         | Assigned activities with **▶ Start**, recent sessions, progress chart                                                        |
| `activities/Index`                     | Clinician's activities, grouped by game type                                                                                 |
| `activities/Create`, `activities/Edit` | Pick game type → title, language, settings → pick words (filter by sound, position, category); build pairs for minimal pairs |
| `library/Index`                        | Starter + own words; add, edit, upload picture                                                                               |
| `play/Show`                            | The game screen                                                                                                              |
| `play/Summary`                         | End-of-session summary                                                                                                       |

Sidebar: add **Clients** and **Activities** (library reachable from Activities).

## 6. Routes

```
Route::resource('clients', ClientController::class);
Route::resource('activities', ActivityController::class)->except('show');
Route::resource('content-items', ContentItemController::class)->only(['index', 'store', 'update', 'destroy']);

Route::post('clients/{client}/assignments', [AssignmentController::class, 'store']);
Route::delete('assignments/{assignment}', [AssignmentController::class, 'destroy']);

Route::post('play-sessions', [PlaySessionController::class, 'store']);            // start → redirect to play
Route::get('play-sessions/{playSession}', [PlaySessionController::class, 'show']); // play screen
Route::post('play-sessions/{playSession}/trials', [TrialController::class, 'store']);
Route::patch('play-sessions/{playSession}/finish', [PlaySessionController::class, 'finish']);
Route::get('play-sessions/{playSession}/summary', [PlaySessionController::class, 'summary']);
```

All inside the existing `auth` + `verified` group. Frontend uses Wayfinder imports from `@/actions/...`.

## 7. Backend pieces

- **Policies:** `ClientPolicy`, `ActivityPolicy`, `ContentItemPolicy` (starter items are view-only), `PlaySessionPolicy` — owner only.
- **Form requests:** `ClientRequest` (with `NoPersonalData` + confirmation), `ActivityRequest` (items must be visible to the user and match the activity language; minimal pairs require `paired_item_id`), `TrialRequest`.
- **Rule:** `App\Rules\NoPersonalData`.
- **Service:** `App\Services\Therapy\ClientProgress`
    - Per activity: accuracy per finished session (`correct / total`, prompted shown separately).
    - Breakdown by target sound × word position.
- **Factories** for every model, with states (e.g. `ActivityFactory::minimalPairs()`, `TrialFactory::correct()`).

## 8. Progress chart

- Line chart: accuracy % per session for each assigned activity.
- Table: target sound × position with accuracy and number of tries.
- Drawn with a small hand-written SVG component (no new dependency). A chart library can be added later with approval.

## 9. Tests (Pest)

- Clinicians cannot view or change another clinician's clients, activities, sessions or words.
- `NoPersonalData` rejects IC numbers, phone numbers and emails; accepts reference codes like `C-01` and RN numbers.
- Creating a client without the confirmation checkbox fails.
- Activity validation: language mismatch and missing pairs are rejected.
- Recording a trial; posting the same `client_uuid` twice stores one trial.
- Trials cannot be added to a finished session.
- `ClientProgress` accuracy and sound/position breakdown.
- Starter library words cannot be edited or deleted.

## 10. Build order

Each step is reviewable and committable on its own.

1. **Clients + privacy** — enums, migration, model, factory, policy, `NoPersonalData`, pages, sidebar, tests
2. **Word library** — `content_items`, starter seeder, upload, library page, tests
3. **Activities** — `activities` + `activity_items`, builder pages, assignments on client page, tests
4. **Play** — `play_sessions` + `trials`, game registry, both games, retry queue, summary, tests
5. **Progress** — `ClientProgress` service, SVG chart, sound/position table, tests

## Open question

- Is **RN no.** acceptable as a client reference, or should clients use clinician-made codes only (`C-01`, `C-02` …)?

## Done when

- A clinician can add a client, build both kinds of activity in Malay or English, play a full session with a child, and see accuracy per session on the client page.
- No screen or field allows identifying patient data without a warning, and obvious personal data is rejected.
- All new tests pass; Pint, `vue-tsc` and `vp check` are clean.
