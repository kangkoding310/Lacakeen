<script setup lang="ts">
import { useDialog } from '@/composables/useDialog';
import {
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogOverlay,
    AlertDialogPortal,
    AlertDialogRoot,
    AlertDialogTitle,
} from 'reka-ui';
import { nextTick, ref, watch } from 'vue';

const { state, confirmAction, cancelAction } = useDialog();
const inputRef = ref<HTMLInputElement | null>(null);

watch(
    () => state.open,
    (open) => {
        if (open && state.variant === 'prompt') nextTick(() => inputRef.value?.focus());
    }
);
</script>

<template>
    <AlertDialogRoot :open="state.open">
        <AlertDialogPortal>
            <AlertDialogOverlay class="fixed inset-0 z-[110] bg-slate-950/45 backdrop-blur-sm" />
            <AlertDialogContent
                class="fixed left-1/2 top-1/2 z-[110] w-full max-w-sm -translate-x-1/2 -translate-y-1/2 rounded-2xl border border-white/60 bg-white p-6 shadow-2xl focus:outline-none"
                @escape-key-down="cancelAction"
            >
                <form @submit.prevent="confirmAction">
                    <AlertDialogTitle class="text-lg font-bold text-slate-950">
                        {{ state.title }}
                    </AlertDialogTitle>
                    <AlertDialogDescription
                        v-if="state.description"
                        class="mt-1.5 text-sm text-slate-500"
                    >
                        {{ state.description }}
                    </AlertDialogDescription>
                    <template v-if="state.variant === 'prompt'">
                        <input
                            ref="inputRef"
                            v-model="state.value"
                            type="text"
                            class="ui-input mt-4"
                            :placeholder="state.placeholder"
                            @input="state.error = undefined"
                        />
                        <p v-if="state.error" class="mt-1.5 text-xs font-medium text-red-600">
                            {{ state.error }}
                        </p>
                    </template>
                    <div class="mt-6 flex justify-end gap-2.5">
                        <AlertDialogCancel
                            v-if="state.variant !== 'info'"
                            type="button"
                            class="ui-button-secondary"
                            @click="cancelAction"
                        >
                            {{ state.cancelText }}
                        </AlertDialogCancel>
                        <AlertDialogAction
                            type="submit"
                            :class="state.danger ? 'ui-button-danger' : 'ui-button-primary'"
                        >
                            {{ state.confirmText }}
                        </AlertDialogAction>
                    </div>
                </form>
            </AlertDialogContent>
        </AlertDialogPortal>
    </AlertDialogRoot>
</template>
