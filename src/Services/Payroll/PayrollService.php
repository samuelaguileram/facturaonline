<?php

declare(strict_types=1);

namespace FacturaOnline\Services\Payroll;

use FacturaOnline\Contracts\TransportInterface;
use FacturaOnline\Exceptions\ApiException;

final class PayrollService
{
    private const CANCELATION_REASONS = ['01', '02', '03', '04'];
    private const BASE_PATH = 'payroll';
    private const REQUIRED_CREATE_FIELDS = [
        'grupo',
        'fecha_pago',
        'num_dias',
        'incial',
        'final',
        'tipo_nomina',
        'descripcion',
        'serie',
        'concepto',
        'version_cfdi',
        'registros',
    ];
    private const REQUIRED_RECORD_FIELDS = [
        'data',
        'percepciones',
    ];
    private const REQUIRED_RECORD_DATA_FIELDS = [
        'id',
        'dias',
    ];
    private const REQUIRED_CONCEPT_FIELDS = [
        'tipo',
        'clave',
        'descripcion',
    ];
    private const REQUIRED_OTROS_PAGOS_FIELDS = [
        'tipo',
        'clave',
        'descripcion',
        'importe',
    ];
    private const REQUIRED_INCAPACIDAD_FIELDS = [
        'DiasIncapacidad',
        'TipoIncapacidad',
        'ImporteMonetario',
    ];
    private const REQUIRED_SEPARACION_FIELDS = [
        'TotalPagado',
        'NumAñosServicio',
        'UltimoSueldoMensOrd',
        'IngresoAcumulable',
        'IngresoNoAcumulable',
    ];
    private const NUMERIC_CREATE_FIELDS = [
        'num_dias',
        'serie',
    ];
    private const NUMERIC_RECORD_FIELDS = [
        'dias',
    ];
    private const NUMERIC_CONCEPT_FIELDS = [
        'exento',
        'gravado',
        'importe',
    ];
    private const NUMERIC_INCAPACIDAD_FIELDS = [
        'DiasIncapacidad',
        'ImporteMonetario',
    ];
    private const NUMERIC_SEPARACION_FIELDS = [
        'TotalPagado',
        'NumAñosServicio',
        'UltimoSueldoMensOrd',
        'IngresoAcumulable',
        'IngresoNoAcumulable',
    ];
    private const TIPO_NOMINA = ['O', 'E'];
    private const CFDI_VERSIONS = ['3.3', '4.0'];

    private ?EmployeeService $employeeService = null;
    private ?EmployeeGroupService $employeeGroupService = null;

    public function __construct(private readonly TransportInterface $http)
    {
    }

    public function employeeGroups(): EmployeeGroupService
    {
        return $this->employeeGroupService ??= new EmployeeGroupService($this->http);
    }

    public function employees(): EmployeeService
    {
        return $this->employeeService ??= new EmployeeService($this->http);
    }

    public function listar(): array
    {
        return $this->http->get($this->route('list'));
    }

    public function listarGrupo(string $uid): array
    {
        $this->assertUid($uid, 'grupo');

        return $this->http->get($this->route(sprintf('%s/view', $uid)));
    }

    public function listarEmpleado(string $uid): array
    {
        $this->assertUid($uid, 'empleado');

        return $this->http->get($this->route(sprintf('%s/view/item', $uid)));
    }

    public function crear(array $data): array
    {
        $payload = $this->prepareCreatePayload($data);

        return $this->http->post($this->route('create'), $payload);
    }

    public function cancelar(string $uid, string $motivo, ?string $folioSustituto = null): array
    {
        $this->assertUid($uid, 'nómina');

        if (!in_array($motivo, self::CANCELATION_REASONS, true)) {
            throw new ApiException('Motivo de cancelación SAT inválido.');
        }

        if ($motivo === '01' && ($folioSustituto === null || trim($folioSustituto) === '')) {
            throw new ApiException('folioSustituto es requerido cuando el motivo es 01.');
        }

        if ($motivo !== '01' && $folioSustituto !== null && trim($folioSustituto) !== '') {
            throw new ApiException('folioSustituto solo debe enviarse cuando el motivo es 01.');
        }

        $payload = ['motivo' => $motivo];

        if ($folioSustituto !== null && trim($folioSustituto) !== '') {
            $payload['folioSustituto'] = $folioSustituto;
        }

        return $this->http->post($this->route(sprintf('%s/cancel', $uid)), $payload);
    }

    public function descargarPdf(string $uid, ?string $path = null): string
    {
        return $this->downloadDocument($uid, 'pdf', $path);
    }

    public function descargarXml(string $uid, ?string $path = null): string
    {
        return $this->downloadDocument($uid, 'xml', $path);
    }

    public function descargarAcuse(string $uid, ?string $path = null): string
    {
        return $this->downloadDocument($uid, 'acuse', $path);
    }

    public function enviarEmail(string $uid): array
    {
        $this->assertUid($uid, 'nómina');

        return $this->http->get($this->route(sprintf('%s/item/email', $uid)));
    }

    private function prepareCreatePayload(array $data): array
    {
        $payload = $data;

        if (!array_key_exists('incial', $payload) && array_key_exists('inicial', $payload)) {
            $payload['incial'] = $payload['inicial'];
            unset($payload['inicial']);
        }

        foreach (self::REQUIRED_CREATE_FIELDS as $field) {
            $this->assertPresent($payload, $field, sprintf('El campo "%s" es requerido para crear una nómina.', $field));
        }

        foreach (self::NUMERIC_CREATE_FIELDS as $field) {
            $this->assertNumeric($payload[$field], $field);
        }

        $this->assertDate((string) $payload['fecha_pago'], 'fecha_pago');
        $this->assertDate((string) $payload['incial'], 'incial');
        $this->assertDate((string) $payload['final'], 'final');
        $this->assertFechaFromApi($payload);

        if (!in_array((string) $payload['tipo_nomina'], self::TIPO_NOMINA, true)) {
            throw new ApiException('El campo "tipo_nomina" solo acepta "O" o "E".');
        }

        if (!in_array((string) $payload['version_cfdi'], self::CFDI_VERSIONS, true)) {
            throw new ApiException('El campo "version_cfdi" solo acepta "3.3" o "4.0".');
        }

        if (!is_array($payload['registros']) || $payload['registros'] === []) {
            throw new ApiException('El campo "registros" debe contener al menos un empleado.');
        }

        foreach ($payload['registros'] as $index => $record) {
            $this->assertRecord($record, $index);
        }

        return $payload;
    }

    private function assertRecord(mixed $record, int|string $index): void
    {
        if (!is_array($record)) {
            throw new ApiException(sprintf('El registro de nómina en la posición "%s" debe ser un arreglo.', (string) $index));
        }

        foreach (self::REQUIRED_RECORD_FIELDS as $field) {
            $this->assertPresent($record, $field, sprintf('El campo "%s" es requerido en cada registro de nómina.', $field));
        }

        if (!is_array($record['data'])) {
            throw new ApiException('El campo "data" de cada registro debe ser un arreglo.');
        }

        foreach (self::REQUIRED_RECORD_DATA_FIELDS as $field) {
            $this->assertPresent($record['data'], $field, sprintf('El campo "data.%s" es requerido en cada registro.', $field));
        }

        $this->assertNumeric($record['data']['dias'], 'data.dias');

        if (!is_array($record['percepciones']) || $record['percepciones'] === []) {
            throw new ApiException('El campo "percepciones" debe contener al menos un concepto por empleado.');
        }

        foreach ($record['percepciones'] as $conceptIndex => $concept) {
            $this->assertConcept($concept, sprintf('percepciones.%s', (string) $conceptIndex));
        }

        if (array_key_exists('deducciones', $record)) {
            if (!is_array($record['deducciones'])) {
                throw new ApiException('El campo "deducciones" debe ser un arreglo cuando se envía.');
            }

            foreach ($record['deducciones'] as $conceptIndex => $concept) {
                $this->assertConcept($concept, sprintf('deducciones.%s', (string) $conceptIndex), true);
            }
        }

        if (array_key_exists('otrospagos', $record)) {
            if (!is_array($record['otrospagos'])) {
                throw new ApiException('El campo "otrospagos" debe ser un arreglo cuando se envía.');
            }

            foreach ($record['otrospagos'] as $paymentIndex => $payment) {
                $this->assertOtroPago($payment, sprintf('otrospagos.%s', (string) $paymentIndex));
            }
        }

        if (array_key_exists('relacionados', $record)) {
            if (!is_array($record['relacionados'])) {
                throw new ApiException('El campo "relacionados" debe ser un arreglo cuando se envía.');
            }

            foreach ($record['relacionados'] as $relatedIndex => $related) {
                $this->assertRelacionado($related, sprintf('relacionados.%s', (string) $relatedIndex));
            }
        }

        if (array_key_exists('SeparacionIndemnizacion', $record)) {
            $this->assertSeparacionIndemnizacion($record['SeparacionIndemnizacion']);
        }
    }

    private function assertConcept(mixed $concept, string $path, bool $allowImporte = false): void
    {
        if (!is_array($concept)) {
            throw new ApiException(sprintf('El concepto "%s" debe ser un arreglo.', $path));
        }

        foreach (self::REQUIRED_CONCEPT_FIELDS as $field) {
            $this->assertPresent($concept, $field, sprintf('El campo "%s.%s" es requerido.', $path, $field));
        }

        $hasExentoGravado = array_key_exists('exento', $concept) && array_key_exists('gravado', $concept);
        $hasImporte = array_key_exists('importe', $concept);

        if ($allowImporte) {
            if (!$hasExentoGravado && !$hasImporte) {
                throw new ApiException(sprintf('El concepto "%s" debe incluir "importe" o bien "exento" y "gravado".', $path));
            }
        } elseif (!$hasExentoGravado) {
            throw new ApiException(sprintf('El concepto "%s" debe incluir "exento" y "gravado".', $path));
        }

        if ($hasExentoGravado) {
            $this->assertNumeric($concept['exento'], sprintf('%s.exento', $path));
            $this->assertNumeric($concept['gravado'], sprintf('%s.gravado', $path));
        }

        if ($hasImporte) {
            $this->assertNumeric($concept['importe'], sprintf('%s.importe', $path));
        }

        if (array_key_exists('Incapacidad', $concept)) {
            $this->assertIncapacidad($concept['Incapacidad'], sprintf('%s.Incapacidad', $path));
        }
    }

    private function assertIncapacidad(mixed $incapacity, string $path): void
    {
        if (!is_array($incapacity)) {
            throw new ApiException(sprintf('El concepto "%s" debe ser un arreglo.', $path));
        }

        foreach (self::REQUIRED_INCAPACIDAD_FIELDS as $field) {
            $this->assertPresent($incapacity, $field, sprintf('El campo "%s.%s" es requerido.', $path, $field));
        }

        foreach (self::NUMERIC_INCAPACIDAD_FIELDS as $field) {
            $this->assertNumeric($incapacity[$field], sprintf('%s.%s', $path, $field));
        }

        if (trim((string) $incapacity['TipoIncapacidad']) === '') {
            throw new ApiException(sprintf('El campo "%s.TipoIncapacidad" no puede estar vacío.', $path));
        }
    }

    private function assertOtroPago(mixed $payment, string $path): void
    {
        if (!is_array($payment)) {
            throw new ApiException(sprintf('El concepto "%s" debe ser un arreglo.', $path));
        }

        foreach (self::REQUIRED_OTROS_PAGOS_FIELDS as $field) {
            $this->assertPresent($payment, $field, sprintf('El campo "%s.%s" es requerido.', $path, $field));
        }

        $this->assertNumeric($payment['importe'], sprintf('%s.importe', $path));

        if (array_key_exists('SubsidioAlEmpleo', $payment)) {
            if (!is_array($payment['SubsidioAlEmpleo'])) {
                throw new ApiException(sprintf('El campo "%s.SubsidioAlEmpleo" debe ser un arreglo.', $path));
            }

            $this->assertPresent($payment['SubsidioAlEmpleo'], 'SubsidioCausado', sprintf('El campo "%s.SubsidioAlEmpleo.SubsidioCausado" es requerido.', $path));
            $this->assertNumeric($payment['SubsidioAlEmpleo']['SubsidioCausado'], sprintf('%s.SubsidioAlEmpleo.SubsidioCausado', $path));
        }
    }

    private function assertRelacionado(mixed $related, string $path): void
    {
        if (!is_array($related)) {
            throw new ApiException(sprintf('El concepto "%s" debe ser un arreglo.', $path));
        }

        $this->assertPresent($related, 'nom_reg_uuid', sprintf('El campo "%s.nom_reg_uuid" es requerido.', $path));

        if (!$this->isUuid((string) $related['nom_reg_uuid'])) {
            throw new ApiException(sprintf('El campo "%s.nom_reg_uuid" debe ser un UUID válido.', $path));
        }
    }

    private function assertSeparacionIndemnizacion(mixed $data): void
    {
        if (!is_array($data)) {
            throw new ApiException('El campo "SeparacionIndemnizacion" debe ser un arreglo cuando se envía.');
        }

        foreach (self::REQUIRED_SEPARACION_FIELDS as $field) {
            $this->assertPresent($data, $field, sprintf('El campo "SeparacionIndemnizacion.%s" es requerido.', $field));
            $this->assertNumeric($data[$field], sprintf('SeparacionIndemnizacion.%s', $field));
        }
    }

    private function assertPresent(array $data, string $field, string $message): void
    {
        if (!array_key_exists($field, $data)) {
            throw new ApiException($message);
        }

        $value = $data[$field];

        if (is_array($value)) {
            if ($value === []) {
                throw new ApiException($message);
            }

            return;
        }

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

    private function assertDate(string $value, string $field): void
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);

        if ($date === false || $date->format('Y-m-d') !== $value) {
            throw new ApiException(sprintf('El campo "%s" debe tener el formato "Y-m-d".', $field));
        }
    }

    private function assertFechaFromApi(array $payload): void
    {
        if (!array_key_exists('FechaFromAPI', $payload) || trim((string) $payload['FechaFromAPI']) === '') {
            return;
        }

        $value = (string) $payload['FechaFromAPI'];
        $date = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s', $value);

        if ($date === false || $date->format('Y-m-d\TH:i:s') !== $value) {
            throw new ApiException('El campo "FechaFromAPI" debe tener el formato "Y-m-d\\TH:i:s".');
        }

        $now = new \DateTimeImmutable('now');
        $minAllowed = $now->modify('-72 hours');

        if ($date > $now) {
            throw new ApiException('El campo "FechaFromAPI" no puede ser una fecha futura.');
        }

        if ($date < $minAllowed) {
            throw new ApiException('El campo "FechaFromAPI" no puede ser mayor a 72 horas hacia atrás.');
        }
    }

    private function isUuid(string $value): bool
    {
        return preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[1-5][0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $value) === 1;
    }

    private function assertUid(string $uid, string $entity): void
    {
        if (trim($uid) === '') {
            throw new ApiException(sprintf('El uid de la %s es requerido.', $entity));
        }
    }

    private function downloadDocument(string $uid, string $format, ?string $path): string
    {
        $this->assertUid($uid, 'nómina');

        $endpoint = $this->route(sprintf('%s/item/%s', $uid, $format));

        if ($path !== null) {
            return $this->http->download($endpoint, $path);
        }

        return $this->http->getRaw($endpoint);
    }

    private function route(string $suffix): string
    {
        return sprintf('%s/%s', self::BASE_PATH, ltrim($suffix, '/'));
    }
}
