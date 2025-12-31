import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig(({ isSsrBuild }) => ({
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    build: {
        cssCodeSplit: !isSsrBuild,  // Disable for SSR to avoid unnecessary splits
        rollupOptions: {
            output: {
                manualChunks: isSsrBuild
                    ? undefined  // Disable manual chunks for SSR
                    : (id) => {
                        // Export helper utilities
                        if (id.includes('export-helper')) {
                            return 'helper';
                        }

                        if (id.includes('node_modules')) {
                            // Inertia.js (core navigation, loaded on every page)
                            if (id.includes('@inertiajs')) {
                                return 'glue';
                            }

                            // VueUse composables (frequently used utilities)
                            if (id.includes('@vueuse')) {
                                return 'hooks';
                            }

                            // Chart libraries (only loaded on dashboard/analytics pages)
                            if (id.includes('apexcharts') || id.includes('vue3-apexcharts')) {
                                return 'graph';
                            }

                            // UI Component Library (reka-ui/radix-vue primitives)
                            if (
                                id.includes('reka-ui') ||
                                id.includes('radix-vue') ||
                                id.includes('@floating-ui')
                            ) {
                                return 'widgets';
                            }

                            // Headless UI (modals, menus, etc.)
                            if (id.includes('@headlessui')) {
                                return 'headless';
                            }

                            // Tiptap editor + ProseMirror
                            if (id.includes('@tiptap') || id.includes('prosemirror')) {
                                return 'editor';
                            }

                            // Icon libraries (lucide) - split into smaller chunk
                            if (id.includes('lucide-vue-next')) {
                                return 'icons';
                            }

                            // Syntax highlighting
                            if (id.includes('highlight.js') || id.includes('lowlight')) {
                                return 'syntax';
                            }

                            // Drag and drop
                            if (id.includes('sortablejs')) {
                                return 'dnd';
                            }

                            // Utility libraries (class manipulation, merging)
                            if (
                                id.includes('clsx') ||
                                id.includes('tailwind-merge') ||
                                id.includes('class-variance-authority')
                            ) {
                                return 'styles';
                            }

                            // Toast/notification libraries
                            if (id.includes('vue-sonner')) {
                                return 'toast';
                            }

                            // User agent parsing (session/device detection)
                            if (id.includes('ua-parser-js')) {
                                return 'device';
                            }

                            // Laravel/Wayfinder utilities
                            if (id.includes('laravel-vite-plugin') || id.includes('wayfinder')) {
                                return 'router';
                            }

                            // Animation utilities
                            if (id.includes('tw-animate-css')) {
                                return 'motion';
                            }

                            // Vue core ecosystem (most critical, loaded on every page)
                            if (id.includes('/vue/') || id.includes('@vue/')) {
                                return 'core';
                            }

                            // Any remaining node_modules go to vendor
                            return 'vendor';
                        }
                    },
            },
        },
    },
}));