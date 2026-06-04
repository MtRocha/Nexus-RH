<?php

declare(strict_types=1);

namespace NexusRH\Models\DTO;

final class CentroCustoDTO
{
    public function __construct(
        public ?int $CentroCustoID,
        public string $Codigo,
        public string $Nome
    ) {
    }
}
