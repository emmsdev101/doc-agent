import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [react()],
    define: {
        'process.env.NODE_ENV': JSON.stringify('production'),
    },
    publicDir: false,
    build: {
        lib: {
            entry: 'resources/js/widget/index.jsx',
            name: 'DocAgentWidget',
            formats: ['iife'],
            fileName: () => 'widget.js',
        },
        outDir: 'public',
        emptyOutDir: false,
        sourcemap: false,
        cssCodeSplit: false,
        rollupOptions: {
            output: {
                inlineDynamicImports: true,
            },
        },
    },
});
