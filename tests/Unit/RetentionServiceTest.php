<?php

declare(strict_types=1);

namespace FacturaOnline\Tests\Unit;

use FacturaOnline\Contracts\TransportInterface;
use FacturaOnline\Services\Retention\RetentionService;
use PHPUnit\Framework\TestCase;

final class RetentionServiceTest extends TestCase
{
    public function test_create_envia_endpoint_esperado(): void
    {
        $transport = new RetentionTransportSpy();
        $service = new RetentionService($transport);

        $service->create($this->validPayload());

        self::assertSame('POST', $transport->lastMethod);
        self::assertSame('v4/cfdi40/retenciones', $transport->lastEndpoint);
    }

    public function test_cancel_envia_endpoint_esperado(): void
    {
        $transport = new RetentionTransportSpy();
        $service = new RetentionService($transport);

        $service->cancel('ret_uid', '02');

        self::assertSame('POST', $transport->lastMethod);
        self::assertSame('v4/cfdi40/retenciones/ret_uid/cancel', $transport->lastEndpoint);
    }

    private function validPayload(): array
    {
        return [
            'LugarExpedicion' => '26015',
            'CveRetenc' => '03',
            'Receptor' => [
                'UID' => '61af9fdc030e2',
            ],
            'Periodo' => [
                'MesIni' => '01',
                'MesFin' => '03',
                'Ejercicio' => '2022',
            ],
            'Totales' => [
                'MontoTotOperacion' => 0,
                'MontoTotGrav' => 0,
                'MontoTotExent' => 0,
                'MontoTotRet' => 123,
                'ImpRetenidos' => [
                    [
                        'BaseRet' => 12,
                        'ImpuestoRet' => '001',
                        'MontoRet' => 123,
                        'TipoPagoRet' => '03',
                    ],
                ],
            ],
        ];
    }
}

final class RetentionTransportSpy implements TransportInterface
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
