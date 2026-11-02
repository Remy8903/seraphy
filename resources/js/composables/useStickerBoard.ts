import { useHttp } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import updateStickers from '@/actions/App/Http/Controllers/PlaySessionStickerController';

/**
 * The stickers a child has earned in a session.
 *
 * The whole board is sent after every new sticker, so a lost request is fixed
 * by the next one. Stickers are a motivation tool only and never affect results.
 */
export function useStickerBoard(sessionId: number, initialStickers: string[]) {
    const stickers = ref<string[]>([...initialStickers]);
    const isSaved = ref(true);
    const http = useHttp<{ stickers: string[] }, { stickers: string[] }>({
        stickers: [],
    });

    /** Save the board. Resolves true once the server has the latest stickers. */
    async function save(): Promise<boolean> {
        if (isSaved.value) {
            return true;
        }

        if (http.processing) {
            return false;
        }

        const sending = [...stickers.value];
        http.stickers = sending;

        try {
            await http.put(updateStickers.url(sessionId));
        } catch {
            return false;
        }

        isSaved.value = sending.length === stickers.value.length;

        return isSaved.value || save();
    }

    function add(sticker: string): void {
        stickers.value = [...stickers.value, sticker];
        isSaved.value = false;
        void save();
    }

    function saveWhenOnline(): void {
        void save();
    }

    onMounted(() => window.addEventListener('online', saveWhenOnline));
    onBeforeUnmount(() => window.removeEventListener('online', saveWhenOnline));

    return { stickers, isSaved, add, save };
}
