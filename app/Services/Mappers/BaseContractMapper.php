<?php

namespace App\Services\Mappers;

abstract class BaseContractMapper
{
    abstract public function map(string $text): array;

    protected function extractNumero(string $text): ?string
    {
        return null;
    }

    protected function extractProveedor(string $text): ?string
    {
        return null;
    }

    protected function extractRFC(string $text): ?string
    {
        return null;
    }

    protected function extractDependencia(string $text): ?string
    {
        return null;
    }

    protected function extractMonto(string $text): ?float
    {
        return null;
    }

    protected function extractFechaFirma(string $text): ?string
    {
        return null;
    }

    protected function extractFechaInicio(string $text): ?string
    {
        return null;
    }

    protected function extractFechaFin(string $text): ?string
    {
        return null;
    }
}