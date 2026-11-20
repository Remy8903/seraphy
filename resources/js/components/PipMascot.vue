<script setup lang="ts">
import { useId } from 'vue';

withDefaults(defineProps<{ title?: string }>(), {
    title: 'Pip, your little lavender voice buddy, waving hello',
});

const id = useId();
</script>

<template>
    <svg
        viewBox="0 0 240 220"
        fill="none"
        role="img"
        :aria-labelledby="`${id}-title`"
    >
        <title :id="`${id}-title`">{{ title }}</title>
        <defs>
            <linearGradient
                :id="`${id}-feathers`"
                x1="69"
                y1="53"
                x2="175"
                y2="185"
                gradientUnits="userSpaceOnUse"
            >
                <stop stop-color="#C4A5FF" />
                <stop offset="1" stop-color="#9065DF" />
            </linearGradient>
            <linearGradient
                :id="`${id}-wing`"
                x1="174"
                y1="76"
                x2="183"
                y2="137"
                gradientUnits="userSpaceOnUse"
            >
                <stop stop-color="#8BDDEF" />
                <stop offset="1" stop-color="#52BBDC" />
            </linearGradient>
        </defs>
        <ellipse
            cx="121"
            cy="200"
            rx="58"
            ry="7"
            fill="#8663BB"
            opacity=".14"
        />
        <path
            d="M95 181v15m-8 1h17m38-16v15m-8 1h17"
            stroke="#EBAE7D"
            stroke-width="6"
            stroke-linecap="round"
        />
        <path d="M79 119Q39 134 49 164Q77 166 95 146" fill="#68CCE6" />
        <path
            d="M158 117Q181 95 187 74Q203 75 198 96Q193 122 165 141"
            :fill="`url(#${id}-wing)`"
        />
        <path
            d="M61 133Q52 70 91 54Q89 37 107 44Q115 26 124 48Q183 50 181 124Q181 190 123 191Q65 193 61 133Z"
            :fill="`url(#${id}-feathers)`"
        />
        <path
            d="M75 83Q82 66 97 63"
            stroke="#DCC7FF"
            stroke-width="5"
            stroke-linecap="round"
            opacity=".7"
        />
        <ellipse cx="121" cy="138" rx="46" ry="47" fill="#FFF9F0" />
        <ellipse cx="98" cy="99" rx="7.5" ry="9.5" fill="#38244F" />
        <ellipse cx="144" cy="99" rx="7.5" ry="9.5" fill="#38244F" />
        <circle cx="100" cy="96" r="2.6" fill="white" />
        <circle cx="146" cy="96" r="2.6" fill="white" />
        <ellipse cx="82" cy="113" rx="10" ry="6" fill="#EFB7CC" />
        <ellipse cx="159" cy="113" rx="10" ry="6" fill="#EFB7CC" />
        <path d="M108 111Q121 102 135 111L122 126Z" fill="#F0AB71" />
        <path d="M115 120Q122 128 129 119" fill="#C87E55" />
        <path
            d="M171 31h30q14 0 14 14v15q0 14-14 14h-17l-15 11 2-14q-12-2-12-13V45q0-14 12-14Z"
            fill="white"
        />
        <path
            d="M176 49v9m10-15v21m10-15v9"
            stroke="#65C5DE"
            stroke-width="4"
            stroke-linecap="round"
        />
        <path d="m39 58 3 7 7 3-7 3-3 7-3-7-7-3 7-3Z" fill="#BA9CE9" />
        <circle cx="49" cy="103" r="3" fill="#9DDCEB" />
        <path d="m201 151 2 5 6 2-6 2-2 6-2-6-5-2 5-2Z" fill="#C9B3ED" />
    </svg>
</template>
