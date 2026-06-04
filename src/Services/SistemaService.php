<?php

declare(strict_types=1);

namespace NexusRH\Services;

use NexusRH\DAO\SistemaDAO;
use NexusRH\Models\DTO\ConfiguracaoDTO;
use NexusRH\Support\SessionAuth;
use NexusRH\Support\Totp;

final class SistemaService
{
    private SistemaDAO $sistemaDAO;

    public function __construct(
    ) {
        $this->sistemaDAO = new SistemaDAO();
    }

    public function listarConfiguracoes(): array
    {
        return $this->sistemaDAO->listarConfiguracoes();
    }

    public function salvarConfiguracao(ConfiguracaoDTO $configuracao): int
    {
        return $this->sistemaDAO->salvarConfiguracao($configuracao);
    }

    public function obterConfiguracao(string $chave, mixed $padrao = null): mixed
    {
        $configuracao = $this->sistemaDAO->buscarConfiguracaoPorChave($chave);

        return $configuracao['Valor'] ?? $padrao;
    }

    public function registrarOperacao(string $tipoOperacao, string $mensagem, bool $sucesso, array $detalhes = [], ?string $entidade = null, ?string $entidadeId = null): void
    {
        $usuario = SessionAuth::currentUser();

        $this->sistemaDAO->registrarOperacao([
            'tipoOperacao' => $tipoOperacao,
            'entidade' => $entidade,
            'entidadeId' => $entidadeId,
            'funcionarioId' => $usuario['FuncionarioID'] ?? null,
            'sucesso' => $sucesso,
            'mensagem' => $mensagem,
            'detalhesJson' => $detalhes === [] ? null : json_encode($detalhes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'ipOrigem' => $_SERVER['REMOTE_ADDR'] ?? null,
            'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }

    public function registrarConsumoApi(string $endpoint, string $metodo, int $statusHttp, ?int $tempoRespostaMs = null): void
    {
        $usuario = SessionAuth::currentUser();

        $this->sistemaDAO->registrarConsumoApi([
            'endpoint' => $endpoint,
            'metodo' => $metodo,
            'statusHttp' => $statusHttp,
            'funcionarioId' => $usuario['FuncionarioID'] ?? null,
            'tempoRespostaMs' => $tempoRespostaMs,
            'ipOrigem' => $_SERVER['REMOTE_ADDR'] ?? null,
            'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }

    public function resumoDashboard(): array
    {
        $resumo = $this->sistemaDAO->resumoDashboard();
        $resumo['PorCentroCusto'] = $this->sistemaDAO->indicadoresPorCentroCusto();
        $resumo['PorCargo'] = $this->sistemaDAO->indicadoresPorCargo();
        $resumo['UltimosLogs'] = $this->sistemaDAO->listarLogsOperacao(6);
        $resumo['Configuracoes'] = [
            'NomeEmpresa' => $this->obterConfiguracao('empresa.nome', 'Nexus RH'),
            'Slogan' => $this->obterConfiguracao('empresa.slogan', 'Gestao inteligente de RH'),
            'MapaLatitude' => (float) $this->obterConfiguracao('mapa.latitude', '-23.5629'),
            'MapaLongitude' => (float) $this->obterConfiguracao('mapa.longitude', '-46.6560'),
            'MapaZoom' => (int) $this->obterConfiguracao('mapa.zoom', '15'),
            'MfaObrigatorio' => (bool) (int) $this->obterConfiguracao('mfa.obrigatorio', '0'),
        ];

        return $resumo;
    }

    public function listarLogsOperacao(int $limite = 100): array
    {
        return $this->sistemaDAO->listarLogsOperacao($limite);
    }

    public function listarLogsApi(int $limite = 100): array
    {
        return $this->sistemaDAO->listarLogsApi($limite);
    }

    public function gerarSecretTotp(): string
    {
        return Totp::generateSecret();
    }
}