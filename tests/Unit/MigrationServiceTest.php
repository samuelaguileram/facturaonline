<?php

declare(strict_types=1);

namespace FacturaOnline\Tests\Unit;

use FacturaOnline\Contracts\TransportInterface;
use FacturaOnline\Exceptions\ApiException;
use FacturaOnline\Services\Migration\MigrationService;
use PHPUnit\Framework\TestCase;

final class MigrationServiceTest extends TestCase
{
    public function test_crear_envia_endpoint_y_payload_esperado(): void
    {
        $transport = new MigrationTransportSpy();
        $service = new MigrationService($transport);

        $service->crear($this->validPayload());

        self::assertSame('POST', $transport->lastMethod);
        self::assertSame('migrator/create', $transport->lastEndpoint);
        self::assertSame('2020-12-04T10:02:01', $transport->lastPayload['inicio']);
    }

    public function test_listar_consulta_endpoint_esperado(): void
    {
        $transport = new MigrationTransportSpy();
        $service = new MigrationService($transport);

        $service->listar();

        self::assertSame('GET', $transport->lastMethod);
        self::assertSame('migrator/list', $transport->lastEndpoint);
    }

    public function test_obtener_consulta_endpoint_esperado(): void
    {
        $transport = new MigrationTransportSpy();
        $service = new MigrationService($transport);

        $service->obtener('704e9db8-8fc3-4fa3-8ec0-424b47ac0072');

        self::assertSame('GET', $transport->lastMethod);
        self::assertSame('migrator/704e9db8-8fc3-4fa3-8ec0-424b47ac0072/view', $transport->lastEndpoint);
    }

    public function test_crear_rechaza_fecha_invalida(): void
    {
        $service = new MigrationService(new MigrationTransportSpy());
        $payload = $this->validPayload();
        $payload['inicio'] = '2020/12/04';

        $this->expectException(ApiException::class);
        $service->crear($payload);
    }

    public function test_obtener_rechaza_uid_vacio(): void
    {
        $service = new MigrationService(new MigrationTransportSpy());

        $this->expectException(ApiException::class);
        $service->obtener('');
    }

    private function validPayload(): array
    {
        return [
            'inicio' => '2020-12-04T10:02:01',
            'fin' => '2020-12-13T10:02:01',
        ];
    }
}

final class MigrationTransportSpy implements TransportInterface
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
