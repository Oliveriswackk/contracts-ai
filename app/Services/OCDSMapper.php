<?php

namespace App\Services;

use App\Services\DTO\ContractDTO;

class OCDSMapper
{
    public function map(ContractDTO $dto): array
    {
        return [
            'ocid' => $this->buildOcid($dto),
            'id' => $dto->numero,
            'date' => $dto->fechaFirma,
            'buyer' => $this->buildBuyer($dto),
            'parties' => $this->buildParties($dto),
            'awards' => $this->buildAwards($dto),
            'contracts' => $this->buildContracts($dto),
        ];
    }


    //OCID
    private function buildOcid(ContractDTO $dto): ?string
    {
        if (!$dto->numero) return null;

        return 'ocds-' . md5($dto->numero);
    }


    // Buyer
    private function buildBuyer(ContractDTO $dto): ?array
    {
        if (!$dto->dependencia) return null;

        return [
            'name' => $dto->dependencia
        ];
    }


    // Parties
    private function buildParties(ContractDTO $dto): array
    {
        $parties = [];

        if ($dto->dependencia) {
            $parties[] = [
                'name' => $dto->dependencia,
                'roles' => ['buyer']
            ];
        }

        if ($dto->proveedor) {
            $party = [
                'name' => $dto->proveedor,
                'roles' => ['supplier']
            ];

            if ($dto->rfcProveedor) {
                $party['identifier'] = [
                    'scheme' => 'MX-RFC',
                    'id' => $dto->rfcProveedor
                ];
            }

            $parties[] = $party;
        }

        return $parties;
    }


    // Awards
    private function buildAwards(ContractDTO $dto): array
    {
        if (!$dto->proveedor) return [];

        return [[
            'id' => $dto->numero,
            'title' => $dto->tipo,
            'date' => $dto->fechaFirma,
            'suppliers' => [[
                'name' => $dto->proveedor
            ]]
        ]];
    }


    // Contracts
    private function buildContracts(ContractDTO $dto): array
    {
        return [[
            'id' => $dto->numero,
            'title' => $dto->tipo,
            'period' => [
                'startDate' => $dto->fechaInicio,
                'endDate' => $dto->fechaFin
            ],
            'value' => [
                'amount' => $dto->monto,
                'currency' => $dto->moneda
            ]
        ]];
    }
}