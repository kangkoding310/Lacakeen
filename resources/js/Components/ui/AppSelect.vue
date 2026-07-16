<script setup>
import Multiselect from '@vueform/multiselect';

defineOptions({ inheritAttrs: false });
defineProps({
    options: { type: Array, default: () => [] },
    placeholder: { type: String, default: 'Select an option' },
    mode: { type: String, default: 'single' },
    searchable: { type: Boolean, default: true },
    canClear: { type: Boolean, default: true },
});
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
        <template v-if="$slots.option" #option="slotProps"><slot name="option" v-bind="slotProps" /></template>
        <template v-if="$slots.singlelabel" #singlelabel="slotProps"><slot name="singlelabel" v-bind="slotProps" /></template>
    </Multiselect>
</template>
