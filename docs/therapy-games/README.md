# Therapy Games — Overview

Mini-games that a student clinician assigns to their **clients (patients)** and plays **together with the child during a clinic session**. Every answer the child gives is recorded as a _trial_, and the client's progress is calculated from those trials.

## Decisions

| Topic                        | Decision                                                             |
| ---------------------------- | -------------------------------------------------------------------- |
| Who receives the games       | Clients / patients (often children)                                  |
| Who operates the games       | The clinician, sitting with the child, on one device                 |
| Languages                    | Malay (`ms`) and English (`en`) from the start                       |
| Supervisor / lecturer role   | Not needed                                                           |
| Home practice / parent links | Not needed                                                           |
| Patient data                 | Real patients → **reference codes only**, with a clear privacy alert |

## Core flow

```
Client ─▶ Assign activity ─▶ Play in session ─▶ Record each answer (trial)
                                                   │
                                                   ▼
                              Progress & goals update automatically
                                                   │
                                                   ▼
                              (Phase 2) Add session to Clinical Hours
```

## Privacy rules (apply to every phase)

- Never store patient names, IC numbers, phone numbers, addresses, emails or photos of patients.
- Clients are identified by a **reference code** only (e.g. RN no. or the clinician's own code like `C-01`).
- A warning banner is shown on every client page and on picture uploads.
- Creating a client requires ticking: _"I confirm this contains no information that identifies the patient."_
- The server rejects text that looks like an IC number, a Malaysian phone number or an email address in client fields.
- The child-facing screen never shows the client reference.
- Deleting a client deletes all of their game history.
- Microphone audio (Phase 3) is processed on the device only, never recorded or uploaded.

## Phases

| Phase | Focus                                                                                          | File                     |
| ----- | ---------------------------------------------------------------------------------------------- | ------------------------ |
| 1     | Clients, privacy, word library, activities, two games, progress chart                          | [phase-1.md](phase-1.md) |
| 2     | Therapy goals, add session to Clinical Hours, progress report PDF, Find it! and Story sequence | [phase-2.md](phase-2.md) |
| 3     | Sort it, Follow directions, Balloon voice, sticker rewards, offline play                       | [phase-3.md](phase-3.md) |

## Game catalogue

| Game                 | Therapy area                     | Phase |
| -------------------- | -------------------------------- | ----- |
| Sound card drill     | Articulation                     | 1     |
| Minimal pairs        | Phonology                        | 1     |
| Find it!             | Receptive vocabulary             | 2     |
| Story sequence       | Language / narrative             | 2     |
| Sort it              | Word groups (semantics)          | 3     |
| Follow directions    | Understanding instructions       | 3     |
| Balloon voice        | Voice / loudness                 | 3     |
| Sticker reward board | Motivation (works with any game) | 3     |

## Architecture principles

- Each game is a Vue component in `resources/js/games/`, registered in a game registry keyed by the `GameType` enum. Adding a game = one enum case + one component.
- Games are **data-driven**: the game type is fixed in code, but clinicians configure activities with their own words, sounds and settings.
- All progress is derived from `trials`; nothing is typed in by hand except session notes.
- Every model is owned by a clinician (`user_id`) and protected by a policy, following the existing `ClinicalLogFormPolicy` pattern.
- New dependencies (chart library, PWA plugin) need approval before they are added.
