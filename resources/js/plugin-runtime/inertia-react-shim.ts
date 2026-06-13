type InertiaReactModule = typeof import('@inertiajs/react');

function getInertiaReact(): InertiaReactModule {
    const inertiaReact = window.InertiaReact as InertiaReactModule | undefined;

    if (!inertiaReact) {
        throw new Error('Nebula plugin Inertia runtime host is not loaded.');
    }

    return inertiaReact;
}

const InertiaReactShim = getInertiaReact();

export const Deferred = InertiaReactShim.Deferred;
export const Head = InertiaReactShim.Head;
export const Link = InertiaReactShim.Link;
export const WhenVisible = InertiaReactShim.WhenVisible;
export const createInertiaApp = InertiaReactShim.createInertiaApp;
export const router = InertiaReactShim.router;
export const useForm = InertiaReactShim.useForm;
export const usePage = InertiaReactShim.usePage;
export const usePoll = InertiaReactShim.usePoll;
export const usePrefetch = InertiaReactShim.usePrefetch;
export const useRemember = InertiaReactShim.useRemember;
