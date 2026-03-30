<?php

namespace App\Services\Mappers;

use App\Services\Text\TextPreprocessor;
use Illuminate\Support\Facades\Log;

class ContractMapper extends BaseContractMapper implements MapperInterface
{
    public function map(string $text): array
    {
        $text = TextPreprocessor::normalize($text);

        $class = MapperRegistry::resolve($text);

        $mapper = new $class();

        $result = $mapper->map($text);

        // Detectar campos nulos correctamente
        $nullFields = array_keys(array_filter(
            $result,
            fn($v) => is_array($v) && is_null($v['value'] ?? null)
        ));

        if (!empty($nullFields)) {
            Log::info('contract_mapper.null_fields', [
                'fields' => $nullFields,
                'mapper' => $class,
                'snippet' => mb_substr($text, 0, 500)
            ]);
        }

        return $result;
    }
}