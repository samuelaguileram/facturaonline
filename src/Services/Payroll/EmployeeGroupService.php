<?php

declare(strict_types=1);

namespace FacturaOnline\Services\Payroll;

use FacturaOnline\Contracts\TransportInterface;
use FacturaOnline\Exceptions\ApiException;

final class EmployeeGroupService
{
    private const BASE_PATH = 'payroll/employee/group';

    public function __construct(private readonly TransportInterface $http)
    {
    }

    public function listar(): array
    {
        return $this->http->get($this->route('list'));
    }

    public function obtener(string $uid): array
    {
        $this->assertUid($uid);

        return $this->http->get($this->route(sprintf('%s/view', $uid)));
    }

    public function listarPorUid(string $uid): array
    {
        return $this->obtener($uid);
    }

    public function crear(array $data): array
    {
        $this->assertPayload($data);

        return $this->http->post($this->route('create'), $data);
    }

    public function editar(string $uid, array $data): array
    {
        $this->assertUid($uid);
        $this->assertPayload($data);

        return $this->http->post($this->route(sprintf('%s/update', $uid)), $data);
    }

    public function eliminar(string $uid): array
    {
        $this->assertUid($uid);

        return $this->http->post($this->route(sprintf('%s/delete', $uid)));
    }

    public function desactivar(string $uid): array
    {
        $this->assertUid($uid);

        return $this->http->post($this->route(sprintf('%s/down', $uid)));
    }

    public function activar(string $uid): array
    {
        $this->assertUid($uid);

        return $this->http->post($this->route(sprintf('%s/up', $uid)));
    }

    private function route(string $suffix): string
    {
        return sprintf('%s/%s', self::BASE_PATH, ltrim($suffix, '/'));
    }

    private function assertUid(string $uid): void
    {
        if (trim($uid) === '') {
            throw new ApiException('El uid del grupo es requerido.');
        }
    }

    private function assertPayload(array $data): void
    {
        $group = $data['grupo'] ?? null;

        if (!is_string($group) || trim($group) === '') {
            throw new ApiException('El nombre del grupo es requerido.');
        }
    }
}
