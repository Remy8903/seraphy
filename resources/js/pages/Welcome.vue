<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { dashboard, login } from '@/routes';
import { register } from '@/routes';
</script>

<template>
    <Head title="Welcome">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    <div
        class="flex min-h-screen flex-col items-center bg-[#FDFDFC] p-6 text-[#1b1b18] lg:justify-center lg:p-8 dark:bg-[#0a0a0a]"
    >
        <header
            class="mb-6 w-full max-w-[335px] text-sm not-has-[nav]:hidden lg:max-w-4xl"
        >
            <nav class="flex items-center justify-end gap-4">
                <Link
                    v-if="$page.props.auth.user"
                    :href="dashboard()"
                    class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                >
                    Dashboard
                </Link>
                <template v-else>
                    <Link
                        :href="login()"
                        class="inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]"
                    >
                        Log in
                    </Link>
                    <Link
                        :href="register()"
                        class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                    >
                        Register
                    </Link>
                </template>
            </nav>
        </header>
        <div
            class="flex w-full items-center justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0"
        >
            <main
                class="flex w-full max-w-[335px] flex-col-reverse overflow-hidden rounded-lg lg:max-w-4xl lg:flex-row"
            >
                <div
                    class="flex-1 rounded-br-lg rounded-bl-lg bg-white p-6 pb-12 text-[13px] leading-[20px] shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] lg:rounded-tl-lg lg:rounded-br-none lg:p-20 dark:bg-[#161615] dark:text-[#EDEDEC] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]"
                >
                    <h1
                        class="mb-1 text-3xl font-semibold tracking-tight lg:text-4xl"
                    >
                        TheraTrack
                    </h1>
                    <p class="mb-4 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        A workspace for speech therapists to track therapy
                        sessions and carry out activities with their clients.
                    </p>
                    <ul class="mb-4 flex flex-col lg:mb-6">
                        <li
                            class="relative flex items-start gap-4 py-2 before:absolute before:top-1/2 before:bottom-0 before:left-[0.4rem] before:border-l before:border-[#e3e3e0] dark:before:border-[#3E3E3A]"
                        >
                            <span
                                class="relative bg-white py-1 dark:bg-[#161615]"
                            >
                                <span
                                    class="flex h-3.5 w-3.5 items-center justify-center rounded-full border border-[#e3e3e0] bg-[#FDFDFC] shadow-[0px_0px_1px_0px_rgba(0,0,0,0.03),0px_1px_2px_0px_rgba(0,0,0,0.06)] dark:border-[#3E3E3A] dark:bg-[#161615]"
                                >
                                    <span
                                        class="h-1.5 w-1.5 rounded-full bg-[#7c5cf5] dark:bg-[#a48bff]"
                                    />
                                </span>
                            </span>
                            <span>
                                <span class="font-medium"
                                    >Session tracking</span
                                >
                                <span
                                    class="block text-[#706f6c] dark:text-[#A1A09A]"
                                    >Log every therapy session, goals and notes
                                    in one place.</span
                                >
                            </span>
                        </li>
                        <li
                            class="relative flex items-start gap-4 py-2 before:absolute before:top-0 before:bottom-0 before:left-[0.4rem] before:border-l before:border-[#e3e3e0] dark:before:border-[#3E3E3A]"
                        >
                            <span
                                class="relative bg-white py-1 dark:bg-[#161615]"
                            >
                                <span
                                    class="flex h-3.5 w-3.5 items-center justify-center rounded-full border border-[#e3e3e0] bg-[#FDFDFC] shadow-[0px_0px_1px_0px_rgba(0,0,0,0.03),0px_1px_2px_0px_rgba(0,0,0,0.06)] dark:border-[#3E3E3A] dark:bg-[#161615]"
                                >
                                    <span
                                        class="h-1.5 w-1.5 rounded-full bg-[#7c5cf5] dark:bg-[#a48bff]"
                                    />
                                </span>
                            </span>
                            <span>
                                <span class="font-medium"
                                    >Therapy activities</span
                                >
                                <span
                                    class="block text-[#706f6c] dark:text-[#A1A09A]"
                                    >Run speech and language activities with
                                    clients during sessions.</span
                                >
                            </span>
                        </li>
                        <li
                            class="relative flex items-start gap-4 py-2 before:absolute before:top-0 before:bottom-1/2 before:left-[0.4rem] before:border-l before:border-[#e3e3e0] dark:before:border-[#3E3E3A]"
                        >
                            <span
                                class="relative bg-white py-1 dark:bg-[#161615]"
                            >
                                <span
                                    class="flex h-3.5 w-3.5 items-center justify-center rounded-full border border-[#e3e3e0] bg-[#FDFDFC] shadow-[0px_0px_1px_0px_rgba(0,0,0,0.03),0px_1px_2px_0px_rgba(0,0,0,0.06)] dark:border-[#3E3E3A] dark:bg-[#161615]"
                                >
                                    <span
                                        class="h-1.5 w-1.5 rounded-full bg-[#7c5cf5] dark:bg-[#a48bff]"
                                    />
                                </span>
                            </span>
                            <span>
                                <span class="font-medium"
                                    >Progress monitoring</span
                                >
                                <span
                                    class="block text-[#706f6c] dark:text-[#A1A09A]"
                                    >See how each client improves over
                                    time.</span
                                >
                            </span>
                        </li>
                    </ul>
                    <div class="flex gap-3 text-sm leading-normal">
                        <Link
                            v-if="$page.props.auth.user"
                            :href="dashboard()"
                            class="inline-block rounded-sm border border-[#7c5cf5] bg-[#7c5cf5] px-5 py-1.5 text-sm leading-normal text-white hover:border-[#6a48ef] hover:bg-[#6a48ef]"
                        >
                            Go to dashboard
                        </Link>
                        <template v-else>
                            <Link
                                :href="register()"
                                class="inline-block rounded-sm border border-[#7c5cf5] bg-[#7c5cf5] px-5 py-1.5 text-sm leading-normal text-white hover:border-[#6a48ef] hover:bg-[#6a48ef]"
                            >
                                Get started
                            </Link>
                            <Link
                                :href="login()"
                                class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                            >
                                Log in
                            </Link>
                        </template>
                    </div>
                </div>
                <div
                    class="relative -mb-px aspect-[335/364] w-full shrink-0 overflow-hidden rounded-t-lg bg-[#f3f0ff] lg:mb-0 lg:-ml-px lg:aspect-auto lg:w-[438px] lg:rounded-t-none lg:rounded-r-lg dark:bg-[#130f24]"
                >
                    <div
                        class="flex h-full w-full items-center justify-center p-12"
                    >
                        <img
                            src="/logo.png"
                            alt="TheraTrack"
                            class="w-full max-w-[280px] opacity-100 transition-all duration-750 starting:opacity-0 motion-safe:starting:translate-y-6"
                        />
                    </div>
                    <div
                        class="absolute inset-0 rounded-t-lg shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] lg:rounded-t-none lg:rounded-r-lg dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]"
                    ></div>
                </div>
            </main>
        </div>
        <div class="hidden h-14.5 lg:block"></div>
    </div>
</template>
