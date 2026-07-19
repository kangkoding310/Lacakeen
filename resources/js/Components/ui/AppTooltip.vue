<script setup lang="ts">
defineProps<{ text: string }>();
</script>

<template>
    <span class="group/tooltip relative inline-flex">
        <slot />
        <span
            class="pointer-events-none absolute bottom-full left-1/2 z-50 mb-1.5 -translate-x-1/2 whitespace-nowrap rounded-lg bg-slate-900 px-2 py-1 text-[11px] font-medium text-white opacity-0 shadow-lg transition delay-150 duration-100 group-hover/tooltip:opacity-100"
        >
            {{ text }}
        </span>
    </span>
</template>
