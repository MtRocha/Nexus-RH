<?php

declare(strict_types=1);

namespace NexusRH\DAO;

use PDO;

final class RegistroPontoDAO extends BaseDAO
{
    public function buscarUltimaBatidaHoje(int $funcionarioId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT PontoID, TipoBatida, DataHoraRegistro FROM RegistroPonto WHERE FuncionarioID = :FuncionarioID AND DATE(DataHoraRegistro) = CURDATE() ORDER BY DataHoraRegistro DESC LIMIT 1;'
        );
        $statement->bindValue(':FuncionarioID', $funcionarioId, PDO::PARAM_INT);
        $statement->execute();

        $data = $statement->fetch();

        return $data !== false ? $data : null;
    }

    public function registrarBatida(int $funcionarioId, string $tipoBatida, string $origem): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO RegistroPonto (FuncionarioID, DataHoraRegistro, TipoBatida, Origem, StatusAprovacao) VALUES (:FuncionarioID, NOW(), :TipoBatida, :Origem, "Aprovado");'
        );
        $statement->bindValue(':FuncionarioID', $funcionarioId, PDO::PARAM_INT);
        $statement->bindValue(':TipoBatida', $tipoBatida);
        $statement->bindValue(':Origem', $origem);
        $statement->execute();

        return (int) $this->connection->lastInsertId();
    }
}
