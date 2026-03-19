<?php

declare(strict_types=1);

namespace FacturaOnline\Tests\Unit;

use FacturaOnline\Contracts\TransportInterface;
use FacturaOnline\DTO\CfdiData;
use FacturaOnline\Exceptions\ApiException;
use FacturaOnline\Services\Payment\PaymentService;
use PHPUnit\Framework\TestCase;

final class PaymentServiceTest extends TestCase
{
    public function test_create_acepta_complemento_de_pago_valido(): void
    {
        $transport = new PaymentTransportSpy();
        $service = new PaymentService($transport);

        $service->create(CfdiData::fromArray($this->validPayload()));

        self::assertSame('POST', $transport->lastMethod);
        self::assertSame('v4/cfdi40/create', $transport->lastEndpoint);
        self::assertSame('pago', $transport->lastPayload['TipoDocumento']);
    }

    public function test_create_rechaza_tipo_documento_invalido(): void
    {
        $service = new PaymentService(new PaymentTransportSpy());
        $payload = $this->validPayload();
        $payload['TipoDocumento'] = 'factura';

        $this->expectException(ApiException::class);
        $service->create($payload);
    }

    private function validPayload(): array
    {
        return [
            'TipoDocumento' => 'pago',
            'RegimenFiscal' => '601',
            'Serie' => '1247',
            'FechaFromAPI' => (new \DateTimeImmutable('now'))
                ->modify('-2 hours')
                ->format('Y-m-d\\TH:i:s'),
            'LugarExpedicion' => '44555',
            'UsoCFDI' => 'CP01',
            'Moneda' => 'XXX',
            'Receptor' => [
                'UID' => '55c0fdc67593d',
            ],
            'Conceptos' => [
                [
                    'ClaveProdServ' => '84111506',
                    'Cantidad' => '1',
                    'ClaveUnidad' => 'ACT',
                    'Descripcion' => 'Pago',
                    'ValorUnitario' => '0',
                    'Importe' => '0',
                ],
            ],
            'Pagos' => [
                [
                    'FechaPago' => '2026-03-18T12:00:00',
                    'FormaDePagoP' => '01',
                    'MonedaP' => 'MXN',
                    'TipoCambioP' => '1',
                    'Monto' => '2500',
                    'DoctoRelacionado' => [
                        [
                            'IdDocumento' => '3e27ae21-b190-4c4c-a8d1-d3b6ab993122',
                            'MonedaDR' => 'MXN',
                            'EquivalenciaDR' => '1',
                            'NumParcialidad' => '1',
                            'ImpSaldoAnt' => '1200',
                            'ImpPagado' => '1200',
                            'ImpSaldoInsoluto' => '0',
                            'Impuestos' => [
                                'Traslados' => [
                                    [
                                        'Base' => '1300.000000',
                                        'Impuesto' => '002',
                                        'TipoFactor' => 'Tasa',
                                        'TasaOCuota' => '0.16',
                                        'Importe' => '208.000000',
                                    ],
                                ],
                                'Retenidos' => [
                                    [
                                        'Base' => '1300.000000',
                                        'Impuesto' => '001',
                                        'TipoFactor' => 'Tasa',
                                        'TasaOCuota' => '0.10',
                                        'Importe' => '130.000000',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}

final class PaymentTransportSpy implements TransportInterface
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
