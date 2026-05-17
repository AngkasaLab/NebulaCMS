import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import { AppearanceProvider } from './contexts/appearance-context';
import { resolveInertiaPage } from './lib/resolve-inertia-page';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

import { ErrorBoundary } from './components/error-boundary';

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
