import type * as InertiaReact from '@inertiajs/react';
import type React from 'react';
import type * as ReactJSXRuntime from 'react/jsx-runtime';

type PluginPageComponent = unknown;

declare global {
    interface Window {
        NebulaPlugins?: Record<string, PluginPageComponent>;
        NebulaCMS?: {
            registerInertiaPage: (name: string, component: PluginPageComponent) => void;
            getInertiaPage: (name: string) => PluginPageComponent | undefined;
        };
        React?: typeof React;
        ReactJSXRuntime?: typeof ReactJSXRuntime;
        InertiaReact?: typeof InertiaReact;
    }
}

export {};
