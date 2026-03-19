<?php

declare(strict_types=1);

namespace FacturaOnline\Tests\Unit;

use FacturaOnline\Contracts\TransportInterface;
use FacturaOnline\Exceptions\ApiException;
use FacturaOnline\Services\Payroll\EmployeeGroupService;
use PHPUnit\Framework\TestCase;

final class EmployeeGroupServiceTest extends TestCase
{
    public function test_obtener_usa_endpoint_esperado(): void
    {
        $transport = new EmployeeGroupTransportSpy();
        $service = new EmployeeGroupService($transport);

        $service->obtener('group_uid');

        self::assertSame('GET', $transport->lastMethod);
        self::assertSame('payroll/employee/group/group_uid/view', $transport->lastEndpoint);
    }

    public function test_crear_requiere_nombre_de_grupo(): void
    {
        $service = new EmployeeGroupService(new EmployeeGroupTransportSpy());

        $this->expectException(ApiException::class);
        $service->crear([]);
    }

    public function test_editar_usa_endpoint_esperado(): void
    {
        $transport = new EmployeeGroupTransportSpy();
        $service = new EmployeeGroupService($transport);

        $service->editar('group_uid', ['grupo' => 'Administracion']);

        self::assertSame('POST', $transport->lastMethod);
        self::assertSame('payroll/employee/group/group_uid/update', $transport->lastEndpoint);
        self::assertSame(['grupo' => 'Administracion'], $transport->lastPayload);
    }

    public function test_activar_requiere_uid(): void
    {
        $service = new EmployeeGroupService(new EmployeeGroupTransportSpy());

        $this->expectException(ApiException::class);
        $service->activar('   ');
    }
}

final class EmployeeGroupTransportSpy implements TransportInterface
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
