<?php

declare(strict_types=1);

namespace NexusRH\Services;

use NexusRH\DAO\RegistroPontoDAO;
use NexusRH\Exceptions\ValidationException;
use NexusRH\Support\SessionAuth;

final class RegistroPontoService
{
    private RegistroPontoDAO $registroPontoDAO;

    public function __construct()
    {
        $this->registroPontoDAO = new RegistroPontoDAO();
    }

    public function registrarBatida(): array
    {
        $usuario = SessionAuth::currentUser();
        if ($usuario === null) {
            throw new ValidationException('Usuario nao autenticado.');
        }

        $funcionarioId = (int) $usuario['FuncionarioID'];
        $ultima = $this->registroPontoDAO->buscarUltimaBatidaHoje($funcionarioId);

        $tipoBatida = ($ultima !== null && ($ultima['TipoBatida'] ?? '') === 'Entrada') ? 'Saida' : 'Entrada';
        $pontoId = $this->registroPontoDAO->registrarBatida($funcionarioId, $tipoBatida, 'Web');

        return [
            'PontoID' => $pontoId,
            'FuncionarioID' => $funcionarioId,
            'TipoBatida' => $tipoBatida,
            'DataHoraRegistro' => date('d/m/Y H:i:s'),
        ];
    }
}
