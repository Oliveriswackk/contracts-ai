<?php

namespace App\Services\Mappers;

interface MapperInterface
{
    public function map(string $text): array;
}