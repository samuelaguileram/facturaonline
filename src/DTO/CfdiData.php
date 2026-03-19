<?php

declare(strict_types=1);

namespace FacturaOnline\DTO;

final class CfdiData
{
    private array $data = [];

    public static function make(): self
    {
        return new self();
    }

    public static function fromArray(array $data): self
    {
        $instance = new self();
        $instance->data = $data;

        return $instance;
    }

    public function receptorUid(string $uid): self
    {
        $this->data['Receptor'] = ['UID' => $uid];

        return $this;
    }

    public function tipoDocumento(string $tipo): self
    {
        $this->data['TipoDocumento'] = $tipo;

        return $this;
    }

    public function regimenFiscal(int $regimen): self
    {
        $this->data['RegimenFiscal'] = $regimen;

        return $this;
    }

    public function usoCfdi(string $uso): self
    {
        $this->data['UsoCFDI'] = $uso;

        return $this;
    }

    public function serie(string $serie): self
    {
        $this->data['Serie'] = $serie;

        return $this;
    }

    public function formaPago(string $forma): self
    {
        $this->data['FormaPago'] = $forma;

        return $this;
    }

    public function metodoPago(string $metodo): self
    {
        $this->data['MetodoPago'] = $metodo;

        return $this;
    }

    public function moneda(string $moneda): self
    {
        $this->data['Moneda'] = $moneda;

        return $this;
    }

    public function addConcepto(array|Concepto $concepto): self
    {
        $this->data['Conceptos'][] = $concepto instanceof Concepto ? $concepto->toArray() : $concepto;

        return $this;
    }

    public function informacionGlobal(string $periodicidad, string $meses, string $anio): self
    {
        $this->data['InformacionGlobal'] = [
            'Periodicidad' => $periodicidad,
            'Meses' => $meses,
            'Año' => $anio,
        ];

        return $this;
    }

    public function borradorSiFalla(bool $value = true): self
    {
        $this->data['BorradorSiFalla'] = $value ? '1' : '0';

        return $this;
    }

    public function enviarCorreo(bool $value): self
    {
        $this->data['EnviarCorreo'] = $value ? '1' : '0';

        return $this;
    }

    public function comentarios(string $comentario): self
    {
        $this->data['Comentarios'] = $comentario;

        return $this;
    }

    public function with(string $key, mixed $value): self
    {
        $this->data[$key] = $value;

        return $this;
    }

    public function merge(array $attributes): self
    {
        $this->data = array_replace_recursive($this->data, $attributes);

        return $this;
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
