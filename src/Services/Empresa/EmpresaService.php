<?php

declare(strict_types=1);

namespace FacturaOnline\Services\Empresa;

use FacturaOnline\Contracts\TransportInterface;
use FacturaOnline\Exceptions\ApiException;

final class EmpresaService
{
    public function __construct(private readonly TransportInterface $http)
    {
    }

    public function listarEmpresas(): array
    {
        return $this->http->get('v1/accounts');
    }

    public function empresaActual(): array
    {
        return $this->http->get('v1/current/account');
    }

    public function buscarEmpresa(string $uid): array
    {
        $this->assertIdentifier($uid, 'uid');

        return $this->http->get(sprintf('v1/account/%s', $uid));
    }

    public function crearEmpresa(array $data, array $files = []): array
    {
        return $this->http->postAuto('v4/account/create', $data, $files);
    }

    public function actualizarEmpresa(string $uid, array $data, array $files = []): array
    {
        $this->assertIdentifier($uid, 'uid');

        return $this->http->postAuto(sprintf('v4/account/%s/update', $uid), $data, $files);
    }

    public function eliminarEmpresa(string $uid): array
    {
        $this->assertIdentifier($uid, 'uid');

        return $this->http->post(sprintf('v4/account/%s/delete', $uid));
    }

    public function uploadCertificados(string $uid, array $data = [], array $files = []): array
    {
        $this->assertIdentifier($uid, 'uid');

        return $this->http->postMultipart(sprintf('v4/account/%s/update', $uid), $data, $files);
    }

    private function assertIdentifier(string $value, string $label): void
    {
        if (trim($value) === '') {
            throw new ApiException(sprintf('El %s es requerido.', $label));
        }
    }
}
