<?php

declare(strict_types=1);

namespace NexusRH\Models\DTO;

final class FuncionarioDTO
{
    public function __construct(
        public ?int $FuncionarioID,
        public string $Nome,
        public string $CPF,
        public ?string $Email,
        public string $SenhaHash,
        public string $PerfilAcesso,
        public int $CargoID,
        public int $CentroCustoID,
        public ?int $SupervisorID,
        public string $SalarioAtual,
        public string $DataAdmissao,
        public ?string $DataDesligamento,
        public string $Status
    ) {
    }
}
