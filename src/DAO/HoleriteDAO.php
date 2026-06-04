<?php

declare(strict_types=1);

namespace NexusRH\DAO;

use PDO;

final class HoleriteDAO extends BaseDAO
{
    public function listarPorFuncionarioId(int $funcionarioId): array
    {
        $statement = $this->connection->prepare(
            'SELECT FolhaID, MesReferencia, AnoReferencia, ValorLiquido, DataPagamento FROM FolhaPagamento WHERE FuncionarioID = :FuncionarioID ORDER BY AnoReferencia DESC, MesReferencia DESC;'
        );
        $statement->bindValue(':FuncionarioID', $funcionarioId, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function buscarPorId(int $folhaId, int $funcionarioId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT f.FolhaID, f.MesReferencia, f.AnoReferencia, f.SalarioBase, f.TotalProventos, f.TotalDescontos, f.ValorLiquido, f.DataPagamento, fu.Nome, fu.CPF FROM FolhaPagamento f JOIN Funcionario fu ON fu.FuncionarioID = f.FuncionarioID WHERE f.FolhaID = :FolhaID AND f.FuncionarioID = :FuncionarioID LIMIT 1;'
        );
        $statement->bindValue(':FolhaID', $folhaId, PDO::PARAM_INT);
        $statement->bindValue(':FuncionarioID', $funcionarioId, PDO::PARAM_INT);
        $statement->execute();

        $data = $statement->fetch();

        return $data !== false ? $data : null;
    }
}
