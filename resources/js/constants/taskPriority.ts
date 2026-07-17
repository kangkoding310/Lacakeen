import type { TaskPriority } from '@/types/task';

export const TASK_PRIORITIES: TaskPriority[] = ['urgent', 'high', 'medium', 'low'];

export const TASK_PRIORITY_OPTIONS = TASK_PRIORITIES.map((priority) => ({
    value: priority,
    label: priority[0].toUpperCase() + priority.slice(1),
}));

export const TASK_PRIORITY_BADGE_CLASS: Record<TaskPriority, string> = {
    urgent: 'bg-red-50 text-red-600',
    high: 'bg-orange-50 text-orange-600',
    medium: 'bg-violet-50 text-violet-600',
    low: 'bg-emerald-50 text-emerald-600',
};
