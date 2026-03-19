<?php

declare(strict_types=1);

namespace FacturaOnline\Tests\Unit;

use FacturaOnline\Contracts\TransportInterface;
use FacturaOnline\DTO\CfdiData;
use FacturaOnline\Exceptions\ApiException;
use FacturaOnline\Services\CFDI\CfdiService;
use PHPUnit\Framework\TestCase;

final class CfdiVehicleComplementTest extends TestCase
{
    public function test_create_acepta_complemento_venta_vehiculos_valido(): void
    {
        $transport = new CfdiVehicleTransportSpy();
        $service = new CfdiService($transport);

        $service->create(CfdiData::fromArray($this->validPayload()));

        self::assertSame('POST', $transport->lastMethod);
        self::assertSame('v4/cfdi40/create', $transport->lastEndpoint);
        self::assertSame(
            'VentaVehiculos',
            $transport->lastPayload['Conceptos'][0]['Complemento'][0]['typeComplement']
        );
    }

    public function test_create_rechaza_clave_vehicular_invalida(): void
    {
        $service = new CfdiService(new CfdiVehicleTransportSpy());
        $payload = $this->validPayload();
        $payload['Conceptos'][0]['Complemento'][0]['ClaveVehicular'] = '60110';

        $this->expectException(ApiException::class);
        $service->create(CfdiData::fromArray($payload));
    }

    public function test_create_rechaza_niv_invalido(): void
    {
        $service = new CfdiService(new CfdiVehicleTransportSpy());
        $payload = $this->validPayload();
        $payload['Conceptos'][0]['Complemento'][0]['Niv'] = '123';

        $this->expectException(ApiException::class);
        $service->create(CfdiData::fromArray($payload));
    }

    public function test_create_valida_fecha_de_informacion_aduanera_en_parte(): void
    {
        $service = new CfdiService(new CfdiVehicleTransportSpy());
        $payload = $this->validPayload();
        $payload['Conceptos'][0]['Complemento'][0]['Parte'][0]['InformacionAduanera']['fecha'] = '15/02/2025';

        $this->expectException(ApiException::class);
        $service->create(CfdiData::fromArray($payload));
    }

    private function validPayload(): array
    {
        return [
            'Receptor' => [
                'UID' => '66db4e54b4f35',
            ],
            'TipoDocumento' => 'factura',
            'UsoCFDI' => 'G01',
            'Redondeo' => '2',
            'Conceptos' => [
                [
                    'ClaveProdServ' => '83101800',
                    'NoIdentificacion' => '20190417C009001P00',
                    'Cantidad' => '100',
                    'ClaveUnidad' => 'XVN',
                    'Unidad' => 'Vehículo',
                    'ValorUnitario' => '33000.00',
                    'Descripcion' => 'Venta de vehículo',
                    'Descuento' => '0',
                    'Impuestos' => [
                        'Traslados' => [
                            [
                                'Base' => '33000.00',
                                'Impuesto' => '002',
                                'TipoFactor' => 'Tasa',
                                'TasaOCuota' => '0.160000',
                                'Importe' => '5280',
                            ],
                        ],
                    ],
                    'Complemento' => [
                        [
                            'typeComplement' => 'VentaVehiculos',
                            'ClaveVehicular' => '6011011',
                            'Niv' => '3VWDX7AJ5BM012345',
                            'InformacionAduanera' => [
                                'numero' => '',
                                'fecha' => '',
                                'aduana' => '',
                            ],
                            'Parte' => [
                                [
                                    'cantidad' => '1',
                                    'unidad' => 'Pieza',
                                    'noIdentificacion' => '3948KW8475L1',
                                    'descripcion' => 'Sistema de sonido premium',
                                    'valorUnitario' => '12000',
                                    'importe' => '12000',
                                    'InformacionAduanera' => [
                                        'numero' => '16',
                                        'fecha' => '2025-02-15',
                                        'aduana' => 'Aduana de Manzanillo, Colima',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'FormaPago' => '99',
            'NumOrder' => '',
            'MetodoPago' => 'PPD',
            'Moneda' => 'MXN',
            'CondicionesDePago' => 'Pago en una sola exhibición',
            'Serie' => '55764',
            'EnviarCorreo' => '',
        ];
    }
}

final class CfdiVehicleTransportSpy implements TransportInterface
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
