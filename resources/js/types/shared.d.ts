import type { AdminNavigationItem, Auth } from './index';

interface ZiggyConfigLike {
    location: string;
    [key: string]: unknown;
}

declare interface SharedData {
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
    adminNavigation: AdminNavigationItem[];
    [key: string]: unknown;
}

export type { SharedData };
