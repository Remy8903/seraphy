import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { VitePWA } from 'vite-plugin-pwa';
import { defineConfig, lazyPlugins } from 'vite-plus';

export default defineConfig({
    plugins: lazyPlugins(() => [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.ts'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        inertia(),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        wayfinder({
            formVariants: true,
        }),
        // Makes the app installable and lets the offline play screen load
        // without internet. The service worker is written to public/sw.js so
        // it can control every page, not just /build.
        VitePWA({
            outDir: 'public',
            base: '/',
            scope: '/',
            registerType: 'autoUpdate',
            injectRegister: false,
            manifest: {
                name: 'TheraTrack',
                short_name: 'TheraTrack',
                description:
                    'Therapy games and clinical hours for speech therapy students',
                start_url: '/offline',
                scope: '/',
                display: 'standalone',
                background_color: '#ffffff',
                theme_color: '#ffffff',
                icons: [
                    { src: '/logo.png', sizes: '512x512', type: 'image/png' },
                    {
                        src: '/apple-touch-icon.png',
                        sizes: '180x180',
                        type: 'image/png',
                    },
                ],
            },
            workbox: {
                globDirectory: 'public',
                globPatterns: [
                    'build/assets/**/*.{js,css,woff,woff2}',
                    'logo.png',
                    'favicon.ico',
                ],
                maximumFileSizeToCacheInBytes: 5 * 1024 * 1024,
                inlineWorkboxRuntime: true,
                navigateFallback: null,
                // The offline play screen's HTML, refreshed on every build.
                additionalManifestEntries: [
                    { url: '/offline', revision: Date.now().toString() },
                ],
                runtimeCaching: [
                    {
                        urlPattern: ({ request }) =>
                            request.mode === 'navigate',
                        handler: 'NetworkOnly',
                        options: {
                            precacheFallback: { fallbackURL: '/offline' },
                        },
                    },
                    {
                        urlPattern: ({ url }) =>
                            url.pathname.startsWith(
                                '/storage/content-items/',
                            ) ||
                            url.pathname.startsWith(
                                '/storage/activity-backgrounds/',
                            ),
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'therapy-pictures',
                            expiration: { maxEntries: 500 },
                        },
                    },
                ],
            },
        }),
    ]),
    server: {
        watch: {
            ignored: [
                '**/.agents/**',
                '**/.claude/**',
                '**/.cursor/**',
                '**/.junie/**',
                '**/vendor/**',
            ],
        },
    },
    lint: {
        ignorePatterns: [
            'vendor/**',
            'node_modules/**',
            'public/**',
            'bootstrap/ssr/**',
            'tailwind.config.js',
            'resources/js/actions/**',
            'resources/js/components/ui/*',
            'resources/js/routes/**',
            'resources/js/wayfinder/**',
        ],
        options: {
            denyWarnings: true,
            typeAware: true,
        },
    },
    fmt: {
        printWidth: 80,
        tabWidth: 4,
        singleQuote: true,
        semi: true,
        singleAttributePerLine: false,
        htmlWhitespaceSensitivity: 'css',
        ignorePatterns: [
            '.github/**',
            'composer.json',
            'resources/js/components/ui/*',
            'resources/views/mail/*',
        ],
        sortTailwindcss: {
            functions: ['clsx', 'cn', 'cva'],
            stylesheet: 'resources/css/app.css',
        },
    },
});
