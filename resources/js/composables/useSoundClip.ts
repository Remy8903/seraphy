import { onBeforeUnmount, ref } from 'vue';

/** Downloaded clips, kept as object URLs so replays are instant and work offline. */
const loadedClips = new Map<string, Promise<string>>();

/**
 * Fetch a clip once and keep it in memory. Fetching (rather than streaming it in
 * an audio element) lets the service worker serve the clip saved for offline play.
 */
function loadClip(url: string): Promise<string> {
    let clip = loadedClips.get(url);

    if (!clip) {
        clip = fetch(url, { credentials: 'same-origin' })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`Sound failed with ${response.status}`);
                }

                return response.blob();
            })
            .then((blob) => URL.createObjectURL(blob))
            .catch((error: unknown) => {
                loadedClips.delete(url);
                throw error;
            });
        loadedClips.set(url, clip);
    }

    return clip;
}

/**
 * Play uploaded sound clips, such as an animal sound, one at a time. Starting a
 * clip stops the one before it, and leaving the screen stops playback.
 */
export function useSoundClip() {
    const isPlaying = ref(false);
    const hasFailed = ref(false);
    let current: HTMLAudioElement | null = null;
    let playRequest = 0;

    function stop(): void {
        playRequest++;
        current?.pause();
        current = null;
        isPlaying.value = false;
    }

    async function play(url: string): Promise<void> {
        stop();
        const request = playRequest;
        hasFailed.value = false;
        isPlaying.value = true;

        try {
            const source = await loadClip(url);

            if (request !== playRequest) {
                return;
            }

            const audio = new Audio(source);
            current = audio;
            audio.addEventListener('ended', () => {
                if (current === audio) {
                    isPlaying.value = false;
                }
            });
            await audio.play();
        } catch {
            if (request === playRequest) {
                hasFailed.value = true;
                isPlaying.value = false;
            }
        }
    }

    function preload(urls: (string | null | undefined)[]): void {
        for (const url of urls) {
            if (url) {
                void loadClip(url).catch(() => {});
            }
        }
    }

    onBeforeUnmount(stop);

    return { isPlaying, hasFailed, play, stop, preload };
}
