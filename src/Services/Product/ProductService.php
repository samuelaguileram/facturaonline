<?php

declare(strict_types=1);

namespace FacturaOnline\Services\Product;

use FacturaOnline\Contracts\TransportInterface;
use FacturaOnline\Exceptions\ApiException;

final class ProductService
{
    private const BASE_PATH = 'v3/products';
    private const REQUIRED_FIELDS = [
        'name',
        'price',
        'clavePS',
        'unity',
        'claveUnity',
    ];

    public function __construct(private readonly TransportInterface $http)
    {
    }

    public function listar(array $query = []): array
    {
        $this->validateListQuery($query);

        return $this->http->get($this->route('list'), $query);
    }

    public function obtener(string $uid): array
    {
        $this->assertUid($uid);

        return $this->http->get($this->route(sprintf('show/%s', $uid)));
    }

    public function buscarPorUid(string $uid): array
    {
        return $this->obtener($uid);
    }

    public function crear(array $data): array
    {
        $this->validatePayload($data);

        return $this->http->post($this->route('create'), $data);
    }

    public function actualizar(string $uid, array $data): array
    {
        $this->assertUid($uid);
        $this->validatePayload($data);

        return $this->http->post($this->route(sprintf('update/%s', $uid)), $data);
    }

    public function eliminar(string $uid): array
    {
        $this->assertUid($uid);

        return $this->http->get($this->route(sprintf('delete/%s', $uid)));
    }

    private function route(string $suffix): string
    {
        return sprintf('%s/%s', self::BASE_PATH, ltrim($suffix, '/'));
    }

    private function validateListQuery(array $query): void
    {
        foreach (['page', 'per_page'] as $field) {
            if (!array_key_exists($field, $query) || $query[$field] === null || $query[$field] === '') {
                continue;
            }

            if (!is_scalar($query[$field]) || !ctype_digit((string) $query[$field]) || (int) $query[$field] < 1) {
                throw new ApiException(sprintf('El parámetro "%s" debe ser un entero positivo.', $field));
            }
        }
    }

    private function validatePayload(array $data): void
    {
        foreach (self::REQUIRED_FIELDS as $field) {
            $this->assertPresent($data, $field, sprintf('El campo "%s" es requerido para productos.', $field));
        }

        if (!is_scalar($data['price']) || !is_numeric((string) $data['price'])) {
            throw new ApiException('El campo "price" debe ser numérico.');
        }
    }

    private function assertUid(string $uid): void
    {
        if (trim($uid) === '') {
            throw new ApiException('El UID del producto es requerido.');
        }
    }

    private function assertPresent(array $data, string $field, string $message): void
    {
        $value = $data[$field] ?? null;

        if (!is_scalar($value) || trim((string) $value) === '') {
            throw new ApiException($message);
        }
    }
}
