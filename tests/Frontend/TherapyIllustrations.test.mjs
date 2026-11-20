import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import {
    illustrationOptions,
    resolveIllustration,
} from '../../resources/js/lib/therapyIllustrations.ts';

test('renders original artwork for every existing starter word and story card', () => {
    const seeder = readFileSync(
        new URL(
            '../../database/seeders/TherapyStarterContentSeeder.php',
            import.meta.url,
        ),
        'utf8',
    );
    const cards = [...seeder.matchAll(/^\s*\['([^']+)', '([^']+)'[,\]]/gm)];

    assert.ok(cards.length > 0);

    for (const [, word, symbol] of cards) {
        assert.equal(resolveIllustration(symbol, word).isKnown, true, word);
    }
});

test('renders existing saved sticker sets without changing their storage keys', () => {
    const stickerSet = readFileSync(
        new URL('../../app/Enums/Therapy/StickerSet.php', import.meta.url),
        'utf8',
    ).split('public function stickers()')[1];
    const symbols = [...stickerSet.matchAll(/'([^']+)'/g)];

    assert.ok(symbols.length > 0);

    for (const [, symbol] of symbols) {
        assert.equal(resolveIllustration(symbol).isKnown, true, symbol);
    }
});

test('keeps every illustration-picker option renderable and compatible with the saved reference field', () => {
    const symbols = illustrationOptions.map((item) => item.symbol);

    assert.equal(new Set(symbols).size, symbols.length);

    for (const option of illustrationOptions) {
        const illustration = resolveIllustration(option.symbol);
        const svg = decodeURIComponent(illustration.source.split(',')[1]);

        assert.equal(illustration.key, option.key, option.label);
        assert.ok(option.symbol.length <= 16, option.label);
        assert.match(svg, /^<svg xmlns="http:\/\/www.w3.org\/2000\/svg"/);
        assert.doesNotMatch(svg, /undefined|<script|<text/, option.label);
    }
});

test('shows the exact starter action rather than a shared water or soap symbol', () => {
    const examples = [
        ['wet hands', 'basahkan tangan', '💧', 'wet'],
        ['add soap', 'sapu sabun', '🧴', 'add_soap'],
        ['scrub hands', 'gosok tangan', '🙌', 'scrub'],
        ['dry hands', 'lap tangan', '🧻', 'dry'],
        ['wake up', 'bangun tidur', '⏰', 'wake'],
        ['brush teeth', 'gosok gigi', '🪥', 'brush'],
        ['eat breakfast', 'makan sarapan', '🍳', 'breakfast'],
        ['go to school', 'pergi sekolah', '🏫', 'school'],
        ['plant a seed', 'tanam biji', '🫘', 'seed'],
        ['water it', 'siram air', '💦', 'watering'],
        ['it sprouts', 'pokok tumbuh', '🌱', 'sprout'],
        ['a flower grows', 'bunga kembang', '🌻', 'sunflower'],
    ];

    for (const [english, malay, symbol, expected] of examples) {
        assert.equal(resolveIllustration(symbol, english).key, expected);
        assert.equal(resolveIllustration(symbol, malay).key, expected);
    }

    assert.equal(resolveIllustration('💧', 'air').key, 'water');
    assert.equal(resolveIllustration('🧴', 'syampu').key, 'shampoo');
});

test('finds illustrations from English and Malay words when no reference is stored', () => {
    assert.equal(resolveIllustration(null, '  KUCING  ').key, 'cat');
    assert.equal(resolveIllustration(null, 'CAT').key, 'cat');
    assert.equal(resolveIllustration(null, 'rama-rama').key, 'butterfly');
});

test('keeps an explicitly selected illustration instead of guessing from the word', () => {
    assert.equal(resolveIllustration('🐶', 'cat').key, 'dog');
});

test('supports older symbols with and without variation selectors', () => {
    assert.equal(resolveIllustration('☁').key, 'cloud');
    assert.equal(resolveIllustration('🌞').key, 'sun');
    assert.equal(resolveIllustration('🐵').key, 'monkey');
});

test('shows a neutral missing-picture state without inserting unknown user input into the SVG', () => {
    const input = '<svg onload="alert(1)">';
    const illustration = resolveIllustration(input, input);
    const svg = decodeURIComponent(illustration.source.split(',')[1]);

    assert.equal(illustration.key, 'picture');
    assert.equal(illustration.isKnown, false);
    assert.doesNotMatch(svg, /onload|alert/);
});
