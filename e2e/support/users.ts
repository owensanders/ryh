/** A name no other record in the run will share. */
export const uniqueName = (prefix: string): string => `${prefix} ${Date.now().toString(36)}${Math.floor(Math.random() * 1000)}`;
