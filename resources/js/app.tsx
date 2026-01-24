import '../css/app.css';

// Import Echo configuration for real-time broadcasting
import './echo';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';

import { initializeTheme } from './hooks/use-appearance';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.tsx`,
            import.meta.glob('./pages/**/*.tsx'),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(
            <StrictMode>
                <App {...props} />
            </StrictMode>,
        );
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();

// Handle flash messages from Laravel
document.addEventListener('DOMContentLoaded', () => {
    // Check for flash success messages
    const flashSuccess = document.querySelector('[data-flash="success"]');
    if (flashSuccess?.textContent?.trim()) {
        (
            window as Window & {
                showToast?: (message: string, type: string) => void;
            }
        ).showToast?.(flashSuccess.textContent.trim(), 'success');
    }

    // Check for flash error messages
    const flashError = document.querySelector('[data-flash="error"]');
    if (flashError?.textContent?.trim()) {
        (
            window as Window & {
                showToast?: (message: string, type: string) => void;
            }
        ).showToast?.(flashError.textContent.trim(), 'error');
    }

    // Check for flash warning messages
    const flashWarning = document.querySelector('[data-flash="warning"]');
    if (flashWarning?.textContent?.trim()) {
        (
            window as Window & {
                showToast?: (message: string, type: string) => void;
            }
        ).showToast?.(flashWarning.textContent.trim(), 'warning');
    }

    // Check for flash info messages
    const flashInfo = document.querySelector('[data-flash="info"]');
    if (flashInfo?.textContent?.trim()) {
        (
            window as Window & {
                showToast?: (message: string, type: string) => void;
            }
        ).showToast?.(flashInfo.textContent.trim(), 'info');
    }
});
