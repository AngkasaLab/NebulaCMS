import Index from './StarterAdminIndex';

const pages = {
    'Plugins/StarterInertiaPrebuilt/Admin/Index': Index,
} as const;

for (const [name, component] of Object.entries(pages)) {
    if (window.NebulaCMS?.registerInertiaPage) {
        window.NebulaCMS.registerInertiaPage(name, component);
        continue;
    }

    window.NebulaPlugins ??= {};
    window.NebulaPlugins[name] = component;
}
