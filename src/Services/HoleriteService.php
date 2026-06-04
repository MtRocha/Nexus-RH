<?php

declare(strict_types=1);

namespace NexusRH\Services;

use NexusRH\DAO\HoleriteDAO;
use NexusRH\Exceptions\BusinessRuleException;
use NexusRH\Exceptions\ValidationException;
use NexusRH\Support\SessionAuth;

final class HoleriteService
{
    private HoleriteDAO $holeriteDAO;

    public function __construct()
    {
        $this->holeriteDAO = new HoleriteDAO();
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
