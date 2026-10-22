<?php

declare(strict_types=1);

namespace Tests\Support;

use PHPUnit\Framework\Assert;
use RuntimeException;
use Stripe\HttpClient\ClientInterface;

/**
 * Stands in for Stripe's HTTP layer, so tests run the real Cashier and SDK
 * code against canned responses. Any request without a stubbed response
 * fails the test, so nothing can quietly reach the real API.
 */
class FakeStripeClient implements ClientInterface
{
    /** @var array<string, array{body: array<string, mixed>, status: int}> */
    private array $responses = [];

    /** @var list<array{method: string, path: string, params: array<string, mixed>, headers: list<string>}> */
    public array $requests = [];

    /** @param  array<string, mixed>  $body */
    public function respond(string $method, string $path, array $body, int $status = 200): self
    {
        $this->responses[strtoupper($method).' '.$path] = ['body' => $body, 'status' => $status];

        return $this;
    }

    /**
     * @param  list<string>  $headers
     * @param  array<string, mixed>  $params
     *
     * @return array{0: string, 1: int, 2: array<string, string>}
     */
    public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null): array
    {
        $method = strtoupper($method);
        $path = (string) parse_url($absUrl, PHP_URL_PATH);
        $this->requests[] = ['method' => $method, 'path' => $path, 'params' => $params, 'headers' => $headers];

        $response = $this->responses[$method.' '.$path]
            ?? throw new RuntimeException("Unexpected Stripe request: {$method} {$path}");

        return [json_encode($response['body'], JSON_THROW_ON_ERROR), $response['status'], []];
    }

    /**
     * @param  (callable(array<string, mixed>): bool)|null  $matching
     *
     * @return array<string, mixed> The params of the matching request.
     */
    public function assertRequested(string $method, string $path, ?callable $matching = null): array
    {
        $match = collect($this->requests)->first(fn (array $request): bool => $request['method'] === strtoupper($method)
            && $request['path'] === $path
            && ($matching === null || $matching($request['params'])));

        Assert::assertNotNull($match, "Expected a Stripe request to {$method} {$path}.");

        return $match['params'];
    }

    /** The value of a header sent with the first matching request, if it was sent. */
    public function headerOf(string $method, string $path, string $header): ?string
    {
        $match = collect($this->requests)->first(fn (array $request): bool => $request['method'] === strtoupper($method) && $request['path'] === $path);

        foreach ($match['headers'] ?? [] as $line) {
            [$name, $value] = array_map('trim', explode(':', $line, 2)) + [1 => ''];

            if (strcasecmp($name, $header) === 0) {
                return $value;
            }
        }

        return null;
    }

    public function assertNotRequested(string $method, string $path): void
    {
        foreach ($this->requests as $request) {
            Assert::assertFalse(
                $request['method'] === strtoupper($method) && $request['path'] === $path,
                "Didn't expect a Stripe request to {$method} {$path}.",
            );
        }
    }

    public function assertNothingRequested(): void
    {
        Assert::assertSame([], $this->requests, 'Expected no Stripe requests.');
    }
}
