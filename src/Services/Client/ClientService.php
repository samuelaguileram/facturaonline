<?php

declare(strict_types=1);

namespace FacturaOnline\Services\Client;

use FacturaOnline\Contracts\TransportInterface;
use FacturaOnline\Exceptions\ApiException;

final class ClientService
{
    private const BASE_PATH = 'v1/clients';
    private const REQUIRED_FIELDS = [
        'rfc',
        'razons',
        'codpos',
        'email',
        'regimen',
        'pais',
    ];

    public function __construct(private readonly TransportInterface $http)
    {
    }

    public function listar(): array
    {
        return $this->http->get(self::BASE_PATH);
    }

    public function obtener(string $identifier): array
    {
        $this->assertIdentifier($identifier, 'identificador del cliente');

        return $this->http->get($this->route($identifier));
    }

    public function buscarPorUid(string $uid): array
    {
        return $this->obtener($uid);
    }

    public function buscarPorRfc(string $rfc): array
    {
        return $this->obtener($rfc);
    }

    public function rfcRepetido(string $rfc): array
    {
        $this->assertIdentifier($rfc, 'RFC');

        return $this->http->get($this->route(sprintf('rfc/%s', $rfc)));
    }

    public function crear(array $data): array
    {
        $payload = $this->preparePayload($data);

        return $this->http->post($this->route('create'), $payload);
    }

    public function actualizar(string $uid, array $data): array
    {
        $this->assertIdentifier($uid, 'UID');
        $payload = $this->preparePayload($data);

        return $this->http->post($this->route(sprintf('%s/update', $uid)), $payload);
    }

    public function eliminar(string $uid): array
    {
        $this->assertIdentifier($uid, 'UID');

        return $this->http->post($this->route(sprintf('destroy/%s', $uid)));
    }

    private function preparePayload(array $data): array
    {
        foreach (self::REQUIRED_FIELDS as $field) {
            $this->assertPresent($data, $field, sprintf('El campo "%s" es requerido para clientes.', $field));
        }

        $this->assertNumeric($data['codpos'], 'codpos');
        $this->assertEmail($data['email'], 'email');

        foreach (['email2', 'email3'] as $field) {
            if (array_key_exists($field, $data) && trim((string) $data[$field]) !== '') {
                $this->assertEmail($data[$field], $field);
            }
        }

        return $data;
    }

    private function route(string $suffix = ''): string
    {
        $suffix = ltrim($suffix, '/');

        return $suffix === ''
            ? self::BASE_PATH
            : sprintf('%s/%s', self::BASE_PATH, $suffix);
    }

    private function assertIdentifier(string $value, string $label): void
    {
        if (trim($value) === '') {
            throw new ApiException(sprintf('El %s es requerido.', $label));
        }
    }

    private function assertPresent(array $data, string $field, string $message): void
    {
        $value = $data[$field] ?? null;

        if (!is_scalar($value) || trim((string) $value) === '') {
            throw new ApiException($message);
        }
    }

    private function assertNumeric(mixed $value, string $field): void
    {
        if (!is_scalar($value) || !is_numeric((string) $value)) {
            throw new ApiException(sprintf('El campo "%s" debe ser numérico.', $field));
        }
    }

    private function assertEmail(mixed $value, string $field): void
    {
        if (!is_scalar($value) || filter_var((string) $value, FILTER_VALIDATE_EMAIL) === false) {
            throw new ApiException(sprintf('El campo "%s" debe ser un correo válido.', $field));
        }
    }
}
