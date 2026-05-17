import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User | null;
    permissions: string[];
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
    badge?: boolean | string;
    items?: { title: string; href: string }[];
    matchPaths?: string[];
}

export interface AdminNavigationItem {
    title: string;
    href: string;
    group: 'main' | 'content' | 'admin';
    icon?: string | null;
    items?: { title: string; href: string }[];
    badge?: boolean | string | null;
    match_paths?: string[];
}

/** Result shape from UpdateService::checkForUpdate(), also cached under `update_available`. */
export interface UpdateCheckResult {
    available: boolean;
    current: string;
    latest?: string;
    release_notes?: string;
    release_url?: string | null;
    download_url?: string | null;
    published_at?: string | null;
    error?: string;
}

export interface ZiggyConfigLike {
    location: string;
    [key: string]: unknown;
}

export interface SharedData {
    name: string;
    locale: string;
    translations: {
        posts: Record<string, string>;
        common: Record<string, string>;
    };
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: ZiggyConfigLike;
    sidebarOpen: boolean;
    updateAvailable: UpdateCheckResult | null;
    adminNavigation: AdminNavigationItem[];
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}
