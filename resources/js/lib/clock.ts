/** Times of day shown to the person reading, from their own clock, not the server's. */

export function greeting(now: Date = new Date()): string {
    const hour = now.getHours();

    return hour < 12 ? 'Good morning' : hour < 18 ? 'Good afternoon' : 'Good evening';
}

/** e.g. "Thursday 22 October". */
export function dateLine(now: Date = new Date()): string {
    return now.toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long' });
}
