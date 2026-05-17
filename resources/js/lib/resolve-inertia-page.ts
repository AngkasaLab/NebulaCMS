import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

const corePages = import.meta.glob('../pages/**/*.tsx');
const pluginPages = import.meta.glob('../../../plugins/*/resources/js/pages/**/*.tsx');

const availablePages = {
    ...corePages,
    ...pluginPages,
};

export function resolveInertiaPage(name: string) {
    const corePath = `../pages/${name}.tsx`;

    if (corePath in availablePages) {
        return resolvePageComponent(corePath, availablePages);
    }

    return resolvePageComponent(
        `../../../plugins/*/resources/js/pages/${name}.tsx`,
        availablePages
    );
}
