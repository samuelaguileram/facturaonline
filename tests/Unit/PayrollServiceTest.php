<?php

declare(strict_types=1);

namespace FacturaOnline\Tests\Unit;

use FacturaOnline\Contracts\TransportInterface;
use FacturaOnline\Exceptions\ApiException;
use FacturaOnline\Services\Payroll\PayrollService;
use PHPUnit\Framework\TestCase;

final class PayrollServiceTest extends TestCase
{
    public function test_crear_envia_endpoint_esperado(): void
    {
        $transport = new PayrollTransportSpy();
        $service = new PayrollService($transport);
        $payload = $this->validPayload();

        $service->crear($payload);

        self::assertSame('POST', $transport->lastMethod);
        self::assertSame('payroll/create', $transport->lastEndpoint);
        self::assertSame($payload, $transport->lastPayload);
    }

    public function test_crear_acepta_alias_inicial_y_lo_normaliza_a_incial(): void
    {
        $transport = new PayrollTransportSpy();
        $service = new PayrollService($transport);
        $payload = $this->validPayload();
        unset($payload['incial']);
        $payload['inicial'] = '2024-01-01';

        $service->crear($payload);

        self::assertSame('2024-01-01', $transport->lastPayload['incial']);
        self::assertArrayNotHasKey('inicial', $transport->lastPayload);
    }

    public function test_crear_requiere_registros(): void
    {
        $service = new PayrollService(new PayrollTransportSpy());
        $payload = $this->validPayload();
        $payload['registros'] = [];

        $this->expectException(ApiException::class);
        $service->crear($payload);
    }

    public function test_crear_requiere_percepciones_en_cada_registro(): void
    {
        $service = new PayrollService(new PayrollTransportSpy());
        $payload = $this->validPayload();
        unset($payload['registros'][0]['percepciones']);

        $this->expectException(ApiException::class);
        $service->crear($payload);
    }

    public function test_crear_valida_separacion_indemnizacion(): void
    {
        $service = new PayrollService(new PayrollTransportSpy());
        $payload = $this->validPayload();
        $payload['registros'][0]['SeparacionIndemnizacion'] = [
            'TotalPagado' => 4000,
        ];

        $this->expectException(ApiException::class);
        $service->crear($payload);
    }

    public function test_crear_acepta_nomina_relacionada_y_otros_pagos(): void
    {
        $transport = new PayrollTransportSpy();
        $service = new PayrollService($transport);
        $payload = $this->validPayloadWithRelatedPayroll();

        $service->crear($payload);

        self::assertSame('POST', $transport->lastMethod);
        self::assertSame('payroll/create', $transport->lastEndpoint);
        self::assertSame(
            'fdf980db-f6fc-44ff-abdd-9596e02f0ffb',
            $transport->lastPayload['registros'][0]['relacionados'][0]['nom_reg_uuid']
        );
    }

    public function test_crear_acepta_fecha_from_api_dentro_de_72_horas(): void
    {
        $transport = new PayrollTransportSpy();
        $service = new PayrollService($transport);
        $payload = $this->validPayload();
        $payload['FechaFromAPI'] = (new \DateTimeImmutable('now'))
            ->modify('-24 hours')
            ->format('Y-m-d\\TH:i:s');

        $service->crear($payload);

        self::assertSame($payload['FechaFromAPI'], $transport->lastPayload['FechaFromAPI']);
    }

    public function test_crear_rechaza_fecha_from_api_futura(): void
    {
        $service = new PayrollService(new PayrollTransportSpy());
        $payload = $this->validPayload();
        $payload['FechaFromAPI'] = (new \DateTimeImmutable('now'))
            ->modify('+1 hour')
            ->format('Y-m-d\\TH:i:s');

        $this->expectException(ApiException::class);
        $service->crear($payload);
    }

    public function test_crear_rechaza_fecha_from_api_fuera_de_72_horas(): void
    {
        $service = new PayrollService(new PayrollTransportSpy());
        $payload = $this->validPayload();
        $payload['FechaFromAPI'] = (new \DateTimeImmutable('now'))
            ->modify('-73 hours')
            ->format('Y-m-d\\TH:i:s');

        $this->expectException(ApiException::class);
        $service->crear($payload);
    }

    public function test_crear_valida_uuid_de_nomina_relacionada(): void
    {
        $service = new PayrollService(new PayrollTransportSpy());
        $payload = $this->validPayloadWithRelatedPayroll();
        $payload['registros'][0]['relacionados'][0]['nom_reg_uuid'] = 'no-es-uuid';

        $this->expectException(ApiException::class);
        $service->crear($payload);
    }

    public function test_crear_valida_subsidio_al_empleo(): void
    {
        $service = new PayrollService(new PayrollTransportSpy());
        $payload = $this->validPayloadWithRelatedPayroll();
        unset($payload['registros'][0]['otrospagos'][0]['SubsidioAlEmpleo']['SubsidioCausado']);

        $this->expectException(ApiException::class);
        $service->crear($payload);
    }

    private function validPayload(): array
    {
        return [
            'grupo' => '619ead8d6427a',
            'fecha_pago' => '2024-01-15',
            'num_dias' => '15',
            'incial' => '2024-01-01',
            'final' => '2024-01-15',
            'tipo_nomina' => 'O',
            'descripcion' => 'Nomina primera quincena enero',
            'serie' => '1247',
            'concepto' => 'Pago de nómina',
            'identificador' => 'NOM-2024-01-Q1',
            'version_cfdi' => '4.0',
            'registros' => [
                [
                    'data' => [
                        'id' => '60df729dbf583',
                        'dias' => '10',
                    ],
                    'percepciones' => [
                        [
                            'tipo' => '001',
                            'clave' => 'P-001',
                            'descripcion' => 'Sueldos, Salarios',
                            'exento' => '0.00',
                            'gravado' => '1000.00',
                        ],
                    ],
                    'deducciones' => [
                        [
                            'tipo' => '001',
                            'clave' => 'D-001',
                            'descripcion' => 'ISR',
                            'exento' => '0.00',
                            'gravado' => '150.00',
                        ],
                    ],
                ],
            ],
        ];
    }

    private function validPayloadWithRelatedPayroll(): array
    {
        return [
            'grupo' => 'xxxxxxxxxx',
            'fecha_pago' => '2026-02-20',
            'num_dias' => 10,
            'FechaFromAPI' => (new \DateTimeImmutable('now'))
                ->modify('-2 hours')
                ->format('Y-m-d\\TH:i:s'),
            'inicial' => '2026-02-10',
            'final' => '2026-02-20',
            'tipo_nomina' => 'O',
            'descripcion' => 'Nomina Ejemplo',
            'serie' => '12345',
            'concepto' => 'Salarios',
            'identificador' => 'Nomina primera semana de abril',
            'version_cfdi' => '4.0',
            'registros' => [
                [
                    'data' => [
                        'dias' => 10,
                        'id' => 'xxxxxxxxxxx',
                    ],
                    'percepciones' => [
                        [
                            'tipo' => '001',
                            'clave' => '124124',
                            'descripcion' => 'Sueldo del empleado',
                            'exento' => '0.00',
                            'gravado' => '1000.00',
                        ],
                    ],
                    'deducciones' => [
                        [
                            'tipo' => '006',
                            'clave' => '4253624',
                            'importe' => 2400,
                            'descripcion' => 'Incapacidad por accidente en el area de trabajo',
                            'Incapacidad' => [
                                'DiasIncapacidad' => '01',
                                'TipoIncapacidad' => '01',
                                'ImporteMonetario' => 2400,
                            ],
                        ],
                    ],
                    'otrospagos' => [
                        [
                            'tipo' => '002',
                            'clave' => 'MWC02',
                            'descripcion' => 'Subsidio para el empleo',
                            'importe' => '0.00',
                            'SubsidioAlEmpleo' => [
                                'SubsidioCausado' => '0.00',
                            ],
                        ],
                    ],
                    'relacionados' => [
                        [
                            'nom_reg_uuid' => 'fdf980db-f6fc-44ff-abdd-9596e02f0ffb',
                        ],
                    ],
                ],
            ],
        ];
    }
}

final class PayrollTransportSpy implements TransportInterface
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
