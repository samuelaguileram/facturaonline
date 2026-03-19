<?php

declare(strict_types=1);

namespace FacturaOnline\Http;

use FacturaOnline\Contracts\TransportInterface;
use FacturaOnline\Exceptions\ApiException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Utils;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;

final class HttpClient implements TransportInterface
{
    private const DEFAULT_CONNECT_TIMEOUT = 10.0;
    private const DEFAULT_TIMEOUT = 30.0;
    private const DEFAULT_PLUGIN_ID = '9d4095c8f7ed5785cb14c0e3b033eeb8252416ed';

    private Client $client;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $secretKey,
        string $baseUrl,
        array $config = [],
        ?Client $client = null
    ) {
        $this->client = $client ?? new Client([
            'base_uri' => rtrim($baseUrl, '/') . '/',
            'connect_timeout' => $config['connect_timeout'] ?? self::DEFAULT_CONNECT_TIMEOUT,
            'timeout' => $config['timeout'] ?? self::DEFAULT_TIMEOUT,
            'http_errors' => false,
        ]);
    }

    public function get(string $endpoint, array $query = []): array
    {
        $options = ['headers' => $this->headers()];

        if ($query !== []) {
            $options['query'] = $query;
        }

        /** @var array $response */
        $response = $this->request('GET', $endpoint, $options, 'json');

        return $response;
    }

    public function getRaw(string $endpoint): string
    {
        /** @var string $response */
        $response = $this->request('GET', $endpoint, ['headers' => $this->headers(false, false)], 'raw');

        return $response;
    }

    public function download(string $endpoint, string $savePath): string
    {
        $this->assertDownloadPath($savePath);

        try {
            $this->request('GET', $endpoint, [
                'headers' => $this->headers(false, false),
                'sink' => $savePath,
            ], 'empty');
        } catch (ApiException $exception) {
            if (is_file($savePath)) {
                @unlink($savePath);
            }

            throw $exception;
        }

        return $savePath;
    }

    public function post(string $endpoint, array $data = []): array
    {
        /** @var array $response */
        $response = $this->request('POST', $endpoint, [
            'headers' => $this->headers(),
            'json' => $data,
        ], 'json');

        return $response;
    }

    public function postMultipart(string $endpoint, array $data = [], array $files = []): array
    {
        /** @var array $response */
        $response = $this->request('POST', $endpoint, [
            'headers' => $this->headers(false),
            'multipart' => $this->buildMultipartPayload($data, $files),
        ], 'json');

        return $response;
    }

    public function postAuto(string $endpoint, array $data = [], array $files = []): array
    {
        if ($files !== []) {
            return $this->postMultipart($endpoint, $data, $files);
        }

        return $this->post($endpoint, $data);
    }

    public function rawClient(): Client
    {
        return $this->client;
    }

    private function headers(bool $json = true, bool $acceptJson = true): array
    {
        $headers = [
            'Accept' => $acceptJson ? 'application/json' : '*/*',
            'F-PLUGIN' => self::DEFAULT_PLUGIN_ID,
            'F-Api-Key' => $this->apiKey,
            'F-Secret-Key' => $this->secretKey,
        ];

        if ($json) {
            $headers['Content-Type'] = 'application/json';
        }

        return $headers;
    }

    private function request(string $method, string $endpoint, array $options = [], string $responseType = 'json'): array|string|null
    {
        $normalizedEndpoint = ltrim($endpoint, '/');

        try {
            $response = $this->client->request($method, $normalizedEndpoint, $options);
        } catch (RequestException $exception) {
            $response = $exception->getResponse();

            if ($response !== null) {
                throw $this->buildApiException($normalizedEndpoint, $response, $exception);
            }

            throw new ApiException(
                sprintf('Network error calling "%s": %s', $normalizedEndpoint, $exception->getMessage()),
                null,
                null,
                null,
                $normalizedEndpoint,
                ['method' => $method],
                $exception
            );
        } catch (GuzzleException $exception) {
            throw new ApiException(
                sprintf('HTTP client error calling "%s": %s', $normalizedEndpoint, $exception->getMessage()),
                null,
                null,
                null,
                $normalizedEndpoint,
                ['method' => $method],
                $exception
            );
        }

        if ($response->getStatusCode() >= 400) {
            throw $this->buildApiException($normalizedEndpoint, $response);
        }

        return $this->parseResponse($normalizedEndpoint, $response, $responseType);
    }

    private function parseResponse(string $endpoint, ResponseInterface $response, string $responseType): array|string|null
    {
        return match ($responseType) {
            'json' => $this->decodeJsonResponse($endpoint, $response),
            'raw' => (string) $response->getBody(),
            'empty' => null,
            default => throw new InvalidArgumentException(sprintf('Unsupported response type "%s".', $responseType)),
        };
    }

    private function decodeJsonResponse(string $endpoint, ResponseInterface $response): array
    {
        $rawBody = (string) $response->getBody();

        if ($rawBody === '') {
            return [];
        }

        $decoded = json_decode($rawBody, true);

        if (!is_array($decoded) || json_last_error() !== JSON_ERROR_NONE) {
            throw new ApiException(
                sprintf('Expected JSON response from "%s" but received invalid payload.', $endpoint),
                $response->getStatusCode(),
                null,
                $rawBody,
                $endpoint
            );
        }

        return $decoded;
    }

    private function buildMultipartPayload(array $data, array $files): array
    {
        $multipart = [];

        foreach ($data as $name => $value) {
            $this->appendMultipartField($multipart, (string) $name, $value);
        }

        foreach ($files as $name => $filePath) {
            if (!is_string($filePath) || $filePath === '') {
                throw new InvalidArgumentException(sprintf('Invalid file path for "%s".', (string) $name));
            }

            if (!is_file($filePath)) {
                throw new InvalidArgumentException(sprintf('File not found: %s', $filePath));
            }

            $multipart[] = [
                'name' => (string) $name,
                'contents' => Utils::tryFopen($filePath, 'r'),
                'filename' => basename($filePath),
            ];
        }

        return $multipart;
    }

    private function appendMultipartField(array &$multipart, string $name, mixed $value): void
    {
        if (is_array($value)) {
            foreach ($value as $nestedKey => $nestedValue) {
                $this->appendMultipartField($multipart, sprintf('%s[%s]', $name, (string) $nestedKey), $nestedValue);
            }

            return;
        }

        $multipart[] = [
            'name' => $name,
            'contents' => match (true) {
                is_bool($value) => $value ? '1' : '0',
                $value === null => '',
                is_scalar($value) => (string) $value,
                default => json_encode($value, JSON_THROW_ON_ERROR),
            },
        ];
    }

    private function assertDownloadPath(string $savePath): void
    {
        if ($savePath === '') {
            throw new InvalidArgumentException('Download path cannot be empty.');
        }

        $directory = dirname($savePath);

        if (!is_dir($directory)) {
            throw new InvalidArgumentException(sprintf('Download directory does not exist: %s', $directory));
        }

        if (!is_writable($directory)) {
            throw new InvalidArgumentException(sprintf('Download directory is not writable: %s', $directory));
        }
    }

    private function buildApiException(
        string $endpoint,
        ResponseInterface $response,
        ?GuzzleException $previous = null
    ): ApiException {
        $rawBody = (string) $response->getBody();
        $decoded = json_decode($rawBody, true);
        $responseBody = is_array($decoded) ? $decoded : null;
        $message = $this->extractErrorMessage($responseBody, $rawBody, $response->getStatusCode(), $endpoint);

        return new ApiException(
            $message,
            $response->getStatusCode(),
            $responseBody,
            $rawBody,
            $endpoint,
            ['status' => $response->getStatusCode()],
            $previous
        );
    }

    private function extractErrorMessage(?array $responseBody, string $rawBody, int $statusCode, string $endpoint): string
    {
        if ($responseBody !== null) {
            foreach (['message', 'mensaje', 'error'] as $key) {
                if (isset($responseBody[$key]) && is_string($responseBody[$key]) && $responseBody[$key] !== '') {
                    return $responseBody[$key];
                }
            }
        }

        if ($rawBody !== '') {
            return sprintf('API error %d calling "%s": %s', $statusCode, $endpoint, $rawBody);
        }

        return sprintf('API error %d calling "%s".', $statusCode, $endpoint);
    }
}
