export const MAILPIT_URL = process.env.MAILPIT_URL ?? 'http://localhost:8025';

interface MailpitSearch {
    messages: { ID: string }[];
}

interface MailpitMessage {
    Text: string;
}

/**
 * The first link in the newest email to `address` whose URL contains `match`,
 * polling briefly because mail is sent as part of the request that triggers it.
 */
export async function linkFromLatestEmail(address: string, match: string, timeoutMs = 10_000): Promise<string> {
    const deadline = Date.now() + timeoutMs;

    while (Date.now() < deadline) {
        const search = (await (await fetch(`${MAILPIT_URL}/api/v1/search?query=${encodeURIComponent(`to:${address}`)}`)).json()) as MailpitSearch;

        if (search.messages.length > 0) {
            const message = (await (await fetch(`${MAILPIT_URL}/api/v1/message/${search.messages[0].ID}`)).json()) as MailpitMessage;
            const link = message.Text.match(/https?:\/\/\S+/g)?.find((url) => url.includes(match));

            if (link) {
                return link.replace(/[)\]>.,]+$/, '');
            }
        }

        await new Promise((resolve) => setTimeout(resolve, 250));
    }

    throw new Error(`No email to ${address} containing a "${match}" link arrived within ${timeoutMs}ms`);
}
