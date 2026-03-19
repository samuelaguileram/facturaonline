<?php

declare(strict_types=1);

namespace FacturaOnline\Tests\Unit;

use FacturaOnline\Contracts\TransportInterface;
use FacturaOnline\Exceptions\ApiException;
use FacturaOnline\Services\CFDI\CfdiService;
use PHPUnit\Framework\TestCase;

final class CfdiRetentionDividendsTest extends TestCase
{
    public function test_create_retention_acepta_complemento_dividendos_valido(): void
    {
        $transport = new CfdiRetentionDividendsTransportSpy();
        $service = new CfdiService($transport);

        $service->createRetention($this->validPayload());

        self::assertSame('POST', $transport->lastMethod);
        self::assertSame('v4/cfdi40/retenciones', $transport->lastEndpoint);
        self::assertSame('01', $transport->lastPayload['Dividendos']['CveTipDivOUtil']);
    }

    public function test_create_retention_rechaza_tipo_de_dividendo_invalido(): void
    {
        $service = new CfdiService(new CfdiRetentionDividendsTransportSpy());
        $payload = $this->validPayload();
        $payload['Dividendos']['CveTipDivOUtil'] = '99';

        $this->expectException(ApiException::class);
        $service->createRetention($payload);
    }

    public function test_create_retention_rechaza_tipo_de_sociedad_invalido(): void
    {
        $service = new CfdiService(new CfdiRetentionDividendsTransportSpy());
        $payload = $this->validPayload();
        $payload['Dividendos']['TipoSocDistrDiv'] = 'Otra';

        $this->expectException(ApiException::class);
        $service->createRetention($payload);
    }

    private function validPayload(): array
    {
        return [
            'CveRetenc' => '26',
            'LugarExpedicion' => '26015',
            'Periodo' => [
                'Ejercicio' => '2022',
                'MesFin' => '09',
                'MesIni' => '09',
            ],
            'Receptor' => [
                'Nacional' => [
                    'CURPR' => 'XEXX010101HNEXXXA4',
                    'DomicilioFiscalR' => '63180',
                    'NomDenRazSocR' => 'MARIA WATEMBER TORRES',
                    'RfcR' => 'WATM640917J45',
                ],
                'NacionalidadR' => 'Nacional',
                'UID' => '62b1dcf75a60f',
            ],
            'Relacionado' => [
                'TipoRelacion' => '05',
                'UUID' => '7c2ecea6-afbc-46b1-95d0-535fc8e8bba9',
            ],
            'Dividendos' => [
                'CveTipDivOUtil' => '01',
                'MontISRAcredRetMexico' => '1200',
                'MontISRAcredRetExtranjero' => '30000',
                'MontRetExtDivExt' => '40000',
                'TipoSocDistrDiv' => 'Sociedad Nacional',
                'MontISRAcredNal' => '100',
                'MontDivAcumNal' => '200',
                'MontDivAcumExt' => '300',
                'ProporcionRem' => '10',
            ],
            'Totales' => [
                'ImpRetenidos' => [
                    [
                        'BaseRet' => 16,
                        'ImpuestoRet' => '002',
                        'MontoRet' => 8,
                        'TipoPagoRet' => '01',
                    ],
                ],
                'ISRCorrespondiente' => '',
                'MontoTotExent' => 0,
                'MontoTotGrav' => 100,
                'MontoTotOperacion' => 100,
                'MontoTotRet' => 8,
            ],
        ];
    }
}

final class CfdiRetentionDividendsTransportSpy implements TransportInterface
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
