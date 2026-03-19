<?php

declare(strict_types=1);

namespace FacturaOnline\Tests\Unit;

use FacturaOnline\Contracts\TransportInterface;
use FacturaOnline\Exceptions\ApiException;
use FacturaOnline\Services\CFDI\CfdiService;
use PHPUnit\Framework\TestCase;

final class CfdiRetentionTechnologyPlatformsTest extends TestCase
{
    public function test_create_retention_acepta_complemento_de_plataformas_tecnologicas_valido(): void
    {
        $transport = new CfdiRetentionTechnologyPlatformsTransportSpy();
        $service = new CfdiService($transport);

        $service->createRetention($this->validPayload());

        self::assertSame('POST', $transport->lastMethod);
        self::assertSame('v4/cfdi40/retenciones', $transport->lastEndpoint);
        self::assertSame('26', $transport->lastPayload['CveRetenc']);
        self::assertSame(
            '03',
            $transport->lastPayload['ServiciosPlataformasTecnologicas']['Periodicidad']
        );
    }

    public function test_create_retention_requiere_cve_retenc_26_para_plataformas_tecnologicas(): void
    {
        $service = new CfdiService(new CfdiRetentionTechnologyPlatformsTransportSpy());
        $payload = $this->validPayload();
        $payload['CveRetenc'] = '03';

        $this->expectException(ApiException::class);
        $service->createRetention($payload);
    }

    public function test_create_retention_rechaza_detalle_de_servicio_incompleto(): void
    {
        $service = new CfdiService(new CfdiRetentionTechnologyPlatformsTransportSpy());
        $payload = $this->validPayload();
        unset($payload['ServiciosPlataformasTecnologicas']['Servicios']['DetallesDelServicio'][0]['ComisionDelServicio']);

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
            'ServiciosPlataformasTecnologicas' => [
                'DifIVAEntregadoPrestServ' => 8,
                'MonTotalContribucionGubernamental' => 11,
                'MonTotalporUsoPlataforma' => 10,
                'MonTotServSIVA' => 100,
                'NumServ' => 1,
                'Periodicidad' => '03',
                'Servicios' => [
                    'DetallesDelServicio' => [
                        [
                            'ComisionDelServicio' => [
                                'Base' => '100',
                                'Importe' => '10',
                                'Porcentaje' => '0.10',
                            ],
                            'ContribucionGubernamental' => [
                                'EntidadDondePagaLaContribucion' => '14',
                                'ImpContrib' => '11',
                            ],
                            'FechaServ' => '2022-09-21',
                            'FormaPagoServ' => '06',
                            'ImpuestosTrasladadosdelServicio' => [
                                'Base' => '100',
                                'Importe' => 16,
                                'Impuesto' => '02',
                                'TasaCuota' => '0.160000',
                                'TipoFactor' => 'Tasa',
                            ],
                            'PrecioServSinIVA' => '100',
                            'RFCTerceroAutorizado' => '',
                            'SubTipServ' => '01',
                            'TipoDeServ' => '01',
                        ],
                    ],
                ],
                'TotalISRRetenido' => '0',
                'TotalIVARetenido' => 8,
                'TotalIVATrasladado' => 16,
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

final class CfdiRetentionTechnologyPlatformsTransportSpy implements TransportInterface
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
