type ReactModule = typeof import('react');

function getReact(): ReactModule {
    const react = window.React as ReactModule | undefined;

    if (!react) {
        throw new Error('Nebula plugin runtime host is not loaded.');
    }

    return react;
}

const ReactShim = getReact();

export default ReactShim;
export const Children = ReactShim.Children;
export const Component = ReactShim.Component;
export const Fragment = ReactShim.Fragment;
export const Profiler = ReactShim.Profiler;
export const PureComponent = ReactShim.PureComponent;
export const StrictMode = ReactShim.StrictMode;
export const Suspense = ReactShim.Suspense;
export const cloneElement = ReactShim.cloneElement;
export const createContext = ReactShim.createContext;
export const createElement = ReactShim.createElement;
export const createRef = ReactShim.createRef;
export const forwardRef = ReactShim.forwardRef;
export const isValidElement = ReactShim.isValidElement;
export const lazy = ReactShim.lazy;
export const memo = ReactShim.memo;
export const startTransition = ReactShim.startTransition;
export const use = ReactShim.use;
export const useActionState = ReactShim.useActionState;
export const useCallback = ReactShim.useCallback;
export const useContext = ReactShim.useContext;
export const useDebugValue = ReactShim.useDebugValue;
export const useDeferredValue = ReactShim.useDeferredValue;
export const useEffect = ReactShim.useEffect;
export const useId = ReactShim.useId;
export const useImperativeHandle = ReactShim.useImperativeHandle;
export const useInsertionEffect = ReactShim.useInsertionEffect;
export const useLayoutEffect = ReactShim.useLayoutEffect;
export const useMemo = ReactShim.useMemo;
export const useOptimistic = ReactShim.useOptimistic;
export const useReducer = ReactShim.useReducer;
export const useRef = ReactShim.useRef;
export const useState = ReactShim.useState;
export const useSyncExternalStore = ReactShim.useSyncExternalStore;
export const useTransition = ReactShim.useTransition;
