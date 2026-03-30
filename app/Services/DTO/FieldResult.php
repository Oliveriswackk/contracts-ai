<?php

namespace App\Services\DTO;

class FieldResult
{
    private $value;
    private $confidence;
    private $sources;

    public function __construct($value, $confidence = 1.0, $sources = [])
    {
        $this->value = $value;
        $this->confidence = $confidence;
        $this->sources = $sources;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getConfidence()
    {
        return $this->confidence;
    }

    public function getSources()
    {
        return $this->sources;
    }
}