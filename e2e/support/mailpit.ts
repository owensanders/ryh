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

export interface Email {
    id: string;
    subject: string;
}

/** The newest email to `address` whose subject matches, polling briefly like {@link linkFromLatestEmail}. */
export async function waitForEmail(address: string, subject: RegExp, timeoutMs = 10_000): Promise<Email> {
    const deadline = Date.now() + timeoutMs;

    while (Date.now() < deadline) {
        const search = (await (await fetch(`${MAILPIT_URL}/api/v1/search?query=${encodeURIComponent(`to:${address}`)}`)).json()) as {
            messages: { ID: string; Subject: string }[];
        };
        const found = search.messages.find((message) => subject.test(message.Subject));

        if (found) {
            return { id: found.ID, subject: found.Subject };
        }

        await new Promise((resolve) => setTimeout(resolve, 250));
    }

    throw new Error(`No email to ${address} with a subject matching ${subject} arrived within ${timeoutMs}ms`);
}

/** Mailpit's rendering of the email's HTML part, as a mail client would show it. */
export const emailHtmlUrl = (email: Email): string => `${MAILPIT_URL}/view/${email.id}.html`;
