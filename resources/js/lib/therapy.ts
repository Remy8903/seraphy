/** Icons for the starter word groups, so children who cannot read yet can tell the bins apart. */
const categoryIcons: Record<string, string> = {
    haiwan: '🐾',
    animals: '🐾',
    makanan: '🍽️',
    food: '🍽️',
    pakaian: '👕',
    clothes: '👕',
    kenderaan: '🚦',
    vehicles: '🚦',
    badan: '🧍',
    body: '🧍',
    rumah: '🏠',
    home: '🏠',
    alam: '🌳',
    nature: '🌳',
    benda: '📦',
    objects: '📦',
    orang: '🧑',
    people: '🧑',
    bentuk: '🔷',
    numbers: '🔢',
};

/** Get the icon shown on a word group's bin, if it has one. */
export function categoryIcon(category: string): string | null {
    return categoryIcons[category.toLowerCase()] ?? null;
}

/** Turn a stored word group such as "makanan" into a label such as "Makanan". */
export function categoryLabel(category: string): string {
    return category.charAt(0).toUpperCase() + category.slice(1);
}

/**
 * Write a follow directions instruction for the pictures to touch, in order,
 * e.g. "Sentuh bola, kemudian kucing" or "Touch the ball, then the cat".
 */
export function directionText(language: string, words: string[]): string {
    if (words.length === 0) {
        return '';
    }

    if (language === 'ms') {
        const last = words[words.length - 1];

        return words.length === 1
            ? `Sentuh ${last}`
            : `Sentuh ${words.slice(0, -1).join(', ')}, kemudian ${last}`;
    }

    const named = words.map((word) => `the ${word}`);
    const last = named[named.length - 1];

    return named.length === 1
        ? `Touch ${last}`
        : `Touch ${named.slice(0, -1).join(', ')}, then ${last}`;
}
