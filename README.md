# FacturaOnline PHP SDK

PHP SDK for the FacturaOnline / factura.com API. It covers CFDI 4.0, Carta Porte, payments, retentions, payroll, SAT catalogs, clients, products, series and migrations.

## Requirements

- PHP 8.1 or higher
- `ext-json`

## Installation

```bash
composer require samuelaguilera/facturaonline
```

## Create the client

```php
<?php

use FacturaOnline\FacturaOnlineClient;

$client = new FacturaOnlineClient(
    apiKey: $_ENV['FACTURAONLINE_API_KEY'],
    secretKey: $_ENV['FACTURAONLINE_SECRET_KEY'],
    baseUrl: $_ENV['FACTURAONLINE_BASE_URL'] ?? 'https://facturaonline.com.mx/api/',
    httpConfig: [
        'timeout' => 30,
        'connect_timeout' => 10,
    ],
);
```

## Available services

- `cfdi()`
- `cartaPorte()`
- `payment()`
- `retention()`
- `empresa()`
- `catalogs()` / `catalogos()`
- `clients()` / `clientes()`
- `migrations()` / `migraciones()`
- `series()`
- `products()` / `productos()`
- `payroll()`
- `employeeGroups()`

## Examples

### General CFDI

```php
<?php

use FacturaOnline\DTO\CfdiData;
use FacturaOnline\DTO\Concepto;

$cfdi = CfdiData::make()
    ->receptorUid('receptor_uid')
    ->tipoDocumento('factura')
    ->usoCfdi('G03')
    ->moneda('MXN')
    ->addConcepto(
        Concepto::make()
            ->claveProdServ('01010101')
            ->cantidad('1')
            ->claveUnidad('H87')
            ->descripcion('Servicio profesional')
            ->valorUnitario('1000.00')
    );

$response = $client->cfdi()->create($cfdi);
```

### Carta Porte

```php
<?php

$response = $client->cartaPorte()->createIngreso([
    'Receptor' => ['UID' => '63ebd090d6015'],
    'Conceptos' => [[
        'ClaveProdServ' => '78101800',
        'Cantidad' => '1',
        'ClaveUnidad' => 'E48',
        'Descripcion' => 'SERVICIO DE FLETE',
        'ValorUnitario' => '2200.00',
        'Importe' => '2200.00',
    ]],
    'CartaPorte' => [
        'Version' => '3.1',
        'TranspInternac' => 'No',
        'Ubicaciones' => [
            'Ubicacion' => [[
                'TipoUbicacion' => 'Origen',
                'RFCRemitenteDestinatario' => 'XAXX010101000',
                'FechaHoraSalidaLlegada' => '2026-03-19T12:00:00',
            ]],
        ],
        'Mercancias' => [
            'PesoBrutoTotal' => '100',
            'UnidadPeso' => 'KGM',
            'NumTotalMercancias' => '1',
            'Mercancia' => [[
                'BienesTransp' => '25174200',
                'Descripcion' => 'Refacciones',
                'Cantidad' => '1',
                'ClaveUnidad' => 'H87',
                'PesoEnKg' => '100',
            ]],
        ],
    ],
]);
```

### Payment complement

```php
<?php

$response = $client->payment()->create([
    'TipoDocumento' => 'pago',
    'Serie' => '1247',
    'UsoCFDI' => 'CP01',
    'Moneda' => 'XXX',
    'Receptor' => ['UID' => '55c0fdc67593d'],
    'Conceptos' => [[
        'ClaveProdServ' => '84111506',
        'Cantidad' => '1',
        'ClaveUnidad' => 'ACT',
        'Descripcion' => 'Pago',
        'ValorUnitario' => '0',
        'Importe' => '0',
    ]],
    'Pagos' => [[
        'FechaPago' => '2026-03-18T12:00:00',
        'FormaDePagoP' => '01',
        'MonedaP' => 'MXN',
        'Monto' => '2500',
        'DoctoRelacionado' => [[
            'IdDocumento' => '3e27ae21-b190-4c4c-a8d1-d3b6ab993122',
            'MonedaDR' => 'MXN',
            'EquivalenciaDR' => '1',
            'NumParcialidad' => '1',
            'ImpSaldoAnt' => '1200',
            'ImpPagado' => '1200',
            'ImpSaldoInsoluto' => '0',
            'Impuestos' => [
                'Traslados' => [],
                'Retenidos' => [],
            ],
        ]],
    ]],
]);
```

### Retentions

```php
<?php

$response = $client->retention()->create([
    'LugarExpedicion' => '26015',
    'CveRetenc' => '03',
    'Receptor' => ['UID' => '61af9fdc030e2'],
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
        'ImpRetenidos' => [[
            'BaseRet' => 12,
            'ImpuestoRet' => '001',
            'MontoRet' => 123,
            'TipoPagoRet' => '03',
        ]],
    ],
]);
```

### Catalogs

```php
<?php

$usoCfdi = $client->catalogs()->usoCfdi();
$bancos = $client->catalogs()->bancos();
```

### Clients

```php
<?php

$cliente = $client->clientes()->crear([
    'rfc' => 'WERX631016S30',
    'razons' => 'RAZON SOCIAL DE PRUEBA',
    'codpos' => '44680',
    'email' => 'cliente@example.com',
    'regimen' => '601',
    'pais' => 'MEX',
]);
```

## Laravel usage

This package is framework agnostic. In Laravel you can register it as a singleton in your own service provider:

```php
<?php

use FacturaOnline\FacturaOnlineClient;

$this->app->singleton(FacturaOnlineClient::class, function () {
    return new FacturaOnlineClient(
        apiKey: config('services.facturaonline.key'),
        secretKey: config('services.facturaonline.secret'),
        baseUrl: config('services.facturaonline.base_url', 'https://facturaonline.com.mx/api/'),
    );
});
```

## Quality checks

```bash
composer test
composer analyse
composer lint
composer check
```

## Security

- Do not commit `API_KEY` or `SECRET_KEY`.
- Use environment variables for credentials.
- Configure request timeouts before running the SDK in production.
- Log and monitor provider errors.

## Versioning

This package follows Semantic Versioning.

- Breaking changes: major
- Backward compatible features: minor
- Internal fixes: patch
