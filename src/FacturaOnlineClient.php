<?php

declare(strict_types=1);

namespace FacturaOnline;

use FacturaOnline\Contracts\TransportInterface;
use FacturaOnline\Http\HttpClient;
use FacturaOnline\Services\CartaPorte\CartaPorteService;
use FacturaOnline\Services\Catalog\CatalogService;
use FacturaOnline\Services\CFDI\CfdiService;
use FacturaOnline\Services\Client\ClientService;
use FacturaOnline\Services\Empresa\EmpresaService;
use FacturaOnline\Services\Migration\MigrationService;
use FacturaOnline\Services\Payment\PaymentService;
use FacturaOnline\Services\Payroll\EmployeeGroupService;
use FacturaOnline\Services\Payroll\PayrollService;
use FacturaOnline\Services\Product\ProductService;
use FacturaOnline\Services\Retention\RetentionService;
use FacturaOnline\Services\Series\SeriesService;

final class FacturaOnlineClient
{
    private TransportInterface $http;
    private ?CartaPorteService $cartaPorteService = null;
    private ?CatalogService $catalogService = null;
    private ?ClientService $clientService = null;
    private ?CfdiService $cfdiService = null;
    private ?EmpresaService $empresaService = null;
    private ?MigrationService $migrationService = null;
    private ?PaymentService $paymentService = null;
    private ?PayrollService $payrollService = null;
    private ?ProductService $productService = null;
    private ?RetentionService $retentionService = null;
    private ?SeriesService $seriesService = null;
    private ?EmployeeGroupService $employeeGroupService = null;

    public function __construct(
        string $apiKey,
        string $secretKey,
        string $baseUrl,
        array $httpConfig = [],
        ?TransportInterface $httpClient = null
    ) {
        $this->http = $httpClient ?? new HttpClient($apiKey, $secretKey, $baseUrl, $httpConfig);
    }

    public function http(): TransportInterface
    {
        return $this->http;
    }

    public function cfdi(): CfdiService
    {
        return $this->cfdiService ??= new CfdiService($this->http);
    }

    public function cartaPorte(): CartaPorteService
    {
        return $this->cartaPorteService ??= new CartaPorteService($this->http);
    }

    public function catalogs(): CatalogService
    {
        return $this->catalogService ??= new CatalogService($this->http);
    }

    public function catalogos(): CatalogService
    {
        return $this->catalogs();
    }

    public function clients(): ClientService
    {
        return $this->clientService ??= new ClientService($this->http);
    }

    public function clientes(): ClientService
    {
        return $this->clients();
    }

    public function migrations(): MigrationService
    {
        return $this->migrationService ??= new MigrationService($this->http);
    }

    public function migraciones(): MigrationService
    {
        return $this->migrations();
    }

    public function series(): SeriesService
    {
        return $this->seriesService ??= new SeriesService($this->http);
    }

    public function products(): ProductService
    {
        return $this->productService ??= new ProductService($this->http);
    }

    public function productos(): ProductService
    {
        return $this->products();
    }

    public function payment(): PaymentService
    {
        return $this->paymentService ??= new PaymentService($this->http);
    }

    public function retention(): RetentionService
    {
        return $this->retentionService ??= new RetentionService($this->http);
    }

    public function empresa(): EmpresaService
    {
        return $this->empresaService ??= new EmpresaService($this->http);
    }

    public function payroll(): PayrollService
    {
        return $this->payrollService ??= new PayrollService($this->http);
    }

    public function employeeGroups(): EmployeeGroupService
    {
        return $this->employeeGroupService ??= new EmployeeGroupService($this->http);
    }
}
