<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import AvatarStack from '@/Components/ui/AvatarStack.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Modal from '@/Components/ui/Modal.vue';
import { usePermissions } from '@/composables/usePermissions';
import { conversationService } from '@/services/conversationService';
import type { ChatMessage, ConversationSummary } from '@/types/conversation';
import type { TaskAssignee } from '@/types/task';
import { echo } from '@laravel/echo-vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    MessageSquarePlus,
    MoreHorizontal,
    Paperclip,
    Phone,
    Search,
    Send,
    Video,
} from 'lucide-vue-next';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps<{
    conversations: ConversationSummary[];
    activeConversation: ConversationSummary | null;
    members: TaskAssignee[];
}>();
const { currentUser } = usePermissions();

// Local reactive copies so realtime events can mutate state without waiting on an Inertia reload.
const conversations = ref<ConversationSummary[]>([...props.conversations]);
const activeConversation = ref<ConversationSummary | null>(
    props.activeConversation ? { ...props.activeConversation } : null
);
watch(
    () => props.conversations,
    (value) => {
        conversations.value = [...value];
    }
);
watch(
    () => props.activeConversation,
    (value) => {
        activeConversation.value = value ? { ...value } : null;
    }
);

const form = useForm({ body: '' });
const messageList = ref<HTMLElement | null>(null);
const scrollToBottom = () =>
    nextTick(() =>
        messageList.value?.scrollTo({ top: messageList.value.scrollHeight, behavior: 'smooth' })
    );
const send = () =>
    form.post(route('chat.messages.store', activeConversation.value!.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            scrollToBottom();
        },
    });
const time = (date: string) =>
    new Intl.DateTimeFormat('en-US', { hour: 'numeric', minute: '2-digit' }).format(new Date(date));

// --- Start a new conversation (direct or group) ---
const newConversationOpen = ref(false);
const memberSearch = ref('');
const selectedMemberIds = ref<number[]>([]);
const groupName = ref('');
const creatingConversation = ref(false);
const filteredMembers = computed(() =>
    props.members.filter((member) =>
        member.name.toLowerCase().includes(memberSearch.value.toLowerCase())
    )
);
const toggleMember = (memberId: number) => {
    selectedMemberIds.value = selectedMemberIds.value.includes(memberId)
        ? selectedMemberIds.value.filter((id) => id !== memberId)
        : [...selectedMemberIds.value, memberId];
};
const closeNewConversation = () => {
    newConversationOpen.value = false;
    selectedMemberIds.value = [];
    memberSearch.value = '';
    groupName.value = '';
};
const submitNewConversation = () => {
    if (!selectedMemberIds.value.length) return;
    creatingConversation.value = true;
    conversationService.store(
        {
            user_ids: selectedMemberIds.value,
            name: selectedMemberIds.value.length > 1 ? groupName.value || null : null,
        },
        {
            onSuccess: closeNewConversation,
            onFinish: () => {
                creatingConversation.value = false;
            },
        }
    );
};

// --- Realtime (Reverb via Laravel Echo) ---
const subscribedConversations = new Set<string>();
const subscribeToConversation = (conversationId: string) => {
    if (subscribedConversations.has(conversationId)) return;
    subscribedConversations.add(conversationId);
    echo()
        .private(`conversation.${conversationId}`)
        .listen('.MessageSent', (event: { message: ChatMessage }) => {
            const inList = conversations.value.find((item) => item.id === conversationId);
            if (inList) inList.messages = [event.message];
            conversations.value.sort((a, b) =>
                a.id === conversationId ? -1 : b.id === conversationId ? 1 : 0
            );

            if (activeConversation.value?.id === conversationId) {
                activeConversation.value.messages = [
                    ...(activeConversation.value.messages ?? []),
                    event.message,
                ];
                scrollToBottom();
            }
        });
};
const unsubscribeFromConversation = (conversationId: string) => {
    echo().leave(`conversation.${conversationId}`);
    subscribedConversations.delete(conversationId);
};
watch(
    conversations,
    (list) => {
        const currentIds = new Set(list.map((item) => item.id));
        subscribedConversations.forEach((id) => {
            if (!currentIds.has(id)) unsubscribeFromConversation(id);
        });
        list.forEach((item) => subscribeToConversation(item.id));
    },
    { immediate: true, deep: false }
);
echo()
    .private(`App.Models.User.${currentUser.value.id}`)
    .listen('.ConversationCreated', (event: { conversation: ConversationSummary }) => {
        if (!conversations.value.some((item) => item.id === event.conversation.id)) {
            conversations.value = [event.conversation, ...conversations.value];
        }
    });

onMounted(() =>
    nextTick(() => {
        if (messageList.value) messageList.value.scrollTop = messageList.value.scrollHeight;
    })
);
onBeforeUnmount(() => {
    subscribedConversations.forEach((id) => echo().leave(`conversation.${id}`));
    echo().leave(`App.Models.User.${currentUser.value.id}`);
});
</script>

<template>
    <Head title="Chat" />
    <AppLayout title="Chat"
        ><div class="page-shell">
            <div
                class="table-shell grid h-[calc(100vh-126px)] min-h-[620px] md:grid-cols-[280px_1fr] xl:grid-cols-[340px_1fr]"
            >
                <aside
                    class="flex min-h-0 flex-col border-r border-slate-200"
                    :class="activeConversation ? 'hidden md:flex' : 'flex'"
                >
                    <div class="border-b border-slate-100 p-4">
                        <div class="flex items-center justify-between">
                            <h1 class="text-xl font-bold">Messages</h1>
                            <div @click="newConversationOpen = true" class="flex items-center justify-center cursor-pointer w-10 h-10 rounded-lg bg-blue-600 text-white shadow-xl transition hover:bg-blue-700">
                                <MessageSquarePlus class="h-5 w-5" />
                            </div>
                        </div>
                        <div class="relative mt-4">
                            <Search
                                class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                            /><input
                                class="ui-input bg-slate-50 pl-9"
                                placeholder="Search conversations"
                            />
                        </div>
                    </div>
                    <div class="flex-1 overflow-y-auto p-2">
                        <Link
                            v-for="conversation in conversations"
                            :key="conversation.id"
                            :href="route('chat', conversation.id)"
                            class="flex items-center gap-3 rounded-xl p-3 transition hover:bg-slate-50"
                            :class="activeConversation?.id === conversation.id && 'bg-blue-50'"
                            ><div class="relative">
                                <AvatarStack
                                    :users="conversation.participants"
                                    :max="1"
                                    size="md"
                                /><span
                                    class="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full border-2 border-white bg-emerald-500"
                                />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex justify-between gap-2">
                                    <p class="truncate text-sm font-bold text-slate-800">
                                        {{
                                            conversation.name ||
                                            conversation.participants.find(
                                                (p) => p.id !== currentUser.id
                                            )?.name
                                        }}
                                    </p>
                                    <span class="text-[10px] text-slate-400">{{
                                        conversation.messages?.[0]
                                            ? time(conversation.messages[0].created_at)
                                            : ''
                                    }}</span>
                                </div>
                                <p class="mt-1 truncate text-xs text-slate-400">
                                    {{ conversation.messages?.[0]?.body || 'Start a conversation' }}
                                </p>
                            </div></Link
                        >
                    </div>
                </aside>
                <main v-if="activeConversation" class="flex min-h-0 flex-col">
                    <header
                        class="flex h-[74px] items-center justify-between border-b border-slate-100 px-4 sm:px-6"
                    >
                        <div class="flex items-center gap-3">
                            <AvatarStack
                                :users="activeConversation.participants"
                                :max="2"
                                size="md"
                            />
                            <div>
                                <p class="text-sm font-bold">
                                    {{
                                        activeConversation.name ||
                                        (activeConversation.type === 'group'
                                            ? activeConversation.participants
                                                  .map((p) => p.name)
                                                  .join(', ')
                                            : 'Direct message')
                                    }}
                                </p>
                                <p class="text-[11px] text-emerald-600">
                                    {{ activeConversation.participants.length }} members · Active
                                    now
                                </p>
                            </div>
                        </div>
                        <div class="flex gap-1">
                            <button class="ui-icon-button border-0">
                                <Phone class="h-4 w-4" /></button
                            ><button class="ui-icon-button hidden border-0 sm:inline-flex">
                                <Video class="h-4 w-4" /></button
                            ><button class="ui-icon-button border-0">
                                <MoreHorizontal class="h-4 w-4" />
                            </button>
                        </div>
                    </header>
                    <div ref="messageList" class="flex-1 overflow-y-auto bg-slate-50/50 p-4 sm:p-6">
                        <div class="mx-auto max-w-3xl space-y-5">
                            <div
                                v-for="message in activeConversation.messages"
                                :key="message.id"
                                class="flex gap-3"
                                :class="
                                    message.sender_id === currentUser.id ? 'flex-row-reverse [&>div]:items-end' : '[&>div]:items-start'
                                "
                            >
                                <img
                                    :src="
                                        message.sender.avatar ||
                                        `https://ui-avatars.com/api/?name=${encodeURIComponent(message.sender?.name)}`
                                    "
                                    class="h-8 w-8 rounded-full object-cover"
                                />
                                <div class="max-w-[75%] flex flex-col">
                                    <div
                                        class="inline-flex rounded-2xl px-4 py-3 text-sm leading-6 shadow-sm"
                                        :class="
                                            message.sender_id === currentUser.id
                                                ? 'rounded-tr-md bg-blue-600 text-white'
                                                : 'rounded-tl-md bg-slate-200 text-slate-700'
                                        "
                                    >
                                        {{ message.body }}
                                    </div>
                                    <p
                                        class="mt-1 text-[10px] text-slate-400"
                                        :class="
                                            message.sender_id === currentUser.id ? 'text-right' : ''
                                        "
                                    >
                                        {{ message.sender.name }} · {{ time(message.created_at) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <form
                        class="border-t border-slate-100 bg-white p-3 sm:p-4"
                        @submit.prevent="send"
                    >
                        <div
                            class="mx-auto flex max-w-3xl items-end gap-2 rounded-2xl border border-slate-200 p-2 focus-within:border-blue-400 focus-within:ring-4 focus-within:ring-blue-500/10"
                        >
                            <button
                                type="button"
                                class="grid h-9 w-9 shrink-0 place-items-center text-slate-400"
                            >
                                <Paperclip class="h-5 w-5" /></button
                            ><textarea
                                v-model="form.body"
                                class="max-h-32 min-h-9 flex-1 resize-none border-0 bg-transparent py-2 text-sm outline-none focus:ring-0"
                                rows="1"
                                placeholder="Write a message…"
                                @keydown.enter.exact.prevent="send"
                            /><button
                                class="ui-button-primary h-9 w-9 shrink-0 px-0"
                                :disabled="form.processing || !form.body.trim()"
                            >
                                <Send class="h-4 w-4" />
                            </button>
                        </div>
                    </form>
                </main>
                <div v-else class="hidden place-items-center md:grid">
                    <EmptyState
                        title="Start a conversation"
                        description="Select a conversation from the list."
                    />
                </div>
            </div>
        </div>
        <!-- <button
            class="fixed bottom-6 right-6 z-40 grid h-14 w-14 place-items-center rounded-full bg-blue-600 text-white shadow-xl transition hover:bg-blue-700"
            aria-label="Start a new conversation"
            title="Start a new conversation"
            @click="newConversationOpen = true"
        >
            <MessageSquarePlus class="h-6 w-6" />
        </button> -->
        <Modal
            :open="newConversationOpen"
            title="Start a new conversation"
            description="Pick one person for a direct message, or several for a group."
            @close="closeNewConversation"
        >
            <div class="space-y-4">
                <div class="relative">
                    <Search
                        class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                    /><input
                        v-model="memberSearch"
                        class="ui-input pl-9"
                        placeholder="Search people"
                    />
                </div>
                <div
                    class="max-h-72 divide-y divide-slate-100 overflow-y-auto rounded-xl border border-slate-200"
                >
                    <label
                        v-for="member in filteredMembers"
                        :key="member.id"
                        class="flex cursor-pointer items-center gap-3 p-3 hover:bg-slate-50"
                    >
                        <input
                            type="checkbox"
                            class="h-4 w-4 rounded border-slate-300 text-blue-600"
                            :checked="selectedMemberIds.includes(member.id)"
                            @change="toggleMember(member.id)"
                        />
                        <img
                            :src="
                                member.avatar ||
                                `https://ui-avatars.com/api/?name=${encodeURIComponent(member.name)}`
                            "
                            class="h-8 w-8 rounded-full object-cover"
                        />
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold">{{ member.name }}</p>
                            <p class="truncate text-xs text-slate-400">{{ member.email }}</p>
                        </div>
                    </label>
                    <p
                        v-if="!filteredMembers.length"
                        class="p-4 text-center text-sm text-slate-400"
                    >
                        No matching people.
                    </p>
                </div>
                <div v-if="selectedMemberIds.length > 1">
                    <label class="ui-label">Group name (optional)</label>
                    <input v-model="groupName" class="ui-input" placeholder="e.g. Design squad" />
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" class="ui-button-secondary" @click="closeNewConversation">
                        Cancel
                    </button>
                    <button
                        class="ui-button-primary"
                        :disabled="!selectedMemberIds.length || creatingConversation"
                        @click="submitNewConversation"
                    >
                        {{ selectedMemberIds.length > 1 ? 'Create group' : 'Start conversation' }}
                    </button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
