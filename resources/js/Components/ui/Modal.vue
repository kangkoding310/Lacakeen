<script setup>
import { X } from 'lucide-vue-next';

defineProps({ open: Boolean, title: String, description: String, wide: Boolean });
defineEmits(['close']);
</script>

<template>
    <Teleport to="body">
        <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0" leave-active-class="transition duration-150" leave-to-class="opacity-0">
            <div v-if="open" class="fixed inset-0 z-[100] grid place-items-center overflow-y-auto bg-slate-950/45 p-4 backdrop-blur-sm" @mousedown.self="$emit('close')">
                <Transition appear enter-active-class="transition duration-200" enter-from-class="translate-y-3 scale-[.98] opacity-0">
                    <section class="my-6 w-full rounded-2xl border border-white/60 bg-white shadow-2xl" :class="wide ? 'max-w-3xl' : 'max-w-xl'" role="dialog" aria-modal="true">
                        <header class="flex items-start justify-between border-b border-slate-100 px-6 py-5">
                            <div><h2 class="text-lg font-bold text-slate-950">{{ title }}</h2><p v-if="description" class="mt-1 text-sm text-slate-500">{{ description }}</p></div>
                            <button class="ui-icon-button h-9 w-9" type="button" aria-label="Close" @click="$emit('close')"><X class="h-4 w-4" /></button>
                        </header>
                        <div class="px-6 py-5"><slot /></div>
                    </section>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
