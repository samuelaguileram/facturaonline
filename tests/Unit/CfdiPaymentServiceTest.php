<?php

declare(strict_types=1);

namespace FacturaOnline\Tests\Unit;

use FacturaOnline\Contracts\TransportInterface;
use FacturaOnline\DTO\CfdiData;
use FacturaOnline\Exceptions\ApiException;
use FacturaOnline\Services\CFDI\CfdiService;
use PHPUnit\Framework\TestCase;

final class CfdiPaymentServiceTest extends TestCase
{
    public function test_create_acepta_complemento_de_pago_valido(): void
    {
        $transport = new CfdiPaymentTransportSpy();
        $service = new CfdiService($transport);
        $payload = $this->validPaymentPayload();

        $service->create(CfdiData::fromArray($payload));

        self::assertSame('POST', $transport->lastMethod);
        self::assertSame('v4/cfdi40/create', $transport->lastEndpoint);
        self::assertSame('pago', $transport->lastPayload['TipoDocumento']);
    }

    public function test_create_acepta_alias_tipo_cfdi(): void
    {
        $transport = new CfdiPaymentTransportSpy();
        $service = new CfdiService($transport);
        $payload = $this->validPaymentPayload();
        unset($payload['TipoDocumento']);
        $payload['TipoCfdi'] = 'pago';

        $service->create(CfdiData::fromArray($payload));

        self::assertSame('pago', $transport->lastPayload['TipoDocumento']);
        self::assertArrayNotHasKey('TipoCfdi', $transport->lastPayload);
    }

    public function test_create_requiere_uso_cfdi_cp01_en_complemento_de_pago(): void
    {
        $service = new CfdiService(new CfdiPaymentTransportSpy());
        $payload = $this->validPaymentPayload();
        $payload['UsoCFDI'] = 'G03';

        $this->expectException(ApiException::class);
        $service->create(CfdiData::fromArray($payload));
    }

    public function test_create_rechaza_forma_de_pago_99(): void
    {
        $service = new CfdiService(new CfdiPaymentTransportSpy());
        $payload = $this->validPaymentPayload();
        $payload['Pagos'][0]['FormaDePagoP'] = '99';

        $this->expectException(ApiException::class);
        $service->create(CfdiData::fromArray($payload));
    }

    public function test_create_valida_uuid_en_docto_relacionado(): void
    {
        $service = new CfdiService(new CfdiPaymentTransportSpy());
        $payload = $this->validPaymentPayload();
        $payload['Pagos'][0]['DoctoRelacionado'][0]['IdDocumento'] = 'no-es-uuid';

        $this->expectException(ApiException::class);
        $service->create(CfdiData::fromArray($payload));
    }

    public function test_create_requiere_equivalencia_uno_cuando_moneda_es_identica(): void
    {
        $service = new CfdiService(new CfdiPaymentTransportSpy());
        $payload = $this->validPaymentPayload();
        $payload['Pagos'][0]['DoctoRelacionado'][0]['EquivalenciaDR'] = '18';

        $this->expectException(ApiException::class);
        $service->create(CfdiData::fromArray($payload));
    }

    public function test_create_valida_objeto_impuesto_si_se_envia(): void
    {
        $service = new CfdiService(new CfdiPaymentTransportSpy());
        $payload = $this->validPaymentPayload();
        $payload['Pagos'][0]['DoctoRelacionado'][0]['ObjetoImpuesto'] = '99';

        $this->expectException(ApiException::class);
        $service->create(CfdiData::fromArray($payload));
    }

    public function test_create_valida_fecha_from_api_en_complemento_de_pago(): void
    {
        $service = new CfdiService(new CfdiPaymentTransportSpy());
        $payload = $this->validPaymentPayload();
        $payload['FechaFromAPI'] = (new \DateTimeImmutable('now'))
            ->modify('+2 hours')
            ->format('Y-m-d\\TH:i:s');

        $this->expectException(ApiException::class);
        $service->create(CfdiData::fromArray($payload));
    }

    private function validPaymentPayload(): array
    {
        return [
            'TipoDocumento' => 'pago',
            'RegimenFiscal' => '601',
            'Serie' => '1247',
            'FechaFromAPI' => (new \DateTimeImmutable('now'))
                ->modify('-2 hours')
                ->format('Y-m-d\\TH:i:s'),
            'LugarExpedicion' => '44555',
            'UsoCFDI' => 'CP01',
            'Moneda' => 'XXX',
            'EnviarCorreo' => 'true',
            'Receptor' => [
                'UID' => '55c0fdc67593d',
                'RegimenFiscalR' => '612',
            ],
            'Conceptos' => [
                [
                    'ClaveProdServ' => '84111506',
                    'Cantidad' => '1',
                    'ClaveUnidad' => 'ACT',
                    'Descripcion' => 'Pago',
                    'ValorUnitario' => '0',
                    'Importe' => '0',
                ],
            ],
            'Pagos' => [
                [
                    'FechaPago' => '2026-03-18T12:00:00',
                    'FormaDePagoP' => '01',
                    'MonedaP' => 'MXN',
                    'TipoCambioP' => '1',
                    'Monto' => '2500',
                    'NumOperacion' => '',
                    'RfcEmisorCtaOrd' => '',
                    'CtaOrdenante' => '',
                    'NomBancoOrdExt' => '',
                    'RfcEmisorCtaBen' => '',
                    'CtaBeneficiario' => '',
                    'DoctoRelacionado' => [
                        [
                            'IdDocumento' => '3e27ae21-b190-4c4c-a8d1-d3b6ab993122',
                            'MonedaDR' => 'MXN',
                            'EquivalenciaDR' => '1',
                            'NumParcialidad' => '1',
                            'ImpSaldoAnt' => '1200',
                            'ImpPagado' => '1200',
                            'ImpSaldoInsoluto' => '0',
                            'Impuestos' => [
                                'Traslados' => [
                                    [
                                        'Base' => '1300.000000',
                                        'Impuesto' => '002',
                                        'TipoFactor' => 'Tasa',
                                        'TasaOCuota' => '0.16',
                                        'Importe' => '208.000000',
                                    ],
                                ],
                                'Retenidos' => [
                                    [
                                        'Base' => '1300.000000',
                                        'Impuesto' => '001',
                                        'TipoFactor' => 'Tasa',
                                        'TasaOCuota' => '0.10',
                                        'Importe' => '130.000000',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}

final class CfdiPaymentTransportSpy implements TransportInterface
{
    public ?string $lastMethod = null;
    public ?string $lastEndpoint = null;
    public array $lastPayload = [];

    public function get(string $endpoint, array $query = []): array
    {
        return [];
    }

    public function getRaw(string $endpoint): string
    {
        return '';
    }

    public function download(string $endpoint, string $savePath): string
    {
        return $savePath;
    }

    public function post(string $endpoint, array $data = []): array
    {
        $this->lastMethod = 'POST';
        $this->lastEndpoint = $endpoint;
        $this->lastPayload = $data;

        return [];
    }

    public function postMultipart(string $endpoint, array $data = [], array $files = []): array
    {
        return [];
    }

    public function postAuto(string $endpoint, array $data = [], array $files = []): array
    {
        return [];
    }
}
