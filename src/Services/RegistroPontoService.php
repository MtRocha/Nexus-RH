<?php

declare(strict_types=1);

namespace NexusRH\Services;

use NexusRH\DAO\RegistroPontoDAO;
use NexusRH\Exceptions\ValidationException;
use NexusRH\Support\SessionAuth;

final class RegistroPontoService
{
    private RegistroPontoDAO $registroPontoDAO;

    public function __construct()
    {
        $this->registroPontoDAO = new RegistroPontoDAO();
    }

    public function registrarBatida(): array
    {
        $usuario = SessionAuth::currentUser();
        if ($usuario === null) {
            throw new ValidationException('Usuario nao autenticado.');
        }

        $funcionarioId = (int) $usuario['FuncionarioID'];
        $ultima = $this->registroPontoDAO->buscarUltimaBatidaHoje($funcionarioId);

        $tipoBatida = ($ultima !== null && ($ultima['TipoBatida'] ?? '') === 'Entrada') ? 'Saida' : 'Entrada';
        $pontoId = $this->registroPontoDAO->registrarBatida($funcionarioId, $tipoBatida, 'Web');

        return [
            'PontoID' => $pontoId,
            'FuncionarioID' => $funcionarioId,
            'TipoBatida' => $tipoBatida,
            'DataHoraRegistro' => date('d/m/Y H:i:s'),
        ];
    }

    public function listarEspelho(string $inicio, string $fim): array
    {
        $usuario = SessionAuth::currentUser();
        if ($usuario === null) {
            throw new ValidationException('Usuario nao autenticado.');
        }

        $inicioValido = $this->normalizarData($inicio, 'Data inicial invalida. Use o formato YYYY-MM-DD.');
        $fimValido = $this->normalizarData($fim, 'Data final invalida. Use o formato YYYY-MM-DD.');

        if ($inicioValido > $fimValido) {
            throw new ValidationException('Data inicial nao pode ser maior que a data final.');
        }

        return $this->registroPontoDAO->listarPorPeriodo((int) $usuario['FuncionarioID'], $inicioValido, $fimValido);
    }

    public function gerarEspelhoPdf(string $inicio, string $fim): array
    {
        $usuario = SessionAuth::currentUser();
        if ($usuario === null) {
            throw new ValidationException('Usuario nao autenticado.');
        }

        $inicioValido = $this->normalizarData($inicio, 'Data inicial invalida. Use o formato YYYY-MM-DD.');
        $fimValido = $this->normalizarData($fim, 'Data final invalida. Use o formato YYYY-MM-DD.');

        if ($inicioValido > $fimValido) {
            throw new ValidationException('Data inicial nao pode ser maior que a data final.');
        }

        $registros = $this->registroPontoDAO->listarPorPeriodo((int) $usuario['FuncionarioID'], $inicioValido, $fimValido);

        $inicioFormatado = $this->formatarData($inicioValido);
        $fimFormatado = $this->formatarData($fimValido);
        $titulo = 'Espelho de ponto';

        $linhas = [
            'Periodo: ' . $inicioFormatado . ' a ' . $fimFormatado,
            'Total de batidas: ' . count($registros),
            ' ',
        ];

        foreach ($registros as $registro) {
            $dataHora = $this->formatarDataHora((string) ($registro['DataHoraRegistro'] ?? ''));
            $tipo = (string) ($registro['TipoBatida'] ?? '-');
            $origem = (string) ($registro['Origem'] ?? '-');
            $status = (string) ($registro['StatusAprovacao'] ?? '-');

            $linhas[] = $dataHora . ' | ' . $tipo . ' | ' . $origem . ' | ' . $status;
        }

        $pdf = $this->buildSimplePdf($titulo, $linhas);

        return [
            'filename' => 'espelho-ponto-' . $inicioValido . '-' . $fimValido . '.pdf',
            'content' => $pdf,
        ];
    }

    private function normalizarData(string $data, string $mensagemErro): string
    {
        $data = trim($data);
        if ($data === '') {
            throw new ValidationException($mensagemErro);
        }

        $partes = explode('-', $data);
        if (count($partes) !== 3) {
            throw new ValidationException($mensagemErro);
        }

        if (!checkdate((int) $partes[1], (int) $partes[2], (int) $partes[0])) {
            throw new ValidationException($mensagemErro);
        }

        return sprintf('%04d-%02d-%02d', (int) $partes[0], (int) $partes[1], (int) $partes[2]);
    }

    private function formatarData(string $data): string
    {
        $partes = explode('-', $data);
        if (count($partes) !== 3) {
            return $data;
        }

        return sprintf('%02d/%02d/%04d', (int) $partes[2], (int) $partes[1], (int) $partes[0]);
    }

    private function formatarDataHora(string $dataHora): string
    {
        $dataHora = trim($dataHora);
        if ($dataHora === '') {
            return '-';
        }

        $timestamp = strtotime($dataHora);
        if ($timestamp === false) {
            return $dataHora;
        }

        return date('d/m/Y H:i', $timestamp);
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
