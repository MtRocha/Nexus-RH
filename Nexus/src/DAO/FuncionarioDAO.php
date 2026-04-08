<?php

declare(strict_types=1);

namespace NexusRH\DAO;

use NexusRH\Models\DTO\FuncionarioDTO;
use PDO;

final class FuncionarioDAO extends BaseDAO
{
    public function inserir(FuncionarioDTO $funcionario): int
    {
        $sql = <<<SQL
            INSERT INTO Funcionario (
                Nome,
                CPF,
                Email,
                SenhaHash,
                PerfilAcesso,
                CargoID,
                CentroCustoID,
                SupervisorID,
                SalarioAtual,
                DataAdmissao,
                DataDesligamento,
                Status
            )
            VALUES (
                :Nome,
                :CPF,
                :Email,
                :SenhaHash,
                :PerfilAcesso,
                :CargoID,
                :CentroCustoID,
                :SupervisorID,
                :SalarioAtual,
                :DataAdmissao,
                :DataDesligamento,
                :Status
            );
            SELECT CAST(SCOPE_IDENTITY() AS INT) AS FuncionarioID;
        SQL;

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(':Nome', $funcionario->Nome);
        $statement->bindValue(':CPF', $funcionario->CPF);
        $statement->bindValue(':Email', $funcionario->Email);
        $statement->bindValue(':SenhaHash', $funcionario->SenhaHash);
        $statement->bindValue(':PerfilAcesso', $funcionario->PerfilAcesso);
        $statement->bindValue(':CargoID', $funcionario->CargoID, PDO::PARAM_INT);
        $statement->bindValue(':CentroCustoID', $funcionario->CentroCustoID, PDO::PARAM_INT);
        $statement->bindValue(':SupervisorID', $funcionario->SupervisorID, $funcionario->SupervisorID === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $statement->bindValue(':SalarioAtual', $funcionario->SalarioAtual);
        $statement->bindValue(':DataAdmissao', $funcionario->DataAdmissao);
        $statement->bindValue(':DataDesligamento', $funcionario->DataDesligamento);
        $statement->bindValue(':Status', $funcionario->Status);
        $statement->execute();

        $result = $statement->fetch();

        return (int) ($result['FuncionarioID'] ?? 0);
    }

    public function buscarPorId(int $funcionarioId): ?array
    {
        $sql = <<<SQL
            SELECT
                f.FuncionarioID,
                f.Nome,
                f.CPF,
                f.Email,
                f.PerfilAcesso,
                f.CargoID,
                c.Nome AS CargoNome,
                f.CentroCustoID,
                cc.Codigo AS CentroCustoCodigo,
                cc.Nome AS CentroCustoNome,
                f.SupervisorID,
                s.Nome AS SupervisorNome,
                f.SalarioAtual,
                f.DataAdmissao,
                f.DataDesligamento,
                f.Status
            FROM Funcionario f
            INNER JOIN Cargo c ON c.CargoID = f.CargoID
            INNER JOIN CentroCusto cc ON cc.CentroCustoID = f.CentroCustoID
            LEFT JOIN Funcionario s ON s.FuncionarioID = f.SupervisorID
            WHERE f.FuncionarioID = :FuncionarioID;
        SQL;

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(':FuncionarioID', $funcionarioId, PDO::PARAM_INT);
        $statement->execute();

        $data = $statement->fetch();

        return $data !== false ? $data : null;
    }

    public function buscarPorCpf(string $cpf): ?array
    {
        $sql = <<<SQL
            SELECT
                f.FuncionarioID,
                f.Nome,
                f.CPF,
                f.Email,
                f.PerfilAcesso,
                f.CargoID,
                c.Nome AS CargoNome,
                f.CentroCustoID,
                cc.Codigo AS CentroCustoCodigo,
                cc.Nome AS CentroCustoNome,
                f.SupervisorID,
                s.Nome AS SupervisorNome,
                f.SalarioAtual,
                f.DataAdmissao,
                f.DataDesligamento,
                f.Status
            FROM Funcionario f
            INNER JOIN Cargo c ON c.CargoID = f.CargoID
            INNER JOIN CentroCusto cc ON cc.CentroCustoID = f.CentroCustoID
            LEFT JOIN Funcionario s ON s.FuncionarioID = f.SupervisorID
            WHERE f.CPF = :CPF;
        SQL;

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(':CPF', $cpf);
        $statement->execute();

        $data = $statement->fetch();

        return $data !== false ? $data : null;
    }

    public function buscarTodos(): array
    {
        $sql = <<<SQL
            SELECT
                f.FuncionarioID,
                f.Nome,
                f.CPF,
                f.Email,
                f.PerfilAcesso,
                f.CargoID,
                c.Nome AS CargoNome,
                f.CentroCustoID,
                cc.Codigo AS CentroCustoCodigo,
                cc.Nome AS CentroCustoNome,
                f.SupervisorID,
                s.Nome AS SupervisorNome,
                f.SalarioAtual,
                f.DataAdmissao,
                f.DataDesligamento,
                f.Status
            FROM Funcionario f
            INNER JOIN Cargo c ON c.CargoID = f.CargoID
            INNER JOIN CentroCusto cc ON cc.CentroCustoID = f.CentroCustoID
            LEFT JOIN Funcionario s ON s.FuncionarioID = f.SupervisorID
            ORDER BY f.Nome ASC;
        SQL;

        $statement = $this->connection->prepare($sql);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function existeFuncionarioPorId(int $funcionarioId): bool
    {
        $sql = 'SELECT 1 FROM Funcionario WHERE FuncionarioID = :FuncionarioID;';

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(':FuncionarioID', $funcionarioId, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchColumn() !== false;
    }

    public function existeCentroCustoPorId(int $centroCustoId): bool
    {
        $sql = 'SELECT 1 FROM CentroCusto WHERE CentroCustoID = :CentroCustoID;';

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(':CentroCustoID', $centroCustoId, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchColumn() !== false;
    }
}
