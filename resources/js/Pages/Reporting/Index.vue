<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppSelect from '@/Components/ui/AppSelect.vue';
import AvatarStack from '@/Components/ui/AvatarStack.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Bar, Doughnut, Line } from 'vue-chartjs';
import {
    ArcElement,
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    Filler,
    Legend,
    LinearScale,
    LineElement,
    PointElement,
    Tooltip,
} from 'chart.js';
import { Download, Filter, TrendingUp } from 'lucide-vue-next';
import { computed } from 'vue';

ChartJS.register(
    ArcElement,
    BarElement,
    CategoryScale,
    Filler,
    Legend,
    LinearScale,
    LineElement,
    PointElement,
    Tooltip
);
const props = defineProps({ analytics: Object, projects: Array, filters: Object });
const query = useForm({
    project: props.filters.project || '',
    from: props.filters.from || '',
    to: props.filters.to || '',
});
const projectOptions = computed(() =>
    props.projects.map((project) => ({ value: project.id, label: project.name }))
);
const colors = ['#2563EB', '#8B5CF6', '#22C55E', '#F97316', '#EF4444', '#14B8A6'];
const statusData = computed(() => ({
    labels: Object.keys(props.analytics.byStatus),
    datasets: [
        { data: Object.values(props.analytics.byStatus), backgroundColor: colors, borderWidth: 0 },
    ],
}));
const priorityData = computed(() => ({
    labels: Object.keys(props.analytics.byPriority),
    datasets: [
        {
            label: 'Tasks',
            data: Object.values(props.analytics.byPriority),
            backgroundColor: colors,
            borderRadius: 8,
        },
    ],
}));
const completionData = computed(() => ({
    labels: Object.keys(props.analytics.completion),
    datasets: [
        {
            label: 'Completed tasks',
            data: Object.values(props.analytics.completion),
            borderColor: '#2563EB',
            backgroundColor: 'rgba(37,99,235,.1)',
            fill: true,
            tension: 0.35,
        },
    ],
}));
const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { position: 'bottom' } },
};
const apply = () => router.get(route('reporting'), query.data(), { preserveState: true });
</script>
<template>
    <Head title="Reporting" />
    <AppLayout title="Reporting">
        <div class="page-shell">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div>
                    <h1 class="text-2xl font-bold">Reporting</h1>
                    <p class="mt-1 text-sm text-slate-500">
                        Understand delivery health and team throughput.
                    </p>
                </div>
                <button class="ui-button-secondary" onclick="window.print()">
                    <Download class="h-4 w-4" />Export PDF
                </button>
            </div>
            <form class="mt-6 flex flex-wrap gap-3" @submit.prevent="apply">
                <AppSelect
                    v-model="query.project"
                    :options="projectOptions"
                    placeholder="All projects"
                    class="w-52"
                />
                <input v-model="query.from" type="date" class="ui-input w-44" /><input
                    v-model="query.to"
                    type="date"
                    class="ui-input w-44"
                /><button class="ui-button-primary"><Filter class="h-4 w-4" />Apply</button>
            </form>
            <div class="mt-5 grid gap-4 lg:grid-cols-2">
                <section class="ui-card p-5 lg:col-span-2">
                    <div class="mb-4">
                        <h2 class="font-bold">Task completion over time</h2>
                        <p class="text-xs text-slate-400">Completed work by update date</p>
                    </div>
                    <div class="h-72">
                        <Line :data="completionData" :options="chartOptions" />
                    </div>
                </section>
                <section class="ui-card p-5">
                    <h2 class="font-bold">Tasks by status</h2>
                    <div class="mx-auto mt-4 h-72 max-w-md">
                        <Doughnut :data="statusData" :options="chartOptions" />
                    </div>
                </section>
                <section class="ui-card p-5">
                    <h2 class="font-bold">Tasks by priority</h2>
                    <div class="mt-4 h-72">
                        <Bar :data="priorityData" :options="chartOptions" />
                    </div>
                </section>
                <section class="ui-card overflow-hidden lg:col-span-2">
                    <div class="border-b border-slate-100 p-5">
                        <h2 class="font-bold">Member performance</h2>
                    </div>
                    <div class="divide-y divide-slate-100">
                        <div
                            v-for="(member, index) in analytics.members"
                            :key="member.name"
                            class="flex items-center gap-4 px-5 py-4"
                        >
                            <span
                                class="grid h-7 w-7 place-items-center rounded-lg bg-slate-100 text-xs font-bold text-slate-500"
                                >{{ index + 1 }}</span
                            ><img
                                :src="
                                    member.avatar ||
                                    `https://ui-avatars.com/api/?name=${encodeURIComponent(member?.name)}`
                                "
                                class="h-9 w-9 rounded-full object-cover"
                            />
                            <div class="flex-1">
                                <p class="text-sm font-bold">{{ member.name }}</p>
                                <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-slate-100">
                                    <div
                                        class="h-full rounded-full bg-blue-600"
                                        :style="{
                                            width: `${member.tasks ? (member.completed / member.tasks) * 100 : 0}%`,
                                        }"
                                    />
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-bold">
                                    {{ member.completed }}/{{ member.tasks }}
                                </p>
                                <p class="text-[10px] text-slate-400">completed</p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
