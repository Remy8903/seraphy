import { useLocalStorage } from '@vueuse/core';
import type { TrialResultValue } from '@/types';

type Note = {
    frequency: number;
    /** Seconds after the sound starts. */
    at: number;
    duration: number;
    volume?: number;
    wave?: OscillatorType;
};

/** Shared by every game screen so one toggle mutes the whole session. */
const isMuted = useLocalStorage('therapy.sounds-muted', false);

let context: AudioContext | null = null;

function audioContext(): AudioContext | null {
    if (context) {
        return context;
    }

    const AudioContextClass =
        window.AudioContext ??
        (window as typeof window & { webkitAudioContext?: typeof AudioContext })
            .webkitAudioContext;

    if (!AudioContextClass) {
        return null;
    }

    context = new AudioContextClass();

    return context;
}

/**
 * Play a short sequence of notes. Notes fade in and out so they sound
 * soft rather than clicky.
 */
function playNotes(notes: Note[], startDelay = 0): void {
    if (isMuted.value) {
        return;
    }

    const audio = audioContext();

    if (!audio) {
        return;
    }

    // Browsers start the context suspended until a user gesture.
    if (audio.state === 'suspended') {
        void audio.resume();
    }

    const start = audio.currentTime + startDelay;

    for (const note of notes) {
        const oscillator = audio.createOscillator();
        const gain = audio.createGain();
        const noteStart = start + note.at;
        const noteEnd = noteStart + note.duration;
        const peak = note.volume ?? 0.18;

        oscillator.type = note.wave ?? 'triangle';
        oscillator.frequency.setValueAtTime(note.frequency, noteStart);

        gain.gain.setValueAtTime(0.0001, noteStart);
        gain.gain.exponentialRampToValueAtTime(peak, noteStart + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.0001, noteEnd);

        oscillator.connect(gain).connect(audio.destination);
        oscillator.start(noteStart);
        oscillator.stop(noteEnd + 0.05);
    }
}

const answerSounds: Record<TrialResultValue, Note[]> = {
    /** Bright rising two-note chime. */
    correct: [
        { frequency: 784, at: 0, duration: 0.14 },
        { frequency: 1047, at: 0.1, duration: 0.25 },
    ],
    /** A single warm note: encouraging, but distinct from correct. */
    prompted: [{ frequency: 659, at: 0, duration: 0.3, volume: 0.14 }],
    /** Soft, low and quiet so a miss never sounds like a buzzer. */
    incorrect: [
        { frequency: 330, at: 0, duration: 0.16, volume: 0.1, wave: 'sine' },
        { frequency: 262, at: 0.14, duration: 0.24, volume: 0.1, wave: 'sine' },
    ],
};

/** Rising arpeggio for the every-few-answers celebration. */
const celebrationNotes: Note[] = [
    { frequency: 523, at: 0, duration: 0.15 },
    { frequency: 659, at: 0.1, duration: 0.15 },
    { frequency: 784, at: 0.2, duration: 0.15 },
    { frequency: 1047, at: 0.3, duration: 0.45, volume: 0.2 },
];

/** Longer fanfare when the session target is reached. */
const finishNotes: Note[] = [
    { frequency: 523, at: 0, duration: 0.14 },
    { frequency: 523, at: 0.15, duration: 0.14 },
    { frequency: 523, at: 0.3, duration: 0.14 },
    { frequency: 659, at: 0.45, duration: 0.3 },
    { frequency: 784, at: 0.75, duration: 0.18 },
    { frequency: 659, at: 0.93, duration: 0.14 },
    { frequency: 1047, at: 1.07, duration: 0.6, volume: 0.2 },
];

/** Gap that lets an answer chime finish before a milestone sound starts. */
const afterAnswerDelay = 0.3;

/**
 * Feedback sounds for the therapy games, generated with the Web Audio API.
 */
export function useGameSounds() {
    return {
        isMuted,
        toggleMuted: (): void => {
            isMuted.value = !isMuted.value;
        },
        playAnswer: (result: TrialResultValue): void =>
            playNotes(answerSounds[result]),
        playCelebration: (): void =>
            playNotes(celebrationNotes, afterAnswerDelay),
        playFinish: (): void => playNotes(finishNotes, afterAnswerDelay),
    };
}
