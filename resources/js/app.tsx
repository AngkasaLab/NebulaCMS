import '../css/app.css';

import { createInertiaApp, router } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import { AppearanceProvider } from './contexts/appearance-context';
import { resolveInertiaPage } from './lib/resolve-inertia-page';

const appName = import.meta.env.VITE_APP_NAME || 'NebulaCMS';

import { ErrorBoundary } from './components/error-boundary';

// Prevent non-Inertia HTML responses from rendering in a modal overlay (e.g. Blade templates)
router.on('invalid', (event) => {
    if (event.detail.response.status === 200) {
        event.preventDefault();
        const response = event.detail.response as any;
        const redirectUrl = response.request?.responseURL || response.config?.url || '/';
        window.location.href = redirectUrl;
    }
});

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: resolveInertiaPage,
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(
            <ErrorBoundary>
                <AppearanceProvider>
                    <App {...props} />
                </AppearanceProvider>
            </ErrorBoundary>
        );
    },
    progress: {
        color: '#4B5563',
    },
});
