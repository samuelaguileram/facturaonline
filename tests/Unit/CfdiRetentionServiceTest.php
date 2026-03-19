<?php

declare(strict_types=1);

namespace FacturaOnline\Tests\Unit;

use FacturaOnline\Contracts\TransportInterface;
use FacturaOnline\Exceptions\ApiException;
use FacturaOnline\Services\CFDI\CfdiService;
use PHPUnit\Framework\TestCase;

final class CfdiRetentionServiceTest extends TestCase
{
    public function test_create_retention_envia_endpoint_esperado(): void
    {
        $transport = new CfdiRetentionTransportSpy();
        $service = new CfdiService($transport);
        $payload = $this->validPayload();

        $service->createRetention($payload);

        self::assertSame('POST', $transport->lastMethod);
        self::assertSame('v4/cfdi40/retenciones', $transport->lastEndpoint);
        self::assertSame('03', $transport->lastPayload['CveRetenc']);
    }

    public function test_crear_retencion_es_alias_de_create_retention(): void
    {
        $transport = new CfdiRetentionTransportSpy();
        $service = new CfdiService($transport);

        $service->crearRetencion($this->validPayload());

        self::assertSame('v4/cfdi40/retenciones', $transport->lastEndpoint);
    }

    public function test_create_retention_rechaza_mes_invalido(): void
    {
        $service = new CfdiService(new CfdiRetentionTransportSpy());
        $payload = $this->validPayload();
        $payload['Periodo']['MesIni'] = '13';

        $this->expectException(ApiException::class);
        $service->createRetention($payload);
    }

    public function test_create_retention_rechaza_impuesto_retenido_invalido(): void
    {
        $service = new CfdiService(new CfdiRetentionTransportSpy());
        $payload = $this->validPayload();
        $payload['Totales']['ImpRetenidos'][0]['ImpuestoRet'] = '999';

        $this->expectException(ApiException::class);
        $service->createRetention($payload);
    }

    private function validPayload(): array
    {
        return [
            'LugarExpedicion' => '26015',
            'CveRetenc' => '03',
            'DescRetenc' => 100,
            'RegimenFiscal' => '606',
            'Relacionado' => [
                'TipoRelacion' => '01',
                'UUID' => '1084d341-996a-43d7-a115-e15a98a5b3cf',
            ],
            'Receptor' => [
                'UID' => '61af9fdc030e2',
                'NacionalidadR' => 'Nacional',
                'Nacional' => [
                    'RfcR' => 'XAXX010101000',
                    'NomDenRazSocR' => 'Razon de pruebas',
                    'CURPR' => 'XEXX010101HNEXXXA4',
                    'DomicilioFiscalR' => '44680',
                ],
            ],
            'Periodo' => [
                'MesIni' => '01',
                'MesFin' => '03',
                'Ejercicio' => '2022',
            ],
            'Totales' => [
                'MontoTotOperacion' => 0,
                'MontoTotGrav' => 0,
                'MontoTotExent' => 0,
                'MontoTotRet' => 123,
                'UtilidadBimestral' => 1,
                'ISRCorrespondiente' => 1,
                'ImpRetenidos' => [
                    [
                        'BaseRet' => 12,
                        'ImpuestoRet' => '001',
                        'MontoRet' => 123,
                        'TipoPagoRet' => '03',
                    ],
                ],
            ],
        ];
    }
}

final class CfdiRetentionTransportSpy implements TransportInterface
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
