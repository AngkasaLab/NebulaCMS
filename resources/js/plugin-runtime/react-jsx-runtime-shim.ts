type ReactJsxRuntimeModule = typeof import('react/jsx-runtime');

function getReactJsxRuntime(): ReactJsxRuntimeModule {
    const runtime = window.ReactJSXRuntime as ReactJsxRuntimeModule | undefined;

    if (!runtime) {
        throw new Error('Nebula plugin JSX runtime host is not loaded.');
    }

    return runtime;
}

const ReactJsxRuntimeShim = getReactJsxRuntime();

export const Fragment = ReactJsxRuntimeShim.Fragment;
export const jsx = ReactJsxRuntimeShim.jsx;
export const jsxs = ReactJsxRuntimeShim.jsxs;
