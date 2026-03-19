<?php

declare(strict_types=1);

namespace FacturaOnline\Tests\Unit;

use FacturaOnline\Contracts\TransportInterface;
use FacturaOnline\Exceptions\ApiException;
use FacturaOnline\Services\Catalog\CatalogService;
use PHPUnit\Framework\TestCase;

final class CatalogServiceTest extends TestCase
{
    public function test_clave_prod_serv_consulta_endpoint_sat_esperado(): void
    {
        $transport = new CatalogTransportSpy();
        $service = new CatalogService($transport);

        $service->claveProdServ();

        self::assertSame('GET', $transport->lastMethod);
        self::assertSame('v3/catalogo/ClaveProductServ', $transport->lastEndpoint);
    }

    public function test_uso_cfdi_consulta_endpoint_v4_esperado(): void
    {
        $transport = new CatalogTransportSpy();
        $service = new CatalogService($transport);

        $service->usoCfdi();

        self::assertSame('v4/catalogo/UsoCfdi', $transport->lastEndpoint);
    }

    public function test_metodo_pago_nomina_consulta_endpoint_payroll_esperado(): void
    {
        $transport = new CatalogTransportSpy();
        $service = new CatalogService($transport);

        $service->metodoPagoNomina();

        self::assertSame('payroll/catalogos/metodopago', $transport->lastEndpoint);
    }

    public function test_get_sat_catalog_rechaza_catalogo_no_soportado(): void
    {
        $service = new CatalogService(new CatalogTransportSpy());

        $this->expectException(ApiException::class);
        $service->getSatCatalog('NoExiste');
    }

    public function test_get_payroll_catalog_rechaza_catalogo_no_soportado(): void
    {
        $service = new CatalogService(new CatalogTransportSpy());

        $this->expectException(ApiException::class);
        $service->getPayrollCatalog('no_existe');
    }
}

final class CatalogTransportSpy implements TransportInterface
{
    public ?string $lastMethod = null;
    public ?string $lastEndpoint = null;

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
