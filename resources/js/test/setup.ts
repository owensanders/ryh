import { vi } from 'vitest';

// Ziggy's route() is a global in the app; in a test a route is just its name.
vi.stubGlobal(
    'route',
    (name: string, params?: Record<string, unknown>) => `/${name}${params ? `?${new URLSearchParams(params as Record<string, string>)}` : ''}`,
);
