<?php

declare(strict_types=1);

namespace FacturaOnline\Exceptions;

use RuntimeException;
use Throwable;

final class ApiException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly ?int $statusCode = null,
        private readonly ?array $responseBody = null,
        private readonly ?string $responseRawBody = null,
        private readonly ?string $endpoint = null,
        private readonly array $context = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode ?? 0, $previous);
    }

    public function statusCode(): ?int
    {
        return $this->statusCode;
    }

    public function responseBody(): ?array
    {
        return $this->responseBody;
    }

    public function responseRawBody(): ?string
    {
        return $this->responseRawBody;
    }

    public function endpoint(): ?string
    {
        return $this->endpoint;
    }

    public function context(): array
    {
        return $this->context;
    }
}
