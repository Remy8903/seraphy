<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TherapyStarterContentSeeder extends Seeder
{
    /**
     * Starter words per language: [word, emoji, target sound, word position, category, minimal pair key].
     *
     * Words that share a pair key are offered together as a suggested minimal pair.
     *
     * @var array<string, list<array{0: string, 1: string, 2: string|null, 3: string|null, 4: string, 5?: string}>>
     */
    private const array WORDS = [
        'ms' => [
            ['sabun', '🧼', 's', 'initial', 'rumah'],
            ['sayur', '🥬', 's', 'initial', 'makanan'],
            ['susu', '🥛', 's', 'initial', 'makanan'],
            ['sudu', '🥄', 's', 'initial', 'rumah'],
            ['semut', '🐜', 's', 'initial', 'haiwan'],
            ['siput', '🐌', 's', 'initial', 'haiwan'],
            ['bas', '🚌', 's', 'final', 'kenderaan'],
            ['nanas', '🍍', 's', 'final', 'makanan'],
            ['tikus', '🐭', 's', 'final', 'haiwan'],
            ['kertas', '📄', 's', 'final', 'benda'],
            ['kipas', '🪭', 's', 'final', 'rumah'],
            ['kucing', '🐱', 'k', 'initial', 'haiwan'],
            ['kuda', '🐴', 'k', 'initial', 'haiwan'],
            ['kereta', '🚗', 'k', 'initial', 'kenderaan'],
            ['kek', '🎂', 'k', 'initial', 'makanan'],
            ['kaki', '🦶', 'k', 'initial', 'badan'],
            ['kunci', '🔑', 'k', 'initial', 'benda'],
            ['kasut', '👟', 'k', 'initial', 'pakaian'],
            ['gajah', '🐘', 'g', 'initial', 'haiwan'],
            ['gigi', '🦷', 'g', 'initial', 'badan'],
            ['garpu', '🍴', 'g', 'initial', 'rumah'],
            ['gitar', '🎸', 'g', 'initial', 'benda'],
            ['gunung', '⛰️', 'g', 'initial', 'alam'],
            ['telur', '🥚', 't', 'initial', 'makanan'],
            ['tangan', '✋', 't', 'initial', 'badan'],
            ['tomato', '🍅', 't', 'initial', 'makanan'],
            ['tembikai', '🍉', 't', 'initial', 'makanan'],
            ['televisyen', '📺', 't', 'initial', 'rumah'],
            ['ubat', '💊', 't', 'final', 'benda'],
            ['laut', '🌊', 't', 'final', 'alam'],
            ['rambut', '💇', 't', 'final', 'badan'],
            ['daun', '🍃', 'd', 'initial', 'alam'],
            ['dadu', '🎲', 'd', 'initial', 'benda'],
            ['dompet', '👛', 'd', 'initial', 'benda'],
            ['doktor', '🧑‍⚕️', 'd', 'initial', 'orang'],
            ['lampu', '💡', 'l', 'initial', 'rumah'],
            ['lebah', '🐝', 'l', 'initial', 'haiwan'],
            ['lori', '🚚', 'l', 'initial', 'kenderaan'],
            ['limau', '🍊', 'l', 'initial', 'makanan'],
            ['lilin', '🕯️', 'l', 'initial', 'rumah'],
            ['bakul', '🧺', 'l', 'final', 'rumah'],
            ['botol', '🍼', 'l', 'final', 'benda'],
            ['kapal', '🚢', 'l', 'final', 'kenderaan'],
            ['rumah', '🏠', 'r', 'initial', 'rumah'],
            ['roti', '🍞', 'r', 'initial', 'makanan'],
            ['rusa', '🦌', 'r', 'initial', 'haiwan'],
            ['raja', '🤴', 'r', 'initial', 'orang'],
            ['roket', '🚀', 'r', 'initial', 'kenderaan'],
            ['rama-rama', '🦋', 'r', 'initial', 'haiwan'],
            ['ular', '🐍', 'r', 'final', 'haiwan'],
            ['air', '💧', 'r', 'final', 'alam'],
            ['pasir', '🏖️', 'r', 'final', 'alam'],
            ['payung', '☂️', 'ng', 'final', 'benda'],
            ['burung', '🐦', 'ng', 'final', 'haiwan'],
            ['hidung', '👃', 'ng', 'final', 'badan'],
            ['jagung', '🌽', 'ng', 'final', 'makanan'],
            ['syampu', '🧴', 'sy', 'initial', 'rumah'],
            ['syiling', '🪙', 'sy', 'initial', 'benda'],
            ['pisang', '🍌', 'p', 'initial', 'makanan'],
            ['pintu', '🚪', 'p', 'initial', 'rumah'],
            ['pensel', '✏️', 'p', 'initial', 'benda'],
            ['pokok', '🌳', 'p', 'initial', 'alam'],
            ['bola', '⚽', 'b', 'initial', 'benda'],
            ['bunga', '🌸', 'b', 'initial', 'alam'],
            ['buku', '📖', 'b', 'initial', 'benda'],
            ['baju', '👕', 'b', 'initial', 'pakaian'],
            ['monyet', '🐒', 'm', 'initial', 'haiwan'],
            ['mangga', '🥭', 'm', 'initial', 'makanan'],
            ['mulut', '👄', 'm', 'initial', 'badan'],
            ['kopi', '☕', 'k', 'initial', 'makanan', 'ms-kopi-topi'],
            ['topi', '🧢', 't', 'initial', 'pakaian', 'ms-kopi-topi'],
            ['palu', '🔨', 'l', 'medial', 'benda', 'ms-palu-paru'],
            ['paru', '🫁', 'r', 'medial', 'badan', 'ms-palu-paru'],
            ['mata', '👁️', 'm', 'initial', 'badan', 'ms-mata-bata'],
            ['bata', '🧱', 'b', 'initial', 'benda', 'ms-mata-bata'],
            ['bulan', '🌙', 'n', 'final', 'alam', 'ms-bulan-bulat'],
            ['bulat', '⚪', 't', 'final', 'bentuk', 'ms-bulan-bulat'],
            ['ayam', '🐔', 'm', 'final', 'haiwan', 'ms-ayam-ayah'],
            ['ayah', '👨', 'h', 'final', 'orang', 'ms-ayam-ayah'],
        ],
        'en' => [
            ['sun', '☀️', 's', 'initial', 'nature'],
            ['sock', '🧦', 's', 'initial', 'clothes'],
            ['soap', '🧼', 's', 'initial', 'home'],
            ['seal', '🦭', 's', 'initial', 'animals'],
            ['saw', '🪚', 's', 'initial', 'objects'],
            ['bus', '🚌', 's', 'final', 'vehicles'],
            ['house', '🏠', 's', 'final', 'home'],
            ['horse', '🐴', 's', 'final', 'animals'],
            ['dice', '🎲', 's', 'final', 'objects'],
            ['cat', '🐱', 'k', 'initial', 'animals'],
            ['car', '🚗', 'k', 'initial', 'vehicles'],
            ['cake', '🎂', 'k', 'initial', 'food'],
            ['cow', '🐄', 'k', 'initial', 'animals'],
            ['book', '📖', 'k', 'final', 'objects'],
            ['duck', '🦆', 'k', 'final', 'animals'],
            ['clock', '🕐', 'k', 'final', 'home'],
            ['snake', '🐍', 'k', 'final', 'animals'],
            ['girl', '👧', 'g', 'initial', 'people'],
            ['gift', '🎁', 'g', 'initial', 'objects'],
            ['ghost', '👻', 'g', 'initial', 'objects'],
            ['guitar', '🎸', 'g', 'initial', 'objects'],
            ['tiger', '🐯', 't', 'initial', 'animals'],
            ['tooth', '🦷', 't', 'initial', 'body'],
            ['tent', '⛺', 't', 'initial', 'objects'],
            ['turtle', '🐢', 't', 'initial', 'animals'],
            ['dog', '🐶', 'd', 'initial', 'animals'],
            ['door', '🚪', 'd', 'initial', 'home'],
            ['dinosaur', '🦕', 'd', 'initial', 'animals'],
            ['drum', '🥁', 'd', 'initial', 'objects'],
            ['lion', '🦁', 'l', 'initial', 'animals'],
            ['leaf', '🍃', 'l', 'initial', 'nature'],
            ['lamp', '💡', 'l', 'initial', 'home'],
            ['lemon', '🍋', 'l', 'initial', 'food'],
            ['rabbit', '🐰', 'r', 'initial', 'animals'],
            ['rain', '🌧️', 'r', 'initial', 'nature'],
            ['ring', '💍', 'r', 'initial', 'objects'],
            ['rocket', '🚀', 'r', 'initial', 'vehicles'],
            ['fish', '🐟', 'f', 'initial', 'animals'],
            ['fork', '🍴', 'f', 'initial', 'home'],
            ['frog', '🐸', 'f', 'initial', 'animals'],
            ['fire', '🔥', 'f', 'initial', 'nature'],
            ['shoe', '👟', 'sh', 'initial', 'clothes'],
            ['ship', '🚢', 'sh', 'initial', 'vehicles'],
            ['shark', '🦈', 'sh', 'initial', 'animals'],
            ['sheep', '🐑', 'sh', 'initial', 'animals'],
            ['chair', '🪑', 'ch', 'initial', 'home'],
            ['cheese', '🧀', 'ch', 'initial', 'food'],
            ['chicken', '🐔', 'ch', 'initial', 'animals'],
            ['cherry', '🍒', 'ch', 'initial', 'food'],
            ['key', '🔑', 'k', 'initial', 'objects', 'en-key-tea'],
            ['tea', '🍵', 't', 'initial', 'food', 'en-key-tea'],
            ['coat', '🧥', 'k', 'initial', 'clothes', 'en-coat-goat'],
            ['goat', '🐐', 'g', 'initial', 'animals', 'en-coat-goat'],
            ['bear', '🐻', 'b', 'initial', 'animals', 'en-bear-pear'],
            ['pear', '🍐', 'p', 'initial', 'food', 'en-bear-pear'],
            ['pin', '📌', 'p', 'initial', 'objects', 'en-pin-bin'],
            ['bin', '🗑️', 'b', 'initial', 'home', 'en-pin-bin'],
            ['rock', '🪨', 'r', 'initial', 'nature', 'en-rock-lock'],
            ['lock', '🔒', 'l', 'initial', 'objects', 'en-rock-lock'],
            ['fan', '🪭', 'f', 'initial', 'home', 'en-fan-van'],
            ['van', '🚐', 'v', 'initial', 'vehicles', 'en-fan-van'],
            ['three', '3️⃣', 'th', 'initial', 'numbers', 'en-three-tree'],
            ['tree', '🌳', 't', 'initial', 'nature', 'en-three-tree'],
            ['mouse', '🐭', 's', 'final', 'animals', 'en-mouse-mouth'],
            ['mouth', '👄', 'th', 'final', 'body', 'en-mouse-mouth'],
            ['sea', '🌊', 's', 'initial', 'nature'],
        ],
    ];

    /**
     * Starter stories per language for the story sequence game: story key => cards in order as [phrase, emoji].
     *
     * @var array<string, array<string, list<array{0: string, 1: string}>>>
     */
    private const array STORIES = [
        'ms' => [
            'ms-pagi' => [
                ['bangun tidur', '🛏️'],
                ['gosok gigi', '🪥'],
                ['makan sarapan', '🍳'],
                ['pergi sekolah', '🏫'],
            ],
            'ms-cuci-tangan' => [
                ['basahkan tangan', '💧'],
                ['sapu sabun', '🧴'],
                ['gosok tangan', '🙌'],
                ['lap tangan', '🧻'],
            ],
            'ms-tanam-pokok' => [
                ['tanam biji', '🫘'],
                ['siram air', '💦'],
                ['pokok tumbuh', '🌱'],
                ['bunga kembang', '🌻'],
            ],
        ],
        'en' => [
            'en-morning' => [
                ['wake up', '⏰'],
                ['brush teeth', '🪥'],
                ['eat breakfast', '🥣'],
                ['go to school', '🏫'],
            ],
            'en-wash-hands' => [
                ['wet hands', '💧'],
                ['add soap', '🧴'],
                ['scrub hands', '🙌'],
                ['dry hands', '🧻'],
            ],
            'en-plant' => [
                ['plant a seed', '🫘'],
                ['water it', '💦'],
                ['it sprouts', '🌱'],
                ['a flower grows', '🌻'],
            ],
        ],
    ];

    /**
     * Seed the starter word library and story cards shared by every clinician.
     *
     * Safe to run more than once: existing starter words are updated in place.
     */
    public function run(): void
    {
        $this->seedWords();
        $this->seedStories();
    }

    /**
     * Seed the starter words, updating existing ones in place.
     */
    public function seedWords(): void
    {
        $now = now();

        foreach (self::WORDS as $language => $words) {
            foreach ($words as $word) {
                DB::table('content_items')->updateOrInsert(
                    ['user_id' => null, 'language' => $language, 'word' => $word[0]],
                    [
                        'emoji' => $word[1],
                        'target_sound' => $word[2],
                        'word_position' => $word[3],
                        'category' => $word[4],
                        'pair_key' => $word[5] ?? null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                );
            }
        }
    }

    /**
     * Seed the starter story cards, updating existing ones in place.
     */
    public function seedStories(): void
    {
        $now = now();

        foreach (self::STORIES as $language => $stories) {
            foreach ($stories as $storyKey => $cards) {
                foreach ($cards as $position => $card) {
                    DB::table('content_items')->updateOrInsert(
                        ['user_id' => null, 'language' => $language, 'word' => $card[0]],
                        [
                            'emoji' => $card[1],
                            'category' => $language === 'ms' ? 'cerita' : 'stories',
                            'story_key' => $storyKey,
                            'story_position' => $position + 1,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ],
                    );
                }
            }
        }
    }
}
