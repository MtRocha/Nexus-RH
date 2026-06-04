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

    public function buscarPorReferencia(int $funcionarioId, int $mes, int $ano): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT FolhaID FROM FolhaPagamento WHERE FuncionarioID = :FuncionarioID AND MesReferencia = :MesReferencia AND AnoReferencia = :AnoReferencia LIMIT 1;'
        );
        $statement->bindValue(':FuncionarioID', $funcionarioId, PDO::PARAM_INT);
        $statement->bindValue(':MesReferencia', $mes, PDO::PARAM_INT);
        $statement->bindValue(':AnoReferencia', $ano, PDO::PARAM_INT);
        $statement->execute();

        $data = $statement->fetch();

        return $data !== false ? $data : null;
    }

    public function inserir(
        int $funcionarioId,
        int $mes,
        int $ano,
        float $salarioBase,
        float $totalProventos,
        float $totalDescontos,
        float $valorLiquido,
        string $dataPagamento,
        ?int $fechadaPor
    ): int {
        $statement = $this->connection->prepare(
            'INSERT INTO FolhaPagamento (FuncionarioID, MesReferencia, AnoReferencia, SalarioBase, TotalProventos, TotalDescontos, ValorLiquido, DataPagamento, FechadaPor) VALUES (:FuncionarioID, :MesReferencia, :AnoReferencia, :SalarioBase, :TotalProventos, :TotalDescontos, :ValorLiquido, :DataPagamento, :FechadaPor);'
        );
        $statement->bindValue(':FuncionarioID', $funcionarioId, PDO::PARAM_INT);
        $statement->bindValue(':MesReferencia', $mes, PDO::PARAM_INT);
        $statement->bindValue(':AnoReferencia', $ano, PDO::PARAM_INT);
        $statement->bindValue(':SalarioBase', $salarioBase);
        $statement->bindValue(':TotalProventos', $totalProventos);
        $statement->bindValue(':TotalDescontos', $totalDescontos);
        $statement->bindValue(':ValorLiquido', $valorLiquido);
        $statement->bindValue(':DataPagamento', $dataPagamento);
        $statement->bindValue(':FechadaPor', $fechadaPor, $fechadaPor === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $statement->execute();

        return (int) $this->connection->lastInsertId();
    }
}
