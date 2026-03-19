<?php

declare(strict_types=1);

namespace FacturaOnline\Services\Retention;

use FacturaOnline\Contracts\TransportInterface;
use FacturaOnline\Services\CFDI\CfdiService;

final class RetentionService
{
    private ?CfdiService $legacyCfdiService = null;

    public function __construct(private readonly TransportInterface $http)
    {
    }

    public function create(array $data): array
    {
        return $this->legacy()->createRetention($data);
    }

    public function crear(array $data): array
    {
        return $this->create($data);
    }

    public function createRetention(array $data): array
    {
        return $this->create($data);
    }

    public function crearRetencion(array $data): array
    {
        return $this->create($data);
    }

    public function cancel(string $uid, string $motivo, ?string $folioSustituto = null): array
    {
        return $this->legacy()->cancelRetention($uid, $motivo, $folioSustituto);
    }

    public function cancelar(string $uid, string $motivo, ?string $folioSustituto = null): array
    {
        return $this->cancel($uid, $motivo, $folioSustituto);
    }

    public function cancelRetention(string $uid, string $motivo, ?string $folioSustituto = null): array
    {
        return $this->cancel($uid, $motivo, $folioSustituto);
    }

    public function cancelarRetencion(string $uid, string $motivo, ?string $folioSustituto = null): array
    {
        return $this->cancel($uid, $motivo, $folioSustituto);
    }

    public function recancel(string $uid, string $motivo, ?string $folioSustituto = null): array
    {
        return $this->legacy()->recancelRetention($uid, $motivo, $folioSustituto);
    }

    public function recancelar(string $uid, string $motivo, ?string $folioSustituto = null): array
    {
        return $this->recancel($uid, $motivo, $folioSustituto);
    }

    public function recancelRetention(string $uid, string $motivo, ?string $folioSustituto = null): array
    {
        return $this->recancel($uid, $motivo, $folioSustituto);
    }

    public function recancelarRetencion(string $uid, string $motivo, ?string $folioSustituto = null): array
    {
        return $this->recancel($uid, $motivo, $folioSustituto);
    }

    public function downloadPdf(string $uid, ?string $path = null): string
    {
        return $this->legacy()->descargarRetentionPdf($uid, $path);
    }

    public function descargarPdf(string $uid, ?string $path = null): string
    {
        return $this->downloadPdf($uid, $path);
    }

    public function downloadXml(string $uid, ?string $path = null): string
    {
        return $this->legacy()->descargarRetentionXml($uid, $path);
    }

    public function descargarXml(string $uid, ?string $path = null): string
    {
        return $this->downloadXml($uid, $path);
    }

    public function sendEmail(string $uid): array
    {
        return $this->legacy()->enviarRetentionEmail($uid);
    }

    public function enviarEmail(string $uid): array
    {
        return $this->sendEmail($uid);
    }

    public function sendAcuse(string $uid): array
    {
        return $this->legacy()->enviarRetentionAcuse($uid);
    }

    public function enviarAcuse(string $uid): array
    {
        return $this->sendAcuse($uid);
    }

    public function downloadAcuse(string $uid, ?string $path = null): string
    {
        return $this->legacy()->descargarRetentionAcuse($uid, $path);
    }

    public function descargarAcuse(string $uid, ?string $path = null): string
    {
        return $this->downloadAcuse($uid, $path);
    }

    public function cancelStatus(string $uid): array
    {
        return $this->legacy()->retentionCancelStatus($uid);
    }

    public function estatusCancelacion(string $uid): array
    {
        return $this->cancelStatus($uid);
    }

    public function cancelDate(string $uid): array
    {
        return $this->legacy()->retentionCancelDate($uid);
    }

    public function fechaCancelacion(string $uid): array
    {
        return $this->cancelDate($uid);
    }

    public function relacionados(string $uid): array
    {
        return $this->legacy()->retentionRelacionados($uid);
    }

    public function documentosRelacionados(string $uid): array
    {
        return $this->relacionados($uid);
    }

    private function legacy(): CfdiService
    {
        return $this->legacyCfdiService ??= new CfdiService($this->http);
    }
}
