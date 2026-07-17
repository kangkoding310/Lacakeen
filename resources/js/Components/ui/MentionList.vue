<script setup lang="ts">
import type { TaskAssignee } from '@/types/task';
import type { MentionNodeAttrs } from '@tiptap/extension-mention';
import { ref, watch } from 'vue';

const props = defineProps<{
    items: TaskAssignee[];
    command: (item: MentionNodeAttrs) => void;
}>();
const selectedIndex = ref(0);

watch(
    () => props.items,
    () => {
        selectedIndex.value = 0;
    }
);

const select = (index: number) => {
    const item = props.items[index];
    if (item) props.command({ id: String(item.id), label: item.name });
};

const onKeyDown = ({ event }: { event: KeyboardEvent }): boolean => {
    if (event.key === 'ArrowUp') {
        selectedIndex.value = (selectedIndex.value + props.items.length - 1) % props.items.length;
        return true;
    }
    if (event.key === 'ArrowDown') {
        selectedIndex.value = (selectedIndex.value + 1) % props.items.length;
        return true;
    }
    if (event.key === 'Enter') {
        select(selectedIndex.value);
        return true;
    }
    return false;
};

defineExpose({ onKeyDown });
</script>

<template>
    <div class="z-[200] w-56 overflow-hidden rounded-xl border border-slate-200 bg-white p-1 shadow-xl">
        <button
            v-for="(item, index) in items"
            :key="item.id"
            type="button"
            class="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-left text-sm"
            :class="index === selectedIndex ? 'bg-blue-50 text-blue-700' : 'text-slate-700 hover:bg-slate-50'"
            @click="select(index)"
        >
            <img
                :src="item.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(item.name)}`"
                class="h-5 w-5 rounded-full object-cover"
            />
            <span class="truncate">{{ item.name }}</span>
        </button>
        <p v-if="!items.length" class="px-2.5 py-1.5 text-sm text-slate-400">No members found</p>
    </div>
</template>
