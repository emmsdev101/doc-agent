import './bootstrap';
import { createInertiaApp, router } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import '../css/app.css';

router.on('invalid', (event) => {
    event.preventDefault();
});

createInertiaApp({
    title: (title) => (title ? `${title} · DocAgent` : 'DocAgent'),
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.jsx', { eager: true });
        return pages[`./Pages/${name}.jsx`];
    },
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
    progress: {
        color: '#4f46e5',
    },
});
