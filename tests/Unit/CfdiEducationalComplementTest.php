<?php

declare(strict_types=1);

namespace FacturaOnline\Tests\Unit;

use FacturaOnline\Contracts\TransportInterface;
use FacturaOnline\DTO\CfdiData;
use FacturaOnline\Exceptions\ApiException;
use FacturaOnline\Services\CFDI\CfdiService;
use PHPUnit\Framework\TestCase;

final class CfdiEducationalComplementTest extends TestCase
{
    public function test_create_acepta_complemento_educativo_valido(): void
    {
        $transport = new CfdiEducationalTransportSpy();
        $service = new CfdiService($transport);

        $service->create(CfdiData::fromArray($this->validPayload()));

        self::assertSame('POST', $transport->lastMethod);
        self::assertSame('v4/cfdi40/create', $transport->lastEndpoint);
        self::assertSame(
            'iedu',
            $transport->lastPayload['Conceptos'][0]['Complemento'][0]['typeComplement']
        );
    }

    public function test_create_rechaza_version_invalida_del_complemento_educativo(): void
    {
        $service = new CfdiService(new CfdiEducationalTransportSpy());
        $payload = $this->validPayload();
        $payload['Conceptos'][0]['Complemento'][0]['version'] = '2.0';

        $this->expectException(ApiException::class);
        $service->create(CfdiData::fromArray($payload));
    }

    public function test_create_rechaza_nivel_educativo_invalido(): void
    {
        $service = new CfdiService(new CfdiEducationalTransportSpy());
        $payload = $this->validPayload();
        $payload['Conceptos'][0]['Complemento'][0]['nivelEducativo'] = 'Universidad';

        $this->expectException(ApiException::class);
        $service->create(CfdiData::fromArray($payload));
    }

    private function validPayload(): array
    {
        return [
            'TipoDocumento' => 'factura',
            'Conceptos' => [
                [
                    'Descripcion' => 'Pago colegiatura Julio - Agosto 2023',
                    'ClaveProdServ' => '86121501',
                    'ClaveUnidad' => 'E48',
                    'ObjetoImp' => '02',
                    'ValorUnitario' => '14200.000000',
                    'Cantidad' => '1',
                    'Importe' => '14200.000000',
                    'Impuestos' => [
                        'Traslados' => [
                            [
                                'Base' => '14200.000000',
                                'Importe' => '2272.000000',
                                'Impuesto' => '002',
                                'TasaOCuota' => '0.16',
                                'TipoFactor' => 'Tasa',
                            ],
                        ],
                    ],
                    'Descuento' => 0,
                    'tipoDesc' => 'porcentaje',
                    'Complemento' => [
                        [
                            'typeComplement' => 'iedu',
                            'version' => '1.0',
                            'nombreAlumno' => 'XAIME WEIR ROJO',
                            'CURP' => 'XEXX010101HNEXXXA4',
                            'nivelEducativo' => 'Preescolar',
                            'autRVOE' => '3231515138',
                            'rfcPago' => 'CACX7605101P8',
                        ],
                    ],
                    'Unidad' => 'Unidad de servicio',
                    'NoIdentificacion' => 'SCHOOL01',
                ],
            ],
            'Receptor' => [
                'UID' => '62b1dcf75a60f',
            ],
            'UsoCFDI' => 'G03',
            'Serie' => 13910,
            'FormaPago' => '03',
            'MetodoPago' => 'PUE',
            'Moneda' => 'MXN',
            'EnviarCorreo' => false,
        ];
    }
}

final class CfdiEducationalTransportSpy implements TransportInterface
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
