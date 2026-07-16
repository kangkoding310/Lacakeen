<script setup>
import AvatarStack from '@/Components/ui/AvatarStack.vue';
import { formatDate } from '@/lib/date';
import { CalendarDays, MessageCircle, UserRound } from 'lucide-vue-next';

defineProps({ task: Object });
defineEmits(['open']);
const priorityClass = { urgent: 'bg-red-50 text-red-600', high: 'bg-orange-50 text-orange-600', medium: 'bg-violet-50 text-violet-600', low: 'bg-emerald-50 text-emerald-600' };
const formatTaskDate = (date) => formatDate(date, { month: 'short', day: '2-digit', year: '2-digit' }, 'No date');
</script>

<template>
    <article
        class="group cursor-pointer rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md"
        @click="$emit('open', task)">
        <div class="flex items-center justify-between gap-2">
            <div class="flex items-center gap-1.5 text-[11px] font-semibold text-slate-400">
                <UserRound class="h-3.5 w-3.5" />{{ task.code }}
            </div><span class="rounded-lg px-2 py-1 text-[10px] font-bold capitalize"
                :class="priorityClass[task.priority]">{{ task.priority }}</span>
        </div>
        <h3 class="mt-3 text-sm font-bold leading-5 text-slate-900 group-hover:text-blue-700">{{ task.title }}</h3>
        <p class="mt-1 truncate text-xs text-slate-400">{{ task.project?.name }}</p>
        <div class="mt-4 flex items-center gap-2 rounded-lg bg-slate-50 px-2.5 py-2 text-[11px] text-slate-500">
            <CalendarDays class="h-3.5 w-3.5" /><span>Due to:</span><strong class="font-semibold text-slate-700">{{
                formatTaskDate(task.due_date) }}</strong>
        </div>
        <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-3">
            <AvatarStack :users="task.assignees || []" />
            <div class="flex items-center gap-3 text-[11px] text-slate-400"><span class="flex items-center gap-1">
                    <MessageCircle class="h-3.5 w-3.5" />{{ task.comments_count ?? task.comments?.length ?? 0 }}
                </span><span>{{ formatTaskDate(task.updated_at) }}</span></div>
        </div>
    </article>
</template>
