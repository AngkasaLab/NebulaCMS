type ReactDOMModule = typeof import('react-dom');

function getReactDOM(): ReactDOMModule {
    const reactDOM = window.ReactDOM as ReactDOMModule | undefined;

    if (!reactDOM) {
        throw new Error('Nebula plugin ReactDOM runtime host is not loaded.');
    }

    return reactDOM;
}

const ReactDOMShim = getReactDOM();

export default ReactDOMShim;
export const createPortal = ReactDOMShim.createPortal;
export const flushSync = ReactDOMShim.flushSync;
export const unstable_batchedUpdates = ReactDOMShim.unstable_batchedUpdates;
