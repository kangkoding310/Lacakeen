<script setup lang="ts">
import AppTooltip from '@/Components/ui/AppTooltip.vue';

interface AvatarUser {
    id: string | number;
    name: string;
    avatar?: string | null;
}

withDefaults(
    defineProps<{
        users?: AvatarUser[];
        max?: number;
        size?: 'sm' | 'md';
        showTooltip?: boolean;
    }>(),
    { users: () => [], max: 3, size: 'sm', showTooltip: false }
);
const initials = (name = '') =>
    name
        .split(' ')
        .map((part) => part[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();
</script>

<template>
    <div
        class="flex -space-x-2"
        :class="size === 'md' ? '[&>*]:h-9 [&>*]:w-9' : '[&>*]:h-7 [&>*]:w-7'"
    >
        <AppTooltip v-for="user in users.slice(0, max)" :key="user.id" :text="user.name">
            <div
                class="relative grid h-full w-full place-items-center overflow-hidden rounded-full border-2 border-white bg-slate-200 text-[10px] font-bold text-slate-600 hover:z-10 focus:z-10"
            >
                <img
                    v-if="user.avatar"
                    :src="user.avatar"
                    :alt="user.name"
                    class="h-full w-full object-cover"
                />
                <span v-else>{{ initials(user.name) }}</span>
            </div>
        </AppTooltip>
        <AppTooltip
            v-if="users.length > max"
            :text="
                users
                    .slice(max)
                    .map((user) => user.name)
                    .join(', ')
            "
        >
            <div
                class="relative grid h-full w-full place-items-center rounded-full border-2 border-white bg-blue-600 text-[10px] font-bold text-white hover:z-10 focus:z-10"
            >
                +{{ users.length - max }}
            </div>
        </AppTooltip>
    </div>
</template>
