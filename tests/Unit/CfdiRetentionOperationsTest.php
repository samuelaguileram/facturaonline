<?php

declare(strict_types=1);

namespace FacturaOnline\Tests\Unit;

use FacturaOnline\Contracts\TransportInterface;
use FacturaOnline\Services\CFDI\CfdiService;
use PHPUnit\Framework\TestCase;

final class CfdiRetentionOperationsTest extends TestCase
{
    public function test_descargar_retencion_pdf_raw_usa_endpoint_esperado(): void
    {
        $transport = new CfdiRetentionOperationsTransportSpy();
        $service = new CfdiService($transport);

        $result = $service->descargarRetencionPdf('ret_uid');

        self::assertSame('raw-body', $result);
        self::assertSame('GET_RAW', $transport->lastMethod);
        self::assertSame('v4/cfdi40/retenciones/ret_uid/pdf', $transport->lastEndpoint);
    }

    public function test_descargar_retencion_xml_con_path_usa_download(): void
    {
        $transport = new CfdiRetentionOperationsTransportSpy();
        $service = new CfdiService($transport);

        $result = $service->descargarRetencionXml('ret_uid', 'C:\\tmp\\ret.xml');

        self::assertSame('C:\\tmp\\ret.xml', $result);
        self::assertSame('DOWNLOAD', $transport->lastMethod);
        self::assertSame('v4/cfdi40/retenciones/ret_uid/xml', $transport->lastEndpoint);
        self::assertSame('C:\\tmp\\ret.xml', $transport->lastDownloadPath);
    }

    public function test_enviar_retencion_email_usa_endpoint_esperado(): void
    {
        $transport = new CfdiRetentionOperationsTransportSpy();
        $service = new CfdiService($transport);

        $service->enviarRetencionEmail('ret_uid');

        self::assertSame('GET', $transport->lastMethod);
        self::assertSame('v4/cfdi40/retenciones/ret_uid/email', $transport->lastEndpoint);
    }

    public function test_enviar_acuse_retencion_usa_endpoint_esperado(): void
    {
        $transport = new CfdiRetentionOperationsTransportSpy();
        $service = new CfdiService($transport);

        $service->enviarAcuseRetencion('ret_uid');

        self::assertSame('GET', $transport->lastMethod);
        self::assertSame('v4/cfdi40/retenciones/ret_uid/sendAcuse', $transport->lastEndpoint);
    }

    public function test_estatus_y_fecha_cancelacion_y_relacionados_usan_endpoints_correctos(): void
    {
        $transport = new CfdiRetentionOperationsTransportSpy();
        $service = new CfdiService($transport);

        $service->estatusCancelacionRetencion('ret_uid');
        self::assertSame('v4/cfdi40/retenciones/ret_uid/cancel_status', $transport->lastEndpoint);

        $service->fechaCancelacionRetencion('ret_uid');
        self::assertSame('v4/cfdi40/retenciones/ret_uid/cancel_date', $transport->lastEndpoint);

        $service->relacionadosRetencion('ret_uid');
        self::assertSame('v4/cfdi40/retenciones/ret_uid/relacionado', $transport->lastEndpoint);
    }
}

final class CfdiRetentionOperationsTransportSpy implements TransportInterface
{
    public ?string $lastMethod = null;
    public ?string $lastEndpoint = null;
    public ?string $lastDownloadPath = null;

    public function get(string $endpoint, array $query = []): array
    {
        $this->lastMethod = 'GET';
        $this->lastEndpoint = $endpoint;

        return [];
    }

    public function getRaw(string $endpoint): string
    {
        $this->lastMethod = 'GET_RAW';
        $this->lastEndpoint = $endpoint;

        return 'raw-body';
    }

    public function download(string $endpoint, string $savePath): string
    {
        $this->lastMethod = 'DOWNLOAD';
        $this->lastEndpoint = $endpoint;
        $this->lastDownloadPath = $savePath;

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
