import type { MaybeRefOrGetter } from 'vue';
import { computed, onBeforeUnmount, onMounted, ref, toValue } from 'vue';

/**
 * Speak words aloud with the browser's Web Speech API, in the voice of the
 * activity's language (e.g. `ms` matches an `ms-MY` voice).
 *
 * `hasVoice` is false when the device has no voice for the language, in which
 * case the clinician says the word instead. Voices load asynchronously, so it
 * can turn true shortly after mount.
 */
export function useSpeech(language: MaybeRefOrGetter<string>) {
    const voices = ref<SpeechSynthesisVoice[]>([]);
    const isSupported =
        typeof window !== 'undefined' && 'speechSynthesis' in window;

    function loadVoices(): void {
        voices.value = window.speechSynthesis.getVoices();
    }

    const voice = computed(() => {
        const prefix = toValue(language).toLowerCase();

        return (
            voices.value.find((candidate) =>
                candidate.lang
                    .toLowerCase()
                    .replace('_', '-')
                    .startsWith(`${prefix}-`),
            ) ??
            voices.value.find(
                (candidate) => candidate.lang.toLowerCase() === prefix,
            ) ??
            null
        );
    });

    const hasVoice = computed(() => voice.value !== null);

    function speak(text: string): void {
        if (!isSupported || !voice.value) {
            return;
        }

        window.speechSynthesis.cancel();

        const utterance = new SpeechSynthesisUtterance(text);
        utterance.voice = voice.value;
        utterance.lang = voice.value.lang;
        utterance.rate = 0.85;
        window.speechSynthesis.speak(utterance);
    }

    onMounted(() => {
        if (!isSupported) {
            return;
        }

        loadVoices();
        window.speechSynthesis.addEventListener('voiceschanged', loadVoices);
    });

    onBeforeUnmount(() => {
        if (!isSupported) {
            return;
        }

        window.speechSynthesis.removeEventListener('voiceschanged', loadVoices);
        window.speechSynthesis.cancel();
    });

    return { isSupported, hasVoice, speak };
}
