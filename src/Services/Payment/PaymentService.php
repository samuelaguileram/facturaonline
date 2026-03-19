<?php

declare(strict_types=1);

namespace FacturaOnline\Services\Payment;

use FacturaOnline\Contracts\TransportInterface;
use FacturaOnline\DTO\CfdiData;
use FacturaOnline\Exceptions\ApiException;

final class PaymentService
{
    private const ENDPOINT = 'v4/cfdi40/create';
    private const DOCUMENT_TYPE = 'pago';
    private const USO_CFDI = 'CP01';
    private const MONEDA = 'XXX';
    private const CONCEPTO = [
        'ClaveProdServ' => '84111506',
        'Cantidad' => '1',
        'ClaveUnidad' => 'ACT',
        'Descripcion' => 'Pago',
    ];
    private const CONCEPTO_NUMERIC_FIELDS = [
        'ValorUnitario',
        'Importe',
    ];
    private const REQUIRED_PAGO_FIELDS = [
        'FechaPago',
        'FormaDePagoP',
        'MonedaP',
        'Monto',
        'DoctoRelacionado',
    ];
    private const REQUIRED_DOCTO_RELACIONADO_FIELDS = [
        'IdDocumento',
        'MonedaDR',
        'EquivalenciaDR',
        'NumParcialidad',
        'ImpSaldoAnt',
        'ImpPagado',
        'ImpSaldoInsoluto',
        'Impuestos',
    ];
    private const REQUIRED_IMPUESTO_FIELDS = [
        'Base',
        'Impuesto',
        'TipoFactor',
        'Importe',
    ];
    private const OBJETO_IMPUESTO_VALUES = ['01', '02', '03', '04'];

    public function __construct(private readonly TransportInterface $http)
    {
    }

    public function create(CfdiData|array $payment): array
    {
        $data = $payment instanceof CfdiData ? $payment->toArray() : $payment;
        $payload = $this->preparePayload($data);
        $this->validate($payload);

        return $this->http->post(self::ENDPOINT, $payload);
    }

    public function crear(CfdiData|array $payment): array
    {
        return $this->create($payment);
    }

    private function preparePayload(array $data): array
    {
        if (!array_key_exists('TipoDocumento', $data) && array_key_exists('TipoCfdi', $data)) {
            $data['TipoDocumento'] = $data['TipoCfdi'];
            unset($data['TipoCfdi']);
        }

        return $data;
    }

    private function validate(array $data): void
    {
        if ((string) ($data['TipoDocumento'] ?? '') !== self::DOCUMENT_TYPE) {
            throw new ApiException('TipoDocumento debe ser "pago" en PaymentService.');
        }

        if (empty($data['Receptor']) || !is_array($data['Receptor'])) {
            throw new ApiException('El receptor es requerido.');
        }

        if (empty($data['Conceptos']) || !is_array($data['Conceptos'])) {
            throw new ApiException('Debe existir al menos un concepto.');
        }

        $this->assertPresent($data, 'Serie', 'Serie es requerida para un complemento de pago.');
        $this->assertNumeric($data['Serie'], 'Serie');
        $this->assertPresent($data, 'UsoCFDI', 'UsoCFDI es requerido para un complemento de pago.');
        $this->assertPresent($data, 'Moneda', 'Moneda es requerida para un complemento de pago.');
        $this->assertFechaFromApi($data);

        if ((string) $data['UsoCFDI'] !== self::USO_CFDI) {
            throw new ApiException('UsoCFDI debe ser "CP01" en complementos de pago.');
        }

        if ((string) $data['Moneda'] !== self::MONEDA) {
            throw new ApiException('Moneda debe ser "XXX" en complementos de pago.');
        }

        if (count($data['Conceptos']) !== 1) {
            throw new ApiException('Un complemento de pago debe incluir exactamente un concepto.');
        }

        $this->validatePaymentConcept($data['Conceptos'][0]);

        if (!array_key_exists('Pagos', $data) || !is_array($data['Pagos']) || $data['Pagos'] === []) {
            throw new ApiException('El nodo "Pagos" es requerido y debe contener al menos un pago.');
        }

        foreach ($data['Pagos'] as $index => $pago) {
            $this->validatePago($pago, (string) $index);
        }
    }

    private function validatePaymentConcept(mixed $concept): void
    {
        if (!is_array($concept)) {
            throw new ApiException('El concepto del complemento de pago debe ser un arreglo.');
        }

        foreach (self::CONCEPTO as $field => $expectedValue) {
            $this->assertPresent($concept, $field, sprintf('El concepto de pago requiere el campo "%s".', $field));

            if ((string) $concept[$field] !== $expectedValue) {
                throw new ApiException(sprintf('El campo "%s" del concepto de pago debe ser "%s".', $field, $expectedValue));
            }
        }

        foreach (self::CONCEPTO_NUMERIC_FIELDS as $field) {
            $this->assertPresent($concept, $field, sprintf('El concepto de pago requiere el campo "%s".', $field));
            $this->assertNumeric($concept[$field], sprintf('Conceptos.%s', $field));
        }
    }

    private function validatePago(mixed $pago, string $index): void
    {
        if (!is_array($pago)) {
            throw new ApiException(sprintf('El nodo "Pagos.%s" debe ser un arreglo.', $index));
        }

        foreach (self::REQUIRED_PAGO_FIELDS as $field) {
            $this->assertPresent($pago, $field, sprintf('El campo "Pagos.%s.%s" es requerido.', $index, $field));
        }

        $this->assertDateTime((string) $pago['FechaPago'], sprintf('Pagos.%s.FechaPago', $index));

        if ((string) $pago['FormaDePagoP'] === '99') {
            throw new ApiException(sprintf('El campo "Pagos.%s.FormaDePagoP" no puede ser "99".', $index));
        }

        $this->assertNumeric($pago['Monto'], sprintf('Pagos.%s.Monto', $index));

        if ((float) $pago['Monto'] <= 0) {
            throw new ApiException(sprintf('El campo "Pagos.%s.Monto" debe ser mayor a 0.', $index));
        }

        if (array_key_exists('TipoCambioP', $pago) && trim((string) $pago['TipoCambioP']) !== '') {
            $this->assertNumeric($pago['TipoCambioP'], sprintf('Pagos.%s.TipoCambioP', $index));
        }

        if (!is_array($pago['DoctoRelacionado']) || $pago['DoctoRelacionado'] === []) {
            throw new ApiException(sprintf('El campo "Pagos.%s.DoctoRelacionado" debe contener al menos un documento.', $index));
        }

        foreach ($pago['DoctoRelacionado'] as $docIndex => $documento) {
            $this->validateDoctoRelacionado($documento, sprintf('Pagos.%s.DoctoRelacionado.%s', $index, (string) $docIndex), (string) $pago['MonedaP']);
        }
    }

    private function validateDoctoRelacionado(mixed $documento, string $path, string $monedaPago): void
    {
        if (!is_array($documento)) {
            throw new ApiException(sprintf('El nodo "%s" debe ser un arreglo.', $path));
        }

        foreach (self::REQUIRED_DOCTO_RELACIONADO_FIELDS as $field) {
            $this->assertPresent($documento, $field, sprintf('El campo "%s.%s" es requerido.', $path, $field));
        }

        if (!$this->isUuid((string) $documento['IdDocumento'])) {
            throw new ApiException(sprintf('El campo "%s.IdDocumento" debe ser un UUID válido.', $path));
        }

        foreach (['ImpSaldoAnt', 'ImpPagado', 'ImpSaldoInsoluto'] as $field) {
            $this->assertNumeric($documento[$field], sprintf('%s.%s', $path, $field));
        }

        $this->assertNumeric($documento['EquivalenciaDR'], sprintf('%s.EquivalenciaDR', $path));

        if ((float) $documento['EquivalenciaDR'] <= 0) {
            throw new ApiException(sprintf('El campo "%s.EquivalenciaDR" debe ser mayor a 0.', $path));
        }

        if ((string) $documento['MonedaDR'] === $monedaPago && (float) $documento['EquivalenciaDR'] !== 1.0) {
            throw new ApiException(sprintf('El campo "%s.EquivalenciaDR" debe ser "1" cuando MonedaDR y MonedaP son iguales.', $path));
        }

        if (array_key_exists('ObjetoImpuesto', $documento) && trim((string) $documento['ObjetoImpuesto']) !== '') {
            if (!in_array((string) $documento['ObjetoImpuesto'], self::OBJETO_IMPUESTO_VALUES, true)) {
                throw new ApiException(sprintf('El campo "%s.ObjetoImpuesto" debe tener un valor entre "01" y "04".', $path));
            }
        }

        $this->validateImpuestos($documento['Impuestos'], sprintf('%s.Impuestos', $path));
    }

    private function validateImpuestos(mixed $impuestos, string $path): void
    {
        if (!is_array($impuestos)) {
            throw new ApiException(sprintf('El nodo "%s" debe ser un arreglo.', $path));
        }

        foreach (['Traslados', 'Retenidos'] as $field) {
            if (!array_key_exists($field, $impuestos) || !is_array($impuestos[$field])) {
                throw new ApiException(sprintf('El campo "%s.%s" es requerido y debe ser un arreglo.', $path, $field));
            }

            foreach ($impuestos[$field] as $index => $impuesto) {
                $this->validateImpuesto($impuesto, sprintf('%s.%s.%s', $path, $field, (string) $index));
            }
        }
    }

    private function validateImpuesto(mixed $impuesto, string $path): void
    {
        if (!is_array($impuesto)) {
            throw new ApiException(sprintf('El nodo "%s" debe ser un arreglo.', $path));
        }

        foreach (self::REQUIRED_IMPUESTO_FIELDS as $field) {
            $this->assertPresent($impuesto, $field, sprintf('El campo "%s.%s" es requerido.', $path, $field));
        }

        if (!array_key_exists('TasaOCuota', $impuesto) && !array_key_exists('TasaCuota', $impuesto)) {
            throw new ApiException(sprintf('El campo "%s.TasaOCuota" es requerido.', $path));
        }

        foreach (['Base', 'Importe'] as $field) {
            $this->assertNumeric($impuesto[$field], sprintf('%s.%s', $path, $field));
        }

        $rateField = array_key_exists('TasaOCuota', $impuesto) ? 'TasaOCuota' : 'TasaCuota';
        $this->assertNumeric($impuesto[$rateField], sprintf('%s.%s', $path, $rateField));
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

    private function assertDateTime(string $value, string $field): void
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s', $value);

        if ($date === false || $date->format('Y-m-d\TH:i:s') !== $value) {
            throw new ApiException(sprintf('El campo "%s" debe tener el formato "Y-m-d\\TH:i:s".', $field));
        }
    }

    private function isUuid(string $value): bool
    {
        return preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[1-5][0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $value) === 1;
    }
}
