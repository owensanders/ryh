import { execFileSync } from 'node:child_process';
import { MAILPIT_URL } from './support/mailpit';

/** A fresh, seeded `testing_e2e` database and an empty inbox for every run. */
export default async function globalSetup(): Promise<void> {
    try {
        execFileSync('./e2e/scripts/artisan.sh', ['migrate:fresh', '--seed', '--force'], { stdio: 'pipe' });
    } catch (error) {
        const { stdout, stderr } = error as { stdout?: Buffer; stderr?: Buffer };
        throw new Error(`Preparing the e2e database failed:\n${stdout ?? ''}${stderr ?? ''}`);
    }

    await fetch(`${MAILPIT_URL}/api/v1/messages`, { method: 'DELETE' });
}
