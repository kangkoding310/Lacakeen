<script setup>
import AvatarStack from '@/Components/ui/AvatarStack.vue';
import ProjectCreateDialog from '@/Components/ProjectCreateDialog.vue';
import TaskCreateDialog from '@/Components/TaskCreateDialog.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    Bell,
    Building2,
    CalendarDays,
    ChevronLeft,
    ChevronRight,
    CircleHelp,
    GitBranch,
    Inbox,
    LayoutDashboard,
    Mail,
    Menu,
    MessageCircle,
    PanelLeftClose,
    PanelLeftOpen,
    FolderKanban,
    MoreHorizontal,
    PieChart,
    Plus,
    Search,
    Settings,
    SquarePen,
    TrendingUp,
    Users,
    Workflow,
    X,
} from 'lucide-vue-next';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

import logoPath from '@/Assets/images/lacakeen-logo.png';

defineProps({
    title: { type: String, default: 'Overview' },
    section: { type: String, default: 'Dashboard' },
    fullscreen: Boolean,
});
const page = usePage();
const collapsed = ref(localStorage.getItem('lacakeen-sidebar') === 'collapsed');
const mobileOpen = ref(false);
const notificationOpen = ref(false);
const createOpen = ref(false);
const projectCreateOpen = ref(false);
const projectMenuOpen = ref(false);
const projectMenuElement = ref(null);
const search = ref('');
const searchInput = ref();

const dashboardItems = [
    {
        label: 'Overview',
        route: 'dashboard',
        match: 'dashboard',
        icon: TrendingUp,
    },
    { label: 'Tasks', route: 'tasks.index', match: 'tasks.*', icon: SquarePen },
    {
        label: 'Calendar',
        route: 'calendar',
        match: 'calendar*',
        icon: CalendarDays,
    },
    { label: 'Chat', route: 'chat', match: 'chat*', icon: MessageCircle },
    {
        label: 'Reporting',
        route: 'reporting',
        match: 'reporting',
        icon: PieChart,
    },
    // { label: 'Workflow', route: 'workflow', match: 'workflow*', icon: Workflow },
];
const toolItems = computed(() => [
    {
        label: 'Notifications',
        route: 'notifications',
        match: 'notifications*',
        icon: Bell,
        badge: () => page.props.notifications?.unread,
    },
    { label: 'Members', route: 'members', match: 'members*', icon: Users },
    page.props.auth.user.roles.some((role) => role.name === 'admin')
        ? { label: 'Workspaces', route: 'workspaces.index', match: 'workspace*', icon: Building2 }
        : { label: 'Workspace', route: 'workspace.show', match: 'workspace*', icon: Building2 },
    { label: 'Inbox', route: 'inbox', match: 'inbox', icon: Mail },
    {
        label: 'Integrations',
        route: 'integrations',
        match: 'integrations*',
        icon: GitBranch,
    },
]);
const user = computed(() => page.props.auth.user);
const projectNavigation = computed(
    () => page.props.projectNavigation || { recent: [], canCreate: false }
);
const team = computed(
    () =>
        page.props.taskComposer?.projects
            ?.flatMap((project) => project.members || [])
            .filter(
                (member, index, items) => items.findIndex((item) => item.id === member.id) === index
            ) ?? []
);
const toggleSidebar = () => {
    collapsed.value = !collapsed.value;
    localStorage.setItem('lacakeen-sidebar', collapsed.value ? 'collapsed' : 'open');
};
const active = (pattern) => route().current(pattern);
const submitSearch = () => {
    if (search.value.trim())
        router.get(route('tasks.index'), { search: search.value }, { preserveState: true });
};
const keyboard = (event) => {
    if (event.key === 'Escape') {
        projectMenuOpen.value = false;
        notificationOpen.value = false;
    }
    if (
        event.key === '/' &&
        !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)
    ) {
        event.preventDefault();
        nextTick(() => searchInput.value?.focus());
    }
};
const closeDropdowns = (event) => {
    if (!projectMenuElement.value?.contains(event.target)) projectMenuOpen.value = false;
};
const stop = router.on('finish', () => {
    mobileOpen.value = false;
    notificationOpen.value = false;
});
onMounted(() => {
    window.addEventListener('keydown', keyboard);
    document.addEventListener('pointerdown', closeDropdowns);
});
onBeforeUnmount(() => {
    window.removeEventListener('keydown', keyboard);
    document.removeEventListener('pointerdown', closeDropdowns);
    stop();
});
</script>

<template>
    <div class="bg-slate-50" :class="fullscreen ? 'h-screen overflow-hidden' : 'min-h-screen'">
        <Transition
            enter-active-class="transition-opacity"
            enter-from-class="opacity-0"
            leave-active-class="transition-opacity"
            leave-to-class="opacity-0"
        >
            <button
                v-if="mobileOpen"
                cla
                ss="fixed inset-0 z-40 bg-slate-950/40 backdrop-blur-sm lg:hidden"
                aria-label="Close navigation"
                @click="mobileOpen = false"
            />
        </Transition>

        <aside
            class="fixed inset-y-0 left-0 z-50 flex flex-col border-r border-slate-200 bg-white transition-all duration-300 lg:translate-x-0"
            :class="[
                mobileOpen ? 'translate-x-0' : '-translate-x-full',
                collapsed ? 'w-[84px]' : 'w-[272px]',
            ]"
        >
            <div
                class="flex h-[78px] items-center border-b border-slate-100 px-5"
                :class="collapsed ? 'justify-center' : 'justify-between'"
            >
                <Link :href="route('dashboard')" class="flex min-w-0 items-center gap-3">
                    <div class="rounded-xl p-[1px] bg-gradient-to-br from-blue-600 to-violet-600 text-lg font-black text-white shadow-lg shadow-blue-500/20">
                        <img
                            :src="logoPath"
                            alt="logo"
                            class="grid h-11 w-11 shrink-0 place-items-center rounded-[11px]"
                        />
                    </div>
                    <div v-if="!collapsed" class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="text-base font-bold tracking-tight text-slate-950"
                                >Lacakeen</span
                            >
                        </div>
                        <p class="truncate text-[11px] text-slate-400">Your Daily Task Manager</p>
                    </div>
                </Link>
                <button
                    v-if="!collapsed"
                    class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 lg:hidden"
                    @click="mobileOpen = false"
                >
                    <X class="h-5 w-5" />
                </button>
            </div>

            <nav class="flex-1 overflow-y-auto px-3 py-5">
                <div
                    v-for="(group, groupIndex) in [
                        { label: 'Dashboard', items: dashboardItems },
                        { label: 'Tools', items: toolItems },
                    ]"
                    :key="group.label"
                    :class="groupIndex ? 'mt-7' : ''"
                >
                    <p
                        v-if="!collapsed"
                        class="mb-2 px-3 text-[10px] font-bold uppercase tracking-[.15em] text-slate-400"
                    >
                        {{ group.label }}
                    </p>
                    <div class="space-y-1">
                        <Link
                            v-for="item in group.items"
                            :key="item.label"
                            :href="route(item.route)"
                            :title="collapsed ? item.label : undefined"
                            class="relative flex h-10 items-center rounded-xl text-sm font-medium transition"
                            :class="[
                                collapsed ? 'justify-center px-0' : 'gap-3 px-3',
                                active(item.match)
                                    ? 'bg-blue-50 text-blue-700'
                                    : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950',
                            ]"
                        >
                            <span
                                v-if="active(item.match)"
                                class="absolute -left-3 h-5 w-1 rounded-r-full bg-blue-600"
                            />
                            <component :is="item.icon" class="h-[18px] w-[18px] shrink-0" />
                            <span v-if="!collapsed" class="flex-1">{{ item.label }}</span>
                            <span
                                v-if="item.badge?.()"
                                class="grid min-w-5 place-items-center rounded-full bg-red-500 px-1.5 py-0.5 text-[10px] font-bold text-white"
                                >{{ item.badge() }}</span
                            >
                        </Link>
                    </div>
                </div>

                <div class="mt-7">
                    <div v-if="!collapsed" class="mb-2 flex items-center justify-between px-3">
                        <p class="text-[10px] font-bold uppercase tracking-[.15em] text-slate-400">
                            Projects
                        </p>
                        <div class="flex items-center gap-0.5">
                            <span class="group relative">
                                <button
                                    v-if="projectNavigation.canCreate"
                                    class="rounded-md p-1 text-slate-400 hover:bg-slate-100 hover:text-blue-600"
                                    aria-label="Create project"
                                    @click="projectCreateOpen = true"
                                >
                                    <Plus class="h-4 w-4" />
                                </button>
                                <span
                                    class="pointer-events-none absolute bottom-full right-0 z-30 mb-2 whitespace-nowrap rounded-md bg-slate-950 px-2 py-1 text-[10px] font-medium normal-case tracking-normal text-white opacity-0 shadow-lg transition group-hover:opacity-100"
                                    >Create project</span
                                >
                            </span>
                            <span ref="projectMenuElement" class="group relative">
                                <button
                                    class="rounded-md p-1 text-slate-400 hover:bg-slate-100 hover:text-blue-600"
                                    aria-label="Project options"
                                    @click="projectMenuOpen = !projectMenuOpen"
                                >
                                    <MoreHorizontal class="h-4 w-4" />
                                </button>
                                <span
                                    class="pointer-events-none absolute bottom-full right-0 z-30 mb-2 whitespace-nowrap rounded-md bg-slate-950 px-2 py-1 text-[10px] font-medium normal-case tracking-normal text-white opacity-0 shadow-lg transition group-hover:opacity-100"
                                    >Project options</span
                                >
                                <div
                                    v-if="projectMenuOpen"
                                    class="absolute right-0 top-7 z-30 w-40 rounded-xl border border-slate-200 bg-white p-1 shadow-xl"
                                >
                                    <Link
                                        :href="route('projects.index')"
                                        class="flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50"
                                    >
                                        <FolderKanban class="h-4 w-4" />Manage Projects
                                    </Link>
                                </div>
                            </span>
                        </div>
                    </div>
                    <p
                        v-if="!collapsed && projectNavigation.recent.length"
                        class="mb-1 px-3 text-[10px] font-semibold text-slate-400"
                    >
                        Recent
                    </p>
                    <div class="space-y-1">
                        <Link
                            v-for="project in projectNavigation.recent"
                            :key="project.id"
                            :href="route('projects.show', project.id)"
                            :title="collapsed ? project.name : undefined"
                            class="relative flex h-10 items-center rounded-xl text-sm font-medium transition"
                            :class="[
                                collapsed ? 'justify-center' : 'gap-3 px-3',
                                active('projects.show') && page.props.project?.id === project.id
                                    ? 'bg-blue-50 text-blue-700'
                                    : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950',
                            ]"
                        >
                            <span
                                class="grid h-6 w-6 shrink-0 place-items-center rounded-lg text-[9px] font-black text-white"
                                :style="{ backgroundColor: project.color }"
                                >{{ project.prefix.slice(0, 2) }}</span
                            ><span v-if="!collapsed" class="truncate">{{ project.name }}</span>
                        </Link>
                        <Link
                            :href="route('projects.index')"
                            :title="collapsed ? 'Manage Projects' : undefined"
                            class="flex h-10 items-center rounded-xl text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"
                            :class="collapsed ? 'justify-center' : 'gap-3 px-3'"
                        >
                            <FolderKanban class="h-[18px] w-[18px]" /><span v-if="!collapsed"
                                >All projects</span
                            >
                            <ChevronRight
                                v-if="!collapsed"
                                class="ml-auto h-4 w-4 text-slate-300"
                            />
                        </Link>
                    </div>
                </div>
            </nav>

            <div class="border-t border-slate-100 p-3">
                <!-- <Link :href="route('help-center')" class="flex h-10 items-center rounded-xl text-sm font-medium text-slate-600 hover:bg-slate-50" :class="collapsed ? 'justify-center' : 'gap-3 px-3'"><CircleHelp class="h-[18px] w-[18px]" /><span v-if="!collapsed">Help Center</span></Link> -->
                <Link
                    :href="route('settings')"
                    class="flex h-10 items-center rounded-xl text-sm font-medium"
                    :class="[
                        collapsed ? 'justify-center' : 'gap-3 px-3',
                        active('settings*')
                            ? 'bg-blue-50 text-blue-700'
                            : 'text-slate-600 hover:bg-slate-50',
                    ]"
                >
                    <Settings class="h-[18px] w-[18px]" /><span v-if="!collapsed">Settings</span>
                </Link>
                <div
                    class="mt-3 flex items-center rounded-xl bg-slate-50 p-2"
                    :class="collapsed ? 'justify-center' : 'gap-3'"
                >
                    <img
                        :src="
                            user.avatar ||
                            `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}`
                        "
                        :alt="user.name"
                        class="h-9 w-9 shrink-0 rounded-xl object-cover"
                    />
                    <div v-if="!collapsed" class="min-w-0 flex-1">
                        <p class="truncate text-xs font-semibold text-slate-900">
                            {{ user.name }}
                        </p>
                        <p class="truncate text-[10px] text-slate-400">
                            {{ user.email }}
                        </p>
                    </div>
                    <Link
                        v-if="!collapsed"
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="text-[10px] font-semibold text-slate-400 hover:text-red-500"
                        >Logout</Link
                    >
                </div>
            </div>
            <button
                class="absolute -right-3 top-24 hidden h-7 w-7 place-items-center rounded-full border border-slate-200 bg-white text-slate-500 shadow-sm hover:text-blue-600 lg:grid"
                :aria-label="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                @click="toggleSidebar"
            >
                <ChevronRight v-if="collapsed" class="h-3.5 w-3.5" />
                <ChevronLeft v-else class="h-3.5 w-3.5" />
            </button>
        </aside>

        <div
            class="min-w-0 transition-[padding] duration-300"
            :class="[
                collapsed ? 'lg:pl-[84px]' : 'lg:pl-[272px]',
                fullscreen && 'h-screen overflow-hidden',
            ]"
        >
            <header
                class="sticky top-0 z-30 flex h-[78px] items-center justify-between border-b border-slate-200/80 bg-white/90 px-4 backdrop-blur-xl sm:px-6 lg:px-8"
            >
                <div class="flex min-w-0 items-center gap-3">
                    <button class="ui-icon-button lg:hidden" @click="mobileOpen = true">
                        <Menu class="h-5 w-5" />
                    </button>
                    <div class="hidden text-sm sm:flex">
                        <span class="text-slate-400">{{ section }}</span
                        ><span class="mx-2 text-slate-300">/</span
                        ><span class="font-semibold text-slate-900">{{ title }}</span>
                    </div>
                    <span class="truncate text-sm font-semibold sm:hidden">{{ title }}</span>
                </div>
                <div class="flex items-center gap-2 sm:gap-3">
                    <AvatarStack class="hidden sm:flex" :users="team" :max="4" />
                    <Link :href="route('settings')" class="ui-icon-button hidden sm:inline-flex">
                        <Settings class="h-[18px] w-[18px]" />
                    </Link>
                    <div class="relative">
                        <button
                            class="ui-icon-button relative"
                            @click="notificationOpen = !notificationOpen"
                        >
                            <Bell class="h-[18px] w-[18px]" /><span
                                v-if="page.props.notifications?.unread"
                                class="absolute right-2 top-2 h-2 w-2 rounded-full bg-red-500 ring-2 ring-white"
                            />
                        </button>
                        <div
                            v-if="notificationOpen"
                            class="absolute right-0 top-12 w-80 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl"
                        >
                            <div
                                class="flex items-center justify-between border-b border-slate-100 px-4 py-3"
                            >
                                <span class="text-sm font-bold">Notifications</span
                                ><span class="text-xs text-blue-600"
                                    >{{ page.props.notifications.unread }} unread</span
                                >
                            </div>
                            <Link
                                v-for="notification in page.props.notifications.latest"
                                :key="notification.id"
                                :href="notification.data.url || route('notifications')"
                                class="block border-b border-slate-50 px-4 py-3 text-xs hover:bg-slate-50"
                            >
                                <p class="font-medium text-slate-700">
                                    {{ notification.data.message }}
                                </p>
                                <p class="mt-1 text-slate-400">
                                    {{ new Date(notification.created_at).toLocaleDateString() }}
                                </p>
                            </Link>
                            <Link
                                :href="route('notifications')"
                                class="block px-4 py-3 text-center text-xs font-semibold text-blue-600"
                                >View all notifications</Link
                            >
                        </div>
                    </div>
                    <button class="ui-button-primary px-3 sm:px-4" @click="createOpen = true">
                        <Plus class="h-4 w-4" /><span class="hidden sm:inline">Create task</span>
                    </button>
                </div>
            </header>
            <main :class="fullscreen && 'h-[calc(100vh-78px)] overflow-hidden'">
                <slot />
            </main>
        </div>

        <TaskCreateDialog :open="createOpen" @close="createOpen = false" />
        <ProjectCreateDialog :open="projectCreateOpen" @close="projectCreateOpen = false" />
    </div>
</template>
