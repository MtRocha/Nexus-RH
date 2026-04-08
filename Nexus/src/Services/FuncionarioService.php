<?php

declare(strict_types=1);

namespace NexusRH\Services;

use NexusRH\DAO\FuncionarioDAO;
use NexusRH\Exceptions\BusinessRuleException;
use NexusRH\Exceptions\ValidationException;
use NexusRH\Models\DTO\FuncionarioDTO;

final class FuncionarioService
{
    public function __construct(
        private readonly FuncionarioDAO $funcionarioDAO = new FuncionarioDAO()
    ) {
    }

    public function criar(FuncionarioDTO $funcionario): int
    {
        $this->validarDadosObrigatorios($funcionario);
        $this->validarCpfUnico($funcionario->CPF);
        $this->validarCentroCusto($funcionario->CentroCustoID);
        $this->validarSupervisor($funcionario->SupervisorID);

        return $this->funcionarioDAO->inserir($funcionario);
    }

    public function buscarPorId(int $funcionarioId): ?array
    {
        if ($funcionarioId <= 0) {
            throw new ValidationException('FuncionarioID invalido. Informe um valor inteiro positivo.');
        }

        return $this->funcionarioDAO->buscarPorId($funcionarioId);
    }

    public function listarTodos(): array
    {
        return $this->funcionarioDAO->buscarTodos();
    }

    private function validarDadosObrigatorios(FuncionarioDTO $funcionario): void
    {
        if (trim($funcionario->Nome) === '') {
            throw new ValidationException('Nome e obrigatorio.');
        }

        $cpfLimpo = preg_replace('/\D/', '', $funcionario->CPF) ?? '';
        if (strlen($cpfLimpo) !== 11) {
            throw new ValidationException('CPF invalido. Informe 11 digitos numericos.');
        }

        if ($funcionario->Email !== null && !filter_var($funcionario->Email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('Email invalido.');
        }

        if ($funcionario->CargoID <= 0) {
            throw new ValidationException('CargoID invalido.');
        }

        if ($funcionario->CentroCustoID <= 0) {
            throw new ValidationException('CentroCustoID invalido.');
        }

        if (trim($funcionario->SenhaHash) === '') {
            throw new ValidationException('SenhaHash e obrigatorio.');
        }

        if (!is_numeric($funcionario->SalarioAtual) || (float) $funcionario->SalarioAtual <= 0) {
            throw new ValidationException('SalarioAtual invalido. Informe um valor maior que zero.');
        }

        if (!$this->isDataValida($funcionario->DataAdmissao)) {
            throw new ValidationException('DataAdmissao invalida. Use o formato YYYY-MM-DD.');
        }

        if ($funcionario->DataDesligamento !== null && !$this->isDataValida($funcionario->DataDesligamento)) {
            throw new ValidationException('DataDesligamento invalida. Use o formato YYYY-MM-DD.');
        }
    }

    private function validarCpfUnico(string $cpf): void
    {
        $cpfLimpo = preg_replace('/\D/', '', $cpf) ?? '';
        $existente = $this->funcionarioDAO->buscarPorCpf($cpfLimpo);

        if ($existente !== null) {
            throw new BusinessRuleException('Ja existe um funcionario cadastrado com este CPF.');
        }
    }

    private function validarCentroCusto(int $centroCustoId): void
    {
        if (!$this->funcionarioDAO->existeCentroCustoPorId($centroCustoId)) {
            throw new BusinessRuleException('Centro de custo informado nao existe.');
        }
    }

    private function validarSupervisor(?int $supervisorId): void
    {
        if ($supervisorId === null) {
            return;
        }

        if ($supervisorId <= 0) {
            throw new ValidationException('SupervisorID invalido.');
        }

        if (!$this->funcionarioDAO->existeFuncionarioPorId($supervisorId)) {
            throw new BusinessRuleException('Supervisor informado nao existe.');
        }
    }

    private function isDataValida(string $data): bool
    {
        $partes = explode('-', $data);

        if (count($partes) !== 3) {
            return false;
        }

        return checkdate((int) $partes[1], (int) $partes[2], (int) $partes[0]);
    }
}
