<script setup>
defineProps({
    users: { type: Array, default: () => [] },
    max: { type: Number, default: 3 },
    size: { type: String, default: 'sm' },
});
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
        <div
            v-for="user in users.slice(0, max)"
            :key="user.id"
            class="grid place-items-center overflow-hidden rounded-full border-2 border-white bg-slate-200 text-[10px] font-bold text-slate-600"
            :title="user.name"
        >
            <img
                v-if="user.avatar"
                :src="user.avatar"
                :alt="user.name"
                class="h-full w-full object-cover"
            />
            <span v-else>{{ initials(user.name) }}</span>
        </div>
        <div
            v-if="users.length > max"
            class="grid place-items-center rounded-full border-2 border-white bg-blue-600 text-[10px] font-bold text-white"
        >
            +{{ users.length - max }}
        </div>
    </div>
</template>
