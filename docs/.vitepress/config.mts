import { defineConfig } from 'vitepress';

export default defineConfig({
  title: 'Laravel Error Tracker',
  description: 'Laravel-first self-hosted error tracking package',
  base: '/laravel-error-tracker/',
  cleanUrls: true,
  themeConfig: {
    nav: [
      { text: 'Guide', link: '/installation' },
      { text: 'GitHub', link: 'https://github.com/hewerthomn/laravel-error-tracker' },
    ],
    sidebar: [
      {
        text: 'Getting started',
        items: [
          { text: 'Overview', link: '/' },
          { text: 'Installation', link: '/installation' },
          { text: 'Basic Setup', link: '/basic-setup' },
          { text: 'Configuration', link: '/configuration' },
          { text: 'Updating', link: '/updating' },
        ],
      },
      {
        text: 'Features',
        items: [
          { text: 'Dashboard', link: '/dashboard' },
          { text: 'Advanced Search', link: '/advanced-search' },
          { text: 'Smart Stack Trace', link: '/smart-stack-trace' },
          { text: 'Feedback', link: '/feedback' },
          { text: 'Notifications', link: '/notifications' },
          { text: 'Auto Resolve', link: '/auto-resolve' },
        ],
      },
      {
        text: 'Operations',
        items: [
          { text: 'Diagnostics', link: '/diagnostics' },
          { text: 'Demo Data', link: '/demo-data' },
          { text: 'Security', link: '/security' },
        ],
      },
    ],
    search: {
      provider: 'local',
    },
    socialLinks: [
      { icon: 'github', link: 'https://github.com/hewerthomn/laravel-error-tracker' },
    ],
  },
});
