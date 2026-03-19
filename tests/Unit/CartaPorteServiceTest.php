<?php

declare(strict_types=1);

namespace FacturaOnline\Tests\Unit;

use FacturaOnline\Contracts\TransportInterface;
use FacturaOnline\Exceptions\ApiException;
use FacturaOnline\Services\CartaPorte\CartaPorteService;
use PHPUnit\Framework\TestCase;

final class CartaPorteServiceTest extends TestCase
{
    public function test_create_ingreso_acepta_payload_valido(): void
    {
        $transport = new CartaPorteTransportSpy();
        $service = new CartaPorteService($transport);

        $service->createIngreso($this->validIngresoPayload());

        self::assertSame('POST', $transport->lastMethod);
        self::assertSame('v4/cfdi40/create', $transport->lastEndpoint);
        self::assertSame('carta_porte_ingreso', $transport->lastPayload['TipoDocumento']);
    }

    public function test_create_traslado_acepta_payload_valido(): void
    {
        $transport = new CartaPorteTransportSpy();
        $service = new CartaPorteService($transport);

        $service->createTraslado($this->validTrasladoPayload());

        self::assertSame('carta_porte', $transport->lastPayload['TipoDocumento']);
        self::assertSame(
            '99AX7H',
            $transport->lastPayload['CartaPorte']['Mercancias']['Autotransporte']['IdentificacionVehicular']['PlacaVM']
        );
    }

    public function test_create_acepta_payload_internacional_con_regimen_aduanero(): void
    {
        $transport = new CartaPorteTransportSpy();
        $service = new CartaPorteService($transport);

        $service->create($this->validInternationalPayload());

        self::assertSame(
            'EXD',
            $transport->lastPayload['CartaPorte']['RegimenesAduaneros']['RegimenAduaneroCCP'][0]['RegimenAduanero']
        );
    }

    public function test_create_rechaza_idccp_invalido(): void
    {
        $service = new CartaPorteService(new CartaPorteTransportSpy());
        $payload = $this->validIngresoPayload();
        $payload['CartaPorte']['IdCCP'] = 'INVALIDO';

        $this->expectException(ApiException::class);
        $service->create($payload);
    }

    public function test_create_rechaza_transporte_internacional_sin_entrada_salida(): void
    {
        $service = new CartaPorteService(new CartaPorteTransportSpy());
        $payload = $this->validInternationalPayload();
        unset($payload['CartaPorte']['EntradaSalidaMerc']);

        $this->expectException(ApiException::class);
        $service->create($payload);
    }

    public function test_create_ingreso_rechaza_tipo_documento_incompatible(): void
    {
        $service = new CartaPorteService(new CartaPorteTransportSpy());
        $payload = $this->validTrasladoPayload();

        $this->expectException(ApiException::class);
        $service->createIngreso($payload);
    }

    private function validIngresoPayload(): array
    {
        return [
            'Receptor' => [
                'UID' => '63ebd090d6015',
            ],
            'TipoDocumento' => 'carta_porte_ingreso',
            'RegimenFiscal' => '606',
            'UsoCFDI' => 'S01',
            'Redondeo' => '2',
            'Conceptos' => [
                [
                    'ClaveProdServ' => '78101800',
                    'Cantidad' => '1.00',
                    'ClaveUnidad' => 'E48',
                    'Unidad' => 'Unidad de servicio',
                    'Descripcion' => 'SERVICIO DE FLETE',
                    'ValorUnitario' => '2200.00',
                    'Importe' => '2200',
                ],
            ],
            'FormaPago' => '99',
            'MetodoPago' => 'PPD',
            'Moneda' => 'MXN',
            'Serie' => '50450',
            'CartaPorte' => [
                'Version' => '3.1',
                'IdCCP' => 'CCCDA373-FB1A-44AE-83A2-448B0865BDA3',
                'TranspInternac' => 'No',
                'TotalDistRec' => '300',
                'Ubicaciones' => [
                    'Ubicacion' => [
                        [
                            'IDUbicacion' => 'OR123456',
                            'FechaHoraSalidaLlegada' => '2023-10-05T07:00:00',
                            'RFCRemitenteDestinatario' => 'TPM9809038X0',
                            'NombreRemitenteDestinatario' => 'THYSSENKRUPP PRESTA DE MEXICO, S.A. DE C.V. PM1',
                            'TipoUbicacion' => 'Origen',
                            'Domicilio' => [
                                'Calle' => 'Calle Circuito Esteban de Antunano',
                                'Estado' => 'PUE',
                                'Pais' => 'MEX',
                                'CodigoPostal' => '74160',
                                'Colonia' => '1500',
                                'Municipio' => '074',
                            ],
                        ],
                        [
                            'IDUbicacion' => 'DE654321',
                            'DistanciaRecorrida' => '300',
                            'FechaHoraSalidaLlegada' => '2023-10-05T13:50:00',
                            'RFCRemitenteDestinatario' => 'STE071214BE7',
                            'NombreRemitenteDestinatario' => 'Nexteer PLANTA 69',
                            'TipoUbicacion' => 'Destino',
                            'Domicilio' => [
                                'Estado' => 'QUE',
                                'Pais' => 'MEX',
                                'CodigoPostal' => '76246',
                                'Colonia' => '0001',
                                'Municipio' => '011',
                            ],
                        ],
                    ],
                ],
                'Mercancias' => [
                    'Mercancia' => [
                        [
                            'PesoEnKg' => '723',
                            'Cantidad' => '4224',
                            'BienesTransp' => '25174200',
                            'ClaveUnidad' => 'H87',
                            'Descripcion' => 'Sistema de direccion',
                            'CantidadTransporta' => [
                                [
                                    'Cantidad' => '4224',
                                    'IDOrigen' => 'OR123456',
                                    'IDDestino' => 'DE654321',
                                ],
                            ],
                        ],
                    ],
                    'Autotransporte' => [
                        'NumPermisoSCT' => '9639934',
                        'PermSCT' => 'TPAF01',
                        'IdentificacionVehicular' => [
                            'AnioModeloVM' => '2023',
                            'PesoBrutoVehicular' => '200',
                            'ConfigVehicular' => 'VL',
                            'PlacaVM' => '99AX7H',
                        ],
                        'Seguros' => [
                            'AseguraRespCivil' => 'BANORTE',
                            'PolizaRespCivil' => '4111867',
                        ],
                    ],
                    'NumTotalMercancias' => '1',
                    'PesoBrutoTotal' => '723',
                    'UnidadPeso' => 'KGM',
                ],
                'FiguraTransporte' => [
                    'TiposFigura' => [
                        [
                            'Domicilio' => [
                                'Localidad' => '03',
                                'Municipio' => '009',
                                'Estado' => 'COL',
                                'Pais' => 'MEX',
                                'CodigoPostal' => '28934',
                            ],
                            'TipoFigura' => '01',
                            'RFCFigura' => 'CALJ741208LN5',
                            'NumLicencia' => 'PUE0011259',
                            'NombreFigura' => 'JUAN RENE CARRASCO LIZANA',
                        ],
                    ],
                ],
            ],
        ];
    }

    private function validTrasladoPayload(): array
    {
        $payload = $this->validIngresoPayload();
        $payload['TipoDocumento'] = 'carta_porte';

        return $payload;
    }

    private function validInternationalPayload(): array
    {
        $payload = $this->validIngresoPayload();
        $payload['CartaPorte']['TranspInternac'] = 'Sí';
        $payload['CartaPorte']['EntradaSalidaMerc'] = 'Salida';
        $payload['CartaPorte']['RegimenesAduaneros'] = [
            'RegimenAduaneroCCP' => [
                [
                    'RegimenAduanero' => 'EXD',
                ],
            ],
        ];

        return $payload;
    }
}

final class CartaPorteTransportSpy implements TransportInterface
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
