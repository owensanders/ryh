import { execFileSync } from 'node:child_process';
import { MAILPIT_URL } from './support/mailpit';

/**
 * An empty `testing_e2e` database, documents disk and inbox for every run, and
 * no rate-limit counts left from the last one. Tests make their own households
 * (support/fixtures.ts), so nothing is seeded.
 */
export default async function globalSetup(): Promise<void> {
    try {
        execFileSync('./e2e/scripts/artisan.sh', ['migrate:fresh', '--force'], { stdio: 'pipe' });
        execFileSync('./e2e/scripts/artisan.sh', ['cache:clear'], { stdio: 'pipe' });
        execFileSync('./e2e/scripts/artisan.sh', ['tinker', '--execute', "Storage::disk('e2e')->deleteDirectory('documents');"], { stdio: 'pipe' });
    } catch (error) {
        const { stdout, stderr } = error as { stdout?: Buffer; stderr?: Buffer };
        throw new Error(`Preparing the e2e database failed:\n${stdout ?? ''}${stderr ?? ''}`);
    }

    await fetch(`${MAILPIT_URL}/api/v1/messages`, { method: 'DELETE' });
}
