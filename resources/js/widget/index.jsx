import React from 'react';
import { createRoot } from 'react-dom/client';
import ChatWidget from './ChatWidget';

(function boot() {
    const script = document.currentScript;

    if (!script) {
        return;
    }

    const kbId = script.getAttribute('data-kb-id');

    if (!kbId) {
        console.warn('[DocAgent] Missing data-kb-id on the widget script tag.');
        return;
    }

    const apiBase = (script.getAttribute('data-api-url') || new URL(script.src).origin).replace(/\/$/, '');

    if (document.getElementById('docagent-widget-host')) {
        return;
    }

    const host = document.createElement('div');
    host.id = 'docagent-widget-host';
    document.body.appendChild(host);

    const shadow = host.attachShadow({ mode: 'open' });
    const mount = document.createElement('div');
    shadow.appendChild(mount);

    createRoot(mount).render(<ChatWidget kbId={kbId} apiBase={apiBase} />);
})();
