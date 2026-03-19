<?php

declare(strict_types=1);

namespace FacturaOnline\Tests\Unit;

use FacturaOnline\Contracts\TransportInterface;
use FacturaOnline\DTO\CfdiData;
use FacturaOnline\Exceptions\ApiException;
use FacturaOnline\Services\CFDI\CfdiService;
use PHPUnit\Framework\TestCase;

final class CfdiACuentaTercerosTest extends TestCase
{
    public function test_create_acepta_concepto_a_cuenta_terceros_valido(): void
    {
        $transport = new CfdiACuentaTercerosTransportSpy();
        $service = new CfdiService($transport);

        $service->create(CfdiData::fromArray($this->validPayload()));

        self::assertSame('POST', $transport->lastMethod);
        self::assertSame('v4/cfdi40/create', $transport->lastEndpoint);
        self::assertSame(
            'JUFA7608212V6',
            $transport->lastPayload['Conceptos'][0]['RfcACuentaTerceros']
        );
    }

    public function test_create_requiere_bloque_completo_a_cuenta_terceros(): void
    {
        $service = new CfdiService(new CfdiACuentaTercerosTransportSpy());
        $payload = $this->validPayload();
        unset($payload['Conceptos'][0]['RegimenFiscalACuentaTerceros']);

        $this->expectException(ApiException::class);
        $service->create(CfdiData::fromArray($payload));
    }

    private function validPayload(): array
    {
        return [
            'Receptor' => [
                'UID' => '62d8891fd3734',
            ],
            'TipoDocumento' => 'factura',
            'Conceptos' => [
                [
                    'ClaveProdServ' => '81112101',
                    'Cantidad' => 1,
                    'ClaveUnidad' => 'E48',
                    'Unidad' => 'Unidad de servicio',
                    'ValorUnitario' => 229.90,
                    'Descripcion' => 'Desarrollo a la medida',
                    'Impuestos' => [
                        'Traslados' => [
                            [
                                'Base' => 229.90,
                                'Impuesto' => '002',
                                'TipoFactor' => 'Tasa',
                                'TasaOCuota' => '0.16',
                                'Importe' => 36.784,
                            ],
                        ],
                        'Locales' => [
                            [
                                'Base' => 229.90,
                                'Impuesto' => 'ISH',
                                'TipoFactor' => 'Tasa',
                                'TasaOCuota' => '0.03',
                                'Importe' => 6.897,
                            ],
                        ],
                    ],
                    'RfcACuentaTerceros' => 'JUFA7608212V6',
                    'NombreACuentaTerceros' => 'ADRIANA JUAREZ FERNANDEZ',
                    'RegimenFiscalACuentaTerceros' => '601',
                    'DomicilioFiscalACuentaTerceros' => '29133',
                ],
            ],
            'UsoCFDI' => 'G01',
            'Serie' => 13910,
            'FormaPago' => '03',
            'MetodoPago' => 'PUE',
            'Moneda' => 'MXN',
            'EnviarCorreo' => false,
        ];
    }
}

final class CfdiACuentaTercerosTransportSpy implements TransportInterface
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
