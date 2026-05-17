import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

const corePages = import.meta.glob('../pages/**/*.tsx');
const pluginPages = import.meta.glob('../../../plugins/*/resources/js/pages/**/*.tsx');

const availablePages = {
    ...corePages,
    ...pluginPages,
};

function hasOwnPage(pages: Record<string, unknown>, key: string): boolean {
    return Object.prototype.hasOwnProperty.call(pages, key);
}

export function resolveInertiaPage(name: string) {
    const corePath = `../pages/${name}.tsx`;

    const pluginSuffix = `/resources/js/pages/${name}.tsx`;
    const pluginPath = Object.keys(pluginPages).find((path) => path.endsWith(pluginSuffix));

    if (name.startsWith('Plugins/') && pluginPath) {
        return resolvePageComponent(pluginPath, availablePages);
    }

    if (hasOwnPage(availablePages, corePath)) {
        return resolvePageComponent(corePath, availablePages);
    }

    if (!pluginPath) {
        throw new Error(`Page not found: ${name}`);
    }

    return resolvePageComponent(pluginPath, availablePages);
}
