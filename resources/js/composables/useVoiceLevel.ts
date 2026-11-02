import { onBeforeUnmount, ref } from 'vue';

export type MicrophoneState =
    | 'off'
    | 'starting'
    | 'on'
    | 'denied'
    | 'unavailable'
    | 'insecure';

export type Calibration = {
    /** Room noise level, from the quiet step. */
    quiet: number;
    /** The child's normal voice level, from the voice step. */
    normal: number;
};

/**
 * Calibration is kept for the whole page, so it survives the game being
 * hidden by the "all done" screen and shown again with "Keep going".
 */
const calibration = ref<Calibration | null>(null);

/** Below this relative level the child is treated as silent. */
export const voicedThreshold = 0.15;

/**
 * Measure how loud the microphone input is, entirely on this device.
 *
 * Audio is only read as a loudness level with a Web Audio AnalyserNode; it is
 * never recorded, stored or sent anywhere. Levels are relative to the
 * calibration (quiet room = 0, the child's normal voice = 1), not decibels.
 */
export function useVoiceLevel() {
    const state = ref<MicrophoneState>('off');
    const rawLevel = ref(0);

    let stream: MediaStream | null = null;
    let context: AudioContext | null = null;
    let analyser: AnalyserNode | null = null;
    let samples: Float32Array<ArrayBuffer> | null = null;
    let frame: number | null = null;
    const listeners = new Set<(level: number, now: number) => void>();

    function readLevel(): void {
        if (!analyser || !samples) {
            return;
        }

        analyser.getFloatTimeDomainData(samples);

        let sum = 0;

        for (const sample of samples) {
            sum += sample * sample;
        }

        rawLevel.value = Math.sqrt(sum / samples.length);
        const now = performance.now();

        for (const listener of listeners) {
            listener(rawLevel.value, now);
        }

        frame = requestAnimationFrame(readLevel);
    }

    async function start(): Promise<void> {
        if (state.value === 'on' || state.value === 'starting') {
            return;
        }

        if (!window.isSecureContext) {
            state.value = 'insecure';

            return;
        }

        if (!navigator.mediaDevices?.getUserMedia) {
            state.value = 'unavailable';

            return;
        }

        state.value = 'starting';

        try {
            stream = await navigator.mediaDevices.getUserMedia({
                audio: {
                    echoCancellation: false,
                    noiseSuppression: false,
                    autoGainControl: false,
                },
            });
        } catch (error) {
            state.value =
                error instanceof DOMException &&
                (error.name === 'NotAllowedError' ||
                    error.name === 'SecurityError')
                    ? 'denied'
                    : 'unavailable';

            return;
        }

        context = new AudioContext();
        analyser = context.createAnalyser();
        analyser.fftSize = 1024;
        samples = new Float32Array(analyser.fftSize);
        context.createMediaStreamSource(stream).connect(analyser);
        state.value = 'on';
        readLevel();
    }

    function stop(): void {
        if (frame !== null) {
            cancelAnimationFrame(frame);
            frame = null;
        }

        stream?.getTracks().forEach((track) => track.stop());
        void context?.close();
        stream = null;
        context = null;
        analyser = null;
        samples = null;

        if (state.value === 'on' || state.value === 'starting') {
            state.value = 'off';
        }
    }

    /** Call the listener with the raw level on every animation frame until unsubscribed. */
    function onLevel(
        listener: (level: number, now: number) => void,
    ): () => void {
        listeners.add(listener);

        return () => listeners.delete(listener);
    }

    /** Collect raw levels for a number of milliseconds. */
    function collect(durationMs: number): Promise<number[]> {
        return new Promise((resolve) => {
            const levels: number[] = [];
            const startedAt = performance.now();
            const unsubscribe = onLevel((level, now) => {
                levels.push(level);

                if (now - startedAt >= durationMs) {
                    unsubscribe();
                    resolve(levels);
                }
            });
        });
    }

    /** Turn a raw level into a level relative to the calibration (normal voice = 1). */
    function relative(level: number): number {
        const current = calibration.value;

        if (!current) {
            return 0;
        }

        return Math.max(
            0,
            (level - current.quiet) / (current.normal - current.quiet),
        );
    }

    onBeforeUnmount(stop);

    return {
        state,
        rawLevel,
        calibration,
        start,
        stop,
        onLevel,
        collect,
        relative,
    };
}

/** Average of a list of numbers, or 0 when it is empty. */
export function average(values: number[]): number {
    return values.length
        ? values.reduce((sum, value) => sum + value, 0) / values.length
        : 0;
}
