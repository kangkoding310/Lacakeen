import { computed, ref } from 'vue';
import { defineStore } from 'pinia';

export const useDateStore = defineStore('date', () => {
    const today = ref(new Date());

    const formattedDate = computed(() =>
        today.value.toLocaleDateString('en-US', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        })
    );

    const dayName = computed(() => today.value.toLocaleDateString('en-US', { weekday: 'long' }));

    return { today, formattedDate, dayName };
});
