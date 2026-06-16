import { createContext, useContext, useEffect, useState, type Context, type ReactNode } from 'react';
import { type Appearance } from '@/types/appearance';

interface AppearanceContextType {
    appearance: Appearance;
    updateAppearance: (mode: Appearance) => void;
}

const globalKey = '__NebulaCMS_AppearanceContext__' as const;

type AppearanceContextGlobal = {
    [globalKey]?: Context<AppearanceContextType | undefined>;
};

const AppearanceContext = (() => {
    if (typeof window !== 'undefined') {
        const win = window as Window & AppearanceContextGlobal;

        if (!win[globalKey]) {
            win[globalKey] = createContext<AppearanceContextType | undefined>(undefined);
        }

        return win[globalKey];
    }

    return createContext<AppearanceContextType | undefined>(undefined);
})();

const prefersDark = () => {
    if (typeof window === 'undefined') return false;
    return window.matchMedia('(prefers-color-scheme: dark)').matches;
};

const setCookie = (name: string, value: string, days = 365) => {
    if (typeof document === 'undefined') return;
    const maxAge = days * 24 * 60 * 60;
    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const getInitialTheme = (): Appearance => {
    if (typeof window === 'undefined') return 'system';
    return (localStorage.getItem('appearance') as Appearance) || 'system';
};

const applyTheme = (appearance: Appearance) => {
    if (typeof document === 'undefined') return;

    const root = document.documentElement;
    const isDark = appearance === 'dark' || (appearance === 'system' && prefersDark());

    root.classList.remove('light', 'dark');
    root.classList.add(isDark ? 'dark' : 'light');
};

export function AppearanceProvider({ children }: { children: ReactNode }) {
    const [appearance, setAppearance] = useState<Appearance>(getInitialTheme);
    const [mounted, setMounted] = useState(false);

    // Handle initial mount
    useEffect(() => {
        setMounted(true);
        applyTheme(appearance);
    }, [appearance]);

    // Handle system theme changes
    useEffect(() => {
        if (!mounted) return;

        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        const handleChange = () => {
            if (appearance === 'system') {
                applyTheme('system');
            }
        };

        mediaQuery.addEventListener('change', handleChange);
        return () => mediaQuery.removeEventListener('change', handleChange);
    }, [appearance, mounted]);

    // Handle appearance changes
    useEffect(() => {
        if (!mounted) return;

        applyTheme(appearance);
        localStorage.setItem('appearance', appearance);
        setCookie('appearance', appearance);
    }, [appearance, mounted]);

    const updateAppearance = (mode: Appearance) => {
        setAppearance(mode);
    };

    // Prevent flash of wrong theme
    if (!mounted) {
        return null;
    }

    return (
        <AppearanceContext.Provider value={{ appearance, updateAppearance }}>
            {children}
        </AppearanceContext.Provider>
    );
}

export function useAppearance() {
    const context = useContext(AppearanceContext);
    if (context === undefined) {
        throw new Error('useAppearance must be used within an AppearanceProvider');
    }
    return context;
}
