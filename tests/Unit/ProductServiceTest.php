<?php

declare(strict_types=1);

namespace FacturaOnline\Tests\Unit;

use FacturaOnline\Contracts\TransportInterface;
use FacturaOnline\Exceptions\ApiException;
use FacturaOnline\Services\Product\ProductService;
use PHPUnit\Framework\TestCase;

final class ProductServiceTest extends TestCase
{
    public function test_listar_consulta_endpoint_y_query_esperados(): void
    {
        $transport = new ProductTransportSpy();
        $service = new ProductService($transport);

        $service->listar(['page' => 2, 'per_page' => 50]);

        self::assertSame('GET', $transport->lastMethod);
        self::assertSame('v3/products/list', $transport->lastEndpoint);
        self::assertSame(['page' => 2, 'per_page' => 50], $transport->lastQuery);
    }

    public function test_obtener_consulta_endpoint_esperado(): void
    {
        $transport = new ProductTransportSpy();
        $service = new ProductService($transport);

        $service->obtener('product_uid');

        self::assertSame('v3/products/show/product_uid', $transport->lastEndpoint);
    }

    public function test_crear_envia_endpoint_y_payload_esperado(): void
    {
        $transport = new ProductTransportSpy();
        $service = new ProductService($transport);

        $service->crear($this->validPayload());

        self::assertSame('POST', $transport->lastMethod);
        self::assertSame('v3/products/create', $transport->lastEndpoint);
        self::assertSame('SERVICIO DE PRUEBA', $transport->lastPayload['name']);
    }

    public function test_actualizar_envia_endpoint_esperado(): void
    {
        $transport = new ProductTransportSpy();
        $service = new ProductService($transport);

        $service->actualizar('product_uid', $this->validPayload());

        self::assertSame('POST', $transport->lastMethod);
        self::assertSame('v3/products/update/product_uid', $transport->lastEndpoint);
    }

    public function test_eliminar_consulta_endpoint_esperado(): void
    {
        $transport = new ProductTransportSpy();
        $service = new ProductService($transport);

        $service->eliminar('product_uid');

        self::assertSame('GET', $transport->lastMethod);
        self::assertSame('v3/products/delete/product_uid', $transport->lastEndpoint);
    }

    public function test_crear_rechaza_price_invalido(): void
    {
        $service = new ProductService(new ProductTransportSpy());
        $payload = $this->validPayload();
        $payload['price'] = 'abc';

        $this->expectException(ApiException::class);
        $service->crear($payload);
    }

    public function test_listar_rechaza_page_invalida(): void
    {
        $service = new ProductService(new ProductTransportSpy());

        $this->expectException(ApiException::class);
        $service->listar(['page' => 0]);
    }

    private function validPayload(): array
    {
        return [
            'code' => 'SKU-001',
            'name' => 'SERVICIO DE PRUEBA',
            'price' => '100.50',
            'clavePS' => '78101800',
            'unity' => 'Unidad de servicio',
            'claveUnity' => 'E48',
        ];
    }
}

final class ProductTransportSpy implements TransportInterface
{
    public ?string $lastMethod = null;
    public ?string $lastEndpoint = null;
    public array $lastPayload = [];
    public array $lastQuery = [];

    public function get(string $endpoint, array $query = []): array
    {
        $this->lastMethod = 'GET';
        $this->lastEndpoint = $endpoint;
        $this->lastQuery = $query;

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
