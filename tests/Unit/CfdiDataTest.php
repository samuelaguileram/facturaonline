<?php

declare(strict_types=1);

namespace FacturaOnline\Tests\Unit;

use FacturaOnline\DTO\CfdiData;
use FacturaOnline\DTO\Concepto;
use PHPUnit\Framework\TestCase;

final class CfdiDataTest extends TestCase
{
    public function test_it_normalizes_conceptos_from_dto_instances(): void
    {
        $cfdi = CfdiData::make()
            ->receptorUid('receiver_uid')
            ->tipoDocumento('factura')
            ->addConcepto(
                Concepto::make()
                    ->claveProdServ('01010101')
                    ->cantidad('1')
                    ->claveUnidad('H87')
                    ->descripcion('Servicio')
                    ->valorUnitario('100.00')
            );

        $data = $cfdi->toArray();

        self::assertSame('receiver_uid', $data['Receptor']['UID']);
        self::assertSame('factura', $data['TipoDocumento']);
        self::assertSame('01010101', $data['Conceptos'][0]['ClaveProdServ']);
    }
}
