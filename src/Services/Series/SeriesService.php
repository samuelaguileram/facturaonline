<?php

declare(strict_types=1);

namespace FacturaOnline\Services\Series;

use FacturaOnline\Contracts\TransportInterface;
use FacturaOnline\Exceptions\ApiException;

final class SeriesService
{
    private const BASE_PATH = 'v4/series';

    public function __construct(private readonly TransportInterface $http)
    {
    }

    public function listar(): array
    {
        return $this->http->get(self::BASE_PATH);
    }

    public function obtener(string $uid): array
    {
        $this->assertUid($uid);

        return $this->http->get($this->route($uid));
    }

    public function crear(array $data): array
    {
        $this->validateCreatePayload($data);

        return $this->http->post($this->route('create'), $data);
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

    public function eliminar(string $uid): array
    {
        $this->assertUid($uid);

        return $this->http->post($this->route(sprintf('%s/drop', $uid)));
    }

    private function route(string $suffix = ''): string
    {
        $suffix = ltrim($suffix, '/');

        return $suffix === ''
            ? self::BASE_PATH
            : sprintf('%s/%s', self::BASE_PATH, $suffix);
    }

    private function validateCreatePayload(array $data): void
    {
        foreach (['letra', 'tipoDocumento'] as $field) {
            $value = $data[$field] ?? null;

            if (!is_scalar($value) || trim((string) $value) === '') {
                throw new ApiException(sprintf('El campo "%s" es requerido para series.', $field));
            }
        }

        if (array_key_exists('folio', $data) && trim((string) $data['folio']) !== '') {
            if (!is_scalar($data['folio']) || !is_numeric((string) $data['folio'])) {
                throw new ApiException('El campo "folio" debe ser numérico.');
            }
        }
    }

    private function assertUid(string $uid): void
    {
        if (trim($uid) === '') {
            throw new ApiException('El UID de la serie es requerido.');
        }
    }
}
