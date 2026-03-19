<?php

declare(strict_types=1);

namespace FacturaOnline\Services\CFDI;

use FacturaOnline\Contracts\TransportInterface;
use FacturaOnline\DTO\CfdiData;
use FacturaOnline\Exceptions\ApiException;
use FacturaOnline\Services\Payment\PaymentService;

final class CfdiService
{
    private const CANCELATION_REASONS = ['01', '02', '03', '04'];
    private const GENERAL_DOCUMENT_TYPES = [
        'factura',
        'factura_hotel',
        'honorarios',
        'nota_cargo',
        'donativos',
        'arrendamiento',
        'nota_credito',
        'nota_debito',
        'nota_devolucion',
    ];
    private const PAYMENT_DOCUMENT_TYPE = 'pago';
    private const PAYMENT_USO_CFDI = 'CP01';
    private const PAYMENT_MONEDA = 'XXX';
    private const PAYMENT_CONCEPTO = [
        'ClaveProdServ' => '84111506',
        'Cantidad' => '1',
        'ClaveUnidad' => 'ACT',
        'Descripcion' => 'Pago',
    ];
    private const PAYMENT_CONCEPTO_NUMERIC_FIELDS = [
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
    private const REQUIRED_RETENTION_FIELDS = [
        'LugarExpedicion',
        'CveRetenc',
        'Receptor',
        'Periodo',
        'Totales',
    ];
    private const REQUIRED_RETENTION_PERIODO_FIELDS = [
        'MesIni',
        'MesFin',
        'Ejercicio',
    ];
    private const REQUIRED_RETENTION_TOTALES_FIELDS = [
        'MontoTotOperacion',
        'MontoTotGrav',
        'MontoTotExent',
        'MontoTotRet',
        'ImpRetenidos',
    ];
    private const REQUIRED_RETENTION_IMPUESTO_FIELDS = [
        'BaseRet',
        'ImpuestoRet',
        'MontoRet',
        'TipoPagoRet',
    ];
    private const STOCK_DISPOSAL_RETENTION_KEY = '06';
    private const REQUIRED_STOCK_DISPOSAL_FIELDS = [
        'ContratoIntermediacion',
        'Ganancia',
        'Perdida',
    ];
    private const REQUIRED_MORTGAGE_INTEREST_FIELDS = [
        'CreditoDeInstFinanc',
        'SaldoInsoluto',
    ];
    private const REQUIRED_DIVIDEND_FIELDS = [
        'CveTipDivOUtil',
        'MontISRAcredRetMexico',
        'MontISRAcredRetExtranjero',
        'TipoSocDistrDiv',
    ];
    private const TRUST_LEASE_RETENTION_KEY = '17';
    private const REQUIRED_TRUST_LEASE_FIELDS = [
        'PagProvEfecPorFiduc',
        'RendimFideicom',
        'DeduccCorresp',
    ];
    private const DIVIDEND_TYPES = ['01', '02', '03', '04', '05', '06'];
    private const DIVIDEND_SOCIETY_TYPES = ['Sociedad Nacional', 'Sociedad Extranjera'];
    private const INTEREST_RETENTION_KEY = '07';
    private const OBJETO_IMPUESTO_VALUES = ['01', '02', '03', '04'];
    private const REQUIRED_INTEREST_FIELDS = [
        'version',
        'sistFinanciero',
        'retiroAORESRetInt',
        'operFinancDerivad',
        'montIntNominal',
        'montIntReal',
        'perdida',
    ];
    private const YES_NO_VALUES = ['SI', 'NO'];
    private const TECHNOLOGY_PLATFORM_RETENTION_KEY = '26';
    private const REQUIRED_TECH_PLATFORM_FIELDS = [
        'NumServ',
        'MonTotServSIVA',
        'MonTotalporUsoPlataforma',
        'MonTotalContribucionGubernamental',
        'TotalIVATrasladado',
        'TotalIVARetenido',
        'DifIVAEntregadoPrestServ',
        'TotalISRRetenido',
        'Periodicidad',
        'Servicios',
    ];
    private const REQUIRED_TECH_PLATFORM_SERVICES_FIELDS = [
        'DetallesDelServicio',
    ];
    private const REQUIRED_TECH_PLATFORM_SERVICE_DETAIL_FIELDS = [
        'FechaServ',
        'FormaPagoServ',
        'TipoDeServ',
        'SubTipServ',
        'PrecioServSinIVA',
        'ComisionDelServicio',
    ];
    private const RETENTION_IMPUESTOS = ['001', '002', '003'];
    private const RETENTION_TIPO_PAGO = ['01', '02', '03', '04'];
    private const A_CUENTA_TERCEROS_FIELDS = [
        'RfcACuentaTerceros',
        'NombreACuentaTerceros',
        'RegimenFiscalACuentaTerceros',
        'DomicilioFiscalACuentaTerceros',
    ];
    private const EDUCATIONAL_COMPLEMENT_TYPE = 'iedu';
    private const EDUCATIONAL_COMPLEMENT_VERSION = '1.0';
    private const EDUCATIONAL_COMPLEMENT_FIELDS = [
        'typeComplement',
        'version',
        'nombreAlumno',
        'CURP',
        'nivelEducativo',
        'autRVOE',
    ];
    private const EDUCATIONAL_LEVELS = [
        'Preescolar',
        'Primaria',
        'Secundaria',
        'Profesional tecnico',
        'Profesional técnico',
        'Bachillerato o su equivalente',
    ];
    private const VEHICLE_COMPLEMENT_TYPE = 'VentaVehiculos';
    private const VEHICLE_COMPLEMENT_FIELDS = [
        'typeComplement',
        'ClaveVehicular',
        'Niv',
    ];
    private const VEHICLE_PART_FIELDS = [
        'cantidad',
        'descripcion',
    ];
    private const PARCIALES_CONSTRUCCION_VERSION = '1.0';
    private const REQUIRED_PARCIALES_CONSTRUCCION_FIELDS = [
        'NumPerLicoAut',
    ];
    private const REQUIRED_PARCIALES_CONSTRUCCION_INMUEBLE_FIELDS = [
        'Calle',
        'Municipio',
        'Estado',
        'CodigoPostal',
    ];
    private ?PaymentService $paymentService = null;

    public function __construct(private readonly TransportInterface $http)
    {
    }

    public function listar(array $data = []): array
    {
        return $this->http->post('v4/cfdi/list', $data);
    }

    public function buscar(string $uid, bool $fuid = true, bool $fuuid = false, bool $ffolio = false): array
    {
        $filters = array_filter([
            'uid' => $fuid,
            'uuid' => $fuuid,
            'folio' => $ffolio,
        ]);

        if (count($filters) !== 1) {
            throw new ApiException('Debe indicar exactamente un tipo de búsqueda.');
        }

        return match (array_key_first($filters)) {
            'uid' => $this->buscarPorUid($uid),
            'uuid' => $this->buscarPorUuid($uid),
            'folio' => $this->buscarPorFolio($uid),
            default => throw new ApiException('Tipo de búsqueda no soportado.'),
        };
    }

    public function buscarPorUid(string $uid): array
    {
        $this->assertIdentifier($uid, 'uid');

        return $this->http->get(sprintf('v4/cfdi/uid/%s', $uid));
    }

    public function buscarPorUuid(string $uuid): array
    {
        $this->assertIdentifier($uuid, 'uuid');

        return $this->http->get(sprintf('v4/cfdi/uuid/%s', $uuid));
    }

    public function buscarPorFolio(string $folio): array
    {
        $this->assertIdentifier($folio, 'folio');

        return $this->http->get(sprintf('v4/cfdi/folio/%s', $folio));
    }

    public function create(CfdiData $cfdi): array
    {
        $data = $this->preparePayload($cfdi->toArray());

        if ($this->isPaymentCfdi($data)) {
            return $this->payment()->create($data);
        }

        $this->validate($data);

        return $this->http->post('v4/cfdi40/create', $data);
    }

    public function createRetention(array $data): array
    {
        $payload = $this->prepareRetentionPayload($data);
        $this->validateRetention($payload);

        return $this->http->post('v4/cfdi40/retenciones', $payload);
    }

    public function crearRetencion(array $data): array
    {
        return $this->createRetention($data);
    }

    public function cancelRetention(string $uid, string $motivo, ?string $folioSustituto = null): array
    {
        $this->assertIdentifier($uid, 'uid');
        $payload = $this->buildCancelPayload($motivo, $folioSustituto);

        return $this->http->post(sprintf('v4/cfdi40/retenciones/%s/cancel', $uid), $payload);
    }

    public function cancelarRetencion(string $uid, string $motivo, ?string $folioSustituto = null): array
    {
        return $this->cancelRetention($uid, $motivo, $folioSustituto);
    }

    public function recancelRetention(string $uid, string $motivo, ?string $folioSustituto = null): array
    {
        $this->assertIdentifier($uid, 'uid');
        $payload = $this->buildCancelPayload($motivo, $folioSustituto);

        return $this->http->post(sprintf('v4/cfdi40/retenciones/%s/re-cancel', $uid), $payload);
    }

    public function recancelarRetencion(string $uid, string $motivo, ?string $folioSustituto = null): array
    {
        return $this->recancelRetention($uid, $motivo, $folioSustituto);
    }

    public function descargarRetentionPdf(string $uid, ?string $path = null): string
    {
        return $this->downloadRetentionDocument($uid, 'pdf', $path);
    }

    public function descargarRetencionPdf(string $uid, ?string $path = null): string
    {
        return $this->descargarRetentionPdf($uid, $path);
    }

    public function descargarRetentionXml(string $uid, ?string $path = null): string
    {
        return $this->downloadRetentionDocument($uid, 'xml', $path);
    }

    public function descargarRetencionXml(string $uid, ?string $path = null): string
    {
        return $this->descargarRetentionXml($uid, $path);
    }

    public function enviarRetentionEmail(string $uid): array
    {
        $this->assertIdentifier($uid, 'uid');

        return $this->http->get(sprintf('v4/cfdi40/retenciones/%s/email', $uid));
    }

    public function enviarRetencionEmail(string $uid): array
    {
        return $this->enviarRetentionEmail($uid);
    }

    public function enviarRetentionAcuse(string $uid): array
    {
        $this->assertIdentifier($uid, 'uid');

        return $this->http->get(sprintf('v4/cfdi40/retenciones/%s/sendAcuse', $uid));
    }

    public function enviarAcuseRetencion(string $uid): array
    {
        return $this->enviarRetentionAcuse($uid);
    }

    public function descargarRetentionAcuse(string $uid, ?string $path = null): string
    {
        return $this->downloadRetentionDocument($uid, 'acuse', $path);
    }

    public function descargarAcuseRetencion(string $uid, ?string $path = null): string
    {
        return $this->descargarRetentionAcuse($uid, $path);
    }

    public function retentionCancelStatus(string $uid): array
    {
        $this->assertIdentifier($uid, 'uid');

        return $this->http->get(sprintf('v4/cfdi40/retenciones/%s/cancel_status', $uid));
    }

    public function estatusCancelacionRetencion(string $uid): array
    {
        return $this->retentionCancelStatus($uid);
    }

    public function retentionCancelDate(string $uid): array
    {
        $this->assertIdentifier($uid, 'uid');

        return $this->http->get(sprintf('v4/cfdi40/retenciones/%s/cancel_date', $uid));
    }

    public function fechaCancelacionRetencion(string $uid): array
    {
        return $this->retentionCancelDate($uid);
    }

    public function retentionRelacionados(string $uid): array
    {
        $this->assertIdentifier($uid, 'uid');

        return $this->http->get(sprintf('v4/cfdi40/retenciones/%s/relacionado', $uid));
    }

    public function relacionadosRetencion(string $uid): array
    {
        return $this->retentionRelacionados($uid);
    }

    public function createDraft(CfdiData $cfdi): array
    {
        $draft = clone $cfdi;
        $draft->borradorSiFalla(true);

        return $this->create($draft);
    }

    public function drafts(): array
    {
        return $this->http->get('v4/drafts');
    }

    public function draft(string $uid): array
    {
        $this->assertIdentifier($uid, 'uid');

        return $this->http->get(sprintf('v4/drafts/%s', $uid));
    }

    public function updateDraft(string $uid, CfdiData $cfdi): array
    {
        $this->assertIdentifier($uid, 'uid');

        $data = $this->preparePayload($cfdi->toArray());
        $this->validate($data);

        return $this->http->post(sprintf('v4/cfdi40/create/%s', $uid), $data);
    }

    public function timbrarDraft(string $uid): array
    {
        $this->assertIdentifier($uid, 'uid');

        return $this->http->post(sprintf('v4/cfdi40/%s/timbrarborrador', $uid));
    }

    public function deleteDraft(string $uid): array
    {
        $this->assertIdentifier($uid, 'uid');

        return $this->http->post(sprintf('v4/drafts/%s/drop', $uid));
    }

    public function descargarPdf(string $uid, ?string $path = null): string
    {
        return $this->downloadDocument($uid, 'pdf', $path);
    }

    public function descargarXml(string $uid, ?string $path = null): string
    {
        return $this->downloadDocument($uid, 'xml', $path);
    }

    public function cancelar(string $uid, string $motivo, ?string $folioSustituto = null): array
    {
        $this->assertIdentifier($uid, 'uid');

        if (!in_array($motivo, self::CANCELATION_REASONS, true)) {
            throw new ApiException('Motivo de cancelación SAT inválido.');
        }

        if ($motivo === '01' && ($folioSustituto === null || trim($folioSustituto) === '')) {
            throw new ApiException('folioSustituto es requerido cuando el motivo es 01.');
        }

        if ($motivo !== '01' && $folioSustituto !== null && trim($folioSustituto) !== '') {
            throw new ApiException('folioSustituto solo debe enviarse cuando el motivo es 01.');
        }

        $data = ['motivo' => $motivo];

        if ($folioSustituto !== null && trim($folioSustituto) !== '') {
            $data['folioSustituto'] = $folioSustituto;
        }

        return $this->http->post(sprintf('v4/cfdi40/%s/cancel', $uid), $data);
    }

    public function descargarAcuse(string $uid, ?string $path = null): string
    {
        return $this->downloadDocument($uid, 'acuse', $path);
    }

    public function enviarEmail(string $uid): array
    {
        $this->assertIdentifier($uid, 'uid');

        return $this->http->get(sprintf('v4/cfdi40/%s/email', $uid));
    }

    public function estatusCancelacion(string $uid): array
    {
        $this->assertIdentifier($uid, 'uid');

        return $this->http->get(sprintf('v4/cfdi40/%s/cancel_status', $uid));
    }

    private function preparePayload(array $data): array
    {
        if (!array_key_exists('TipoDocumento', $data) && array_key_exists('TipoCfdi', $data)) {
            $data['TipoDocumento'] = $data['TipoCfdi'];
            unset($data['TipoCfdi']);
        }

        return $data;
    }

    private function downloadDocument(string $uid, string $format, ?string $path): string
    {
        $this->assertIdentifier($uid, 'uid');

        $endpoint = sprintf('v4/cfdi40/%s/%s', $uid, $format);

        if ($path !== null) {
            return $this->http->download($endpoint, $path);
        }

        return $this->http->getRaw($endpoint);
    }

    private function downloadRetentionDocument(string $uid, string $format, ?string $path): string
    {
        $this->assertIdentifier($uid, 'uid');

        $endpoint = sprintf('v4/cfdi40/retenciones/%s/%s', $uid, $format);

        if ($path !== null) {
            return $this->http->download($endpoint, $path);
        }

        return $this->http->getRaw($endpoint);
    }

    private function validate(array $data): void
    {
        if (empty($data['Receptor']) || !is_array($data['Receptor'])) {
            throw new ApiException('El receptor es requerido.');
        }

        if (empty($data['Conceptos']) || !is_array($data['Conceptos'])) {
            throw new ApiException('Debe existir al menos un concepto.');
        }

        if (empty($data['TipoDocumento']) || !is_string($data['TipoDocumento'])) {
            throw new ApiException('TipoDocumento es requerido.');
        }

        if (!in_array((string) $data['TipoDocumento'], self::GENERAL_DOCUMENT_TYPES, true)) {
            $type = (string) $data['TipoDocumento'];

            throw new ApiException(match ($type) {
                'pago' => 'El tipo "pago" ahora pertenece a PaymentService.',
                'retencion' => 'El tipo "retencion" ahora pertenece a RetentionService.',
                'carta_porte', 'carta_porte_ingreso' => 'Los tipos de Carta Porte ahora pertenecen a CartaPorteService.',
                default => sprintf('TipoDocumento "%s" no es válido en CfdiService.', $type),
            });
        }

        foreach ($data['Conceptos'] as $index => $concepto) {
            $this->validateConcepto($concepto, (string) $index);
        }

        if (array_key_exists('parcialesconstruccion', $data)) {
            $this->validateParcialesConstruccion($data['parcialesconstruccion']);
        }
    }

    private function validatePaymentCfdi(array $data): void
    {
        $this->assertPresent($data, 'Serie', 'Serie es requerida para un complemento de pago.');
        $this->assertNumeric($data['Serie'], 'Serie');
        $this->assertPresent($data, 'UsoCFDI', 'UsoCFDI es requerido para un complemento de pago.');
        $this->assertPresent($data, 'Moneda', 'Moneda es requerida para un complemento de pago.');
        $this->assertFechaFromApi($data);

        if ((string) $data['UsoCFDI'] !== self::PAYMENT_USO_CFDI) {
            throw new ApiException('UsoCFDI debe ser "CP01" en complementos de pago.');
        }

        if ((string) $data['Moneda'] !== self::PAYMENT_MONEDA) {
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

        foreach (self::PAYMENT_CONCEPTO as $field => $expectedValue) {
            $this->assertPresent($concept, $field, sprintf('El concepto de pago requiere el campo "%s".', $field));

            if ((string) $concept[$field] !== $expectedValue) {
                throw new ApiException(sprintf('El campo "%s" del concepto de pago debe ser "%s".', $field, $expectedValue));
            }
        }

        foreach (self::PAYMENT_CONCEPTO_NUMERIC_FIELDS as $field) {
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

    private function validateParcialesConstruccion(mixed $data): void
    {
        if (!is_array($data)) {
            throw new ApiException('El nodo "parcialesconstruccion" debe ser un arreglo.');
        }

        foreach (self::REQUIRED_PARCIALES_CONSTRUCCION_FIELDS as $field) {
            $this->assertPresent($data, $field, sprintf('El campo "parcialesconstruccion.%s" es requerido.', $field));
        }

        $this->assertNumeric($data['NumPerLicoAut'], 'parcialesconstruccion.NumPerLicoAut');

        if (array_key_exists('Version', $data) && trim((string) $data['Version']) !== '' && (string) $data['Version'] !== self::PARCIALES_CONSTRUCCION_VERSION) {
            throw new ApiException('El campo "parcialesconstruccion.Version" solo acepta "1.0".');
        }

        if (!array_key_exists('Inmueble', $data)) {
            return;
        }

        if (!is_array($data['Inmueble'])) {
            throw new ApiException('El campo "parcialesconstruccion.Inmueble" debe ser un arreglo.');
        }

        foreach (self::REQUIRED_PARCIALES_CONSTRUCCION_INMUEBLE_FIELDS as $field) {
            $this->assertPresent(
                $data['Inmueble'],
                $field,
                sprintf('El campo "parcialesconstruccion.Inmueble.%s" es requerido.', $field)
            );
        }

        foreach (['NoExterior', 'NoInterior', 'Estado', 'CodigoPostal'] as $field) {
            if (array_key_exists($field, $data['Inmueble']) && trim((string) $data['Inmueble'][$field]) !== '') {
                $this->assertNumeric($data['Inmueble'][$field], sprintf('parcialesconstruccion.Inmueble.%s', $field));
            }
        }
    }

    private function validateRetention(array $data): void
    {
        foreach (self::REQUIRED_RETENTION_FIELDS as $field) {
            $this->assertPresent($data, $field, sprintf('El campo "%s" es requerido para una retención.', $field));
        }

        if (array_key_exists('DescRetenc', $data) && trim((string) $data['DescRetenc']) !== '') {
            $this->assertNumeric($data['DescRetenc'], 'DescRetenc');
        }

        if (!is_array($data['Receptor'])) {
            throw new ApiException('El campo "Receptor" debe ser un arreglo en retenciones.');
        }

        $this->assertPresent($data['Receptor'], 'UID', 'El campo "Receptor.UID" es requerido en retenciones.');

        if (array_key_exists('Relacionado', $data)) {
            if (!is_array($data['Relacionado'])) {
                throw new ApiException('El campo "Relacionado" debe ser un arreglo cuando se envía.');
            }

            if (array_key_exists('UUID', $data['Relacionado']) && trim((string) $data['Relacionado']['UUID']) !== '' && !$this->isUuid((string) $data['Relacionado']['UUID'])) {
                throw new ApiException('El campo "Relacionado.UUID" debe ser un UUID válido.');
            }
        }

        if (!is_array($data['Periodo'])) {
            throw new ApiException('El campo "Periodo" debe ser un arreglo.');
        }

        foreach (self::REQUIRED_RETENTION_PERIODO_FIELDS as $field) {
            $this->assertPresent($data['Periodo'], $field, sprintf('El campo "Periodo.%s" es requerido.', $field));
        }

        foreach (['MesIni', 'MesFin'] as $field) {
            if (!preg_match('/^(0[1-9]|1[0-2])$/', (string) $data['Periodo'][$field])) {
                throw new ApiException(sprintf('El campo "Periodo.%s" debe tener un valor entre "01" y "12".', $field));
            }
        }

        if (!preg_match('/^\d{4}$/', (string) $data['Periodo']['Ejercicio'])) {
            throw new ApiException('El campo "Periodo.Ejercicio" debe tener formato de año con 4 dígitos.');
        }

        if (!is_array($data['Totales'])) {
            throw new ApiException('El campo "Totales" debe ser un arreglo.');
        }

        foreach (self::REQUIRED_RETENTION_TOTALES_FIELDS as $field) {
            $this->assertPresent($data['Totales'], $field, sprintf('El campo "Totales.%s" es requerido.', $field));
        }

        foreach (['MontoTotOperacion', 'MontoTotGrav', 'MontoTotExent', 'MontoTotRet'] as $field) {
            $this->assertNumeric($data['Totales'][$field], sprintf('Totales.%s', $field));
        }

        if (array_key_exists('ISRCorrespondiente', $data['Totales']) && trim((string) $data['Totales']['ISRCorrespondiente']) !== '') {
            $this->assertNumeric($data['Totales']['ISRCorrespondiente'], 'Totales.ISRCorrespondiente');
        }

        if (array_key_exists('UtilidadBimestral', $data['Totales']) && trim((string) $data['Totales']['UtilidadBimestral']) !== '') {
            $this->assertNumeric($data['Totales']['UtilidadBimestral'], 'Totales.UtilidadBimestral');
        }

        if (!is_array($data['Totales']['ImpRetenidos']) || $data['Totales']['ImpRetenidos'] === []) {
            throw new ApiException('El campo "Totales.ImpRetenidos" debe contener al menos un impuesto retenido.');
        }

        foreach ($data['Totales']['ImpRetenidos'] as $index => $impuesto) {
            $this->validateRetentionTax($impuesto, (string) $index);
        }

        if (array_key_exists('ServiciosPlataformasTecnologicas', $data)) {
            $this->validateTechnologyPlatformRetention($data['ServiciosPlataformasTecnologicas'], (string) $data['CveRetenc']);
        }

        if (array_key_exists('intereses', $data)) {
            $this->validateInterestRetention($data['intereses'], (string) $data['CveRetenc']);
        }

        if (array_key_exists('EnajenacionDeAcciones', $data)) {
            $this->validateStockDisposalRetention($data['EnajenacionDeAcciones'], (string) $data['CveRetenc']);
        }

        if (array_key_exists('InteresesHipotecarios', $data)) {
            $this->validateMortgageInterestRetention($data['InteresesHipotecarios']);
        }

        if (array_key_exists('Dividendos', $data)) {
            $this->validateDividendsRetention($data['Dividendos']);
        }

        if (array_key_exists('ArrendamientoEnFideicomiso', $data)) {
            $this->validateTrustLeaseRetention($data['ArrendamientoEnFideicomiso'], (string) $data['CveRetenc']);
        }
    }

    private function validateRetentionTax(mixed $impuesto, string $index): void
    {
        if (!is_array($impuesto)) {
            throw new ApiException(sprintf('El nodo "Totales.ImpRetenidos.%s" debe ser un arreglo.', $index));
        }

        foreach (self::REQUIRED_RETENTION_IMPUESTO_FIELDS as $field) {
            $this->assertPresent($impuesto, $field, sprintf('El campo "Totales.ImpRetenidos.%s.%s" es requerido.', $index, $field));
        }

        foreach (['BaseRet', 'MontoRet'] as $field) {
            $this->assertNumeric($impuesto[$field], sprintf('Totales.ImpRetenidos.%s.%s', $index, $field));
        }

        if (!in_array((string) $impuesto['ImpuestoRet'], self::RETENTION_IMPUESTOS, true)) {
            throw new ApiException(sprintf('El campo "Totales.ImpRetenidos.%s.ImpuestoRet" no es válido.', $index));
        }

        if (!in_array((string) $impuesto['TipoPagoRet'], self::RETENTION_TIPO_PAGO, true)) {
            throw new ApiException(sprintf('El campo "Totales.ImpRetenidos.%s.TipoPagoRet" no es válido.', $index));
        }
    }

    private function validateInterestRetention(mixed $data, string $cveRetenc): void
    {
        if (!is_array($data)) {
            throw new ApiException('El nodo "intereses" debe ser un arreglo.');
        }

        if ($cveRetenc !== self::INTEREST_RETENTION_KEY) {
            throw new ApiException('El complemento de intereses requiere CveRetenc "07".');
        }

        foreach (self::REQUIRED_INTEREST_FIELDS as $field) {
            $this->assertPresent($data, $field, sprintf('El campo "intereses.%s" es requerido.', $field));
        }

        if ((string) $data['version'] !== '1.0') {
            throw new ApiException('El campo "intereses.version" solo acepta "1.0".');
        }

        foreach (['sistFinanciero', 'retiroAORESRetInt', 'operFinancDerivad'] as $field) {
            if (!in_array((string) $data[$field], self::YES_NO_VALUES, true)) {
                throw new ApiException(sprintf('El campo "intereses.%s" solo acepta "SI" o "NO".', $field));
            }
        }

        foreach (['montIntNominal', 'montIntReal', 'perdida'] as $field) {
            $this->assertNumeric($data[$field], sprintf('intereses.%s', $field));
        }
    }

    private function validateStockDisposalRetention(mixed $data, string $cveRetenc): void
    {
        if (!is_array($data)) {
            throw new ApiException('El nodo "EnajenacionDeAcciones" debe ser un arreglo.');
        }

        if ($cveRetenc !== self::STOCK_DISPOSAL_RETENTION_KEY) {
            throw new ApiException('El complemento de enajenación de acciones requiere CveRetenc "06".');
        }

        foreach (self::REQUIRED_STOCK_DISPOSAL_FIELDS as $field) {
            $this->assertPresent($data, $field, sprintf('El campo "EnajenacionDeAcciones.%s" es requerido.', $field));
        }

        foreach (['Ganancia', 'Perdida'] as $field) {
            $this->assertNumeric($data[$field], sprintf('EnajenacionDeAcciones.%s', $field));
        }
    }

    private function validateMortgageInterestRetention(mixed $data): void
    {
        if (!is_array($data)) {
            throw new ApiException('El nodo "InteresesHipotecarios" debe ser un arreglo.');
        }

        foreach (self::REQUIRED_MORTGAGE_INTEREST_FIELDS as $field) {
            $this->assertPresent($data, $field, sprintf('El campo "InteresesHipotecarios.%s" es requerido.', $field));
        }

        if (!in_array((string) $data['CreditoDeInstFinanc'], self::YES_NO_VALUES, true)) {
            throw new ApiException('El campo "InteresesHipotecarios.CreditoDeInstFinanc" solo acepta "SI" o "NO".');
        }

        $this->assertNumeric($data['SaldoInsoluto'], 'InteresesHipotecarios.SaldoInsoluto');

        foreach ([
            'PropDeducDelCredit',
            'MontTotIntNominalesDev',
            'MontTotIntNominalesDevYPag',
            'MontTotIntRealPagDeduc',
            'NumContrato',
        ] as $field) {
            if (array_key_exists($field, $data) && trim((string) $data[$field]) !== '') {
                $this->assertNumeric($data[$field], sprintf('InteresesHipotecarios.%s', $field));
            }
        }
    }

    private function validateDividendsRetention(mixed $data): void
    {
        if (!is_array($data)) {
            throw new ApiException('El nodo "Dividendos" debe ser un arreglo.');
        }

        foreach (self::REQUIRED_DIVIDEND_FIELDS as $field) {
            $this->assertPresent($data, $field, sprintf('El campo "Dividendos.%s" es requerido.', $field));
        }

        if (!in_array((string) $data['CveTipDivOUtil'], self::DIVIDEND_TYPES, true)) {
            throw new ApiException('El campo "Dividendos.CveTipDivOUtil" no es válido.');
        }

        if (!in_array((string) $data['TipoSocDistrDiv'], self::DIVIDEND_SOCIETY_TYPES, true)) {
            throw new ApiException('El campo "Dividendos.TipoSocDistrDiv" solo acepta "Sociedad Nacional" o "Sociedad Extranjera".');
        }

        foreach ([
            'MontISRAcredRetMexico',
            'MontISRAcredRetExtranjero',
            'MontRetExtDivExt',
            'MontISRAcredNal',
            'MontDivAcumNal',
            'MontDivAcumExt',
            'ProporcionRem',
        ] as $field) {
            if (array_key_exists($field, $data) && trim((string) $data[$field]) !== '') {
                $this->assertNumeric($data[$field], sprintf('Dividendos.%s', $field));
            }
        }
    }

    private function validateTrustLeaseRetention(mixed $data, string $cveRetenc): void
    {
        if (!is_array($data)) {
            throw new ApiException('El nodo "ArrendamientoEnFideicomiso" debe ser un arreglo.');
        }

        if ($cveRetenc !== self::TRUST_LEASE_RETENTION_KEY) {
            throw new ApiException('El complemento de arrendamiento en fideicomiso requiere CveRetenc "17".');
        }

        foreach (self::REQUIRED_TRUST_LEASE_FIELDS as $field) {
            $this->assertPresent($data, $field, sprintf('El campo "ArrendamientoEnFideicomiso.%s" es requerido.', $field));
            $this->assertNumeric($data[$field], sprintf('ArrendamientoEnFideicomiso.%s', $field));
        }

        foreach ([
            'MontTotRet',
            'MontResFiscDistFibras',
            'MontOtrosConceptDistr',
        ] as $field) {
            if (array_key_exists($field, $data) && trim((string) $data[$field]) !== '') {
                $this->assertNumeric($data[$field], sprintf('ArrendamientoEnFideicomiso.%s', $field));
            }
        }
    }

    private function validateTechnologyPlatformRetention(mixed $data, string $cveRetenc): void
    {
        if (!is_array($data)) {
            throw new ApiException('El nodo "ServiciosPlataformasTecnologicas" debe ser un arreglo.');
        }

        if ($cveRetenc !== self::TECHNOLOGY_PLATFORM_RETENTION_KEY) {
            throw new ApiException('El complemento de plataformas tecnológicas requiere CveRetenc "26".');
        }

        foreach (self::REQUIRED_TECH_PLATFORM_FIELDS as $field) {
            $this->assertPresent($data, $field, sprintf('El campo "ServiciosPlataformasTecnologicas.%s" es requerido.', $field));
        }

        foreach ([
            'DifIVAEntregadoPrestServ',
            'MonTotalContribucionGubernamental',
            'MonTotalporUsoPlataforma',
            'MonTotServSIVA',
            'NumServ',
            'TotalISRRetenido',
            'TotalIVARetenido',
            'TotalIVATrasladado',
        ] as $field) {
            $this->assertNumeric($data[$field], sprintf('ServiciosPlataformasTecnologicas.%s', $field));
        }

        if (!is_array($data['Servicios'])) {
            throw new ApiException('El campo "ServiciosPlataformasTecnologicas.Servicios" debe ser un arreglo.');
        }

        foreach (self::REQUIRED_TECH_PLATFORM_SERVICES_FIELDS as $field) {
            $this->assertPresent(
                $data['Servicios'],
                $field,
                sprintf('El campo "ServiciosPlataformasTecnologicas.Servicios.%s" es requerido.', $field)
            );
        }

        if (!is_array($data['Servicios']['DetallesDelServicio']) || $data['Servicios']['DetallesDelServicio'] === []) {
            throw new ApiException('El campo "ServiciosPlataformasTecnologicas.Servicios.DetallesDelServicio" debe contener al menos un servicio.');
        }

        foreach ($data['Servicios']['DetallesDelServicio'] as $index => $detail) {
            $this->validateTechnologyPlatformServiceDetail($detail, (string) $index);
        }
    }

    private function validateTechnologyPlatformServiceDetail(mixed $detail, string $index): void
    {
        if (!is_array($detail)) {
            throw new ApiException(sprintf('El nodo "ServiciosPlataformasTecnologicas.Servicios.DetallesDelServicio.%s" debe ser un arreglo.', $index));
        }

        foreach (self::REQUIRED_TECH_PLATFORM_SERVICE_DETAIL_FIELDS as $field) {
            $this->assertPresent(
                $detail,
                $field,
                sprintf('El campo "ServiciosPlataformasTecnologicas.Servicios.DetallesDelServicio.%s.%s" es requerido.', $index, $field)
            );
        }

        $this->assertDate(
            (string) $detail['FechaServ'],
            sprintf('ServiciosPlataformasTecnologicas.Servicios.DetallesDelServicio.%s.FechaServ', $index)
        );
        $this->assertNumeric(
            $detail['PrecioServSinIVA'],
            sprintf('ServiciosPlataformasTecnologicas.Servicios.DetallesDelServicio.%s.PrecioServSinIVA', $index)
        );

        $this->validateTechnologyPlatformTaxBlock(
            $detail['ComisionDelServicio'],
            sprintf('ServiciosPlataformasTecnologicas.Servicios.DetallesDelServicio.%s.ComisionDelServicio', $index),
            true
        );

        if (array_key_exists('ContribucionGubernamental', $detail)) {
            if (!is_array($detail['ContribucionGubernamental'])) {
                throw new ApiException(sprintf('El campo "ServiciosPlataformasTecnologicas.Servicios.DetallesDelServicio.%s.ContribucionGubernamental" debe ser un arreglo.', $index));
            }

            $this->assertPresent(
                $detail['ContribucionGubernamental'],
                'EntidadDondePagaLaContribucion',
                sprintf('El campo "ServiciosPlataformasTecnologicas.Servicios.DetallesDelServicio.%s.ContribucionGubernamental.EntidadDondePagaLaContribucion" es requerido.', $index)
            );
            $this->assertPresent(
                $detail['ContribucionGubernamental'],
                'ImpContrib',
                sprintf('El campo "ServiciosPlataformasTecnologicas.Servicios.DetallesDelServicio.%s.ContribucionGubernamental.ImpContrib" es requerido.', $index)
            );
            $this->assertNumeric(
                $detail['ContribucionGubernamental']['ImpContrib'],
                sprintf('ServiciosPlataformasTecnologicas.Servicios.DetallesDelServicio.%s.ContribucionGubernamental.ImpContrib', $index)
            );
        }

        if (array_key_exists('ImpuestosTrasladadosdelServicio', $detail)) {
            $this->validateTechnologyPlatformTaxBlock(
                $detail['ImpuestosTrasladadosdelServicio'],
                sprintf('ServiciosPlataformasTecnologicas.Servicios.DetallesDelServicio.%s.ImpuestosTrasladadosdelServicio', $index),
                false
            );
        }
    }

    private function validateTechnologyPlatformTaxBlock(mixed $data, string $path, bool $requiresPercentage): void
    {
        if (!is_array($data)) {
            throw new ApiException(sprintf('El nodo "%s" debe ser un arreglo.', $path));
        }

        foreach (['Base', 'Importe'] as $field) {
            $this->assertPresent($data, $field, sprintf('El campo "%s.%s" es requerido.', $path, $field));
            $this->assertNumeric($data[$field], sprintf('%s.%s', $path, $field));
        }

        if ($requiresPercentage) {
            $this->assertPresent($data, 'Porcentaje', sprintf('El campo "%s.Porcentaje" es requerido.', $path));
            $this->assertNumeric($data['Porcentaje'], sprintf('%s.Porcentaje', $path));

            return;
        }

        foreach (['Impuesto', 'TipoFactor'] as $field) {
            $this->assertPresent($data, $field, sprintf('El campo "%s.%s" es requerido.', $path, $field));
        }

        if (!array_key_exists('TasaCuota', $data) && !array_key_exists('TasaOCuota', $data)) {
            throw new ApiException(sprintf('El campo "%s.TasaCuota" es requerido.', $path));
        }

        $rateField = array_key_exists('TasaCuota', $data) ? 'TasaCuota' : 'TasaOCuota';
        $this->assertNumeric($data[$rateField], sprintf('%s.%s', $path, $rateField));
    }

    private function prepareRetentionPayload(array $data): array
    {
        if (
            array_key_exists('Totales', $data)
            && is_array($data['Totales'])
            && array_key_exists('ImpRetenidos', $data['Totales'])
            && is_array($data['Totales']['ImpRetenidos'])
        ) {
            foreach ($data['Totales']['ImpRetenidos'] as $index => $impuesto) {
                if (
                    is_array($impuesto)
                    && !array_key_exists('ImpuestoRet', $impuesto)
                    && array_key_exists('Impuesto', $impuesto)
                ) {
                    $data['Totales']['ImpRetenidos'][$index]['ImpuestoRet'] = $impuesto['Impuesto'];
                    unset($data['Totales']['ImpRetenidos'][$index]['Impuesto']);
                }
            }
        }

        if (
            array_key_exists('Dividendos', $data)
            && is_array($data['Dividendos'])
            && !array_key_exists('CveTipDivOUtil', $data['Dividendos'])
            && array_key_exists('CveTIpDivOUtil', $data['Dividendos'])
        ) {
            $data['Dividendos']['CveTipDivOUtil'] = $data['Dividendos']['CveTIpDivOUtil'];
            unset($data['Dividendos']['CveTIpDivOUtil']);
        }

        return $data;
    }

    private function buildCancelPayload(string $motivo, ?string $folioSustituto = null): array
    {
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

        return $payload;
    }

    private function validateConcepto(mixed $concepto, string $index): void
    {
        if (!is_array($concepto)) {
            throw new ApiException(sprintf('El nodo "Conceptos.%s" debe ser un arreglo.', $index));
        }

        $hasACuentaTerceros = false;

        foreach (self::A_CUENTA_TERCEROS_FIELDS as $field) {
            if (array_key_exists($field, $concepto)) {
                $hasACuentaTerceros = true;
                break;
            }
        }

        if ($hasACuentaTerceros) {
            foreach (self::A_CUENTA_TERCEROS_FIELDS as $field) {
                $this->assertPresent($concepto, $field, sprintf('El campo "Conceptos.%s.%s" es requerido en ACuentaTerceros.', $index, $field));
            }

            $this->assertNumeric(
                $concepto['DomicilioFiscalACuentaTerceros'],
                sprintf('Conceptos.%s.DomicilioFiscalACuentaTerceros', $index)
            );
        }

        if (!array_key_exists('Complemento', $concepto)) {
            return;
        }

        if (!is_array($concepto['Complemento']) || $concepto['Complemento'] === []) {
            throw new ApiException(sprintf('El campo "Conceptos.%s.Complemento" debe ser un arreglo con al menos un complemento.', $index));
        }

        foreach ($concepto['Complemento'] as $complementIndex => $complemento) {
            $this->validateConceptComplement($complemento, sprintf('Conceptos.%s.Complemento.%s', $index, (string) $complementIndex));
        }
    }

    private function validateConceptComplement(mixed $complemento, string $path): void
    {
        if (!is_array($complemento)) {
            throw new ApiException(sprintf('El nodo "%s" debe ser un arreglo.', $path));
        }

        $this->assertPresent($complemento, 'typeComplement', sprintf('El campo "%s.typeComplement" es requerido.', $path));

        if ((string) $complemento['typeComplement'] !== self::EDUCATIONAL_COMPLEMENT_TYPE) {
            if ((string) $complemento['typeComplement'] === self::VEHICLE_COMPLEMENT_TYPE) {
                $this->validateVehicleComplement($complemento, $path);
            }

            return;
        }

        foreach (self::EDUCATIONAL_COMPLEMENT_FIELDS as $field) {
            $this->assertPresent($complemento, $field, sprintf('El campo "%s.%s" es requerido.', $path, $field));
        }

        if ((string) $complemento['version'] !== self::EDUCATIONAL_COMPLEMENT_VERSION) {
            throw new ApiException(sprintf('El campo "%s.version" solo acepta "1.0".', $path));
        }

        if (!in_array((string) $complemento['nivelEducativo'], self::EDUCATIONAL_LEVELS, true)) {
            throw new ApiException(sprintf('El campo "%s.nivelEducativo" no es valido para el complemento educativo.', $path));
        }
    }

    private function validateVehicleComplement(array $complemento, string $path): void
    {
        foreach (self::VEHICLE_COMPLEMENT_FIELDS as $field) {
            $this->assertPresent($complemento, $field, sprintf('El campo "%s.%s" es requerido.', $path, $field));
        }

        if (!preg_match('/^\d{7}$/', (string) $complemento['ClaveVehicular'])) {
            throw new ApiException(sprintf('El campo "%s.ClaveVehicular" debe tener exactamente 7 dígitos.', $path));
        }

        if (strlen((string) $complemento['Niv']) !== 17) {
            throw new ApiException(sprintf('El campo "%s.Niv" debe tener exactamente 17 caracteres.', $path));
        }

        if (array_key_exists('InformacionAduanera', $complemento)) {
            $this->validateInformacionAduanera(
                $complemento['InformacionAduanera'],
                sprintf('%s.InformacionAduanera', $path)
            );
        }

        if (!array_key_exists('Parte', $complemento)) {
            return;
        }

        if (!is_array($complemento['Parte']) || $complemento['Parte'] === []) {
            throw new ApiException(sprintf('El campo "%s.Parte" debe ser un arreglo con al menos una parte.', $path));
        }

        foreach ($complemento['Parte'] as $index => $parte) {
            $this->validateVehiclePart($parte, sprintf('%s.Parte.%s', $path, (string) $index));
        }
    }

    private function validateVehiclePart(mixed $parte, string $path): void
    {
        if (!is_array($parte)) {
            throw new ApiException(sprintf('El nodo "%s" debe ser un arreglo.', $path));
        }

        foreach (self::VEHICLE_PART_FIELDS as $field) {
            $this->assertPresent($parte, $field, sprintf('El campo "%s.%s" es requerido.', $path, $field));
        }

        $this->assertNumeric($parte['cantidad'], sprintf('%s.cantidad', $path));

        foreach (['valorUnitario', 'importe'] as $field) {
            if (array_key_exists($field, $parte) && trim((string) $parte[$field]) !== '') {
                $this->assertNumeric($parte[$field], sprintf('%s.%s', $path, $field));
            }
        }

        if (array_key_exists('InformacionAduanera', $parte)) {
            $this->validateInformacionAduanera(
                $parte['InformacionAduanera'],
                sprintf('%s.InformacionAduanera', $path)
            );
        }
    }

    private function validateInformacionAduanera(mixed $data, string $path): void
    {
        if (!is_array($data)) {
            throw new ApiException(sprintf('El nodo "%s" debe ser un arreglo.', $path));
        }

        $hasMeaningfulData = false;

        foreach (['numero', 'fecha', 'aduana'] as $field) {
            if (array_key_exists($field, $data) && trim((string) $data[$field]) !== '') {
                $hasMeaningfulData = true;
                break;
            }
        }

        if (!$hasMeaningfulData) {
            return;
        }

        foreach (['numero', 'fecha'] as $field) {
            $this->assertPresent($data, $field, sprintf('El campo "%s.%s" es requerido.', $path, $field));
        }

        $this->assertDate((string) $data['fecha'], sprintf('%s.fecha', $path));
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

    private function assertDate(string $value, string $field): void
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);

        if ($date === false || $date->format('Y-m-d') !== $value) {
            throw new ApiException(sprintf('El campo "%s" debe tener el formato "Y-m-d".', $field));
        }
    }

    private function isPaymentCfdi(array $data): bool
    {
        return strtolower((string) $data['TipoDocumento']) === self::PAYMENT_DOCUMENT_TYPE;
    }

    private function payment(): PaymentService
    {
        return $this->paymentService ??= new PaymentService($this->http);
    }

    private function isUuid(string $value): bool
    {
        return preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[1-5][0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $value) === 1;
    }

    private function assertIdentifier(string $value, string $label): void
    {
        if (trim($value) === '') {
            throw new ApiException(sprintf('El %s es requerido.', $label));
        }
    }
}
