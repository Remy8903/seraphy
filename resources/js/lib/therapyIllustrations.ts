/**
 * Original, local artwork for Pip's world. Legacy symbols remain storage keys,
 * not rendered characters, so saved activities and offline sessions still work.
 */
const colors = {
    ink: '#514067',
    violet: '#B69AEF',
    purple: '#8260BC',
    cream: '#FFF5E5',
    blue: '#83D5E8',
    teal: '#4DAE9E',
    green: '#8EC67D',
    peach: '#F1B98F',
    coral: '#F08482',
    yellow: '#F8D267',
    white: '#FFFCF7',
};

const {
    violet,
    purple,
    cream,
    blue,
    teal,
    green,
    peach,
    coral,
    yellow,
    white,
} = colors;

function path(d: string, fill: string, extra = ''): string {
    return `<path d="${d}" fill="${fill}" ${extra}/>`;
}

function circle(cx: number, cy: number, r: number, fill: string): string {
    return `<circle cx="${cx}" cy="${cy}" r="${r}" fill="${fill}"/>`;
}

function ellipse(
    cx: number,
    cy: number,
    rx: number,
    ry: number,
    fill: string,
): string {
    return `<ellipse cx="${cx}" cy="${cy}" rx="${rx}" ry="${ry}" fill="${fill}"/>`;
}

function rect(
    x: number,
    y: number,
    width: number,
    height: number,
    fill: string,
    radius = 6,
): string {
    return `<rect x="${x}" y="${y}" width="${width}" height="${height}" rx="${radius}" fill="${fill}"/>`;
}

function line(d: string, stroke = colors.ink, width = 2.5): string {
    return path(d, 'none', `stroke="${stroke}" stroke-width="${width}"`);
}

function eyes(y = 60, spread = 14): string {
    return (
        ellipse(60 - spread, y, 4, 5.3, colors.ink) +
        ellipse(60 + spread, y, 4, 5.3, colors.ink) +
        circle(61 - spread, y - 2, 1.5, white) +
        circle(61 + spread, y - 2, 1.5, white)
    );
}

function muzzle(y = 74): string {
    return (
        ellipse(60, y, 14, 10, cream) +
        path(`M56 ${y - 3}Q60 ${y - 6}64 ${y - 3}L60 ${y + 1}Z`, colors.ink) +
        line(`M60 ${y + 1}v3m-6 0q6 5 12 0`, colors.ink, 1.6)
    );
}

function animal(
    fill: string,
    ears: string,
    details = '',
    face = eyes() + muzzle(),
): string {
    return (
        ears +
        ellipse(60, 93, 27, 11, fill) +
        ellipse(60, 64, 34, 31, fill) +
        path(
            'M36 56Q39 43 51 39',
            'none',
            'stroke="#FFFFFF" stroke-opacity=".38" stroke-width="4" stroke-linecap="round"',
        ) +
        details +
        face +
        ellipse(37, 72, 5.5, 3, '#F1A3A8') +
        ellipse(83, 72, 5.5, 3, '#F1A3A8')
    );
}

function roundEars(fill: string): string {
    return (
        circle(32, 37, 13, fill) +
        circle(88, 37, 13, fill) +
        circle(32, 37, 6, peach) +
        circle(88, 37, 6, peach)
    );
}

function pointEars(fill: string): string {
    return (
        path('M29 53 27 20Q43 23 49 40M71 40Q79 23 94 20L91 54', fill) +
        path('m32 37-1-10 11 10m36 0 12-10-2 13', peach)
    );
}

function wheels(): string {
    return (
        circle(34, 88, 11, colors.ink) +
        circle(87, 88, 11, colors.ink) +
        circle(34, 88, 4, cream) +
        circle(87, 88, 4, cream)
    );
}

function car(fill = violet, roof = ''): string {
    return (
        path(
            'M17 65 30 61 41 39Q44 34 52 34H75Q81 34 84 41L95 61 104 67V85H16Z',
            fill,
        ) +
        path('M46 42h13v19H36Zm20 0h9l10 19H66Z', blue) +
        line('m42 53 6-9m24 0 6 8', white, 2.5) +
        roof +
        line('M56 68h8', purple, 3) +
        rect(17, 70, 10, 5, yellow, 2) +
        rect(95, 70, 8, 5, coral, 2) +
        wheels()
    );
}

function truck(fill = blue, cargo = ''): string {
    return (
        rect(13, 37, 53, 47, fill, 7) +
        path('M66 51h23l16 19v15H66Z', violet) +
        path('M74 57h12l11 14H74Z', blue) +
        cargo +
        wheels()
    );
}

function person(hair = '#886552', outfit = violet, details = ''): string {
    return (
        path('M31 98V85Q31 70 60 70T89 85V98Z', outfit) +
        ellipse(60, 47, 23, 27, peach) +
        path('M37 48Q29 14 59 15Q88 14 84 46L74 33Q53 43 43 32Z', hair) +
        eyes(48, 9) +
        line('M53 60q7 6 14 0', colors.ink, 1.8) +
        details
    );
}

function bird(fill = yellow, detail = ''): string {
    return (
        line('M49 91v11m22-11v11m-28 0h12m10 0h12', peach, 3) +
        ellipse(60, 68, 30, 30, fill) +
        circle(60, 43, 26, fill) +
        ellipse(60, 73, 19, 20, cream) +
        path('M32 59Q12 66 24 81L38 73M85 58q24-17 17-26Q89 33 81 47', blue) +
        eyes(44, 10) +
        path('m52 53 8 9 9-9q-8-6-17 0Z', peach) +
        ellipse(40, 54, 5, 3, '#F1A3A8') +
        ellipse(80, 54, 5, 3, '#F1A3A8') +
        detail
    );
}

function leaf(): string {
    return (
        path('M31 91Q13 27 94 22Q100 91 31 91Z', green) +
        line('M24 100 78 40m-38 42-4-25m19 11 22 1m-11-14 1-17', teal, 2)
    );
}

function cup(fill = cream): string {
    return (
        path('M82 45h8q21 0 17 18-3 14-24 11', fill) +
        path('M28 40h57v38q-1 16-28 16T28 78Z', fill) +
        ellipse(56, 40, 28, 7, peach) +
        line('M43 28q-7-8 0-15m16 15q-7-8 0-15', violet, 3) +
        ellipse(57, 99, 41, 4, blue)
    );
}

function flower(petal = coral): string {
    return (
        line('M60 62v42', teal, 5) +
        path(
            'M59 89Q29 70 26 86q15 17 33 10M63 80q25-23 33-7-10 17-33 16',
            green,
        ) +
        [0, 60, 120, 180, 240, 300]
            .map(
                (angle) =>
                    `<ellipse cx="60" cy="32" rx="12" ry="19" fill="${petal}" transform="rotate(${angle} 60 51)"/>`,
            )
            .join('') +
        circle(60, 51, 15, yellow)
    );
}

function balloon(): string {
    return (
        path('M60 77q-12 15 0 22t-2 15', 'none', 'stroke="#AA8BC7"') +
        ellipse(60, 43, 30, 36, violet) +
        path('m60 75-6 10h12Z', purple) +
        path('M42 39q0-16 13-20', 'none', 'stroke="#E9DAFF" stroke-width="6"') +
        path(
            'm60 44 3 6 7 1-5 5 1 7-6-3-6 3 1-7-5-5 7-1Z',
            yellow,
            'stroke="none"',
        )
    );
}

function star(fill = yellow): string {
    return (
        path(
            'm60 16 13 26 29 4-21 21 5 30-26-14-26 14 5-30-21-21 29-4Z',
            fill,
        ) +
        eyes(57, 11) +
        line('M53 70q7 7 14 0', colors.ink, 2)
    );
}

const artwork: Record<string, string> = {
    cat: animal(
        '#E9A27F',
        pointEars('#E9A27F'),
        path('M51 35 56 47 60 37 64 47 69 35', '#C67B67') +
            line('m32 69-13-3m13 9-13 2m69-8 13-3m-13 12 13 2', colors.ink, 2),
    ),
    dog: animal(
        peach,
        path(
            'M39 38Q12 18 18 64q4 14 17-1M82 38q26-22 22 23-5 17-19 3',
            '#A9745F',
        ),
        ellipse(45, 57, 11, 14, '#DB9B7D'),
    ),
    rabbit: animal(
        cream,
        ellipse(42, 30, 10, 25, cream) +
            ellipse(78, 30, 10, 25, cream) +
            ellipse(42, 29, 4, 17, coral) +
            ellipse(78, 29, 4, 17, coral),
    ),
    mouse: animal(
        '#C4B5D2',
        roundEars('#C4B5D2'),
        line('m34 73-14-2m15 8-13 4m64-12 14-2m-15 10 13 4', purple, 1.4),
    ),
    horse: animal(
        peach,
        path('M38 40 35 13 52 36M69 35l17-22-2 29', peach),
        path('M49 36q-5-21 14-17l8 25', '#987866'),
        eyes(59, 12) +
            ellipse(60, 79, 23, 14, cream) +
            circle(49, 78, 2.5, colors.ink) +
            circle(71, 78, 2.5, colors.ink),
    ),
    cow: animal(
        cream,
        path(
            'M33 40Q12 40 18 26l22 5m42 9q25-1 20-16L80 31M39 31l-2-17m44 17 2-17',
            peach,
        ),
        ellipse(39, 55, 12, 15, violet),
        eyes(56, 14) +
            ellipse(60, 80, 24, 13, peach) +
            circle(50, 80, 2.5, colors.ink) +
            circle(70, 80, 2.5, colors.ink),
    ),
    bear: animal(peach, roundEars(peach)),
    panda: animal(
        cream,
        circle(33, 36, 13, purple) + circle(87, 36, 13, purple),
        ellipse(45, 59, 10, 13, purple) + ellipse(75, 59, 10, 13, purple),
        circle(45, 58, 3, white) + circle(75, 58, 3, white) + muzzle(),
    ),
    lion: circle(60, 61, 43, '#D9AD77') + animal(yellow, roundEars(yellow)),
    tiger: animal(
        peach,
        roundEars(peach),
        path(
            'm43 36 9 15 3-16m12 0 2 16 9-14M28 60l12 5-13 5m66-10-12 5 13 5',
            '#AA7B68',
        ),
    ),
    fox: animal(
        peach,
        pointEars(peach),
        path('M28 60q15 9 32 28Q80 70 94 60l-7 22q-25 29-51 0Z', cream),
    ),
    koala: animal(
        '#BBAECF',
        roundEars('#BBAECF'),
        '',
        eyes(57, 15) +
            ellipse(60, 70, 8, 12, purple) +
            line('M54 86q6 4 12 0', colors.ink, 1.5),
    ),
    monkey: animal(
        '#B59A81',
        roundEars('#B59A81'),
        ellipse(60, 64, 27, 23, cream),
    ),
    frog: animal(
        green,
        circle(39, 38, 15, green) + circle(81, 38, 15, green),
        '',
        circle(39, 38, 8, white) +
            circle(81, 38, 8, white) +
            circle(40, 39, 4, colors.ink) +
            circle(80, 39, 4, colors.ink) +
            line('M41 70q19 19 38 0', colors.ink, 2.5),
    ),
    elephant: animal(
        '#B7B0D8',
        ellipse(25, 61, 18, 26, '#B7B0D8') + ellipse(95, 61, 18, 26, '#B7B0D8'),
        '',
        eyes(54, 15) + path('M49 68v29q0 16 17 8l9-7-7-8-7 6V68Z', '#B7B0D8'),
    ),
    deer: animal(
        peach,
        line(
            'M37 40 28 20m0 0-10-4m10 4 3-13m52 33 9-20m0 0 10-4m-10 4L89 7',
            '#B69B7D',
            4,
        ) +
            ellipse(29, 48, 13, 7, peach) +
            ellipse(91, 48, 13, 7, peach),
        circle(39, 75, 2, cream) + circle(81, 75, 2, cream),
    ),
    goat: animal(
        cream,
        path('M32 48q-22-6-17-16l27 5m35 1 24-7q7 13-16 18', peach) +
            line('M40 37Q29 8 48 15m30 22q10-29-8-22', '#B39B7F', 7),
        path('m48 88 12 18 12-18', cream),
    ),
    sheep:
        circle(29, 52, 16, cream) +
        circle(42, 32, 17, cream) +
        circle(67, 28, 17, cream) +
        circle(89, 43, 17, cream) +
        circle(93, 68, 17, cream) +
        circle(79, 89, 17, cream) +
        circle(49, 93, 17, cream) +
        circle(28, 77, 17, cream) +
        ellipse(60, 63, 25, 29, '#C3ACCC') +
        eyes(60, 12) +
        muzzle(77),
    seal:
        path('M34 84Q7 99 17 102l30-7m35-11q31 15 20 20L72 96', blue) +
        ellipse(60, 76, 30, 28, blue) +
        circle(60, 48, 29, blue) +
        eyes(48, 12) +
        muzzle(65),
    bird: bird(violet),
    chicken: bird(
        cream,
        circle(48, 19, 7, coral) +
            circle(60, 16, 8, coral) +
            circle(71, 20, 6, coral),
    ),
    duck:
        bird(yellow) +
        ellipse(60, 56, 16, 6, '#F2A95E') +
        line('M48 56q12 6 24 0', '#C77F55', 1.5),
    penguin:
        ellipse(60, 65, 30, 39, purple) +
        path('M33 56 17 82l20-6m48-20 17 26-20-6', purple) +
        ellipse(60, 71, 22, 30, cream) +
        ellipse(44, 102, 14, 5, peach) +
        ellipse(77, 102, 14, 5, peach) +
        eyes(45, 12) +
        path('m52 56 8 8 8-8Z', peach),
    unicorn: animal(
        cream,
        pointEars(cream) + path('m56 34 7-26 10 29Z', yellow),
        path(
            'M28 60Q15 26 48 31l5 15q-23-5-14 29M76 33q28 4 20 37l-14-9',
            violet,
        ),
    ),
    ant:
        line(
            'm45 61-18-15m22 23-24 1m23 8-19 20m45-37 18-15m-22 23 24 1m-23 8 19 20M49 36l-8-15m30 15 8-15',
            purple,
            3,
        ) +
        ellipse(60, 88, 16, 19, purple) +
        circle(60, 65, 13, violet) +
        circle(60, 42, 20, violet) +
        eyes(42, 8),
    snail:
        path(
            'M23 88q0-10 12-10h46V60q0-11 12-11t12 11v29q0 12-17 12H28q-14-1-5-13Z',
            green,
        ) +
        circle(52, 66, 28, peach) +
        line(
            'M56 83q-25 0-22-20 3-18 22-15 20 3 13 20-4 10-14 4-7-4 1-9',
            '#B5877B',
            3,
        ) +
        line('M86 50V34m15 17 5-16', teal, 3) +
        circle(86, 34, 3, colors.ink) +
        circle(106, 35, 3, colors.ink),
    bee:
        ellipse(60, 36, 17, 22, '#D8F0F3') +
        ellipse(82, 42, 17, 22, '#D8F0F3') +
        ellipse(61, 68, 34, 24, yellow) +
        path(
            'M48 47q-10 21 0 42m19-43q-10 22 0 44',
            'none',
            'stroke="#9B7E6C" stroke-width="9"',
        ) +
        circle(30, 63, 17, yellow) +
        circle(26, 59, 3, colors.ink) +
        path('m94 65 12 5-13 7Z', '#9B7E6C'),
    butterfly:
        path(
            'M55 60Q6 2 16 58q-16 38 36 25m14-23q49-58 39-2 16 38-36 25',
            violet,
        ) +
        path('M44 55Q20 23 25 53m49 2q24-32 19-2', blue, 'stroke="none"') +
        ellipse(60, 67, 7, 29, purple) +
        line('M57 42 49 28m14 14 8-14', purple, 3),
    snake:
        path(
            'M25 90q-20-32 11-32h32q20 0 20-18V30',
            'none',
            `stroke="${green}" stroke-width="22"`,
        ) +
        ellipse(86, 29, 18, 15, green) +
        circle(88, 25, 3, colors.ink) +
        line('m102 32 9 3m-1 0 4-3m-4 3 2 5', coral, 2),
    turtle:
        ellipse(60, 69, 34, 25, green) +
        circle(98, 59, 13, green) +
        ellipse(35, 92, 12, 6, teal) +
        ellipse(77, 92, 12, 6, teal) +
        path('M27 74q3-48 39-41 26 5 29 41Z', teal) +
        line('m48 38-9 22 10 15h22l11-16-12-22m-31 23h43m-22-17v31', green, 2) +
        circle(102, 55, 3, colors.ink),
    dinosaur:
        path(
            'M22 80Q7 66 10 53q13 18 27 11 6-24 31-20V28q-2-15 16-16 24-1 23 18v10H87v38q0 15-14 16v13H59V93H46v14H32V88Z',
            green,
        ) +
        path('m39 48 1-12 11 8 6-16 10 16', violet) +
        circle(91, 26, 3, colors.ink),
    fish:
        path('M84 57 108 37v48L84 69', blue) +
        ellipse(53, 61, 36, 25, blue) +
        path('M41 38 58 23l7 15m-24 46 18 13 7-15', violet) +
        circle(35, 56, 6, colors.ink) +
        circle(37, 54, 2, white) +
        line('M26 69q5 5 10 0M57 49q10 12 0 24m10-23q10 11 0 21', teal, 2.5) +
        circle(13, 38, 4, '#D9F3F5') +
        circle(20, 28, 2.5, '#D9F3F5'),
    shark:
        path(
            'M14 74Q27 45 61 45L70 21l11 26 20-15-5 31 10 21-26-7q-33 22-66-3Z',
            '#97BDD1',
        ) +
        path('M15 74q36 23 66-4l-10 15H36Z', cream) +
        circle(32, 65, 3, colors.ink) +
        line('m52 59-3 10m11-11-3 10', '#648FA7', 2),
    soap:
        rect(26, 48, 68, 43, '#C8ADE7', 17) +
        ellipse(59, 66, 23, 9, '#E4D3F4') +
        circle(85, 30, 11, '#E1F3F4') +
        circle(37, 30, 7, '#DCEEF4') +
        circle(101, 45, 5, '#E1F3F4'),
    vegetables:
        path(
            'M59 99Q9 82 22 48q6-14 19-4 9-30 24-13 15-23 25 3 26-5 18 24-3 28-49 41Z',
            green,
        ) + line('M60 96 62 42m-2 42L35 64m27 9 23-24', teal, 3),
    milk:
        path('M33 36 45 18h31l12 18v66H33Z', cream) +
        path('M33 36h55v19H33Z', blue) +
        path('M45 18v18m31-18v18', 'none') +
        path('M60 63q-22 23 0 27 21-4 0-27Z', blue),
    spoon:
        ellipse(60, 33, 19, 25, blue) +
        rect(55, 51, 10, 58, '#B5A6CB', 5) +
        ellipse(57, 28, 9, 13, '#D8EEF3'),
    fork: path(
        'M42 16v27q0 16 18 16t18-16V16M54 16v26m12-26v26M60 59v48',
        'none',
        `stroke="${blue}" stroke-width="7"`,
    ),
    pineapple:
        ellipse(60, 73, 28, 35, yellow) +
        path('M60 43 39 17l20 7L68 8l6 21 18-2-19 22Z', green) +
        line(
            'm38 58 42 37m-46-21 28 26m-11-53 32 29m-3-22L40 92m46-20-28 30',
            '#C7A572',
            1.6,
        ),
    paper:
        path('M32 14h40l20 21v70H32Z', cream) +
        path('M72 14v22h20', blue) +
        line('M45 52h33M45 65h33M45 78h22', '#B6A3C8', 3),
    fan:
        path('M53 71h14l5 29H48Z', cream) +
        rect(36, 99, 48, 8, violet, 4) +
        circle(60, 47, 35, '#E5F2F1') +
        path(
            'M60 46Q33 13 45 17q24-5 21 28M60 47q41-10 33 6-12 17-32 1M59 49q-3 39-19 29-13-17 12-28',
            blue,
        ) +
        circle(60, 48, 8, purple),
    car: car(),
    bus:
        rect(14, 31, 92, 54, yellow, 12) +
        rect(22, 40, 16, 21, blue, 3) +
        rect(45, 40, 16, 21, blue, 3) +
        rect(68, 40, 16, 21, blue, 3) +
        rect(90, 40, 10, 35, blue, 3) +
        line('M16 71h68', peach, 3) +
        wheels(),
    truck: truck(),
    van:
        path('M16 38q0-7 10-7h51q9 0 13 10l15 24v20H16Z', green) +
        rect(24, 41, 24, 23, blue, 4) +
        path('M60 41h17l15 23H60Z', blue) +
        wheels(),
    cake:
        rect(26, 60, 68, 39, peach, 7) +
        path(
            'M25 63q0-10 12-10h47q12 0 12 10v8q-7 13-14 0-7 14-15 0-8 13-16 0-8 14-17 0-9 8-9-8Z',
            cream,
        ) +
        line('M42 51V34m18 17V29m18 22V34', violet, 5) +
        path(
            'M42 21q-13 12 0 13 12-1 0-13m18-5q-13 12 0 13 12-1 0-13m18 5q-13 12 0 13 12-1 0-13',
            yellow,
        ) +
        ellipse(60, 103, 44, 4, blue),
    foot:
        path(
            'M53 46Q31 42 32 74q-13 30 11 32 22 2 25-21 26-12 20-25-5-9-16 0L65 42Z',
            peach,
        ) +
        ellipse(63, 29, 10, 15, peach) +
        ellipse(45, 30, 7, 11, peach) +
        ellipse(32, 38, 6, 9, peach) +
        ellipse(23, 49, 5, 8, peach),
    key:
        circle(39, 45, 23, yellow) +
        circle(39, 45, 9, cream) +
        path('m55 60 35 35 9-9-9-9 8-8-10-10-7 7-14-14Z', yellow),
    shoe:
        path(
            'M20 50q22 4 31-15l15 22 32 13q11 4 9 19H15V63q0-10 5-13Z',
            violet,
        ) +
        path('M15 84h92v13H15Z', cream) +
        line('m54 55 11-7m-3 15 11-7m-3 15 11-7', cream, 4),
    tooth:
        path(
            'M26 46Q13 13 41 14q19 8 38 0 29-1 17 35-10 17-11 41-4 24-16 8l-8-25-9 25q-13 20-18-9-2-26-8-43Z',
            cream,
        ) +
        path('M39 27q9 6 19 5', 'none', 'stroke="#FFFFFF" stroke-width="7"'),
    guitar:
        path('m65 16 13 3-14 50-14-3Z', '#B39BC8') +
        path('M48 53q-27-7-22 15-29 21-3 35 22 17 35-10 27-6 10-25Z', peach) +
        circle(44, 79, 10, purple) +
        line('M44 80 71 23m-31 70 13 4', cream, 3),
    mountain:
        path('m10 99 34-62 21 31 16-46 30 77Z', '#A8B9CD') +
        path('m32 59 12-22 15 22-9-4-6 6-5-6m32-7 10-26 12 31-12-7Z', cream),
    egg:
        path('M30 75Q27 43 60 15q32 29 30 60-1 30-30 30T30 75Z', cream) +
        path('M43 60q0-14 12-24', 'none', 'stroke="#FFFFFF" stroke-width="6"'),
    hand:
        path(
            'M39 99 19 74q-6-11 4-14l17 14V36q0-12 10-7V19q0-13 10-7 4 0 4 10v-5q12-8 12 7v9q12-7 12 8v36q0 32-30 31Z',
            peach,
        ) + line('M50 29v27m14-34v34m12-23v27', '#C8947E', 1.8),
    tomato:
        circle(60, 69, 35, coral) +
        path('m60 42-21-8 16-3 7-15 5 16 17 2-16 10Z', green) +
        path('M36 63q1-10 11-14', 'none', 'stroke="#F5C4B5" stroke-width="5"'),
    watermelon:
        path('M16 40h88q-2 63-44 64T16 40Z', green) +
        path('M24 40h72q-3 52-36 53T24 40Z', cream) +
        path('M31 40h58q-2 43-29 44T31 40Z', coral) +
        line('m43 49 3 5m28-5-3 5m-13 9 1 6', colors.ink, 2.6),
    television:
        rect(15, 33, 90, 62, violet, 10) +
        rect(23, 41, 60, 45, '#DCEFF0', 7) +
        circle(94, 54, 4, yellow) +
        circle(94, 70, 3, cream) +
        line('m42 32-9-15m30 15 14-16m-40 82-5 10m52-10 5 10', purple, 4),
    medicine:
        path(
            'M36 84q-17-17-1-34l24-24q17-17 34 0t0 34L69 84q-17 17-33 0Z',
            cream,
        ) +
        path('M47 39 81 73l12-13q17-17 0-34T59 26Z', violet) +
        line('m47 39 34 34', purple, 2),
    sea:
        path(
            'M12 51q12-24 24 0 12-24 24 0 12-24 24 0 12-24 24 0v45H12Z',
            blue,
        ) +
        line(
            'M15 67q10-15 22 0 12-15 23 0 12-15 23 0 12-15 22 0M16 84q10-12 21 0 12-12 23 0 12-12 23 0 12-12 22 0',
            '#D9F0F2',
            3,
        ),
    hair:
        ellipse(60, 60, 27, 33, peach) +
        path(
            'M31 78Q11 19 52 17q43-14 46 30l-5 40-17-8 3-34q-19 4-31-9-1 17-17 20Z',
            '#977766',
        ) +
        eyes(58, 10),
    leaf: leaf(),
    dice:
        rect(26, 26, 68, 68, cream, 14) +
        circle(43, 43, 5, purple) +
        circle(77, 43, 5, purple) +
        circle(60, 60, 5, purple) +
        circle(43, 77, 5, purple) +
        circle(77, 77, 5, purple),
    purse:
        path('M20 45h80l7 53H13Z', violet) +
        path(
            'M39 45V31q0-22 21-22t21 22v14',
            'none',
            `stroke="${purple}" stroke-width="6"`,
        ) +
        circle(54, 44, 5, yellow) +
        circle(66, 44, 5, yellow),
    doctor: person(
        '#886552',
        cream,
        path('M48 75 59 95l13-20', blue) +
            line('M40 80v12q0 8 9 8m31-20v12q0 8-9 8', teal, 2) +
            path(
                'M84 81v13m-6-6h12',
                coral,
                'stroke="#E9958F" stroke-width="3"',
            ),
    ),
    lamp:
        path(
            'M60 67v33m-20 4h40',
            'none',
            `stroke="${purple}" stroke-width="6"`,
        ) +
        path('M39 22h42l18 48H21Z', yellow) +
        path('M60 70v14', 'none', `stroke="${peach}" stroke-width="4"`),
    orange:
        circle(60, 69, 34, peach) +
        path(
            'M60 36q-5-20 13-26',
            'none',
            `stroke="${teal}" stroke-width="4"`,
        ) +
        path('M65 24q20-19 29-5-9 17-29 12Z', green),
    candle:
        rect(42, 49, 36, 52, cream, 5) +
        path('M60 42Q37 28 61 8q23 28-1 34Z', yellow) +
        line('M60 49V38', purple, 3) +
        ellipse(60, 104, 30, 4, violet) +
        path(
            'M43 61q9 15 16 0 9 17 18-1',
            'none',
            `stroke="${peach}" stroke-width="4"`,
        ),
    basket:
        path('M20 55h80l-10 46H31Z', peach) +
        path(
            'M35 55q0-49 25-49t25 49',
            'none',
            'stroke="#B89479" stroke-width="6"',
        ) +
        line('M24 70h73M28 86h65M43 57l4 43m17-43v43m17-43-4 43', '#C59878', 2),
    bottle:
        rect(37, 37, 46, 68, '#DDF0EF', 12) +
        rect(42, 27, 36, 14, violet, 4) +
        path('M50 27V17q10-13 20 0v10Z', peach) +
        line('M68 53h11M68 65h11M68 77h11', teal, 2),
    ship:
        path('M10 73h100L92 99H30Z', blue) +
        rect(32, 43, 56, 29, cream, 3) +
        rect(42, 27, 30, 16, violet, 2) +
        rect(53, 11, 12, 16, peach, 2) +
        circle(43, 57, 5, blue) +
        circle(62, 57, 5, blue) +
        circle(80, 57, 5, blue) +
        line('M12 106q12-9 24 0 12-9 24 0 12-9 24 0 12-9 24 0', teal, 3),
    house:
        path('M26 53h68v51H26Z', cream) +
        path('m13 56 47-42 47 42Z', violet) +
        rect(51, 71, 20, 33, peach, 3) +
        rect(33, 68, 12, 14, blue, 2) +
        rect(77, 68, 10, 14, blue, 2) +
        path('M80 30V15h11v26', purple),
    bread:
        path(
            'M27 45q-20-13-7-26 12-12 24-4 14-13 30-3 21-6 29 10 8 15-11 24v56H27Z',
            peach,
        ) + path('M36 49q-10-13-2-22h47q10 8 0 22v43H36Z', cream),
    king: person(
        '#886552',
        violet,
        path('M36 28 28 7l21 12L60 3l12 16L93 7l-8 23Z', yellow) +
            circle(60, 21, 3, coral),
    ),
    rocket:
        path('M43 72 24 83V62l19-19m35 29 18 11V62L78 43', violet) +
        path('M43 87Q28 39 60 11q34 29 18 76Z', cream) +
        path('M42 85h37v9H42Z', purple) +
        circle(60, 48, 13, blue) +
        path('m49 97 11 19 11-19Z', peach) +
        path('m55 97 5 11 5-11Z', yellow),
    water:
        path('M60 13Q7 72 32 96q28 25 55-1Q111 72 60 13Z', blue) +
        path('M37 74q-1 13 10 17', 'none', 'stroke="#DAF4F3" stroke-width="5"'),
    beach:
        path('M9 92q53-26 102 0v16H9Z', yellow) +
        line('M65 79V28', purple, 3) +
        path('M25 46q39-60 77 0Z', violet) +
        path('M44 46q20-59 39 0Z', blue) +
        ellipse(38, 94, 8, 4, cream),
    umbrella:
        path('M20 51q40-75 80 0Z', violet) +
        path('M43 51q18-75 35 0Z', blue) +
        path(
            'M60 52v41q0 22-18 10',
            'none',
            `stroke="${purple}" stroke-width="5"`,
        ),
    nose:
        path(
            'M60 21Q40 45 41 65q-22 14-6 24 13 8 25-1 15 10 27 0 15-15-8-24-1-26-19-43Z',
            peach,
        ) +
        path(
            'M42 79q7-6 11 2m17-1q7-6 11 0',
            'none',
            'stroke="#BA8577" stroke-width="2"',
        ),
    corn:
        path('M54 99Q25 61 42 31q20-29 37 0 15 27-13 68Z', yellow) +
        line(
            'M50 26v56m12-62v67m12-58v45M42 39h37M40 52h40M42 65h35',
            '#D4AF70',
            1.8,
        ) +
        path(
            'M59 107Q18 91 22 49q26 12 37 58m2 0q37-11 37-53-25 5-37 53Z',
            green,
        ),
    shampoo:
        rect(37, 38, 46, 68, violet, 12) +
        rect(45, 22, 30, 17, purple, 3) +
        path('M52 20V12h34', 'none', `stroke="${purple}" stroke-width="6"`) +
        ellipse(60, 73, 16, 20, cream) +
        path('M60 59q-17 22 0 26 17-4 0-26Z', blue),
    coin:
        circle(60, 61, 38, yellow) +
        circle(60, 61, 29, '#F7DEAA') +
        path('m60 39 6 13 15 2-11 10 3 15-13-7-13 7 3-15-11-10 15-2Z', peach),
    banana:
        path('M27 23q-13 66 46 69 30-1 28-40-9 24-30 24Q42 73 39 26Z', yellow) +
        path('M27 23 29 13l9 2 1 11', '#B89975') +
        line('M36 39q-5 48 47 45', '#D0AE76', 2),
    door:
        rect(30, 12, 61, 96, purple, 3) +
        rect(36, 18, 49, 90, peach, 2) +
        rect(44, 27, 33, 29, cream, 2) +
        rect(44, 69, 33, 28, cream, 2) +
        circle(77, 62, 3, yellow),
    pencil:
        path('m29 84 48-63 17 13-48 63-21 11Z', yellow) +
        path('m77 21 6-8q3-4 7-1l11 8q4 3 1 7l-8 7Z', coral) +
        path('m29 84 17 13-21 11Z', cream) +
        path('m26 99-1 9 8-5Z', colors.ink) +
        line('m36 89 48-62', cream, 3),
    tree:
        path('M51 62h18v45H51Z', '#BC947D') +
        circle(39, 52, 25, green) +
        circle(78, 52, 25, green) +
        circle(60, 31, 27, green) +
        line('M60 84V52m0 19L45 58m15 5 16-17', teal, 3),
    ball:
        circle(60, 62, 40, cream) +
        path(
            'm60 43 17 12-7 20H50l-7-20Zm-33-4 17-7-5-12m54 19-17-7 5-12m-45 68 6-18-16-4m68 22-6-18 16-4m-50 33 6-13 7 13',
            violet,
        ) +
        line(
            'm44 32 7 16m25-16-7 16M25 66l19-7m51 7-19-7m-34 29 10-13m26 13L68 75',
            '#B5A4CB',
            2,
        ),
    flower: flower(),
    book:
        path(
            'M60 32Q36 18 12 29v66q26-12 48 2 24-14 48-2V29q-25-11-48 3Z',
            cream,
        ) +
        path('M60 32v65', 'none', `stroke="${purple}" stroke-width="3"`) +
        line(
            'M23 43q13-4 26 2m-26 12q13-4 26 2m-26 12q13-4 26 2m23-28q13-6 25-2m-25 16q13-6 25-2m-25 16q13-6 25-2',
            '#BEA5D7',
            2.5,
        ),
    shirt:
        path(
            'm40 23-28 20 15 22 15-9v49h37V56l15 9 15-22-29-20q-20 17-40 0Z',
            blue,
        ) +
        path('M43 25q17 23 34 0', 'none', `stroke="${cream}" stroke-width="5"`),
    mango:
        path('M86 31q30 37-9 68-43 30-53-7-10-35 28-61 16-11 34 0Z', peach) +
        path('M73 28q7-21 21-17 9 16-13 21Z', green) +
        path('M44 80q-5 15 8 20', 'none', 'stroke="#F7D9AF" stroke-width="4"'),
    mouth:
        path(
            'M15 61 42 43q13-9 18 0 7-9 19 0l26 18-22 21q-23 18-46 0Z',
            coral,
        ) +
        path('M25 62q35 10 70 0-34 29-70 0Z', '#8C5B71') +
        path('M27 60h66l-9 10H37Z', cream),
    coffee: cup(),
    tea:
        cup('#DDEBDD') +
        line('M79 47v29h13', purple, 1.5) +
        rect(85, 74, 12, 14, green, 2),
    cap:
        path('M24 73V58q0-37 37-37 33 0 37 38v14Z', blue) +
        path('M22 70q69-11 85 4l-3 13q-49 9-82-5Z', violet) +
        line('M60 23v38', teal, 2),
    hammer:
        path('m51 52 13 7-23 50-13-7Z', peach) +
        path('m43 20 43 20-10 22-23-11-13 3-14-7Z', '#B1A4C6'),
    lungs:
        path(
            'M55 25v32Q34 31 26 54L16 90q-1 24 36 7V58m13-33v32q21-26 29-3l10 36q1 24-36 7V58',
            '#E7ACB0',
        ) +
        path(
            'M60 11v43m0-2L39 73m21-21 20 21',
            'none',
            'stroke="#BA8299" stroke-width="5"',
        ),
    eye:
        path('M10 61q50-63 100 0-50 61-100 0Z', cream) +
        circle(60, 61, 25, blue) +
        circle(60, 61, 13, purple) +
        circle(66, 53, 5, white),
    brick:
        path('m17 48 57-17 31 18-57 19Z', coral) +
        path('M17 48v34l31 18V68Zm31 20 57-19v33l-57 18Z', '#D58E89') +
        line('M33 57v30m35-27v31m-21-7 58-19', '#EAB2A6', 2),
    moon:
        path(
            'M76 14Q29 11 23 55q-5 47 42 51 25 0 38-22-41 11-52-20-12-31 25-50Z',
            yellow,
        ) +
        circle(81, 37, 3, violet) +
        path('m99 48 3 6 7 1-5 5 1 7-6-3-6 3 1-7-5-5 7-1Z', violet),
    circle:
        circle(60, 61, 39, '#C5ABE8') +
        path('M34 52q3-18 19-20', 'none', 'stroke="#E9DDF8" stroke-width="6"'),
    father: person(),
    girl: person(
        '#8B6D5F',
        blue,
        path('M37 37Q10 30 21 66l16-7m47-22q24-9 15 29L85 58', '#8B6D5F') +
            circle(29, 40, 6, coral) +
            circle(92, 40, 6, coral),
    ),
    sun:
        circle(60, 60, 27, yellow) +
        line(
            'M60 9v13m0 76v13M9 60h13m76 0h13M24 24l9 9m54 54 9 9M24 96l9-9m54-54 9-9',
            '#D7AF70',
            5,
        ),
    sock:
        path(
            'M48 13h39v58q0 14-15 21l-26 15q-25 11-29-11-3-14 13-23l18-10Z',
            violet,
        ) +
        path('M48 13h39v18H48Z', cream) +
        path('m20 83 23 19q-20 13-26-3-4-8 3-16Z', blue),
    saw:
        path(
            'M15 85 81 29l18 21-7 12-10-1-3 10-10-1-3 10-10-1-3 10-10-1-3 10Z',
            '#C7BDD4',
        ) +
        path('m76 23 12-9q8-4 14 4l12 16q4 9-3 14l-12 8Zm13 1 14 17', peach),
    clock:
        circle(60, 62, 39, cream) +
        circle(60, 62, 33, '#E8E1F2') +
        line('M60 35v28l19 13M60 30v3m0 58v3M28 62h3m58 0h3', purple, 3) +
        circle(60, 62, 4, purple),
    gift:
        rect(22, 48, 76, 54, violet, 5) +
        rect(17, 38, 86, 18, '#C9B2E8', 4) +
        rect(53, 38, 14, 64, cream, 1) +
        path('M60 37Q24 42 34 22q13-15 26 15 14-30 26-15 11 20-26 15Z', peach),
    ghost:
        path(
            'M29 98V51q0-36 31-36t31 36v47L80 88l-10 10-10-10-10 10-10-10Z',
            cream,
        ) +
        eyes(51, 12) +
        ellipse(60, 71, 6, 8, violet),
    tent:
        path('m11 100 49-85 49 85Z', peach) +
        path('M60 15v85h49Z', violet) +
        path('m31 100 29-52 1 52Z', cream) +
        line('M60 15V7m-48 94-6 6m103-6 6 6', purple, 3),
    drum:
        ellipse(60, 48, 38, 15, cream) +
        path('M22 48v43q38 26 76 0V48q-38 22-76 0Z', coral) +
        line('m26 56 14 38 20-31 19 31 15-38', cream, 2) +
        line('M31 17 69 45m16-28L51 45', peach, 5),
    lemon:
        path(
            'M23 64Q18 30 58 29q29-5 35 25l6 9-7 7q-4 24-34 24-30 2-35-24l-8-3Z',
            yellow,
        ) + path('M67 29q10-22 27-13-5 22-27 20Z', green),
    rain:
        circle(47, 40, 20, '#D9D3E9') +
        circle(72, 34, 24, '#D9D3E9') +
        rect(24, 42, 77, 22, '#D9D3E9', 11) +
        line(
            'm35 76-5 13m28-13-5 13m28-13-5 13m23-13-5 13m-45 11-3 8m27-8-3 8',
            blue,
            5,
        ),
    ring:
        circle(60, 75, 28, yellow) +
        circle(60, 75, 19, cream) +
        path('m37 28 12-15h23l12 15-24 27Z', blue) +
        line('M37 28h47M49 13l11 42 12-42', teal, 1.5),
    fire:
        path(
            'M60 12q8 28 21 29l2-14q38 55 6 73-33 21-58-1Q6 78 35 42l1 20q23-22 24-50Z',
            peach,
        ) + path('M60 58q-32 39-4 44 29 1 4-44Z', yellow),
    chair:
        rect(35, 15, 51, 47, violet, 8) +
        rect(27, 66, 67, 13, peach, 4) +
        line('M36 79v28m49-28v28M42 61v6m37-6v6', '#A98F80', 6),
    cheese:
        path('m22 53 64-29 19 53-83 24Z', yellow) +
        path('m22 53 83 24-83 24Z', '#E4BE7B') +
        circle(67, 51, 6, '#F8DFAB') +
        circle(82, 67, 5, '#F8DFAB') +
        ellipse(39, 78, 6, 5, '#CDA56D') +
        ellipse(66, 85, 5, 4, '#CDA56D'),
    cherry:
        circle(37, 80, 21, coral) +
        circle(85, 80, 21, coral) +
        path(
            'M37 59q23-16 25-44 2 30 23 44',
            'none',
            `stroke="${teal}" stroke-width="4"`,
        ) +
        path('M62 22Q80 4 96 19 84 37 62 22Z', green),
    coat:
        path(
            'M43 19 20 39 8 76l18 9 14-29v50h43V56l12 29 18-9-14-37-23-20Z',
            violet,
        ) +
        path('m43 19 17 21-13 13-7-20m36-14L60 40l14 13 9-20', cream) +
        line('M60 40v66', purple, 2) +
        circle(66, 67, 2, yellow) +
        circle(66, 83, 2, yellow),
    pear:
        path('M50 26q9-8 19 0l10 29q42 46-19 51-60-4-19-51Z', '#C2D3A2') +
        path('M60 27q-3-17 9-19', 'none', 'stroke="#A78B6D" stroke-width="4"') +
        path('M64 20q11-18 29-8-10 18-29 14Z', green),
    pin:
        path('m57 70-32 40 42-33', '#AFA1C5') +
        path('m56 28 35 29-10 12-11-2-15 18-25-21 15-18-2-11Z', coral) +
        path('m60 13 43 35-13 13-43-35Z', '#F0AEA2'),
    bin:
        path('M29 36h63l-7 70H37Z', blue) +
        rect(24, 25, 73, 12, teal, 3) +
        path('M46 25v-9h29v9', 'none', `stroke="${teal}" stroke-width="5"`) +
        line('M46 48v43m16-43v43m15-43v43', '#D5EFEE', 4),
    rock:
        path('m15 81 17-42 24-14 35 9 18 42-20 25H36Z', '#BCB2C8') +
        path(
            'm32 39 26 27 33-32m-33 32-22 35m22-35 31 35',
            'none',
            'stroke="#D9D0E1" stroke-width="3"',
        ),
    lock:
        path(
            'M37 52V34q0-25 23-25t23 25v18',
            'none',
            `stroke="${purple}" stroke-width="9"`,
        ) +
        rect(25, 48, 70, 57, yellow, 10) +
        circle(60, 71, 8, purple) +
        rect(56, 74, 8, 15, purple, 3),
    three: path(
        'M36 28Q76 1 87 34q4 18-18 23 29 2 23 27-11 37-57 14',
        'none',
        `stroke="${violet}" stroke-width="17"`,
    ),
    apple:
        path(
            'M59 40Q30 20 21 55q-6 32 21 48 10 4 18-2 8 6 19 2 25-16 20-48-8-34-40-15Z',
            coral,
        ) +
        path('M60 38q-6-22 9-29', 'none', 'stroke="#A88C74" stroke-width="4"') +
        path('M64 27q14-23 31-10-9 22-31 17Z', green),
    balloon: balloon(),
    cloud:
        circle(43, 57, 22, cream) +
        circle(70, 44, 28, cream) +
        circle(94, 61, 17, cream) +
        rect(20, 60, 88, 24, cream, 12),
    trophy:
        path(
            'M37 27H18q-2 33 26 32m39-32h19q2 33-26 32',
            'none',
            `stroke="${peach}" stroke-width="7"`,
        ) +
        path('M36 19h48v32q0 26-24 26T36 51Z', yellow) +
        line('M60 77v19', peach, 8) +
        rect(36, 96, 48, 10, violet, 4) +
        path('m60 32 5 10 11 2-8 8 2 11-10-5-10 5 2-11-8-8 11-2Z', cream),
    star: star(),
    sparkle: star('#D0B4EF'),
    rainbow:
        path(
            'M15 99V66a45 45 0 0 1 90 0v33',
            'none',
            `stroke="${coral}" stroke-width="12"`,
        ) +
        path(
            'M28 99V66a32 32 0 0 1 64 0v33',
            'none',
            `stroke="${yellow}" stroke-width="12"`,
        ) +
        path(
            'M41 99V66a19 19 0 0 1 38 0v33',
            'none',
            `stroke="${blue}" stroke-width="12"`,
        ),
    party:
        path('m17 105 19-64 48 47Z', violet) +
        line('m27 73 27 26m-20-46 38 36', cream, 4) +
        path(
            'M51 29q24 12 26-14m7 32q-1-24 20-13',
            'none',
            `stroke="${peach}" stroke-width="5"`,
        ) +
        circle(98, 64, 5, blue) +
        circle(35, 21, 4, coral) +
        path('m93 87 7 2-2 7-7-2Z', yellow),
    fire_engine: truck(
        coral,
        line('M21 30h39m-39-7h39M27 23v7m12-7v7m12-7v7', '#B3A2C8', 3) +
            rect(73, 45, 12, 6, blue, 2),
    ),
    police: car(
        '#B4A7CF',
        rect(53, 26, 14, 8, coral, 3) +
            path(
                'm43 70 5 8 10-2-4 10 7 7-11 1-4 9-6-8-10 1 4-10-6-8Z',
                cream,
                'transform="translate(14 -5) scale(.75)"',
            ),
    ),
    ambulance: truck(
        cream,
        line('M39 50v22m-11-11h22', coral, 6) + rect(73, 45, 12, 6, coral, 2),
    ),
    tractor:
        path('M28 55h44V25H48V12h33v52h23v22H28Z', green) +
        rect(53, 32, 19, 24, blue, 3) +
        circle(39, 85, 22, colors.ink) +
        circle(39, 85, 12, peach) +
        circle(92, 91, 14, colors.ink) +
        circle(92, 91, 6, peach),
    train:
        rect(28, 37, 55, 51, violet, 8) +
        rect(67, 17, 28, 37, blue, 3) +
        rect(20, 24, 14, 27, peach, 2) +
        rect(18, 19, 20, 8, purple, 3) +
        circle(37, 90, 13, colors.ink) +
        circle(74, 90, 13, colors.ink) +
        circle(37, 90, 5, cream) +
        circle(74, 90, 5, cream) +
        line('M37 90h37', peach, 5) +
        path('m90 77 18 18H85Z', peach),
    plane:
        path(
            'M54 98V67L12 79V64l42-24V18q7-17 14 0v22l41 24v15L68 67v31l14 7v6l-21-5-21 5v-6Z',
            blue,
        ) + path('M56 18h10v21H56Z', cream),
    helicopter:
        line('M60 18H15m45 0h45M60 18v16', purple, 4) +
        path('M35 45 10 34v25l27 10', violet) +
        ellipse(69, 60, 37, 25, violet) +
        path('M72 38q31-3 31 27H72Z', blue) +
        line('M49 83v16m37-16v16M36 100h64', purple, 4),
    sailboat:
        path('M13 83h96l-21 22H32Z', violet) +
        line('M60 15v69', purple, 3) +
        path('M55 21 15 75h40Z', cream) +
        path('m66 35 34 40H66Z', blue),
    bicycle:
        circle(28, 82, 23, '#F3EBF5') +
        circle(93, 82, 23, '#F3EBF5') +
        path(
            'm28 82 21-37 20 37H28l44-27m21 27L76 30H64M40 42h19m17-12h14',
            'none',
            `stroke="${teal}" stroke-width="4"`,
        ),
    ufo:
        ellipse(60, 51, 29, 27, '#D9F0EA') +
        ellipse(60, 65, 48, 16, violet) +
        ellipse(60, 78, 23, 7, '#C9B5E2') +
        circle(30, 65, 4, yellow) +
        circle(60, 69, 4, yellow) +
        circle(90, 65, 4, yellow),
    earth:
        circle(60, 60, 42, blue) +
        path(
            'm30 31 18-8 8 9-12 11 5 18-16 7-11-18m46-25 18 8 9 22-19 9-14-14 6-13M58 74l16 5 5 13-16 9-12-14Z',
            green,
            'stroke="none"',
        ),
    comet:
        path('m31 83 58-68-18 43 37-17-51 54Z', peach) +
        circle(37, 82, 22, yellow) +
        path('m48 57 40-42', 'none', `stroke="${coral}" stroke-width="3"`),
    planet:
        circle(60, 59, 32, violet) +
        path(
            'M31 40Q-9 61 18 76t73-19q35-35 2-27',
            'none',
            `stroke="${peach}" stroke-width="10"`,
        ) +
        line('M45 36h20m-9 14h26', '#DBC6F2', 3),
    alien:
        path('M24 47q0-31 36-31t36 31q0 29-36 56-36-27-36-56Z', green) +
        ellipse(43, 54, 10, 16, purple) +
        ellipse(77, 54, 10, 16, purple) +
        line('M52 82q8 6 16 0', colors.ink, 2),
    satellite:
        rect(48, 40, 25, 38, cream, 4) +
        path('M15 31h25v53H15Zm66 0h25v53H81Z', blue) +
        line(
            'M15 48h25M15 66h25m41-18h25M81 66h25m-66-9h8m25 0h8M61 39V22',
            purple,
            2,
        ) +
        path('M45 17q17 24 33 0', violet),
    astronaut:
        path('M32 105V83q0-17 28-17t28 17v22Z', cream) +
        circle(60, 41, 33, cream) +
        rect(34, 22, 52, 37, blue, 14) +
        path('M44 31h16', 'none', 'stroke="#E8F8F4" stroke-width="5"') +
        rect(46, 78, 29, 20, violet, 3) +
        circle(54, 87, 3, yellow),
    quiet: bird(violet) + line('M109 33v17m-6-7h12', blue, 3),
    speaking: bird(violet) + line('M95 13q12 7 0 15m9-21q19 13 0 27', teal, 3),
    paw:
        ellipse(60, 77, 28, 23, violet) +
        ellipse(28, 51, 11, 16, violet) +
        ellipse(48, 30, 11, 16, violet) +
        ellipse(74, 30, 11, 16, violet) +
        ellipse(94, 51, 11, 16, violet),
    plate:
        circle(60, 61, 34, cream) +
        circle(60, 61, 25, '#E1EDE8') +
        line(
            'M11 31v28m-5-28v15q0 12 10 12V31m-5 27v44m95-71v71m0-71q-18 28 0 31',
            teal,
            4,
        ),
    traffic:
        rect(43, 12, 34, 82, '#B4A2C9', 10) +
        circle(60, 29, 9, coral) +
        circle(60, 53, 9, yellow) +
        circle(60, 77, 9, green) +
        line('M60 94v15', purple, 5),
    body:
        circle(60, 25, 14, peach) +
        path(
            'M42 45h36l14 31-10 5-10-21v21l7 28H65l-5-24-5 24H41l7-28V60L38 81l-10-5Z',
            violet,
        ),
    objects:
        path('m17 47 43-20 43 20-43 21Z', peach) +
        path('M17 47v43l43 20V68Zm43 21 43-21v43l-43 20Z', '#E0B996') +
        line('M60 27v21m-23-9 43 20v20', cream, 5),
    shapes:
        rect(14, 47, 44, 44, violet, 7) +
        circle(81, 73, 26, blue) +
        path('m59 13 24 37H35Z', peach),
    numbers:
        rect(20, 18, 80, 86, '#E9DCF6', 12) +
        path(
            'M34 42 44 32v30m15-23q14-17 20-2 3 8-17 24h19M37 78h41m-41 9h25',
            'none',
            `stroke="${purple}" stroke-width="5"`,
        ),
    picture:
        rect(20, 23, 80, 73, '#EEE4F5', 12) +
        path('m27 85 23-27 19 20 12-13 13 20Z', '#CBB6DF') +
        circle(78, 43, 9, '#DDBFA4'),
};

/** Story artwork uses actions, not a loosely related object, to preserve sequencing cues. */
artwork.wake =
    rect(12, 66, 96, 32, violet, 5) +
    rect(12, 43, 10, 61, peach, 3) +
    rect(98, 63, 10, 41, peach, 3) +
    rect(23, 57, 25, 14, cream, 5) +
    circle(39, 54, 10, peach) +
    path('M29 52q1-12 11-12t10 10q-9 1-15-5Z', '#795D55') +
    line('M35 55q2 2 4 0m3 0q2 2 4 0', colors.ink, 1.5) +
    path('M50 60h47v28H50Z', blue) +
    line('M47 49 60 29m-1 12 13-8', peach, 5) +
    circle(91, 25, 12, yellow) +
    line('M91 8v4m0 26v4m-17-17h4m26 0h4m-29-12 3 3m19 19 3 3', '#D9A845', 2);
artwork.brush =
    person('#886552', blue) +
    path('M90 86Q101 78 98 69', 'none', `stroke="${peach}" stroke-width="9"`) +
    path('M61 62 96 67', 'none', `stroke="${teal}" stroke-width="5"`) +
    rect(57, 56, 15, 7, white, 2) +
    circle(58, 68, 4, white) +
    circle(67, 72, 3, white) +
    circle(75, 66, 3, white) +
    line('M82 47v7m-4-3h8', blue, 2);
artwork.breakfast =
    ellipse(60, 53, 20, 23, peach) +
    path('M40 51Q35 27 58 27q23-2 23 22l-7-11q-17 8-29 0Z', '#795D55') +
    ellipse(52, 52, 2.5, 3.5, colors.ink) +
    ellipse(68, 52, 2.5, 3.5, colors.ink) +
    line('M54 61q6 5 12 0', colors.ink, 1.5) +
    path('M30 83q2-15 17-16h26q15 1 17 16Z', violet) +
    rect(11, 83, 98, 9, peach, 3) +
    path('M34 66h50q-2 24-25 24T34 66Z', blue) +
    ellipse(59, 66, 25, 7, cream) +
    circle(49, 66, 4, yellow) +
    circle(64, 66, 4, peach) +
    line('m77 65 12-22', purple, 4) +
    rect(14, 48, 17, 33, cream, 3);
artwork.school =
    rect(20, 43, 80, 62, cream, 3) +
    path('m12 44 48-31 48 31Z', violet) +
    rect(48, 77, 23, 28, peach, 2) +
    rect(29, 55, 12, 15, blue, 2) +
    rect(79, 55, 12, 15, blue, 2) +
    circle(60, 44, 11, cream) +
    line('M60 38v7h6', purple, 2) +
    path('M89 105v-9q0-10 9-10t9 10v9Z', blue) +
    circle(98, 80, 8, peach) +
    path('M90 79q0-12 9-11 8 1 7 10l-5-4-8 4Z', '#795D55');
const hands = path(
    'M16 84q10-16 28-10l20 10q18-16 34-10 10 7-2 14L67 102H34Z',
    peach,
);
const tap =
    path('M23 31h33v12h15v15H53V45H23Z', '#BEB2D0') +
    rect(29, 20, 13, 10, violet, 3) +
    line('M24 19h24', purple, 4);
artwork.wet =
    tap +
    hands +
    path('M62 55q-8 13 0 15 8-2 0-15m15 5q-8 13 0 15 8-2 0-15Z', blue) +
    line('M63 53v-8m9 12v-8', blue, 3);
artwork.add_soap =
    hands +
    rect(39, 34, 38, 38, violet, 8) +
    rect(46, 25, 24, 10, purple, 3) +
    line('M57 25v-9h24', purple, 4) +
    circle(60, 51, 7, cream) +
    path('M90 37q-8 12 0 13 8-1 0-13Z', blue);
artwork.scrub =
    hands +
    path('M30 65q-10-18 5-18l32 16q12-12 26-3l-19 23Z', peach) +
    circle(42, 39, 8, '#E0F0F1') +
    circle(71, 47, 7, '#E0F0F1') +
    circle(88, 37, 10, '#E0F0F1') +
    circle(58, 29, 5, '#E0F0F1') +
    line('M20 54q-7 5-6 13m86-12q7 5 6 13', teal, 2.5);
artwork.dry =
    rect(39, 23, 62, 77, '#D6EBEA', 6) +
    line('M49 31v62m11-62v62m11-62v62', cream, 2) +
    hands;
artwork.seed =
    ellipse(60, 94, 46, 12, '#C7AC93') +
    path('M35 93q22-22 48 0', '#A68A76') +
    ellipse(60, 79, 8, 11, yellow) +
    path('m43 27 29 15-7 10-23-8-18 3-9-9Z', peach);
artwork.watering =
    ellipse(56, 99, 43, 7, '#CCB59C') +
    path('M22 30h39v30H22Zm39 13 28 12-4 10-24-9M22 36Q4 30 9 47l13 9', blue) +
    path(
        'm86 75-4 7m13-7-4 9m-13 3-3 6',
        'none',
        `stroke="${blue}" stroke-width="3"`,
    ) +
    ellipse(62, 96, 9, 5, yellow);
artwork.sprout =
    ellipse(60, 99, 40, 8, '#C7AC93') +
    line('M60 97V49', teal, 4) +
    path('M59 67Q16 65 26 37q35-3 33 30m2-14q1-34 36-25 4 28-36 25Z', green);
artwork.sunflower = flower(yellow);

type IllustrationDefinition = {
    key: string;
    symbol: string;
    label: string;
    words?: string[];
};

export const illustrationOptions: IllustrationDefinition[] = [
    { key: 'cat', symbol: '🐱', label: 'Cat', words: ['kucing'] },
    { key: 'dog', symbol: '🐶', label: 'Dog' },
    { key: 'rabbit', symbol: '🐰', label: 'Rabbit' },
    { key: 'mouse', symbol: '🐭', label: 'Mouse', words: ['tikus'] },
    { key: 'horse', symbol: '🐴', label: 'Horse', words: ['kuda'] },
    { key: 'cow', symbol: '🐄', label: 'Cow' },
    { key: 'bear', symbol: '🐻', label: 'Bear' },
    { key: 'panda', symbol: '🐼', label: 'Panda' },
    { key: 'lion', symbol: '🦁', label: 'Lion' },
    { key: 'tiger', symbol: '🐯', label: 'Tiger' },
    { key: 'fox', symbol: '🦊', label: 'Fox' },
    { key: 'koala', symbol: '🐨', label: 'Koala' },
    { key: 'monkey', symbol: '🐒', label: 'Monkey', words: ['monyet'] },
    { key: 'frog', symbol: '🐸', label: 'Frog' },
    { key: 'elephant', symbol: '🐘', label: 'Elephant', words: ['gajah'] },
    { key: 'deer', symbol: '🦌', label: 'Deer', words: ['rusa'] },
    { key: 'goat', symbol: '🐐', label: 'Goat' },
    { key: 'sheep', symbol: '🐑', label: 'Sheep' },
    { key: 'seal', symbol: '🦭', label: 'Seal' },
    { key: 'bird', symbol: '🐦', label: 'Bird', words: ['burung'] },
    { key: 'chicken', symbol: '🐔', label: 'Chicken', words: ['ayam'] },
    { key: 'duck', symbol: '🦆', label: 'Duck' },
    { key: 'penguin', symbol: '🐧', label: 'Penguin' },
    { key: 'unicorn', symbol: '🦄', label: 'Unicorn' },
    { key: 'ant', symbol: '🐜', label: 'Ant', words: ['semut'] },
    { key: 'snail', symbol: '🐌', label: 'Snail', words: ['siput'] },
    { key: 'bee', symbol: '🐝', label: 'Bee', words: ['lebah'] },
    {
        key: 'butterfly',
        symbol: '🦋',
        label: 'Butterfly',
        words: ['rama-rama'],
    },
    { key: 'snake', symbol: '🐍', label: 'Snake', words: ['ular'] },
    { key: 'turtle', symbol: '🐢', label: 'Turtle' },
    { key: 'dinosaur', symbol: '🦕', label: 'Dinosaur' },
    { key: 'fish', symbol: '🐟', label: 'Fish' },
    { key: 'shark', symbol: '🦈', label: 'Shark' },
    { key: 'soap', symbol: '🧼', label: 'Soap', words: ['sabun'] },
    { key: 'vegetables', symbol: '🥬', label: 'Vegetables', words: ['sayur'] },
    { key: 'milk', symbol: '🥛', label: 'Milk', words: ['susu'] },
    { key: 'spoon', symbol: '🥄', label: 'Spoon', words: ['sudu'] },
    { key: 'fork', symbol: '🍴', label: 'Fork', words: ['garpu'] },
    { key: 'pineapple', symbol: '🍍', label: 'Pineapple', words: ['nanas'] },
    { key: 'paper', symbol: '📄', label: 'Paper', words: ['kertas'] },
    { key: 'fan', symbol: '🪭', label: 'Fan', words: ['kipas'] },
    { key: 'car', symbol: '🚗', label: 'Car', words: ['kereta'] },
    { key: 'bus', symbol: '🚌', label: 'Bus', words: ['bas'] },
    { key: 'truck', symbol: '🚚', label: 'Truck', words: ['lori'] },
    { key: 'van', symbol: '🚐', label: 'Van' },
    { key: 'cake', symbol: '🎂', label: 'Cake', words: ['kek'] },
    { key: 'foot', symbol: '🦶', label: 'Foot', words: ['kaki'] },
    { key: 'key', symbol: '🔑', label: 'Key', words: ['kunci'] },
    { key: 'shoe', symbol: '👟', label: 'Shoe', words: ['kasut'] },
    { key: 'tooth', symbol: '🦷', label: 'Tooth', words: ['gigi'] },
    { key: 'guitar', symbol: '🎸', label: 'Guitar', words: ['gitar'] },
    { key: 'mountain', symbol: '⛰️', label: 'Mountain', words: ['gunung'] },
    { key: 'egg', symbol: '🥚', label: 'Egg', words: ['telur'] },
    { key: 'hand', symbol: '✋', label: 'Hand', words: ['tangan'] },
    { key: 'tomato', symbol: '🍅', label: 'Tomato' },
    {
        key: 'watermelon',
        symbol: '🍉',
        label: 'Watermelon',
        words: ['tembikai'],
    },
    {
        key: 'television',
        symbol: '📺',
        label: 'Television',
        words: ['televisyen'],
    },
    { key: 'medicine', symbol: '💊', label: 'Medicine', words: ['ubat'] },
    { key: 'sea', symbol: '🌊', label: 'Sea', words: ['laut'] },
    { key: 'hair', symbol: '💇', label: 'Hair', words: ['rambut'] },
    { key: 'leaf', symbol: '🍃', label: 'Leaf', words: ['daun'] },
    { key: 'dice', symbol: '🎲', label: 'Dice', words: ['dadu'] },
    { key: 'purse', symbol: '👛', label: 'Purse', words: ['dompet'] },
    { key: 'doctor', symbol: '🧑‍⚕️', label: 'Doctor', words: ['doktor'] },
    { key: 'lamp', symbol: '💡', label: 'Lamp', words: ['lampu'] },
    { key: 'orange', symbol: '🍊', label: 'Orange', words: ['limau'] },
    { key: 'candle', symbol: '🕯️', label: 'Candle', words: ['lilin'] },
    { key: 'basket', symbol: '🧺', label: 'Basket', words: ['bakul'] },
    { key: 'bottle', symbol: '🍼', label: 'Bottle', words: ['botol'] },
    { key: 'ship', symbol: '🚢', label: 'Ship', words: ['kapal'] },
    { key: 'house', symbol: '🏠', label: 'House', words: ['rumah'] },
    { key: 'bread', symbol: '🍞', label: 'Bread', words: ['roti'] },
    { key: 'king', symbol: '🤴', label: 'King', words: ['raja'] },
    { key: 'rocket', symbol: '🚀', label: 'Rocket', words: ['roket'] },
    { key: 'water', symbol: '💧', label: 'Water', words: ['air'] },
    { key: 'beach', symbol: '🏖️', label: 'Beach', words: ['pasir'] },
    { key: 'umbrella', symbol: '☂️', label: 'Umbrella', words: ['payung'] },
    { key: 'nose', symbol: '👃', label: 'Nose', words: ['hidung'] },
    { key: 'corn', symbol: '🌽', label: 'Corn', words: ['jagung'] },
    { key: 'shampoo', symbol: '🧴', label: 'Shampoo', words: ['syampu'] },
    { key: 'coin', symbol: '🪙', label: 'Coin', words: ['syiling'] },
    { key: 'banana', symbol: '🍌', label: 'Banana', words: ['pisang'] },
    { key: 'door', symbol: '🚪', label: 'Door', words: ['pintu'] },
    { key: 'pencil', symbol: '✏️', label: 'Pencil', words: ['pensel'] },
    { key: 'tree', symbol: '🌳', label: 'Tree', words: ['pokok'] },
    { key: 'ball', symbol: '⚽', label: 'Ball', words: ['bola'] },
    { key: 'flower', symbol: '🌸', label: 'Flower', words: ['bunga'] },
    { key: 'book', symbol: '📖', label: 'Book', words: ['buku'] },
    { key: 'shirt', symbol: '👕', label: 'Shirt', words: ['baju'] },
    { key: 'mango', symbol: '🥭', label: 'Mango', words: ['mangga'] },
    { key: 'mouth', symbol: '👄', label: 'Mouth', words: ['mulut'] },
    { key: 'coffee', symbol: '☕', label: 'Coffee', words: ['kopi'] },
    { key: 'tea', symbol: '🍵', label: 'Tea' },
    { key: 'cap', symbol: '🧢', label: 'Cap', words: ['topi'] },
    { key: 'hammer', symbol: '🔨', label: 'Hammer', words: ['palu'] },
    { key: 'lungs', symbol: '🫁', label: 'Lungs', words: ['paru'] },
    { key: 'eye', symbol: '👁️', label: 'Eye', words: ['mata'] },
    { key: 'brick', symbol: '🧱', label: 'Brick', words: ['bata'] },
    { key: 'moon', symbol: '🌙', label: 'Moon', words: ['bulan'] },
    { key: 'circle', symbol: '⚪', label: 'Circle', words: ['bulat'] },
    { key: 'father', symbol: '👨', label: 'Father', words: ['ayah'] },
    { key: 'girl', symbol: '👧', label: 'Girl' },
    { key: 'sun', symbol: '☀️', label: 'Sun' },
    { key: 'sock', symbol: '🧦', label: 'Sock' },
    { key: 'saw', symbol: '🪚', label: 'Saw' },
    { key: 'clock', symbol: '🕐', label: 'Clock' },
    { key: 'gift', symbol: '🎁', label: 'Gift' },
    { key: 'ghost', symbol: '👻', label: 'Ghost' },
    { key: 'tent', symbol: '⛺', label: 'Tent' },
    { key: 'drum', symbol: '🥁', label: 'Drum' },
    { key: 'lemon', symbol: '🍋', label: 'Lemon' },
    { key: 'rain', symbol: '🌧️', label: 'Rain' },
    { key: 'ring', symbol: '💍', label: 'Ring' },
    { key: 'fire', symbol: '🔥', label: 'Fire' },
    { key: 'chair', symbol: '🪑', label: 'Chair' },
    { key: 'cheese', symbol: '🧀', label: 'Cheese' },
    { key: 'cherry', symbol: '🍒', label: 'Cherry' },
    { key: 'coat', symbol: '🧥', label: 'Coat' },
    { key: 'pear', symbol: '🍐', label: 'Pear' },
    { key: 'pin', symbol: '📌', label: 'Pin' },
    { key: 'bin', symbol: '🗑️', label: 'Bin' },
    { key: 'rock', symbol: '🪨', label: 'Rock' },
    { key: 'lock', symbol: '🔒', label: 'Lock' },
    { key: 'three', symbol: '3️⃣', label: 'Three' },
    { key: 'apple', symbol: '🍎', label: 'Apple' },
    { key: 'balloon', symbol: '🎈', label: 'Balloon' },
    { key: 'cloud', symbol: '☁️', label: 'Cloud' },
    { key: 'trophy', symbol: '🏆', label: 'Trophy' },
    { key: 'star', symbol: '⭐', label: 'Star' },
    { key: 'sparkle', symbol: '🌟', label: 'Sparkling star' },
    { key: 'rainbow', symbol: '🌈', label: 'Rainbow' },
    { key: 'party', symbol: '🎉', label: 'Celebration' },
    { key: 'fire_engine', symbol: '🚒', label: 'Fire engine' },
    { key: 'police', symbol: '🚓', label: 'Police car' },
    { key: 'ambulance', symbol: '🚑', label: 'Ambulance' },
    { key: 'tractor', symbol: '🚜', label: 'Tractor' },
    { key: 'train', symbol: '🚂', label: 'Train' },
    { key: 'plane', symbol: '✈️', label: 'Plane' },
    { key: 'helicopter', symbol: '🚁', label: 'Helicopter' },
    { key: 'sailboat', symbol: '⛵', label: 'Sailboat' },
    { key: 'bicycle', symbol: '🚲', label: 'Bicycle' },
    { key: 'ufo', symbol: '🛸', label: 'Flying saucer' },
    { key: 'earth', symbol: '🌍', label: 'Earth' },
    { key: 'comet', symbol: '☄️', label: 'Comet' },
    { key: 'planet', symbol: '🪐', label: 'Planet' },
    { key: 'alien', symbol: '👽', label: 'Alien' },
    { key: 'satellite', symbol: '🛰️', label: 'Satellite' },
    { key: 'astronaut', symbol: '👩‍🚀', label: 'Astronaut' },
    { key: 'quiet', symbol: '🤫', label: 'Quiet voice' },
    { key: 'speaking', symbol: '🗣️', label: 'Speaking voice' },
    { key: 'paw', symbol: '🐾', label: 'Animals group' },
    { key: 'plate', symbol: '🍽️', label: 'Food group' },
    { key: 'traffic', symbol: '🚦', label: 'Vehicles group' },
    { key: 'body', symbol: '🧍', label: 'Body group' },
    { key: 'objects', symbol: '📦', label: 'Objects group' },
    { key: 'father', symbol: '🧑', label: 'People group' },
    { key: 'shapes', symbol: '🔷', label: 'Shapes group' },
    { key: 'numbers', symbol: '🔢', label: 'Numbers group' },
    { key: 'wake', symbol: '🛏️', label: 'Wake up', words: ['bangun tidur'] },
    { key: 'brush', symbol: '🪥', label: 'Brush teeth', words: ['gosok gigi'] },
    {
        key: 'breakfast',
        symbol: '🥣',
        label: 'Eat breakfast',
        words: ['makan sarapan'],
    },
    {
        key: 'school',
        symbol: '🏫',
        label: 'Go to school',
        words: ['pergi sekolah'],
    },
    {
        key: 'wet',
        symbol: 'wet',
        label: 'Wet hands',
        words: ['basahkan tangan'],
    },
    {
        key: 'add_soap',
        symbol: 'add-soap',
        label: 'Add soap',
        words: ['sapu sabun'],
    },
    {
        key: 'scrub',
        symbol: '🙌',
        label: 'Scrub hands',
        words: ['gosok tangan'],
    },
    { key: 'dry', symbol: '🧻', label: 'Dry hands', words: ['lap tangan'] },
    { key: 'seed', symbol: '🫘', label: 'Plant a seed', words: ['tanam biji'] },
    { key: 'watering', symbol: '💦', label: 'Water it', words: ['siram air'] },
    {
        key: 'sprout',
        symbol: '🌱',
        label: 'It sprouts',
        words: ['pokok tumbuh'],
    },
    {
        key: 'sunflower',
        symbol: '🌻',
        label: 'A flower grows',
        words: ['bunga kembang'],
    },
];

function normalize(value: string): string {
    return value.toLowerCase().trim().replaceAll('\uFE0F', '');
}

const bySymbol = new Map(
    illustrationOptions.map((item) => [normalize(item.symbol), item]),
);
const byWord = new Map(
    illustrationOptions.flatMap((item) =>
        [item.label, ...(item.words ?? [])].map((word) => [
            normalize(word),
            item,
        ]),
    ),
);

const aliases: Record<string, string> = {
    '🐵': '🐒',
    '🌞': '☀️',
    '⏰': '🕐',
    '🍳': '🥣',
};

const storyKeys = new Set([
    'wake',
    'brush',
    'breakfast',
    'school',
    'wet',
    'add_soap',
    'scrub',
    'dry',
    'seed',
    'watering',
    'sprout',
    'sunflower',
]);
const sources = new Map<string, string>();

function illustrationSource(key: string): string {
    const existing = sources.get(key);

    if (existing) {
        return existing;
    }

    const svg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 120"><ellipse cx="60" cy="108" rx="35" ry="4" fill="#BCA5D1" opacity=".16"/><g stroke="${colors.ink}" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">${artwork[key]}</g></svg>`;
    const source = `data:image/svg+xml,${encodeURIComponent(svg)}`;
    sources.set(key, source);

    return source;
}

/**
 * A known symbol wins over a word guess, except for the exact starter story
 * phrases whose shared symbols (water, soap) otherwise lose the action.
 */
export function resolveIllustration(
    symbol?: string | null,
    word = '',
): {
    key: string;
    label: string;
    source: string;
    isKnown: boolean;
} {
    const named = byWord.get(normalize(word));
    const stored = symbol ? (aliases[symbol] ?? symbol) : '';
    const selected =
        named && storyKeys.has(named.key)
            ? named
            : (bySymbol.get(normalize(stored)) ?? named);
    const key = selected?.key ?? 'picture';

    return {
        key,
        label: selected?.label ?? 'Picture needed',
        source: illustrationSource(key),
        isKnown: selected !== undefined,
    };
}
