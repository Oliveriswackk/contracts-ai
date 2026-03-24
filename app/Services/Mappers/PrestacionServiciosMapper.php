<?php

namespace App\Services\Mappers;

use App\Services\Mappers\BaseContractMapper;

class PrestacionServiciosMapper extends BaseContractMapper implements MapperInterface
{
    public function map(string $text): array
    {
        $mapper = new ContractMapper();

        $data = $mapper->map($text);

        $data['tipo'] = 'PRESTACION_SERVICIOS';

        return $data;
    }
}