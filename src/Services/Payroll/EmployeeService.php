<?php

declare(strict_types=1);

namespace FacturaOnline\Services\Payroll;

use FacturaOnline\Contracts\TransportInterface;
use FacturaOnline\Exceptions\ApiException;

final class EmployeeService
{
    private const BASE_PATH = 'payroll/employee';
    private const REQUIRED_CREATE_FIELDS = [
        'grupo',
        'no_empleado',
        'nombre',
        'paterno',
        'metodo_pago',
        'periodo',
        'regimen',
        'puesto',
        'departamento',
        'curp',
        'imss',
        'rfc',
        'calle',
        'colonia',
        'no_ext',
        'cp',
        'municipio',
        'estado',
        'tipo_contrato',
        'asimilados',
        'sindicalizado',
        'entidad_emite',
    ];
    private const REQUIRED_NON_ASIMILADOS_FIELDS = [
        'tipo_jornada',
        'patronal',
        'cuota_diaria',
        'salario',
        'riesgo',
        'inicio',
    ];
    private const NUMERIC_FIELDS = [
        'no_empleado',
        'no_ext',
        'cp',
        'cuota_diaria',
        'salario',
    ];
    private const PERIODOS = ['01', '02', '03', '04', '05'];

    public function __construct(private readonly TransportInterface $http)
    {
    }

    public function listar(array $filters = []): array
    {
        return $this->http->get($this->route('list'), $filters);
    }

    public function obtener(string $uid): array
    {
        $this->assertUid($uid);

        return $this->http->get($this->route(sprintf('%s/view', $uid)));
    }

    public function crear(array $data): array
    {
        $payload = $this->prepareCreatePayload($data);

        return $this->http->post($this->route('create'), $payload);
    }

    public function editar(string $uid, array $data): array
    {
        $this->assertUid($uid);
        $payload = $this->prepareUpdatePayload($data);

        return $this->http->post($this->route(sprintf('%s/update', $uid)), $payload);
    }

    public function update(string $uid, array $data): array
    {
        return $this->editar($uid, $data);
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
            throw new ApiException('El uid del empleado es requerido.');
        }
    }

    private function prepareCreatePayload(array $data): array
    {
        $payload = $this->normalizePayload($data);

        foreach (self::REQUIRED_CREATE_FIELDS as $field) {
            $this->assertPresent($payload, $field, sprintf('El campo "%s" es requerido para crear un empleado.', $field));
        }

        $this->assertCommonRules($payload);
        $this->assertAsimiladosRules($payload, true);

        return $payload;
    }

    private function prepareUpdatePayload(array $data): array
    {
        if ($data === []) {
            throw new ApiException('Debes enviar al menos un campo para editar un empleado.');
        }

        $payload = $this->normalizePayload($data);

        $this->assertCommonRules($payload);
        $this->assertAsimiladosRules($payload, false);

        return $payload;
    }

    private function normalizePayload(array $data): array
    {
        $normalized = $data;

        if (array_key_exists('asimilados', $normalized)) {
            $normalized['asimilados'] = $this->normalizeBinaryFlag($normalized['asimilados'], 'asimilados');
        }

        if (array_key_exists('sindicalizado', $normalized)) {
            $normalized['sindicalizado'] = $this->normalizeBinaryFlag($normalized['sindicalizado'], 'sindicalizado');
        }

        return $normalized;
    }

    private function assertCommonRules(array $data): void
    {
        foreach (self::NUMERIC_FIELDS as $field) {
            if (!array_key_exists($field, $data) || $data[$field] === null || $data[$field] === '') {
                continue;
            }

            if (!is_scalar($data[$field]) || !is_numeric((string) $data[$field])) {
                throw new ApiException(sprintf('El campo "%s" debe ser numérico.', $field));
            }
        }

        if (array_key_exists('periodo', $data) && !in_array((string) $data['periodo'], self::PERIODOS, true)) {
            throw new ApiException('El campo "periodo" debe tener un valor entre "01" y "05".');
        }

        if (array_key_exists('banco', $data) && trim((string) $data['banco']) !== '') {
            $this->assertPresent($data, 'clabe', 'El campo "clabe" es obligatorio cuando se envía "banco".');
        }

        if (array_key_exists('inicio', $data) && trim((string) $data['inicio']) !== '') {
            $date = \DateTimeImmutable::createFromFormat('Y-m-d', (string) $data['inicio']);

            if ($date === false || $date->format('Y-m-d') !== (string) $data['inicio']) {
                throw new ApiException('El campo "inicio" debe tener el formato "Y-m-d".');
            }
        }

        if (array_key_exists('email', $data) && trim((string) $data['email']) !== '' && filter_var((string) $data['email'], FILTER_VALIDATE_EMAIL) === false) {
            throw new ApiException('El campo "email" debe ser un correo válido.');
        }
    }

    private function assertAsimiladosRules(array $data, bool $isCreate): void
    {
        if (!array_key_exists('asimilados', $data)) {
            return;
        }

        if ($data['asimilados'] !== '0') {
            return;
        }

        foreach (self::REQUIRED_NON_ASIMILADOS_FIELDS as $field) {
            $message = $isCreate
                ? sprintf('El campo "%s" es obligatorio cuando "asimilados" es "0".', $field)
                : sprintf('Si envías "asimilados" = "0", el campo "%s" también debe enviarse.', $field);

            $this->assertPresent($data, $field, $message);
        }
    }

    private function assertPresent(array $data, string $field, string $message): void
    {
        $value = $data[$field] ?? null;

        if (!is_scalar($value) || trim((string) $value) === '') {
            throw new ApiException($message);
        }
    }

    private function normalizeBinaryFlag(mixed $value, string $field): string
    {
        $normalizedString = is_string($value) ? trim($value) : null;

        return match (true) {
            $value === '1',
            $value === 1,
            $value === true,
            in_array($normalizedString, ['1', 'si', 'Si', 'SI', 'sí', 'Sí', 'SÍ', 'yes', 'Yes', 'YES'], true) => '1',
            $value === '0',
            $value === 0,
            $value === false,
            in_array($normalizedString, ['0', 'no', 'No', 'NO'], true) => '0',
            default => throw new ApiException(sprintf('El campo "%s" solo acepta "0" o "1".', $field)),
        };
    }
}
