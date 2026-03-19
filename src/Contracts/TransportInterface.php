<?php

declare(strict_types=1);

namespace FacturaOnline\Contracts;

interface TransportInterface
{
    public function get(string $endpoint, array $query = []): array;

    public function getRaw(string $endpoint): string;

    public function download(string $endpoint, string $savePath): string;

    public function post(string $endpoint, array $data = []): array;

    public function postMultipart(string $endpoint, array $data = [], array $files = []): array;

    public function postAuto(string $endpoint, array $data = [], array $files = []): array;
}
