<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowDown,
    ArrowRight,
    BookOpen,
    Check,
    Clock3,
    Gamepad2,
    Heart,
    MessageCircle,
    ShieldCheck,
    Sparkles,
    TrendingUp,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import PipMascot from '@/components/PipMascot.vue';
import { dashboard, home, login, register } from '@/routes';

const page = usePage();
const startLink = computed(() =>
    page.props.auth.user ? dashboard() : register(),
);

const features = [
    {
        icon: Gamepad2,
        title: 'A little play. A lot of possibility.',
        description:
            'Turn speech and language practice into playful, purposeful activities you can enjoy together.',
        label: 'THERAPY ACTIVITIES',
        colors: 'bg-[#eee6fc] text-[#7850bd] dark:bg-[#392951] dark:text-[#d0b3ff]',
    },
    {
        icon: TrendingUp,
        title: 'See the small steps add up.',
        description:
            'Record responses, follow therapy goals, and see each client’s progress from session to session.',
        label: 'MEANINGFUL PROGRESS',
        colors: 'bg-[#dff2ef] text-[#387c73] dark:bg-[#203c37] dark:text-[#9bd9c9]',
    },
    {
        icon: Clock3,
        title: 'Less admin. More connection.',
        description:
            'Keep your clinical hours, session notes, and client references together in one calm workspace.',
        label: 'YOUR CLINICAL WORKSPACE',
        colors: 'bg-[#fcebdc] text-[#a1663d] dark:bg-[#432f25] dark:text-[#efbc96]',
    },
];

const steps = [
    {
        number: '01',
        title: 'Make yourself at home',
        description:
            'Create your workspace and add a client by reference code.',
    },
    {
        number: '02',
        title: 'Find a little spark',
        description: 'Choose an activity and tailor it to your client’s goals.',
    },
    {
        number: '03',
        title: 'Play, connect, grow',
        description:
            'Run a session together and keep track of every small win.',
    },
];
</script>

<template>
    <div
        class="welcome-page min-h-screen bg-[#fbf9f5] text-[#302740] selection:bg-[#e2d5f9] dark:bg-[#17131f] dark:text-[#f5effc]"
    >
        <Head title="A little encouragement. A growing voice.">
            <meta
                name="description"
                content="Meet TheraTrack, your friendly speech therapy workspace. Plan playful activities, track client progress, and manage clinical hours with a little encouragement from Pip."
            />
        </Head>

        <a
            href="#main-content"
            class="sr-only z-50 rounded-lg bg-[#573a8d] p-3 text-white focus:not-sr-only focus:fixed focus:top-3 focus:left-3"
        >
            Skip to content
        </a>

        <header
            class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-5 py-6 sm:px-8"
        >
            <Link
                :href="home()"
                aria-label="TheraTrack home"
                class="flex shrink-0 items-center gap-2.5 rounded-lg"
            >
                <AppLogoIcon class="size-10 rounded-xl" />
                <span class="text-xl font-semibold tracking-tight">
                    Thera<span class="text-[#8258c3] dark:text-[#bd9ae8]"
                        >Track</span
                    >
                </span>
            </Link>
            <nav
                aria-label="Main navigation"
                class="hidden items-center gap-7 text-sm font-medium text-[#746a80] md:flex dark:text-[#beb2cb]"
            >
                <a
                    class="transition-colors hover:text-[#865bc5]"
                    href="#features"
                >
                    Why TheraTrack
                </a>
                <a
                    class="transition-colors hover:text-[#865bc5]"
                    href="#how-it-works"
                >
                    How it works
                </a>
                <a
                    class="transition-colors hover:text-[#865bc5]"
                    href="#meet-pip"
                >
                    Meet Pip
                </a>
            </nav>
            <div class="flex items-center gap-4 text-sm font-semibold">
                <Link
                    v-if="page.props.auth.user"
                    :href="dashboard()"
                    class="inline-flex min-h-11 items-center gap-2 rounded-full bg-[#7850b7] px-5 py-2.5 text-white transition-colors hover:bg-[#654098]"
                >
                    Dashboard <ArrowRight class="size-4" />
                </Link>
                <template v-else>
                    <Link
                        :href="login()"
                        class="rounded-lg py-2 transition-colors hover:text-[#865bc5]"
                    >
                        Log in
                    </Link>
                    <Link
                        :href="register()"
                        class="hidden min-h-11 items-center gap-2 rounded-full bg-[#7850b7] px-5 py-2.5 text-white transition-colors hover:bg-[#654098] sm:inline-flex"
                    >
                        Get started <ArrowRight class="size-4" />
                    </Link>
                </template>
            </div>
        </header>

        <main id="main-content">
            <section
                aria-labelledby="hero-heading"
                class="mx-auto grid max-w-6xl items-center gap-3 px-5 pt-8 pb-14 sm:px-8 sm:pt-14 sm:pb-20 lg:grid-cols-[1.06fr_1fr] lg:gap-8 lg:pt-16 lg:pb-24"
            >
                <div class="relative z-10 flex flex-col items-start gap-7">
                    <span
                        class="inline-flex items-center gap-2 rounded-full border border-[#e7ddf1] bg-[#f2ecf9] px-3.5 py-2 text-[0.6875rem] font-semibold tracking-[0.12em] text-[#79579b] dark:border-[#4c365f] dark:bg-[#30223f] dark:text-[#d2b6ef]"
                    >
                        <span class="size-1.5 rounded-full bg-[#a77bd4]" />
                        A LITTLE SUPPORT. A WORLD OF POSSIBILITY.
                    </span>
                    <h1
                        id="hero-heading"
                        class="max-w-xl text-[clamp(2.75rem,5.7vw,4.6rem)] leading-[1.09] font-semibold tracking-[-0.055em]"
                    >
                        Small steps.<br />
                        Brighter voices.<br />
                        <span
                            class="relative inline-block text-[#8a60bd] dark:text-[#c4a2ec]"
                        >
                            Together.
                            <svg
                                viewBox="0 0 300 16"
                                class="absolute -bottom-3 left-0 h-4 w-full text-[#e6be96] dark:text-[#b38868]"
                                aria-hidden="true"
                            >
                                <path
                                    d="M4 10Q145-3 294 8M43 15Q149 7 250 12"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="4"
                                    stroke-linecap="round"
                                />
                            </svg>
                        </span>
                    </h1>
                    <p
                        class="mt-2 max-w-[27rem] text-base leading-7 text-[#7a7083] sm:text-lg sm:leading-8 dark:text-[#baafc6]"
                    >
                        A friendlier space for speech therapy. Bring play to
                        your sessions, celebrate every little win, and help each
                        voice find its way.
                    </p>
                    <div class="flex flex-wrap items-center gap-5">
                        <Link
                            :href="startLink"
                            class="group inline-flex min-h-13 items-center justify-center gap-3 rounded-full bg-[#7850b7] px-6 py-3.5 text-sm font-semibold text-white shadow-[0_7px_18px_-7px_#7850b780] transition-colors hover:bg-[#654098]"
                        >
                            {{
                                page.props.auth.user
                                    ? 'Back to your workspace'
                                    : 'Find your starting point'
                            }}
                            <ArrowRight
                                class="size-4 transition-transform motion-safe:group-hover:translate-x-1"
                            />
                        </Link>
                        <a
                            href="#how-it-works"
                            class="inline-flex min-h-11 items-center gap-2 rounded-lg text-sm font-medium text-[#71647e] hover:text-[#865bc5] dark:text-[#c0b3ce]"
                        >
                            Take a little look <ArrowDown class="size-4" />
                        </a>
                    </div>
                    <p
                        class="flex items-center gap-2 text-xs text-[#8b8192] dark:text-[#aea0bc]"
                    >
                        <Heart class="size-3.5 text-[#ad8bc8]" />
                        Made for clinicians. Inspired by little voices.
                    </p>
                </div>

                <div
                    id="meet-pip"
                    class="relative isolate mx-auto mt-5 flex aspect-square w-full max-w-[500px] scroll-mt-8 items-center justify-center lg:mt-0"
                >
                    <div
                        aria-hidden="true"
                        class="absolute inset-[8%] -z-10 rotate-[-8deg] rounded-[43%_57%_51%_49%/51%_47%_53%_49%] bg-[#eee5f7] dark:bg-[#332440]"
                    />
                    <div
                        aria-hidden="true"
                        class="absolute inset-[15%] -z-10 rounded-full border border-dashed border-[#d5bee9] dark:border-[#63447b]"
                    />
                    <span
                        class="pip-note absolute top-[9%] right-[1%] z-10 -rotate-6 rounded-2xl rounded-bl-sm bg-white px-4 py-3 text-sm font-semibold text-[#73528d] shadow-[0_6px_24px_-10px_#78618d40] sm:right-0 sm:text-base dark:bg-[#443151] dark:text-[#e0c7f1]"
                    >
                        Every little sound counts.
                    </span>
                    <PipMascot class="pip-float relative w-[87%]" />
                    <div
                        class="absolute top-[45%] left-0 flex -rotate-6 items-center gap-2 rounded-xl bg-white px-3 py-2.5 shadow-[0_5px_22px_-9px_#78618d40] sm:-left-2 dark:bg-[#302639]"
                    >
                        <span
                            class="flex size-7 items-center justify-center rounded-full bg-[#e6f3ed] text-[#5b947b] dark:bg-[#314b41] dark:text-[#a5d9bc]"
                        >
                            <Check class="size-4" />
                        </span>
                        <span class="text-xs font-medium">One little win!</span>
                    </div>
                    <div
                        class="absolute right-0 bottom-[24%] flex rotate-6 items-center gap-2 rounded-xl bg-white px-3 py-2.5 shadow-[0_5px_22px_-9px_#78618d40] sm:-right-3 dark:bg-[#302639]"
                    >
                        <MessageCircle class="size-4 text-[#a07ac7]" />
                        <span class="text-xs font-medium"
                            >You’ve got this.</span
                        >
                    </div>
                    <div
                        class="absolute bottom-[2%] flex flex-col items-center gap-1.5 text-center"
                    >
                        <span
                            class="flex items-center gap-1.5 text-sm font-semibold text-[#795198] dark:text-[#c7a3e5]"
                        >
                            Meet Pip <Sparkles class="size-3.5" />
                        </span>
                        <span
                            class="text-xs text-[#8b7c98] dark:text-[#b6a5c4]"
                        >
                            Your little voice buddy. Your biggest cheerleader.
                        </span>
                    </div>
                </div>
            </section>

            <div
                class="border-y border-[#eae4ed] bg-[#f5f1f7] dark:border-[#3a2e45] dark:bg-[#211a2a]"
            >
                <div
                    class="mx-auto flex max-w-6xl flex-wrap items-center justify-center gap-x-10 gap-y-4 px-5 py-5 text-xs font-medium text-[#84718e] sm:px-8 md:justify-between dark:text-[#bca5cc]"
                >
                    <span class="flex items-center gap-2.5">
                        <Heart class="size-4" /> Built for speech &amp; language
                        therapy
                    </span>
                    <span class="flex items-center gap-2.5">
                        <BookOpen class="size-4" /> English &amp; Bahasa Melayu
                    </span>
                    <span class="flex items-center gap-2.5">
                        <ShieldCheck class="size-4" /> Reference codes, not
                        patient names
                    </span>
                </div>
            </div>

            <section
                id="features"
                aria-labelledby="features-heading"
                class="mx-auto max-w-6xl scroll-mt-8 px-5 py-16 sm:px-8 sm:py-20"
            >
                <div class="flex flex-col gap-3 text-center">
                    <p
                        class="text-[0.6875rem] font-semibold tracking-[0.17em] text-[#9576ac] dark:text-[#b799cc]"
                    >
                        MORE CONNECTION. LESS COMPLICATION.
                    </p>
                    <h2
                        id="features-heading"
                        class="text-3xl font-semibold tracking-[-0.035em] sm:text-4xl"
                    >
                        A little help for the work that matters.
                    </h2>
                    <p
                        class="text-sm leading-6 text-[#877b90] dark:text-[#b6a7c2]"
                    >
                        Your everyday essentials, with a softer landing.
                    </p>
                </div>
                <div class="mt-10 grid gap-4 md:grid-cols-3">
                    <article
                        v-for="feature in features"
                        :key="feature.title"
                        class="flex flex-col items-start gap-5 rounded-[1.5rem] border border-[#eee8ef] bg-[#fffdfb] p-7 sm:p-8 dark:border-[#403148] dark:bg-[#241c2c]"
                    >
                        <span
                            class="flex size-12 items-center justify-center rounded-2xl"
                            :class="feature.colors"
                        >
                            <component :is="feature.icon" class="size-5" />
                        </span>
                        <div class="flex flex-col gap-3">
                            <p
                                class="text-[0.625rem] font-semibold tracking-[0.14em] text-[#9888a1] dark:text-[#b5a0c4]"
                            >
                                {{ feature.label }}
                            </p>
                            <h3
                                class="max-w-56 text-xl leading-7 font-semibold tracking-tight"
                            >
                                {{ feature.title }}
                            </h3>
                            <p
                                class="text-sm leading-7 text-[#897d92] dark:text-[#bbacc6]"
                            >
                                {{ feature.description }}
                            </p>
                        </div>
                    </article>
                </div>
            </section>

            <section
                id="how-it-works"
                aria-labelledby="steps-heading"
                class="mx-auto max-w-6xl scroll-mt-8 px-5 pb-16 sm:px-8 sm:pb-20"
            >
                <div
                    class="grid gap-9 rounded-[1.75rem] bg-[#f0eaf5] p-7 sm:p-10 lg:grid-cols-[0.8fr_1.5fr] lg:gap-14 lg:p-12 dark:bg-[#2d2138]"
                >
                    <div class="flex flex-col items-start gap-4">
                        <span
                            class="text-[0.6875rem] font-semibold tracking-[0.16em] text-[#9676ac] dark:text-[#bca0d0]"
                        >
                            LET’S FIND YOUR RHYTHM
                        </span>
                        <h2
                            id="steps-heading"
                            class="text-3xl leading-tight font-semibold tracking-[-0.035em]"
                        >
                            Big possibilities.<br />A simple beginning.
                        </h2>
                        <p
                            class="max-w-72 text-sm leading-7 text-[#87758f] dark:text-[#bca8c9]"
                        >
                            You bring the care and encouragement. We’ll help you
                            keep everything else in place.
                        </p>
                        <Link
                            :href="startLink"
                            class="inline-flex min-h-11 items-center gap-2 rounded-lg text-sm font-semibold text-[#7950a5] hover:text-[#56367c] dark:text-[#d6b2f2] dark:hover:text-white"
                        >
                            {{
                                page.props.auth.user
                                    ? 'Open your workspace'
                                    : 'Let’s get started'
                            }}
                            <ArrowRight class="size-4" />
                        </Link>
                    </div>
                    <ol class="grid gap-7 sm:grid-cols-3 sm:gap-6">
                        <li
                            v-for="step in steps"
                            :key="step.number"
                            class="flex flex-col items-start gap-4"
                        >
                            <span
                                class="flex size-11 items-center justify-center rounded-full border border-[#d9c7e6] text-sm font-medium text-[#9979b1] dark:border-[#684d7e] dark:text-[#c4a6dc]"
                            >
                                {{ step.number }}
                            </span>
                            <h3 class="text-base leading-6 font-semibold">
                                {{ step.title }}
                            </h3>
                            <p
                                class="text-sm leading-7 text-[#88758f] dark:text-[#bda8cb]"
                            >
                                {{ step.description }}
                            </p>
                        </li>
                    </ol>
                </div>
            </section>
        </main>

        <footer
            class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-5 pb-7 text-xs text-[#998b9f] sm:flex-row sm:px-8 dark:text-[#a894b6]"
        >
            <span class="font-medium"
                >TheraTrack · A little care goes a long way.</span
            >
            <span class="flex items-center gap-1.5">
                Small steps, celebrated with
                <Heart class="size-3 text-[#ad89c5]" />
                and Pip.
            </span>
        </footer>
    </div>
</template>

<style scoped>
.welcome-page :is(a, button):focus-visible {
    outline: 3px solid #9c73c9;
    outline-offset: 5px;
}

@media (prefers-reduced-motion: no-preference) {
    .pip-float {
        animation: pip-bob 5s ease-in-out infinite;
    }

    .pip-note {
        animation: pip-hello 0.7s ease-out both;
    }
}

@keyframes pip-bob {
    0%,
    100% {
        transform: translateY(0) rotate(-2deg);
    }
    50% {
        transform: translateY(-9px) rotate(1deg);
    }
}

@keyframes pip-hello {
    from {
        opacity: 0;
        translate: 0 8px;
    }
    to {
        opacity: 1;
        translate: 0 0;
    }
}
</style>
