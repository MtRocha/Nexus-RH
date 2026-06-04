<?php

declare(strict_types=1);

namespace NexusRH\Models\DTO;

final class ConfiguracaoDTO
{
    public function __construct(
        public ?int $ConfiguracaoID,
        public string $Chave,
        public string $Valor,
        public string $Categoria,
        public ?string $Descricao,
        public string $TipoCampo,
        public bool $Editavel,
        public bool $Ativo
    ) {
    }
}