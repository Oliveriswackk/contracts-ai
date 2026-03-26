<?php

namespace App\Services\Mappers;

class ContractMapper extends BaseContractMapper implements MapperInterface
{
    public function map(string $text): array
    {
        $class = MapperRegistry::resolve($text);

        if ($class === self::class) {
            $class = GenericContractMapper::class;
        }

        $mapper = new $class();

        return $mapper->map($text);
    }
}