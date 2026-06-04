<?php

declare(strict_types=1);

namespace NexusRH\Services;

use DateTime;
use NexusRH\DAO\FuncionarioDAO;
use NexusRH\DAO\HoleriteDAO;
use NexusRH\Exceptions\BusinessRuleException;
use NexusRH\Exceptions\ValidationException;
use NexusRH\Support\SessionAuth;

final class HoleriteService
{
    private HoleriteDAO $holeriteDAO;
    private FuncionarioDAO $funcionarioDAO;
    private SistemaService $sistemaService;

    public function __construct()
    {
        $this->holeriteDAO = new HoleriteDAO();
        $this->funcionarioDAO = new FuncionarioDAO();
        $this->sistemaService = new SistemaService();
    }

    public function listarPorUsuarioAtual(): array
    {
        $usuario = SessionAuth::currentUser();
        if ($usuario === null) {
            throw new ValidationException('Usuario nao autenticado.');
        }

        return $this->holeriteDAO->listarPorFuncionarioId((int) $usuario['FuncionarioID']);
    }

    public function gerarPdfPorId(int $folhaId): array
    {
        $usuario = SessionAuth::currentUser();
        if ($usuario === null) {
            throw new ValidationException('Usuario nao autenticado.');
        }

        $holerite = $this->holeriteDAO->buscarPorId($folhaId, (int) $usuario['FuncionarioID']);
        if ($holerite === null) {
            throw new BusinessRuleException('Holerite nao encontrado.');
        }

        $mes = str_pad((string) $holerite['MesReferencia'], 2, '0', STR_PAD_LEFT);
        $ano = (string) $holerite['AnoReferencia'];
        $titulo = "Holerite {$mes}/{$ano}";

        $linhas = [
            'Funcionario: ' . ($holerite['Nome'] ?? '-'),
            'CPF: ' . ($holerite['CPF'] ?? '-'),
            'Referencia: ' . $mes . '/' . $ano,
            'Salario base: R$ ' . number_format((float) $holerite['SalarioBase'], 2, ',', '.'),
            'Total proventos: R$ ' . number_format((float) $holerite['TotalProventos'], 2, ',', '.'),
            'Total descontos: R$ ' . number_format((float) $holerite['TotalDescontos'], 2, ',', '.'),
            'Valor liquido: R$ ' . number_format((float) $holerite['ValorLiquido'], 2, ',', '.'),
            'Data pagamento: ' . ($holerite['DataPagamento'] ?? '-'),
        ];

        $pdf = $this->buildSimplePdf($titulo, $linhas);

        return [
            'filename' => 'holerite-' . $mes . '-' . $ano . '.pdf',
            'content' => $pdf,
        ];
    }

    public function gerarPorAdmin(int $funcionarioId, int $mes, int $ano, int $diasTrabalhados): array
    {
        $usuario = SessionAuth::currentUser();
        if ($usuario === null) {
            throw new ValidationException('Usuario nao autenticado.');
        }

        if (($usuario['PerfilAcesso'] ?? '') !== 'Administrador') {
            throw new BusinessRuleException('Acesso restrito a administradores.');
        }

        if ($funcionarioId <= 0) {
            throw new ValidationException('FuncionarioID invalido.');
        }

        if ($mes < 1 || $mes > 12) {
            throw new ValidationException('Mes de referencia invalido.');
        }

        if ($ano < 2000 || $ano > 2100) {
            throw new ValidationException('Ano de referencia invalido.');
        }

        if ($diasTrabalhados < 0 || $diasTrabalhados > 31) {
            throw new ValidationException('Dias trabalhados invalido.');
        }

        $funcionario = $this->funcionarioDAO->buscarPorId($funcionarioId);
        if ($funcionario === null) {
            throw new BusinessRuleException('Funcionario nao encontrado.');
        }

        $existente = $this->holeriteDAO->buscarPorReferencia($funcionarioId, $mes, $ano);
        if ($existente !== null) {
            throw new BusinessRuleException('Ja existe holerite para este periodo.');
        }

        $salarioBase = (float) ($funcionario['SalarioAtual'] ?? 0);
        if ($salarioBase <= 0) {
            throw new BusinessRuleException('Salario do funcionario invalido.');
        }

        $proventos = $salarioBase * ($diasTrabalhados / 30);
        $totalProventos = round($proventos, 2);
        $totalDescontos = 0.0;
        $valorLiquido = max(0.0, $totalProventos - $totalDescontos);

        $dataPagamento = $this->obterDataPagamento($ano, $mes);
        $folhaId = $this->holeriteDAO->inserir(
            $funcionarioId,
            $mes,
            $ano,
            $salarioBase,
            $totalProventos,
            $totalDescontos,
            $valorLiquido,
            $dataPagamento,
            isset($usuario['FuncionarioID']) ? (int) $usuario['FuncionarioID'] : null
        );

        $this->sistemaService->registrarOperacao(
            'Holerite',
            'Holerite gerado manualmente.',
            true,
            [
                'FuncionarioID' => $funcionarioId,
                'MesReferencia' => $mes,
                'AnoReferencia' => $ano,
                'DiasTrabalhados' => $diasTrabalhados,
                'ValorLiquido' => $valorLiquido,
            ],
            'FolhaPagamento',
            (string) $folhaId
        );

        return [
            'FolhaID' => $folhaId,
            'FuncionarioID' => $funcionarioId,
            'MesReferencia' => $mes,
            'AnoReferencia' => $ano,
            'ValorLiquido' => $valorLiquido,
            'DataPagamento' => $dataPagamento,
        ];
    }

    private function obterDataPagamento(int $ano, int $mes): string
    {
        $data = DateTime::createFromFormat('Y-n-j', sprintf('%04d-%d-1', $ano, $mes));
        if ($data === false) {
            return date('Y-m-d');
        }

        $data->modify('last day of this month');
        return $data->format('Y-m-d');
    }

    private function buildSimplePdf(string $title, array $lines): string
    {
        $escape = static function (string $text): string {
            $text = preg_replace('/[^\x20-\x7E]/', '?', $text) ?? '';
            return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
        };

        $content = "BT\n/F1 16 Tf\n72 780 Td\n(" . $escape($title) . ") Tj\n/F1 12 Tf\n0 -24 Td\n";
        foreach ($lines as $line) {
            $content .= "(" . $escape((string) $line) . ") Tj\n0 -18 Td\n";
        }
        $content .= "ET\n";

        $objects = [];
        $objects[] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $objects[] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $objects[] = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>\nendobj\n";
        $objects[] = "4 0 obj\n<< /Length " . strlen($content) . " >>\nstream\n" . $content . "endstream\nendobj\n";
        $objects[] = "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $obj) {
            $offsets[] = strlen($pdf);
            $pdf .= $obj;
        }

        $xrefPos = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefPos}\n%%EOF";

        return $pdf;
    }
}
