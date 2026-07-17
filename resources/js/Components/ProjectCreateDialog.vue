<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/ui/Modal.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps<{ open: boolean }>();
const emit = defineEmits<{ close: [] }>();
const page = usePage();
const workspace = computed(() => page.props.projectNavigation?.workspace);
const form = useForm({ name: '', prefix: '', description: '', color: '#2563EB', workspace_id: '' });

const submit = () => {
    form.workspace_id = workspace.value?.id || '';
    form.post(route('projects.store'), {
        onSuccess: () => {
            form.reset();
            emit('close');
        },
    });
};
</script>

<template>
    <Modal
        :open="open"
        title="Create project"
        description="Set up a dedicated workspace for your team's work."
        @close="$emit('close')"
    >
        <form class="space-y-4" @submit.prevent="submit">
            <div>
                <label class="ui-label">Project name</label
                ><input
                    v-model="form.name"
                    class="ui-input"
                    autofocus
                    required
                    placeholder="e.g. Mobile application"
                />
                <InputError class="mt-1" :message="form.errors.name" />
            </div>
            <div class="grid grid-cols-[1fr_80px] gap-3">
                <div>
                    <label class="ui-label">Prefix</label>
                    <input
                        v-model="form.prefix"
                        class="ui-input uppercase"
                        maxlength="12"
                        required
                        placeholder="APP"
                        @input="form.prefix = form.prefix.replace(/[^a-zA-Z0-9]/g, '')"
                    />
                    <InputError class="mt-1" :message="form.errors.prefix" />
                </div>
                <div>
                    <label class="ui-label">Color</label
                    ><input v-model="form.color" type="color" class="ui-input p-1" />
                </div>
            </div>
            <div>
                <label class="ui-label">Description</label
                ><textarea
                    v-model="form.description"
                    class="ui-input min-h-24 resize-y py-2"
                    placeholder="What will this project deliver?"
                />
            </div>
            <InputError :message="form.errors.workspace_id" />
            <div class="flex justify-end gap-2">
                <button type="button" class="ui-button-secondary" @click="$emit('close')">
                    Cancel</button
                ><button class="ui-button-primary" :disabled="form.processing || !workspace">
                    {{ form.processing ? 'Creating…' : 'Create project' }}
                </button>
            </div>
        </form>
    </Modal>
</template>
