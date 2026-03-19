<?php

declare(strict_types=1);

namespace FacturaOnline\Services\Catalog;

use FacturaOnline\Contracts\TransportInterface;
use FacturaOnline\Exceptions\ApiException;

final class CatalogService
{
    private const SAT_CATALOGS = [
        'ClaveProductServ' => 'v3/catalogo/ClaveProductServ',
        'Aduana' => 'v3/catalogo/Aduana',
        'ClaveUnidad' => 'v3/catalogo/ClaveUnidad',
        'FormaPago' => 'v3/catalogo/FormaPago',
        'Impuesto' => 'v3/catalogo/Impuesto',
        'MetodoPago' => 'v3/catalogo/MetodoPago',
        'Moneda' => 'v3/catalogo/Moneda',
        'Pais' => 'v3/catalogo/Pais',
        'RegimenFiscal' => 'v3/catalogo/RegimenFiscal',
        'Relacion' => 'v3/catalogo/Relacion',
        'UsoCfdi' => 'v4/catalogo/UsoCfdi',
        'claveRetencion' => 'v4/catalogos/retenciones/claveRetencion',
    ];
    private const PAYROLL_CATALOGS = [
        'bancos' => 'payroll/catalogos/bancos',
        'estados' => 'payroll/catalogos/estados',
        'origenrecurso' => 'payroll/catalogos/origenrecurso',
        'periodicidad' => 'payroll/catalogos/periodicidad',
        'riesgo' => 'payroll/catalogos/riesgo',
        'tipocontrato' => 'payroll/catalogos/tipocontrato',
        'tipodeduccion' => 'payroll/catalogos/tipodeduccion',
        'tipohoras' => 'payroll/catalogos/tipohoras',
        'tipoincapacidad' => 'payroll/catalogos/tipoincapacidad',
        'tipojornada' => 'payroll/catalogos/tipojornada',
        'tipopago' => 'payroll/catalogos/tipopago',
        'tipopercepcion' => 'payroll/catalogos/tipopercepcion',
        'tiporegimen' => 'payroll/catalogos/tiporegimen',
        'metodopago' => 'payroll/catalogos/metodopago',
    ];

    public function __construct(private readonly TransportInterface $http)
    {
    }

    public function sat(string $catalog): array
    {
        return $this->getSatCatalog($catalog);
    }

    public function getSatCatalog(string $catalog): array
    {
        $endpoint = self::SAT_CATALOGS[$catalog] ?? null;

        if ($endpoint === null) {
            throw new ApiException(sprintf('El catálogo SAT "%s" no está soportado.', $catalog));
        }

        return $this->http->get($endpoint);
    }

    public function payroll(string $catalog): array
    {
        return $this->getPayrollCatalog($catalog);
    }

    public function getPayrollCatalog(string $catalog): array
    {
        $endpoint = self::PAYROLL_CATALOGS[$catalog] ?? null;

        if ($endpoint === null) {
            throw new ApiException(sprintf('El catálogo de nómina "%s" no está soportado.', $catalog));
        }

        return $this->http->get($endpoint);
    }

    public function claveProdServ(): array
    {
        return $this->getSatCatalog('ClaveProductServ');
    }

    public function aduana(): array
    {
        return $this->getSatCatalog('Aduana');
    }

    public function claveUnidad(): array
    {
        return $this->getSatCatalog('ClaveUnidad');
    }

    public function formaPago(): array
    {
        return $this->getSatCatalog('FormaPago');
    }

    public function impuesto(): array
    {
        return $this->getSatCatalog('Impuesto');
    }

    public function metodoPago(): array
    {
        return $this->getSatCatalog('MetodoPago');
    }

    public function moneda(): array
    {
        return $this->getSatCatalog('Moneda');
    }

    public function pais(): array
    {
        return $this->getSatCatalog('Pais');
    }

    public function regimenFiscal(): array
    {
        return $this->getSatCatalog('RegimenFiscal');
    }

    public function relacion(): array
    {
        return $this->getSatCatalog('Relacion');
    }

    public function usoCfdi(): array
    {
        return $this->getSatCatalog('UsoCfdi');
    }

    public function claveRetencion(): array
    {
        return $this->getSatCatalog('claveRetencion');
    }

    public function bancos(): array
    {
        return $this->getPayrollCatalog('bancos');
    }

    public function estados(): array
    {
        return $this->getPayrollCatalog('estados');
    }

    public function origenRecurso(): array
    {
        return $this->getPayrollCatalog('origenrecurso');
    }

    public function periodicidad(): array
    {
        return $this->getPayrollCatalog('periodicidad');
    }

    public function riesgo(): array
    {
        return $this->getPayrollCatalog('riesgo');
    }

    public function tipoContrato(): array
    {
        return $this->getPayrollCatalog('tipocontrato');
    }

    public function tipoDeduccion(): array
    {
        return $this->getPayrollCatalog('tipodeduccion');
    }

    public function tipoHoras(): array
    {
        return $this->getPayrollCatalog('tipohoras');
    }

    public function tipoIncapacidad(): array
    {
        return $this->getPayrollCatalog('tipoincapacidad');
    }

    public function tipoJornada(): array
    {
        return $this->getPayrollCatalog('tipojornada');
    }

    public function tipoPago(): array
    {
        return $this->getPayrollCatalog('tipopago');
    }

    public function tipoPercepcion(): array
    {
        return $this->getPayrollCatalog('tipopercepcion');
    }

    public function tipoRegimen(): array
    {
        return $this->getPayrollCatalog('tiporegimen');
    }

    public function metodoPagoNomina(): array
    {
        return $this->getPayrollCatalog('metodopago');
    }
}
