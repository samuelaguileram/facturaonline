<?php

declare(strict_types=1);

namespace FacturaOnline\Tests\Unit;

use FacturaOnline\Contracts\TransportInterface;
use FacturaOnline\Exceptions\ApiException;
use FacturaOnline\Services\Payroll\EmployeeService;
use PHPUnit\Framework\TestCase;

final class EmployeeServiceTest extends TestCase
{
    public function test_obtener_usa_endpoint_esperado(): void
    {
        $transport = new EmployeeTransportSpy();
        $service = new EmployeeService($transport);

        $service->obtener('employee_uid');

        self::assertSame('GET', $transport->lastMethod);
        self::assertSame('payroll/employee/employee_uid/view', $transport->lastEndpoint);
    }

    public function test_crear_requiere_campos_obligatorios_documentados(): void
    {
        $service = new EmployeeService(new EmployeeTransportSpy());

        $this->expectException(ApiException::class);
        $service->crear(['grupo' => '619ead8d6427a']);
    }

    public function test_crear_permite_omitir_apellido_materno(): void
    {
        $transport = new EmployeeTransportSpy();
        $service = new EmployeeService($transport);
        $payload = $this->validPayload();
        unset($payload['materno']);

        $service->crear($payload);

        self::assertSame('POST', $transport->lastMethod);
        self::assertSame('payroll/employee/create', $transport->lastEndpoint);
        self::assertArrayNotHasKey('materno', $transport->lastPayload);
    }

    public function test_crear_requiere_campos_condicionales_cuando_no_es_asimilado(): void
    {
        $service = new EmployeeService(new EmployeeTransportSpy());
        $payload = $this->validPayload();
        unset($payload['tipo_jornada']);

        $this->expectException(ApiException::class);
        $service->crear($payload);
    }

    public function test_crear_requiere_clabe_cuando_hay_banco(): void
    {
        $service = new EmployeeService(new EmployeeTransportSpy());
        $payload = $this->validPayload();
        unset($payload['clabe']);

        $this->expectException(ApiException::class);
        $service->crear($payload);
    }

    public function test_crear_normaliza_banderas_binarias(): void
    {
        $transport = new EmployeeTransportSpy();
        $service = new EmployeeService($transport);
        $payload = $this->validPayload();
        $payload['sindicalizado'] = 'No';
        $payload['asimilados'] = false;

        $service->crear($payload);

        self::assertSame('0', $transport->lastPayload['sindicalizado']);
        self::assertSame('0', $transport->lastPayload['asimilados']);
    }

    public function test_crear_envia_payload_esperado(): void
    {
        $transport = new EmployeeTransportSpy();
        $service = new EmployeeService($transport);
        $payload = $this->validPayload();

        $service->crear($payload);

        self::assertSame('POST', $transport->lastMethod);
        self::assertSame('payroll/employee/create', $transport->lastEndpoint);
        self::assertSame($payload, $transport->lastPayload);
    }

    public function test_editar_requiere_uid(): void
    {
        $service = new EmployeeService(new EmployeeTransportSpy());

        $this->expectException(ApiException::class);
        $service->editar('', $this->validPayload());
    }

    public function test_editar_permite_payload_parcial(): void
    {
        $transport = new EmployeeTransportSpy();
        $service = new EmployeeService($transport);

        $service->editar('employee_uid', ['nombre' => 'FERNANDO']);

        self::assertSame('POST', $transport->lastMethod);
        self::assertSame('payroll/employee/employee_uid/update', $transport->lastEndpoint);
        self::assertSame(['nombre' => 'FERNANDO'], $transport->lastPayload);
    }

    public function test_editar_requiere_payload_no_vacio(): void
    {
        $service = new EmployeeService(new EmployeeTransportSpy());

        $this->expectException(ApiException::class);
        $service->editar('employee_uid', []);
    }

    public function test_editar_si_envia_asimilados_cero_requiere_campos_dependientes(): void
    {
        $service = new EmployeeService(new EmployeeTransportSpy());

        $this->expectException(ApiException::class);
        $service->editar('employee_uid', [
            'asimilados' => '0',
            'nombre' => 'FERNANDO',
        ]);
    }

    private function validPayload(): array
    {
        return [
            'grupo' => '619ead8d6427a',
            'no_empleado' => '100',
            'nombre' => 'FERNANDO',
            'paterno' => 'SANCHEZ',
            'materno' => 'SANTIAGO',
            'metodo_pago' => '03',
            'periodo' => '04',
            'regimen' => '02',
            'puesto' => 'PROGRAMADOR',
            'departamento' => 'Desarrollo',
            'curp' => 'XEXX010101HNEXXXA4',
            'imss' => '1234567890',
            'rfc' => 'XAXX010101000',
            'calle' => 'GUADALUPE CALLEJON JAZMIN',
            'colonia' => '7a Seccion',
            'no_ext' => '0',
            'cp' => '70020',
            'municipio' => 'Juchitlan de Zaragoza',
            'estado' => 'Oaxaca - OAX',
            'tipo_contrato' => '01',
            'asimilados' => '0',
            'sindicalizado' => '0',
            'entidad_emite' => 'OAX',
            'banco' => '012',
            'clabe' => '1236547890',
            'no_int' => '0',
            'localidad' => 'Juchitlan (Juchitlan de Zaragoza)',
            'email' => 'fernando@traveli.mx',
            'tipo_jornada' => '01',
            'patronal' => 'Y6468923104',
            'cuota_diaria' => '80',
            'salario' => '250',
            'riesgo' => '99',
            'inicio' => '2023-08-03',
        ];
    }
}

final class EmployeeTransportSpy implements TransportInterface
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
