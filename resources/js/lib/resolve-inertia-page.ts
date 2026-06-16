import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

const corePages = import.meta.glob('../pages/**/*.tsx');
const availablePages = {
    ...corePages,
};

type ResolvedPageModule = {
    default: unknown;
};

type PluginRuntimeWindow = Window & {
    NebulaCMS?: {
        getInertiaPage: (name: string) => unknown;
    };
    NebulaPlugins?: Record<string, unknown>;
};

function hasOwnPage(pages: Record<string, unknown>, key: string): boolean {
    return Object.prototype.hasOwnProperty.call(pages, key);
}

function isResolvedPageModule(value: unknown): value is ResolvedPageModule {
    return typeof value === 'object' && value !== null && 'default' in value;
}

function resolvePluginPage(name: string): Promise<ResolvedPageModule> {
    const runtimeWindow = window as PluginRuntimeWindow;
    const page = runtimeWindow.NebulaCMS?.getInertiaPage(name) ?? runtimeWindow.NebulaPlugins?.[name];

    if (!page) {
        throw new Error(`Plugin page not found: ${name}`);
    }

    if (isResolvedPageModule(page)) {
        return Promise.resolve(page);
    }

    return Promise.resolve({ default: page });
}

export function resolveInertiaPage(name: string) {
    const corePath = `../pages/${name}.tsx`;

    if (hasOwnPage(availablePages, corePath)) {
        return resolvePageComponent(corePath, availablePages);
    }

    if (name.startsWith('Plugins/')) {
        return resolvePluginPage(name);
    }

    throw new Error(`Page not found: ${name}`);
}
