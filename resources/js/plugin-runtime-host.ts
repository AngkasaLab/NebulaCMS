import * as InertiaReact from '@inertiajs/react';
import React from 'react';
import * as ReactJSXRuntime from 'react/jsx-runtime';

type PluginPageComponent = unknown;

window.React = React;
window.ReactJSXRuntime = ReactJSXRuntime;
window.InertiaReact = InertiaReact;
window.NebulaPlugins ??= {};

const nebulaRuntime = window.NebulaCMS ?? {
    registerInertiaPage: (name: string, component: PluginPageComponent) => {
        window.NebulaPlugins ??= {};
        window.NebulaPlugins[name] = component;
    },
    getInertiaPage: (name: string) => window.NebulaPlugins?.[name],
};

window.NebulaCMS = nebulaRuntime;

export {};
