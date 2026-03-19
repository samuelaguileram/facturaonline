<?php

declare(strict_types=1);

namespace FacturaOnline\Tests\Unit;

use FacturaOnline\Contracts\TransportInterface;
use FacturaOnline\Exceptions\ApiException;
use FacturaOnline\Services\CFDI\CfdiService;
use PHPUnit\Framework\TestCase;

final class CfdiRetentionTrustLeaseTest extends TestCase
{
    public function test_create_retention_acepta_complemento_arrendamiento_en_fideicomiso_valido(): void
    {
        $transport = new CfdiRetentionTrustLeaseTransportSpy();
        $service = new CfdiService($transport);

        $service->createRetention($this->validPayload());

        self::assertSame('POST', $transport->lastMethod);
        self::assertSame('v4/cfdi40/retenciones', $transport->lastEndpoint);
        self::assertSame(
            10,
            $transport->lastPayload['ArrendamientoEnFideicomiso']['PagProvEfecPorFiduc']
        );
    }

    public function test_create_retention_rechaza_cve_retenc_invalida_para_arrendamiento_en_fideicomiso(): void
    {
        $service = new CfdiService(new CfdiRetentionTrustLeaseTransportSpy());
        $payload = $this->validPayload();
        $payload['CveRetenc'] = '26';

        $this->expectException(ApiException::class);
        $service->createRetention($payload);
    }

    public function test_create_retention_rechaza_monto_requerido_no_numerico(): void
    {
        $service = new CfdiService(new CfdiRetentionTrustLeaseTransportSpy());
        $payload = $this->validPayload();
        $payload['ArrendamientoEnFideicomiso']['RendimFideicom'] = 'abc';

        $this->expectException(ApiException::class);
        $service->createRetention($payload);
    }

    private function validPayload(): array
    {
        return [
            'LugarExpedicion' => '45625',
            'CveRetenc' => '17',
            'DescRetenc' => 100,
            'Receptor' => [
                'UID' => '6661e28104f69',
                'NacionalidadR' => 'Nacional',
                'Nacional' => [
                    'RfcR' => 'S&S051221SE2',
                    'NomDenRazSocR' => 'S & SOFTWARE',
                    'CURPR' => '',
                    'DomicilioFiscalR' => '76022',
                ],
            ],
            'Periodo' => [
                'MesIni' => '01',
                'MesFin' => '03',
                'Ejercicio' => '2024',
            ],
            'Totales' => [
                'MontoTotOperacion' => 1010,
                'MontoTotGrav' => 1000,
                'MontoTotExent' => 10,
                'MontoTotRet' => 10,
                'UtilidadBimestral' => 1,
                'ISRCorrespondiente' => 1,
                'ImpRetenidos' => [
                    [
                        'BaseRet' => 10,
                        'ImpuestoRet' => '001',
                        'MontoRet' => 10,
                        'TipoPagoRet' => '03',
                    ],
                ],
            ],
            'ArrendamientoEnFideicomiso' => [
                'PagProvEfecPorFiduc' => 10,
                'RendimFideicom' => 10,
                'DeduccCorresp' => 10,
                'MontTotRet' => 10,
                'MontResFiscDistFibras' => 10,
                'MontOtrosConceptDistr' => 10,
                'DescrMontOtrosConceptDistr' => 'Fideicomiso de Inversion y Bienes Raices',
            ],
        ];
    }
}

final class CfdiRetentionTrustLeaseTransportSpy implements TransportInterface
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
