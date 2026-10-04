/** The seeded "Parkers" household (database/seeders/HouseHubSeeder.php). */
export const PASSWORD = 'password';

export const users = {
    owner: { email: 'sarah@househub.test', name: 'Sarah Parker' },
    adult: { email: 'james@househub.test', name: 'James Parker' },
    teen: { email: 'mia@househub.test', name: 'Mia Parker' },
    child: { email: 'noah@househub.test', name: 'Noah Parker' },
} as const;

export type Role = keyof typeof users;

export const storageState = (role: Role): string => `e2e/.auth/${role}.json`;

/** A name no seeded record or earlier test in the run will share. */
export const uniqueName = (prefix: string): string => `${prefix} ${Date.now().toString(36)}${Math.floor(Math.random() * 1000)}`;
