# Phase 3 — More Games, Rewards and Offline Play

**Goal:** widen therapy coverage with three more games (**Sort it**, **Follow directions**, **Balloon voice**), keep children motivated with a **sticker reward board**, and let clinicians play sessions **without a reliable internet connection**.

**Depends on:** Phase 2 complete (uses `group_number` and `trials.meta` added there).

## 1. Sort it (word groups / semantics)

- Activity settings: 2–3 **categories** (e.g. _haiwan / makanan / pakaian_). Items come from `content_items.category`.
- Child area: category bins along the bottom, one picture at a time on top; the child drags the picture into a bin.
- Scored automatically; one trial per picture.
    - Wrong bin → picture bounces back; second try correct = `Prompted`.
- `trials.meta`: `{ "chosen_category": "makanan" }`.
- Progress breakdown by category (which groups the child confuses).

## 2. Follow directions (understanding instructions)

- Activity content: a **board** of 4–8 pictures and a list of **directions**, each an ordered list of pictures to touch, e.g. _"Sentuh bola, kemudian kucing"_ (2 steps).
- Clinician strip shows the direction to read aloud (or text-to-speech, reusing the Phase 2 option).
- The child touches pictures in order; scored automatically when the number of steps is reached.
    - Correct order = `Correct`; clinician repeats the instruction and the child succeeds = `Prompted`.
- Progress breakdown by **number of steps** (1-step, 2-step, 3-step accuracy).

### Data

- New table `activity_directions`:

| Column      | Type                 | Notes                               |
| ----------- | -------------------- | ----------------------------------- |
| id          | id                   |                                     |
| activity_id | foreignId            | cascade                             |
| text        | string               | what the clinician reads            |
| item_ids    | json                 | ordered `content_item` ids to touch |
| position    | unsignedSmallInteger |                                     |

- `trials`: add `activity_direction_id` (nullable); `meta`: `{ "touched_item_ids": [...] }`.

## 3. Balloon voice (voice / loudness)

- Uses the device microphone (`getUserMedia`) and Web Audio `AnalyserNode` to measure loudness.
- **Calibration step** before playing: 3 seconds of quiet, then 3 seconds of the child's normal voice, to set the scale for that room and device.
- Activity settings: target range (e.g. _louder than normal_, _steady normal voice_), hold time (e.g. 3 s), number of tries.
- Child area: a balloon that rises while the voice is in the target range and sinks when it is not; reaching the top = success.
- One trial per attempt; `meta`: `{ "held_ms": 2800, "average_level": 0.62 }`.

### Privacy and technical notes

- Audio is **processed on the device only** — never recorded, stored or uploaded. Shown on screen before the microphone permission prompt.
- Microphone access requires HTTPS (or `localhost`). Check the Laragon/production setup.
- Clear message if the microphone permission is denied, with how to allow it.
- Levels are relative to calibration, not real decibels — the UI must not claim dB values.

## 4. Sticker reward board

- Works with **any** game. Activity settings: `reward_every` (e.g. every 5 answers, `0` = off) and a sticker set (animals, vehicles, space — emoji).
- After every N answers, a short reward screen: the child picks a sticker, which lands on their board.
- The board is shown at the end of the session and on the summary (_"5 stickers earned!"_).
- Stickers are a motivation tool only — no effect on accuracy or goals.
- Stored on the session: `play_sessions.stickers` json (list of sticker emoji earned).

## 5. Offline play

### What "offline" covers

- **Phase 1 already** queues answers when the connection drops mid-session.
- **Phase 3 adds:** the app can be installed on a tablet, the play screen loads without internet, and a session can be **started offline** for activities that were opened before.

### Approach

- Make the app an installable PWA with a service worker that caches the app shell, game components and starter emoji content.
- **"Make available offline"** on a client's assignment: downloads the activity (items, settings, uploaded pictures) into IndexedDB.
- Offline session start creates the session locally with a client-generated UUID; trials are stored in IndexedDB.
- When back online, a sync step uploads sessions and trials in order. `play_sessions` gets a unique `client_uuid` (like `trials`) so syncing twice never duplicates data.
- Banner while offline: _"Offline — results will sync when you're back online"_, with a count of unsynced sessions.

### Data changes

- `play_sessions.client_uuid` — uuid, unique.
- New endpoint: `POST play-sessions/sync` — accepts a batch of sessions with their trials; idempotent.

### Dependency

- A PWA plugin for Vite (e.g. `vite-plugin-pwa`) is a **new dependency and needs approval**. Without it, a hand-written service worker is possible but more work to maintain.

### Data safety

- Offline data on the device contains only reference codes and results — the same privacy rules apply.
- Signing out clears offline data from the device.

## 6. Tests

- **Pest (backend):**
    - Sort it / Follow directions trials stored with correct `meta` and direction ids.
    - Follow directions progress breakdown by number of steps.
    - Sync endpoint: creates sessions and trials; re-sending the same batch creates no duplicates; rejects sessions for another clinician's client.
    - Stickers saved on the session and shown on the summary.
- **Manual / browser checks:**
    - Balloon voice calibration and permission-denied flow on a real tablet.
    - Offline: open an activity online, switch to airplane mode, play a full session, reconnect, confirm results synced once.

## 7. Build order

1. **Sticker reward board** — smallest, improves every existing game
2. **Sort it** — game component, category settings, breakdown, tests
3. **Follow directions** — `activity_directions`, builder UI, game, step breakdown, tests
4. **Balloon voice** — microphone handling, calibration, game, privacy notice, manual tests
5. **Offline play** — dependency approval, service worker, IndexedDB storage, sync endpoint, tests

## Done when

- All eight games in the catalogue are playable, configurable in Malay and English, and tracked in progress and goals.
- Children see stickers during play when rewards are on.
- A clinician can run a full session on a tablet with no internet and the results sync exactly once when back online.
- Microphone audio never leaves the device.
