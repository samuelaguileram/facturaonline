<?php

declare(strict_types=1);

namespace FacturaOnline\Tests\Unit;

use FacturaOnline\Contracts\TransportInterface;
use FacturaOnline\Exceptions\ApiException;
use FacturaOnline\Services\CFDI\CfdiService;
use PHPUnit\Framework\TestCase;

final class CfdiRetentionCancelTest extends TestCase
{
    public function test_cancel_retention_envia_endpoint_y_payload_esperado(): void
    {
        $transport = new CfdiRetentionCancelTransportSpy();
        $service = new CfdiService($transport);

        $service->cancelRetention('ret_uid', '02');

        self::assertSame('POST', $transport->lastMethod);
        self::assertSame('v4/cfdi40/retenciones/ret_uid/cancel', $transport->lastEndpoint);
        self::assertSame(['motivo' => '02'], $transport->lastPayload);
    }

    public function test_recancel_retention_envia_endpoint_y_payload_esperado(): void
    {
        $transport = new CfdiRetentionCancelTransportSpy();
        $service = new CfdiService($transport);

        $service->recancelRetention('ret_uid', '01', '6206eec5d7cd4');

        self::assertSame('POST', $transport->lastMethod);
        self::assertSame('v4/cfdi40/retenciones/ret_uid/re-cancel', $transport->lastEndpoint);
        self::assertSame(
            ['motivo' => '01', 'folioSustituto' => '6206eec5d7cd4'],
            $transport->lastPayload
        );
    }

    public function test_cancelar_retencion_es_alias_de_cancel_retention(): void
    {
        $transport = new CfdiRetentionCancelTransportSpy();
        $service = new CfdiService($transport);

        $service->cancelarRetencion('ret_uid', '02');

        self::assertSame('v4/cfdi40/retenciones/ret_uid/cancel', $transport->lastEndpoint);
    }

    public function test_recancelar_retencion_es_alias_de_recancel_retention(): void
    {
        $transport = new CfdiRetentionCancelTransportSpy();
        $service = new CfdiService($transport);

        $service->recancelarRetencion('ret_uid', '02');

        self::assertSame('v4/cfdi40/retenciones/ret_uid/re-cancel', $transport->lastEndpoint);
    }

    public function test_cancel_retention_requiere_folio_sustituto_para_motivo_01(): void
    {
        $service = new CfdiService(new CfdiRetentionCancelTransportSpy());

        $this->expectException(ApiException::class);
        $service->cancelRetention('ret_uid', '01');
    }

    public function test_cancel_retention_rechaza_folio_sustituto_para_motivo_distinto_de_01(): void
    {
        $service = new CfdiService(new CfdiRetentionCancelTransportSpy());

        $this->expectException(ApiException::class);
        $service->cancelRetention('ret_uid', '02', '6206eec5d7cd4');
    }
}

final class CfdiRetentionCancelTransportSpy implements TransportInterface
{
    public ?string $lastMethod = null;
    public ?string $lastEndpoint = null;
    public array $lastPayload = [];

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
