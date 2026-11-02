# Phase 2 — Goals, Clinical Hours Link, Reports and Two More Games

**Goal:** turn raw accuracy into **therapy goals** that are marked achieved automatically, connect game sessions to the existing **Clinical Hours** log, produce a **progress report PDF**, and add the **Find it!** and **Story sequence** games.

**Depends on:** Phase 1 complete.

## 1. Therapy goals

A goal describes a target the child should reach, e.g. _"/s/ at the start of words, Malay, 80% correct across 3 sessions in a row"_.

### Table `therapy_goals`

| Column                 | Type                 | Notes                                                              |
| ---------------------- | -------------------- | ------------------------------------------------------------------ |
| id                     | id                   |                                                                    |
| client_id              | foreignId            | cascade                                                            |
| description            | string               | clinician's wording                                                |
| game_type              | string, nullable     | limit to one game type, or any                                     |
| language               | string, nullable     |                                                                    |
| target_sound           | string(10), nullable | matches `content_items.target_sound`                               |
| word_position          | string, nullable     | matches `content_items.word_position`                              |
| target_accuracy        | unsignedTinyInteger  | percent, e.g. `80`                                                 |
| consecutive_sessions   | unsignedTinyInteger  | e.g. `3`                                                           |
| min_trials_per_session | unsignedTinyInteger  | default `10` — a session with fewer matching trials does not count |
| status                 | string               | `GoalStatus`: `Active`, `Achieved`, `Discontinued`                 |
| achieved_at            | timestamp, nullable  |                                                                    |
| timestamps             |                      |                                                                    |

### How a goal is evaluated

`App\Services\Therapy\GoalEvaluator`, run when a play session is finished:

1. For each active goal of the client, take the trials of each finished session whose target word matches the goal's filters (sound, position, language, game type).
2. Ignore sessions with fewer than `min_trials_per_session` matching trials.
3. Accuracy per qualifying session = `correct / matching trials` (prompted counts as not correct).
4. If the latest `consecutive_sessions` qualifying sessions are all ≥ `target_accuracy`, mark the goal **Achieved** and set `achieved_at`.

### UI

- Client page: **Goals** card — progress toward each goal (e.g. _2 of 3 sessions at ≥ 80%_), status badge, achieved date.
- Create / edit / discontinue goals from the client page.
- Session summary shows which goals the session counted toward, and celebrates newly achieved goals.

## 2. Add session to Clinical Hours

On the session summary (and on the client page for past sessions): **Add to clinical hours**.

### Behaviour

- Groups all finished play sessions **with the same client on the same day** into one clinical log row, because one clinic visit often uses several activities.
- Clinician picks a **log form** (log form mode only; defaults to the latest form for the current semester) or is sent to create one.
- Pre-filled, editable before saving:

| Log entry field | Filled from                                                  |
| --------------- | ------------------------------------------------------------ |
| date            | session date                                                 |
| age_group       | client's age group                                           |
| rn_no           | client reference code                                        |
| session_mode    | face-to-face                                                 |
| session_type    | therapy                                                      |
| diagnosis       | client's diagnosis                                           |
| hours / minutes | first session start → last session end, rounded to 5 minutes |

### Data

- `play_sessions.clinical_log_entry_id` — foreignId, nullable, `nullOnDelete`.
- A session already linked shows **Added to clinical hours** with a link to the log form.

### Required change to existing code

`ClinicalLogFormController@update` currently **deletes and recreates all entries** on every save, which would break the link. Change it to **sync entries by id** (update existing, create new, delete removed), and send entry ids from `ClinicalLogFormEditor.vue`. Existing clinical hours tests must keep passing, plus a new test that links survive an edit.

## 3. Progress report PDF

- Button on the client page: **Download progress report** with a date range (default: current semester).
- Uses the existing DomPDF setup (same approach as `ClinicalLogFormPdfController`).
- Contents:
    - Header: clinician name, client **reference code only**, age group, language, date range
    - Goals with status and achieved date
    - Accuracy per activity per session (table)
    - Target sound × position breakdown
    - Session list with date, activity, accuracy, notes
    - Footer: _"Reference only — contains no identifying patient information."_
- File name: `PROGRESS REPORT - {REFERENCE} - {FROM} TO {TO}.pdf`.

## 4. New games

### Find it! (receptive vocabulary)

- Child area: a field of 3–6 pictures (`settings.field_size`).
- Clinician strip shows _"Say: kucing"_; the clinician says it, the child taps a picture; scored automatically.
- **Optional text-to-speech** (`settings.speak_word`): uses the browser's Web Speech API with an `ms-MY` or `en-*` voice. If no voice exists for the language, the toggle is disabled with a note, and the clinician says the word instead.
- Records response time; summary shows which wrong pictures were chosen (helps spot confusions).

### Story sequence (language / narrative)

- Activity content is grouped into **stories** of 3–5 cards in the correct order.
- Child area: the story's cards shuffled; the child drags them into the slots.
- **Check** button (clinician) → correct / try again. One trial per story:
    - `Correct` = right on first attempt
    - `Prompted` = right after a retry or clinician help
    - `Incorrect` = clinician skips
- Works with touch and mouse (pointer events; no new drag-and-drop library).

### Data changes for the new games

- `GameType`: add `FindIt` (`find_it`), `StorySequence` (`story_sequence`).
- `activity_items`: add `group_number` (unsignedSmallInteger, nullable) — the story a card belongs to; `position` is its correct order.
- `trials`:
    - `content_item_id` becomes nullable (a story trial is not a single word)
    - add `group_number` (nullable)
    - add `meta` json (nullable) — e.g. `{ "attempts": 2, "chosen_item_id": 14 }`
- Starter content: a few simple Malay/English stories (e.g. _bangun tidur → gosok gigi → makan → pergi sekolah_) using emoji.

## 5. Tests (Pest)

- `GoalEvaluator`: achieved after N consecutive qualifying sessions; not achieved when one session dips below target; sessions below `min_trials_per_session` ignored; filters by sound/position/language.
- Discontinued goals are not evaluated.
- Adding to clinical hours creates one entry per client per day with correct duration, links all sessions, and refuses simple-mode forms and other clinicians' forms.
- Editing a clinical log form keeps linked entries (sync by id).
- Progress report downloads as PDF with the expected file name and contains no client fields other than the reference.
- Story sequence and Find it! trials are stored with the correct `meta`.

## 6. Build order

1. **Goals** — enum, migration, model, policy, `GoalEvaluator`, goals card, tests
2. **Clinical log entry sync** — refactor `ClinicalLogFormController@update` + editor to sync by id, tests
3. **Add to clinical hours** — link column, action, summary/client page UI, tests
4. **Progress report PDF** — controller, Blade view, tests
5. **Find it!** — game component, optional TTS, tests
6. **Story sequence** — schema changes, builder support for stories, game component, starter stories, tests

## Done when

- Goals update automatically after each session and show progress on the client page.
- A day's game sessions can be added to a clinical log form in two clicks, and edits to that form keep the link.
- A progress report PDF can be downloaded for any date range.
- Find it! and Story sequence are playable, configurable and tracked like the Phase 1 games.
