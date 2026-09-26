import type { Auth } from './auth';

export type * from './auth';
export type * from './navigation';
export type * from './ui';

export type SharedData = {
    auth: Auth;
    name: string;
    sidebarOpen: boolean;
    locale: string;
    translations: Record<string, string>;
    [key: string]: unknown;
};
