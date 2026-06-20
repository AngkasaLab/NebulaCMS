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

function loadScript(src: string): Promise<void> {
    return new Promise((resolve, reject) => {
        if (document.querySelector(`script[src="${src}"]`)) {
            resolve();
            return;
        }

        const script = document.createElement('script');
        script.type = 'module';
        script.src = src;
        script.onload = () => resolve();
        script.onerror = () => reject(new Error(`Failed to load script: ${src}`));
        document.head.appendChild(script);
    });
}

function loadStyle(href: string): Promise<void> {
    return new Promise((resolve, reject) => {
        if (document.querySelector(`link[href="${href}"]`)) {
            resolve();
            return;
        }

        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = href;
        link.onload = () => resolve();
        link.onerror = () => reject(new Error(`Failed to load stylesheet: ${href}`));
        document.head.appendChild(link);
    });
}

async function resolvePluginPage(name: string): Promise<ResolvedPageModule> {
    const runtimeWindow = window as PluginRuntimeWindow;
    let page = runtimeWindow.NebulaCMS?.getInertiaPage(name) ?? runtimeWindow.NebulaPlugins?.[name];

    if (!page) {
        let pluginAssets: Record<string, { scripts: string[]; styles: string[] }> | undefined;
        try {
            const appElement = document.getElementById('app');
            if (appElement && appElement.dataset.page) {
                const pageData = JSON.parse(appElement.dataset.page);
                pluginAssets = pageData.props?.pluginAssets;
            }
        } catch (e) {
            console.error('Failed to parse initial Inertia page data:', e);
        }
        const assets = pluginAssets?.[name];

        if (assets) {
            try {
                if (assets.styles && assets.styles.length > 0) {
                    await Promise.all(assets.styles.map(loadStyle));
                }
                if (assets.scripts && assets.scripts.length > 0) {
                    await Promise.all(assets.scripts.map(loadScript));
                }
                page = runtimeWindow.NebulaCMS?.getInertiaPage(name) ?? runtimeWindow.NebulaPlugins?.[name];
            } catch (error) {
                console.error(`Error loading plugin assets for ${name}:`, error);
            }
        }
    }

    if (!page) {
        throw new Error(`Plugin page not found: ${name}`);
    }

    if (isResolvedPageModule(page)) {
        return page;
    }

    return { default: page };
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
