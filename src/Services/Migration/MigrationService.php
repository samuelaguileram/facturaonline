<?php

declare(strict_types=1);

namespace FacturaOnline\Services\Migration;

use FacturaOnline\Contracts\TransportInterface;
use FacturaOnline\Exceptions\ApiException;

final class MigrationService
{
    public function __construct(private readonly TransportInterface $http)
    {
    }

    public function crear(array $data): array
    {
        $this->validatePayload($data);

        return $this->http->post('migrator/create', $data);
    }

    public function create(array $data): array
    {
        return $this->crear($data);
    }

    public function listar(): array
    {
        return $this->http->get('migrator/list');
    }

    public function obtener(string $uid): array
    {
        $this->assertUid($uid);

        return $this->http->get(sprintf('migrator/%s/view', $uid));
    }

    public function view(string $uid): array
    {
        return $this->obtener($uid);
    }

    private function validatePayload(array $data): void
    {
        foreach (['inicio', 'fin'] as $field) {
            $value = $data[$field] ?? null;

            if (!is_scalar($value) || trim((string) $value) === '') {
                throw new ApiException(sprintf('El campo "%s" es requerido para migraciones.', $field));
            }

            $this->assertDateTime((string) $value, $field);
        }
    }

    private function assertUid(string $uid): void
    {
        if (trim($uid) === '') {
            throw new ApiException('El UID de la migración es requerido.');
        }
    }

    private function assertDateTime(string $value, string $field): void
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s', $value);

        if ($date === false || $date->format('Y-m-d\TH:i:s') !== $value) {
            throw new ApiException(sprintf('El campo "%s" debe tener el formato "Y-m-d\\TH:i:s".', $field));
        }
    }
}
