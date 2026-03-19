<?php

declare(strict_types=1);

namespace FacturaOnline\Tests\Unit;

use FacturaOnline\Contracts\TransportInterface;
use FacturaOnline\Exceptions\ApiException;
use FacturaOnline\Services\Client\ClientService;
use PHPUnit\Framework\TestCase;

final class ClientServiceTest extends TestCase
{
    public function test_listar_consulta_endpoint_esperado(): void
    {
        $transport = new ClientTransportSpy();
        $service = new ClientService($transport);

        $service->listar();

        self::assertSame('GET', $transport->lastMethod);
        self::assertSame('v1/clients', $transport->lastEndpoint);
    }

    public function test_buscar_por_uid_consulta_endpoint_esperado(): void
    {
        $transport = new ClientTransportSpy();
        $service = new ClientService($transport);

        $service->buscarPorUid('63ebd090d6015');

        self::assertSame('v1/clients/63ebd090d6015', $transport->lastEndpoint);
    }

    public function test_rfc_repetido_consulta_endpoint_esperado(): void
    {
        $transport = new ClientTransportSpy();
        $service = new ClientService($transport);

        $service->rfcRepetido('WERX631016S30');

        self::assertSame('v1/clients/rfc/WERX631016S30', $transport->lastEndpoint);
    }

    public function test_crear_envia_endpoint_y_payload_esperado(): void
    {
        $transport = new ClientTransportSpy();
        $service = new ClientService($transport);

        $service->crear($this->validPayload());

        self::assertSame('POST', $transport->lastMethod);
        self::assertSame('v1/clients/create', $transport->lastEndpoint);
        self::assertSame('WERX631016S30', $transport->lastPayload['rfc']);
    }

    public function test_actualizar_requiere_uid(): void
    {
        $service = new ClientService(new ClientTransportSpy());

        $this->expectException(ApiException::class);
        $service->actualizar('', $this->validPayload());
    }

    public function test_crear_rechaza_email_invalido(): void
    {
        $service = new ClientService(new ClientTransportSpy());
        $payload = $this->validPayload();
        $payload['email'] = 'correo-invalido';

        $this->expectException(ApiException::class);
        $service->crear($payload);
    }

    public function test_eliminar_consulta_endpoint_esperado(): void
    {
        $transport = new ClientTransportSpy();
        $service = new ClientService($transport);

        $service->eliminar('62b1dcf75a60f');

        self::assertSame('POST', $transport->lastMethod);
        self::assertSame('v1/clients/destroy/62b1dcf75a60f', $transport->lastEndpoint);
    }

    private function validPayload(): array
    {
        return [
            'rfc' => 'WERX631016S30',
            'razons' => 'RAZON SOCIAL DE PRUEBA',
            'codpos' => '44680',
            'email' => 'cliente@example.com',
            'regimen' => '601',
            'pais' => 'MEX',
            'usocfdi' => 'G03',
        ];
    }
}

final class ClientTransportSpy implements TransportInterface
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
