<?php

declare(strict_types=1);

namespace FacturaOnline\Tests\Unit;

use FacturaOnline\Contracts\TransportInterface;
use FacturaOnline\DTO\CfdiData;
use FacturaOnline\Exceptions\ApiException;
use FacturaOnline\Services\CFDI\CfdiService;
use PHPUnit\Framework\TestCase;

final class CfdiServiceTest extends TestCase
{
    public function test_cancelar_requiere_folio_sustituto_para_motivo_01(): void
    {
        $service = new CfdiService($this->transportStub());

        $this->expectException(ApiException::class);
        $service->cancelar('uid_123', '01');
    }

    public function test_create_draft_does_not_mutate_original_cfdi(): void
    {
        $service = new CfdiService($this->transportStub(['ok' => true]));
        $cfdi = CfdiData::make()
            ->receptorUid('receiver_uid')
            ->tipoDocumento('factura')
            ->addConcepto(['ClaveProdServ' => '01010101']);

        $service->createDraft($cfdi);

        self::assertArrayNotHasKey('BorradorSiFalla', $cfdi->toArray());
    }

    private function transportStub(array $postResponse = []): TransportInterface
    {
        return new class ($postResponse) implements TransportInterface {
            public function __construct(private readonly array $postResponse)
            {
            }

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
                return $this->postResponse;
            }

            public function postMultipart(string $endpoint, array $data = [], array $files = []): array
            {
                return $this->postResponse;
            }

            public function postAuto(string $endpoint, array $data = [], array $files = []): array
            {
                return $this->postResponse;
            }
        };
    }
}
