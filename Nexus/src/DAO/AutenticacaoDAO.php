<?php

declare(strict_types=1);

namespace NexusRH\DAO;

use PDO;

final class AutenticacaoDAO extends BaseDAO
{
    public function buscarFuncionarioPorLogin(string $login): ?array
    {
        $sql = <<<SQL
            SELECT TOP 1
                FuncionarioID,
                Nome,
                CPF,
                Email,
                SenhaHash,
                PerfilAcesso,
                Status
            FROM Funcionario
            WHERE Email = :Login OR CPF = :Login;
        SQL;

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(':Login', $login);
        $statement->execute();

        $data = $statement->fetch();

        return $data !== false ? $data : null;
    }

    public function buscarMfaPorFuncionarioId(int $funcionarioId): ?array
    {
        $statement = $this->connection->prepare('SELECT TOP 1 * FROM FuncionarioMfa WHERE FuncionarioID = :FuncionarioID;');
        $statement->bindValue(':FuncionarioID', $funcionarioId, PDO::PARAM_INT);
        $statement->execute();

        $data = $statement->fetch();

        return $data !== false ? $data : null;
    }

    public function salvarMfa(int $funcionarioId, string $secretBase32, bool $ativo = true): void
    {
        $existente = $this->buscarMfaPorFuncionarioId($funcionarioId);

        if ($existente === null) {
            $statement = $this->connection->prepare(
                "INSERT INTO FuncionarioMfa (FuncionarioID, SecretBase32, Provedor, Ativo) VALUES (:FuncionarioID, :SecretBase32, 'TOTP', :Ativo);"
            );
        } else {
            $statement = $this->connection->prepare(
                "UPDATE FuncionarioMfa SET SecretBase32 = :SecretBase32, Provedor = 'TOTP', Ativo = :Ativo, UltimoUsoEm = NULL WHERE FuncionarioID = :FuncionarioID;"
            );
        }

        $statement->bindValue(':FuncionarioID', $funcionarioId, PDO::PARAM_INT);
        $statement->bindValue(':SecretBase32', $secretBase32);
        $statement->bindValue(':Ativo', $ativo ? 1 : 0, PDO::PARAM_INT);
        $statement->execute();
    }

    public function marcarMfaUsado(int $funcionarioId): void
    {
        $statement = $this->connection->prepare('UPDATE FuncionarioMfa SET UltimoUsoEm = GETDATE() WHERE FuncionarioID = :FuncionarioID;');
        $statement->bindValue(':FuncionarioID', $funcionarioId, PDO::PARAM_INT);
        $statement->execute();
    }
}