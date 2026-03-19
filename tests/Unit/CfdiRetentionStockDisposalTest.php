<?php

declare(strict_types=1);

namespace FacturaOnline\Tests\Unit;

use FacturaOnline\Contracts\TransportInterface;
use FacturaOnline\Exceptions\ApiException;
use FacturaOnline\Services\CFDI\CfdiService;
use PHPUnit\Framework\TestCase;

final class CfdiRetentionStockDisposalTest extends TestCase
{
    public function test_create_retention_acepta_complemento_enajenacion_de_acciones_valido(): void
    {
        $transport = new CfdiRetentionStockDisposalTransportSpy();
        $service = new CfdiService($transport);

        $service->createRetention($this->validPayload());

        self::assertSame('POST', $transport->lastMethod);
        self::assertSame('v4/cfdi40/retenciones', $transport->lastEndpoint);
        self::assertSame('06', $transport->lastPayload['CveRetenc']);
        self::assertSame(
            'Contrato de prueba llevado acabo en México en 19/Julio/2023',
            $transport->lastPayload['EnajenacionDeAcciones']['ContratoIntermediacion']
        );
    }

    public function test_create_retention_requiere_cve_retenc_06_para_enajenacion_de_acciones(): void
    {
        $service = new CfdiService(new CfdiRetentionStockDisposalTransportSpy());
        $payload = $this->validPayload();
        $payload['CveRetenc'] = '03';

        $this->expectException(ApiException::class);
        $service->createRetention($payload);
    }

    public function test_create_retention_rechaza_ganancia_no_numerica(): void
    {
        $service = new CfdiService(new CfdiRetentionStockDisposalTransportSpy());
        $payload = $this->validPayload();
        $payload['EnajenacionDeAcciones']['Ganancia'] = 'abc';

        $this->expectException(ApiException::class);
        $service->createRetention($payload);
    }

    private function validPayload(): array
    {
        return [
            'CveRetenc' => '06',
            'LugarExpedicion' => '42501',
            'Emisor' => [
                'CURPE' => 'OORB610222MZSRCL02',
            ],
            'Receptor' => [
                'UID' => '6466605c19cfb',
                'NacionalidadR' => 'Nacional',
                'Nacional' => [
                    'CURPR' => 'XEXX010101HNEXXXA4',
                    'DomicilioFiscalR' => '01279',
                    'NomDenRazSocR' => 'XAIME WEIR ROJO',
                    'RfcR' => 'WERX631016S30',
                ],
            ],
            'Periodo' => [
                'Ejercicio' => '2023',
                'MesFin' => '07',
                'MesIni' => '07',
            ],
            'Totales' => [
                'ImpRetenidos' => [
                    [
                        'BaseRet' => '10000',
                        'ImpuestoRet' => '002',
                        'MontoRet' => '1600',
                        'TipoPagoRet' => '01',
                    ],
                ],
                'ISRCorrespondiente' => '',
                'MontoTotExent' => '0',
                'MontoTotGrav' => '11600',
                'MontoTotOperacion' => 11600,
                'MontoTotRet' => 1600,
                'UtilidadBimestral' => '',
            ],
            'DescRetenc' => '0.00',
            'EnajenacionDeAcciones' => [
                'ContratoIntermediacion' => 'Contrato de prueba llevado acabo en México en 19/Julio/2023',
                'Ganancia' => 1234.56,
                'Perdida' => 89,
            ],
        ];
    }
}

final class CfdiRetentionStockDisposalTransportSpy implements TransportInterface
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
