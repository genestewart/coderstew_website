import { defineStore } from 'pinia';

export const useUiStore = defineStore('ui', {
  state: () => ({ darkMode: false }),
  actions: {
    toggleDarkMode() {
      this.darkMode = !this.darkMode;
    },
  },
});
