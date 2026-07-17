<script setup lang="ts">
import Multiselect from '@vueform/multiselect';

defineOptions({ inheritAttrs: false });
withDefaults(
    defineProps<{
        options?: { value: unknown; label: string }[];
        placeholder?: string;
        mode?: 'single' | 'multiple' | 'tags';
        searchable?: boolean;
        canClear?: boolean;
    }>(),
    {
        options: () => [],
        placeholder: 'Select an option',
        mode: 'single',
        searchable: true,
        canClear: true,
    }
);
const model = defineModel();
</script>

<template>
    <Multiselect
        v-model="model"
        v-bind="$attrs"
        class="app-select"
        :options="options"
        :placeholder="placeholder"
        :mode="mode"
        :searchable="searchable"
        :can-clear="canClear"
        value-prop="value"
        label="label"
        track-by="label"
    >
        <template v-if="$slots.option" #option="slotProps"
            ><slot name="option" v-bind="slotProps"
        /></template>
        <template v-if="$slots.singlelabel" #singlelabel="slotProps"
            ><slot name="singlelabel" v-bind="slotProps"
        /></template>
    </Multiselect>
</template>
