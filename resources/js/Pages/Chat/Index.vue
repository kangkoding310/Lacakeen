<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import AvatarStack from '@/Components/ui/AvatarStack.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import { usePermissions } from '@/composables/usePermissions';
import type { ConversationSummary } from '@/types/conversation';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { MoreHorizontal, Paperclip, Phone, Search, Send, Video } from 'lucide-vue-next';
import { nextTick, onMounted, ref } from 'vue';

const props = defineProps<{
    conversations: ConversationSummary[];
    activeConversation: ConversationSummary | null;
}>();
const { currentUser } = usePermissions();
const form = useForm({ body: '' });
const messageList = ref<HTMLElement | null>(null);
const send = () =>
    form.post(route('chat.messages.store', props.activeConversation!.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            nextTick(() =>
                messageList.value?.scrollTo({
                    top: messageList.value.scrollHeight,
                    behavior: 'smooth',
                })
            );
        },
    });
const time = (date: string) =>
    new Intl.DateTimeFormat('en-US', { hour: 'numeric', minute: '2-digit' }).format(new Date(date));
onMounted(() =>
    nextTick(() => {
        if (messageList.value) messageList.value.scrollTop = messageList.value.scrollHeight;
    })
);
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
                        <h1 class="text-xl font-bold">Messages</h1>
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
                                            conversation.participants.map((p) => p.name).join(', ')
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
                                    {{ activeConversation.name || 'Direct message' }}
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
                                    message.sender_id === currentUser.id ? 'flex-row-reverse' : ''
                                "
                            >
                                <img
                                    :src="
                                        message.sender.avatar ||
                                        `https://ui-avatars.com/api/?name=${encodeURIComponent(message.sender?.name)}`
                                    "
                                    class="h-8 w-8 rounded-full object-cover"
                                />
                                <div class="max-w-[75%]">
                                    <div
                                        class="rounded-2xl px-4 py-3 text-sm leading-6 shadow-sm"
                                        :class="
                                            message.sender_id === currentUser.id
                                                ? 'rounded-tr-md bg-blue-600 text-white'
                                                : 'rounded-tl-md bg-white text-slate-700'
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
            </div></div
    ></AppLayout>
</template>
