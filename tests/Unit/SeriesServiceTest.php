<?php

declare(strict_types=1);

namespace FacturaOnline\Tests\Unit;

use FacturaOnline\Contracts\TransportInterface;
use FacturaOnline\Exceptions\ApiException;
use FacturaOnline\Services\Series\SeriesService;
use PHPUnit\Framework\TestCase;

final class SeriesServiceTest extends TestCase
{
    public function test_listar_consulta_endpoint_esperado(): void
    {
        $transport = new SeriesTransportSpy();
        $service = new SeriesService($transport);

        $service->listar();

        self::assertSame('GET', $transport->lastMethod);
        self::assertSame('v4/series', $transport->lastEndpoint);
    }

    public function test_obtener_consulta_endpoint_esperado(): void
    {
        $transport = new SeriesTransportSpy();
        $service = new SeriesService($transport);

        $service->obtener('series_uid');

        self::assertSame('GET', $transport->lastMethod);
        self::assertSame('v4/series/series_uid', $transport->lastEndpoint);
    }

    public function test_crear_envia_endpoint_y_payload_esperado(): void
    {
        $transport = new SeriesTransportSpy();
        $service = new SeriesService($transport);

        $service->crear([
            'letra' => 'A',
            'tipoDocumento' => 'factura',
            'folio' => '10',
        ]);

        self::assertSame('POST', $transport->lastMethod);
        self::assertSame('v4/series/create', $transport->lastEndpoint);
        self::assertSame('factura', $transport->lastPayload['tipoDocumento']);
    }

    public function test_crear_rechaza_folio_invalido(): void
    {
        $service = new SeriesService(new SeriesTransportSpy());

        $this->expectException(ApiException::class);
        $service->crear([
            'letra' => 'A',
            'tipoDocumento' => 'factura',
            'folio' => 'abc',
        ]);
    }

    public function test_desactivar_consulta_endpoint_esperado(): void
    {
        $transport = new SeriesTransportSpy();
        $service = new SeriesService($transport);

        $service->desactivar('series_uid');

        self::assertSame('POST', $transport->lastMethod);
        self::assertSame('v4/series/series_uid/down', $transport->lastEndpoint);
    }

    public function test_activar_consulta_endpoint_esperado(): void
    {
        $transport = new SeriesTransportSpy();
        $service = new SeriesService($transport);

        $service->activar('series_uid');

        self::assertSame('POST', $transport->lastMethod);
        self::assertSame('v4/series/series_uid/up', $transport->lastEndpoint);
    }

    public function test_eliminar_consulta_endpoint_esperado(): void
    {
        $transport = new SeriesTransportSpy();
        $service = new SeriesService($transport);

        $service->eliminar('series_uid');

        self::assertSame('POST', $transport->lastMethod);
        self::assertSame('v4/series/series_uid/drop', $transport->lastEndpoint);
    }
}

final class SeriesTransportSpy implements TransportInterface
{
    public ?string $lastMethod = null;
    public ?string $lastEndpoint = null;
    public array $lastPayload = [];

    public function get(string $endpoint, array $query = []): array
    {
        $this->lastMethod = 'GET';
        $this->lastEndpoint = $endpoint;

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
