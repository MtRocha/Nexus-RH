<?php

declare(strict_types=1);

namespace NexusRH\DAO;

use NexusRH\Models\DTO\ConfiguracaoDTO;
use PDO;

final class SistemaDAO extends BaseDAO
{
    public function listarConfiguracoes(): array
    {
        $statement = $this->connection->query(
            'SELECT ConfiguracaoID, Chave, Valor, Categoria, Descricao, TipoCampo, Editavel, Ativo, AtualizadoEm FROM ConfiguracaoSistema ORDER BY Categoria ASC, Chave ASC;'
        );

        return $statement->fetchAll();
    }

    public function buscarConfiguracaoPorChave(string $chave): ?array
    {
        $statement = $this->connection->prepare('SELECT * FROM ConfiguracaoSistema WHERE Chave = :Chave LIMIT 1;');
        $statement->bindValue(':Chave', $chave);
        $statement->execute();

        $data = $statement->fetch();

        return $data !== false ? $data : null;
    }

    public function salvarConfiguracao(ConfiguracaoDTO $configuracao): int
    {
        $existente = $this->buscarConfiguracaoPorChave($configuracao->Chave);

        if ($existente === null) {
            $statement = $this->connection->prepare(
                'INSERT INTO ConfiguracaoSistema (Chave, Valor, Categoria, Descricao, TipoCampo, Editavel, Ativo) VALUES (:Chave, :Valor, :Categoria, :Descricao, :TipoCampo, :Editavel, :Ativo);'
            );
        } else {
            $statement = $this->connection->prepare(
                'UPDATE ConfiguracaoSistema SET Valor = :Valor, Categoria = :Categoria, Descricao = :Descricao, TipoCampo = :TipoCampo, Editavel = :Editavel, Ativo = :Ativo, AtualizadoEm = NOW() WHERE Chave = :Chave;'
            );
        }

        $statement->bindValue(':Chave', $configuracao->Chave);
        $statement->bindValue(':Valor', $configuracao->Valor);
        $statement->bindValue(':Categoria', $configuracao->Categoria);
        $statement->bindValue(':Descricao', $configuracao->Descricao);
        $statement->bindValue(':TipoCampo', $configuracao->TipoCampo);
        $statement->bindValue(':Editavel', $configuracao->Editavel ? 1 : 0, PDO::PARAM_INT);
        $statement->bindValue(':Ativo', $configuracao->Ativo ? 1 : 0, PDO::PARAM_INT);
        $statement->execute();

        if ($existente === null) {
            return (int) $this->connection->lastInsertId();
        }

        return (int) ($existente['ConfiguracaoID'] ?? 0);
    }

    public function registrarOperacao(array $dados): void
    {
        $statement = $this->connection->prepare(
            'INSERT INTO LogOperacaoSistema (TipoOperacao, Entidade, EntidadeID, FuncionarioID, Sucesso, Mensagem, DetalhesJson, IpOrigem, UserAgent) VALUES (:TipoOperacao, :Entidade, :EntidadeID, :FuncionarioID, :Sucesso, :Mensagem, :DetalhesJson, :IpOrigem, :UserAgent);'
        );

        $statement->bindValue(':TipoOperacao', $dados['tipoOperacao']);
        $statement->bindValue(':Entidade', $dados['entidade'] ?? null);
        $statement->bindValue(':EntidadeID', $dados['entidadeId'] ?? null);
        $statement->bindValue(':FuncionarioID', $dados['funcionarioId'] ?? null, isset($dados['funcionarioId']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $statement->bindValue(':Sucesso', !empty($dados['sucesso']) ? 1 : 0, PDO::PARAM_INT);
        $statement->bindValue(':Mensagem', $dados['mensagem']);
        $statement->bindValue(':DetalhesJson', $dados['detalhesJson'] ?? null);
        $statement->bindValue(':IpOrigem', $dados['ipOrigem'] ?? null);
        $statement->bindValue(':UserAgent', $dados['userAgent'] ?? null);
        $statement->execute();
    }

    public function registrarConsumoApi(array $dados): void
    {
        $statement = $this->connection->prepare(
            'INSERT INTO ConsumoApiLog (Endpoint, Metodo, StatusHttp, FuncionarioID, TempoRespostaMs, IpOrigem, UserAgent) VALUES (:Endpoint, :Metodo, :StatusHttp, :FuncionarioID, :TempoRespostaMs, :IpOrigem, :UserAgent);'
        );

        $statement->bindValue(':Endpoint', $dados['endpoint']);
        $statement->bindValue(':Metodo', $dados['metodo']);
        $statement->bindValue(':StatusHttp', $dados['statusHttp'], PDO::PARAM_INT);
        $statement->bindValue(':FuncionarioID', $dados['funcionarioId'] ?? null, isset($dados['funcionarioId']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $statement->bindValue(':TempoRespostaMs', $dados['tempoRespostaMs'] ?? null, isset($dados['tempoRespostaMs']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $statement->bindValue(':IpOrigem', $dados['ipOrigem'] ?? null);
        $statement->bindValue(':UserAgent', $dados['userAgent'] ?? null);
        $statement->execute();
    }

    public function listarLogsOperacao(int $limite = 100): array
    {
        $limite = max(1, min(500, $limite));
        $statement = $this->connection->query(
            'SELECT l.LogOperacaoID, l.TipoOperacao, l.Entidade, l.EntidadeID, l.FuncionarioID, f.Nome AS FuncionarioNome, l.Sucesso, l.Mensagem, l.DetalhesJson, l.IpOrigem, l.UserAgent, l.DataOperacao FROM LogOperacaoSistema l LEFT JOIN Funcionario f ON f.FuncionarioID = l.FuncionarioID ORDER BY l.DataOperacao DESC, l.LogOperacaoID DESC LIMIT ' . $limite . ';'
        );

        return $statement->fetchAll();
    }

    public function listarLogsApi(int $limite = 100): array
    {
        $limite = max(1, min(500, $limite));
        $statement = $this->connection->query(
            'SELECT l.ConsumoApiLogID, l.Endpoint, l.Metodo, l.StatusHttp, l.FuncionarioID, f.Nome AS FuncionarioNome, l.TempoRespostaMs, l.IpOrigem, l.UserAgent, l.DataConsumo FROM ConsumoApiLog l LEFT JOIN Funcionario f ON f.FuncionarioID = l.FuncionarioID ORDER BY l.DataConsumo DESC, l.ConsumoApiLogID DESC LIMIT ' . $limite . ';'
        );

        return $statement->fetchAll();
    }

    public function resumoDashboard(): array
    {
        $statement = $this->connection->query(
            "SELECT (SELECT COUNT(1) FROM Funcionario) AS TotalFuncionarios, (SELECT COUNT(1) FROM Funcionario WHERE Status = 'Ativo') AS FuncionariosAtivos, (SELECT COUNT(1) FROM RegistroPonto WHERE DATE(DataHoraRegistro) = CURDATE()) AS PontosHoje, (SELECT COUNT(1) FROM SolicitacaoFerias WHERE StatusAprovacao = 'Pendente') AS FeriasPendentes, (SELECT COUNT(1) FROM AfastamentoAtestado WHERE StatusAprovacao = 'Pendente') AS AfastamentosPendentes, (SELECT COUNT(1) FROM LogOperacaoSistema WHERE DataOperacao >= DATE_SUB(NOW(), INTERVAL 7 DAY)) AS OperacoesUltimos7Dias, (SELECT COUNT(1) FROM ConsumoApiLog WHERE DataConsumo >= DATE_SUB(NOW(), INTERVAL 7 DAY)) AS ChamadasApiUltimos7Dias;"
        );

        return $statement->fetch() ?: [];
    }

    public function indicadoresPorCentroCusto(): array
    {
        $statement = $this->connection->query(
            'SELECT cc.Nome AS CentroCusto, COUNT(f.FuncionarioID) AS Total FROM CentroCusto cc LEFT JOIN Funcionario f ON f.CentroCustoID = cc.CentroCustoID GROUP BY cc.Nome ORDER BY Total DESC, cc.Nome ASC;'
        );

        return $statement->fetchAll();
    }

    public function indicadoresPorCargo(): array
    {
        $statement = $this->connection->query(
            'SELECT c.Nome AS Cargo, COUNT(f.FuncionarioID) AS Total FROM Cargo c LEFT JOIN Funcionario f ON f.CargoID = c.CargoID GROUP BY c.Nome ORDER BY Total DESC, c.Nome ASC;'
        );

        return $statement->fetchAll();
    }
}