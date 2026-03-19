<?php

declare(strict_types=1);

namespace FacturaOnline\Tests\Unit;

use FacturaOnline\Contracts\TransportInterface;
use FacturaOnline\Exceptions\ApiException;
use FacturaOnline\Services\CFDI\CfdiService;
use PHPUnit\Framework\TestCase;

final class CfdiRetentionInterestTest extends TestCase
{
    public function test_create_retention_acepta_complemento_intereses_valido(): void
    {
        $transport = new CfdiRetentionInterestTransportSpy();
        $service = new CfdiService($transport);

        $service->createRetention($this->validPayload());

        self::assertSame('POST', $transport->lastMethod);
        self::assertSame('v4/cfdi40/retenciones', $transport->lastEndpoint);
        self::assertSame('07', $transport->lastPayload['CveRetenc']);
        self::assertSame(
            '002',
            $transport->lastPayload['Totales']['ImpRetenidos'][0]['ImpuestoRet']
        );
    }

    public function test_create_retention_requiere_cve_retenc_07_para_intereses(): void
    {
        $service = new CfdiService(new CfdiRetentionInterestTransportSpy());
        $payload = $this->validPayload();
        $payload['CveRetenc'] = '03';

        $this->expectException(ApiException::class);
        $service->createRetention($payload);
    }

    public function test_create_retention_rechaza_valores_fuera_de_si_no_en_intereses(): void
    {
        $service = new CfdiService(new CfdiRetentionInterestTransportSpy());
        $payload = $this->validPayload();
        $payload['intereses']['sistFinanciero'] = 'MAYBE';

        $this->expectException(ApiException::class);
        $service->createRetention($payload);
    }

    private function validPayload(): array
    {
        return [
            'CveRetenc' => '07',
            'Emisor' => [
                'CURPE' => 'OORB610222MZSRCL02',
            ],
            'Receptor' => [
                'UID' => '62b1dcf75a60f',
                'NacionalidadR' => 'Nacional',
                'Nacional' => [
                    'RFCRecep' => 'CACX7605101P8',
                    'NomDenRazSocR' => 'XOCHILT CASAS CHAVEZ',
                    'CURPR' => '',
                ],
            ],
            'Periodo' => [
                'MesIni' => '01',
                'MesFin' => '02',
                'Ejercicio' => '2023',
            ],
            'Totales' => [
                'MontoTotOperacion' => '300',
                'MontoTotGrav' => '100',
                'MontoTotExent' => '200',
                'MontoTotRet' => '10',
                'ImpRetenidos' => [
                    [
                        'BaseRet' => '14',
                        'Impuesto' => '002',
                        'MontoRet' => '10',
                        'TipoPagoRet' => '03',
                    ],
                ],
            ],
            'intereses' => [
                'version' => '1.0',
                'sistFinanciero' => 'SI',
                'retiroAORESRetInt' => 'SI',
                'operFinancDerivad' => 'SI',
                'montIntNominal' => 791.52,
                'montIntReal' => 1000.88,
                'perdida' => 500.31,
            ],
            'LugarExpedicion' => '26015',
        ];
    }
}

final class CfdiRetentionInterestTransportSpy implements TransportInterface
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
