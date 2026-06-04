<?php

declare(strict_types=1);

namespace NexusRH\Services;

use NexusRH\DAO\FuncionarioDAO;
use NexusRH\Exceptions\BusinessRuleException;
use NexusRH\Exceptions\ValidationException;
use NexusRH\Models\DTO\FuncionarioDTO;

final class FuncionarioService
{
    private FuncionarioDAO $funcionarioDAO;

    public function __construct(
    ) {
        $this->funcionarioDAO = new FuncionarioDAO();
    }

    public function criar(FuncionarioDTO $funcionario): int
    {
        $this->validarDadosObrigatorios($funcionario, true);
        $this->validarCpfUnico($funcionario->CPF, null);
        $this->validarCentroCusto($funcionario->CentroCustoID);
        $this->validarSupervisor($funcionario->SupervisorID);

        return $this->funcionarioDAO->inserir($funcionario);
    }

    public function atualizar(FuncionarioDTO $funcionario): void
    {
        if ($funcionario->FuncionarioID === null || $funcionario->FuncionarioID <= 0) {
            throw new ValidationException('FuncionarioID invalido. Informe um valor inteiro positivo.');
        }

        $existente = $this->funcionarioDAO->buscarPorId($funcionario->FuncionarioID);
        if ($existente === null) {
            throw new BusinessRuleException('Funcionario nao encontrado.');
        }

        $alterarSenha = trim($funcionario->SenhaHash) !== '';
        $this->validarDadosObrigatorios($funcionario, $alterarSenha);
        $this->validarCpfUnico($funcionario->CPF, $funcionario->FuncionarioID);
        $this->validarCentroCusto($funcionario->CentroCustoID);
        $this->validarSupervisor($funcionario->SupervisorID);

        if (trim($funcionario->DataAdmissao) === '') {
            $funcionario->DataAdmissao = (string) ($existente['DataAdmissao'] ?? '');
        }

        if ($funcionario->DataDesligamento === null) {
            $funcionario->DataDesligamento = $existente['DataDesligamento'] ?? null;
        }

        $this->funcionarioDAO->atualizar($funcionario, $alterarSenha);
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

    public function catalogos(): array
    {
        return [
            'cargos' => $this->funcionarioDAO->listarCargos(),
            'centrosCusto' => $this->funcionarioDAO->listarCentrosCusto(),
        ];
    }

    public function desativar(int $funcionarioId): void
    {
        if ($funcionarioId <= 0) {
            throw new ValidationException('FuncionarioID invalido. Informe um valor inteiro positivo.');
        }

        if (!$this->funcionarioDAO->existeFuncionarioPorId($funcionarioId)) {
            throw new BusinessRuleException('Funcionario nao encontrado.');
        }

        $this->funcionarioDAO->desativar($funcionarioId);
    }

    private function validarDadosObrigatorios(FuncionarioDTO $funcionario, bool $requireSenha): void
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

        if ($requireSenha && trim($funcionario->SenhaHash) === '') {
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

    private function validarCpfUnico(string $cpf, ?int $funcionarioId): void
    {
        $cpfLimpo = preg_replace('/\D/', '', $cpf) ?? '';
        $existente = $this->funcionarioDAO->buscarPorCpf($cpfLimpo);

        if ($existente !== null && (int) ($existente['FuncionarioID'] ?? 0) !== (int) ($funcionarioId ?? 0)) {
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
