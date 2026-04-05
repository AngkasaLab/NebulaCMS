import { LucideIcon } from "lucide-react";
import type { Config } from 'ziggy-js';

export interface Page {
    id: number;
    title: string;
    slug: string;
    content: string;
    featured_image: string | null;
    meta_description: string | null;
    meta_keywords: string | null;
    status: 'draft' | 'published' | 'pending_review';
    order: number;
    user: {
        id: number;
        name: string;
    };
    created_at: string;
    updated_at: string;
}

export interface NavSubItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href?: string;
    icon?: LucideIcon;
    items?: NavSubItem[];
    badge?: boolean | string;
    isActive?: boolean;
}

export interface BreadcrumbItem {
    title: string;
    href?: string;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar: string;
}

export interface Auth {
    user: User | null;
    permissions: string[];
}

/** Result shape from UpdateService::checkForUpdate(), also cached under `update_available`. */
export interface UpdateCheckResult {
    available: boolean;
    current: string;
    latest?: string;
    /** Short excerpt only; full Markdown is on GitHub. */
    release_notes?: string;
    /** GitHub release page URL for full notes and assets. */
    release_url?: string | null;
    download_url?: string | null;
    published_at?: string | null;
    error?: string;
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
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
    updateAvailable: UpdateCheckResult | null;
    [key: string]: unknown;
}

