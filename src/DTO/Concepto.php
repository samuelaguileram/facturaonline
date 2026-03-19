<?php

declare(strict_types=1);

namespace FacturaOnline\DTO;

final class Concepto
{
    private array $data = [];

    public static function make(): self
    {
        return new self();
    }

    public function claveProdServ(string $value): self
    {
        $this->data['ClaveProdServ'] = $value;

        return $this;
    }

    public function cantidad(int|float|string $value): self
    {
        $this->data['Cantidad'] = $value;

        return $this;
    }

    public function claveUnidad(string $value): self
    {
        $this->data['ClaveUnidad'] = $value;

        return $this;
    }

    public function descripcion(string $value): self
    {
        $this->data['Descripcion'] = $value;

        return $this;
    }

    public function valorUnitario(int|float|string $value): self
    {
        $this->data['ValorUnitario'] = $value;

        return $this;
    }

    public function impuestos(array $impuestos): self
    {
        $this->data['Impuestos'] = $impuestos;

        return $this;
    }

    public function with(string $key, mixed $value): self
    {
        $this->data[$key] = $value;

        return $this;
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
