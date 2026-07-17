<script setup>
import { CheckCircle2, LayoutDashboard, Sparkles } from 'lucide-vue-next';
import logoPath from '@/Assets/images/lacakeen-logo.png'

</script>
<template>
    <main class="grid min-h-screen bg-white lg:grid-cols-[1.05fr_.95fr]">
        <section
            class="relative hidden overflow-hidden bg-slate-950 p-12 text-white lg:flex lg:flex-col lg:justify-between xl:p-16">
            <div
                class="absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(37,99,235,.45),transparent_38%),radial-gradient(circle_at_80%_75%,rgba(124,58,237,.35),transparent_42%)]" />
            <div class="relative flex items-center gap-3">
                <!-- <div class="grid h-11 w-11 place-items-center rounded-2xl bg-white text-xl font-black text-blue-700">L
                </div> -->
                <img :src="logoPath" alt="logo"
                    class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-gradient-to-br from-blue-600 to-violet-600 text-lg font-black text-white shadow-lg shadow-blue-500/20">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-base font-bold tracking-tight">Lacakeen</span>
                    </div>
                    <p class="truncate text-[11px] text-slate-50">
                        Your Daily Task Manager
                    </p>
                </div>
            </div>
            <div class="relative max-w-xl"><span
                    class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs text-blue-200">
                    <Sparkles class="h-3.5 w-3.5" />Built for focused teams
                </span>
                <h1 class="mt-6 text-4xl font-bold leading-tight xl:text-5xl">Plan the work.<br />Move together.</h1>
                <p class="mt-5 max-w-lg text-base leading-7 text-slate-300">One calm workspace for projects, tasks, team
                    conversations, and the insights that keep delivery moving.</p>
                <div class="mt-8 grid gap-3 text-sm text-slate-300 sm:grid-cols-2"><span
                        class="flex items-center gap-2">
                        <CheckCircle2 class="h-4 w-4 text-emerald-400" />Flexible Kanban workflows
                    </span><span class="flex items-center gap-2">
                        <CheckCircle2 class="h-4 w-4 text-emerald-400" />Real-time team visibility
                    </span></div>
            </div>
            <p class="relative text-xs text-slate-500">© {{ new Date().getFullYear() }} Lacakeen. Work beautifully.</p>
        </section>
        <section class="flex items-center justify-center bg-slate-50/50 px-5 py-10 sm:px-10">
            <div class="w-full max-w-md">
                <div class="mb-9 flex items-center gap-3 lg:hidden">
                    <div class="grid h-10 w-10 place-items-center rounded-xl bg-blue-600 font-black text-white">L</div>
                    <span class="font-bold">Lacakeen</span>
                </div>
                <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xl shadow-slate-200/40 sm:p-8">
                    <slot />
                </div>
                <p class="mt-6 text-center text-xs text-slate-400">Secure project collaboration for modern teams.</p>
            </div>
        </section>
    </main>
</template>
