<?php

declare(strict_types=1);

namespace FacturaOnline\Tests\Unit;

use FacturaOnline\Contracts\TransportInterface;
use FacturaOnline\DTO\CfdiData;
use FacturaOnline\Exceptions\ApiException;
use FacturaOnline\Services\CFDI\CfdiService;
use PHPUnit\Framework\TestCase;

final class CfdiGeneralRestrictionTest extends TestCase
{
    public function test_create_rechaza_carta_porte_en_cfdi_service(): void
    {
        $service = new CfdiService(new CfdiRestrictionTransportSpy());

        $this->expectException(ApiException::class);
        $service->create(CfdiData::fromArray([
            'TipoDocumento' => 'carta_porte',
            'Receptor' => ['UID' => 'demo'],
            'Conceptos' => [
                [
                    'ClaveProdServ' => '78101800',
                    'Cantidad' => '1',
                    'ClaveUnidad' => 'E48',
                    'Descripcion' => 'Traslado',
                ],
            ],
        ]));
    }
}

final class CfdiRestrictionTransportSpy implements TransportInterface
{
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
