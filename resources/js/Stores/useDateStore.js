import { computed, ref } from 'vue';
import { defineStore } from 'pinia';

export const useDateStore = defineStore('date', () => {
  const today = ref(new Date());

  const formattedDate = computed(() => {
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    return today.value.toLocaleDateString('en-US', options);
  });

  const dayName = computed(() => {
    return today.value.toLocaleDateString('en-US', { weekday: 'long' });
  });

  return { today, formattedDate, dayName };
});
