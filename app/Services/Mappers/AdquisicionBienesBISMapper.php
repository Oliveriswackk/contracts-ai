<?php

namespace App\Services\Mappers;

use App\Services\Mappers\BaseContractMapper;

class AdquisicionBienesBISMapper extends BaseContractMapper implements MapperInterface
{
    public function map(string $text): array
    {
        $mapper = new ContractMapper();

        $data = $mapper->map($text);

        $data['tipo'] = 'ADQUISICION_BIENES_BIS';

        return $data;
    }
}