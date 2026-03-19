<?php

declare(strict_types=1);

namespace FacturaOnline\Tests\Unit;

use FacturaOnline\Contracts\TransportInterface;
use FacturaOnline\DTO\CfdiData;
use FacturaOnline\Exceptions\ApiException;
use FacturaOnline\Services\CFDI\CfdiService;
use PHPUnit\Framework\TestCase;

final class CfdiConstructionComplementTest extends TestCase
{
    public function test_create_acepta_parciales_construccion_valido(): void
    {
        $transport = new CfdiConstructionTransportSpy();
        $service = new CfdiService($transport);

        $service->create(CfdiData::fromArray($this->validPayload()));

        self::assertSame('POST', $transport->lastMethod);
        self::assertSame('v4/cfdi40/create', $transport->lastEndpoint);
        self::assertSame('123456', $transport->lastPayload['parcialesconstruccion']['NumPerLicoAut']);
    }

    public function test_create_rechaza_version_invalida_de_parciales_construccion(): void
    {
        $service = new CfdiService(new CfdiConstructionTransportSpy());
        $payload = $this->validPayload();
        $payload['parcialesconstruccion']['Version'] = '2.0';

        $this->expectException(ApiException::class);
        $service->create(CfdiData::fromArray($payload));
    }

    public function test_create_requiere_numero_de_permiso(): void
    {
        $service = new CfdiService(new CfdiConstructionTransportSpy());
        $payload = $this->validPayload();
        unset($payload['parcialesconstruccion']['NumPerLicoAut']);

        $this->expectException(ApiException::class);
        $service->create(CfdiData::fromArray($payload));
    }

    public function test_create_requiere_datos_minimos_de_inmueble_si_se_envia(): void
    {
        $service = new CfdiService(new CfdiConstructionTransportSpy());
        $payload = $this->validPayload();
        unset($payload['parcialesconstruccion']['Inmueble']['Municipio']);

        $this->expectException(ApiException::class);
        $service->create(CfdiData::fromArray($payload));
    }

    private function validPayload(): array
    {
        return [
            'TipoDocumento' => 'factura',
            'Serie' => '1247',
            'Receptor' => [
                'UID' => '55c0fdc67593d',
            ],
            'Conceptos' => [
                [
                    'ClaveProdServ' => '72101500',
                    'Cantidad' => '1',
                    'ClaveUnidad' => 'E48',
                    'Descripcion' => 'Servicio de construccion',
                    'ValorUnitario' => '1000.00',
                    'Importe' => '1000.00',
                ],
            ],
            'parcialesconstruccion' => [
                'Version' => '1.0',
                'NumPerLicoAut' => '123456',
                'Inmueble' => [
                    'Calle' => 'Insurgentes Sur',
                    'NoExterior' => '120',
                    'NoInterior' => '4',
                    'Colonia' => 'Centro',
                    'Localidad' => 'Ciudad de Mexico',
                    'Referencia' => 'Frente al parque',
                    'Municipio' => 'Benito Juarez',
                    'Estado' => '9',
                    'CodigoPostal' => '03100',
                ],
            ],
        ];
    }
}

final class CfdiConstructionTransportSpy implements TransportInterface
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
