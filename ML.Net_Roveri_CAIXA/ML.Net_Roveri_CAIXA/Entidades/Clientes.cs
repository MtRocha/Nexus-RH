using Microsoft.Data.SqlClient;
using Microsoft.ML;
using Microsoft.ML.AutoML;
using Microsoft.ML.Data;
using ML.Net_Roveri_CAIXA.Modelos;
using System;
using System.Data;

namespace ML.Net_Roveri_CAIXA.Modelos
{

    public class Clientes
    {
        [ColumnName("NR_CPF")] public string NrCpf { get; set; }
        [ColumnName("ID_CLIENTE")] public string IdCliente { get; set; }
        [ColumnName("TP_CONTRATO")] public string TpContrato { get; set; }
        [ColumnName("ETAPA_ATUACAO")] public string EtapaAtuacao { get; set; }

        // ===== NUMÉRICAS / FLOATS =====
        [ColumnName("VALOR")] public float Valor { get; set; }
        [ColumnName("VL_TOTAL_DIVIDA")] public float VlTotalDivida { get; set; }
        [ColumnName("PERC_DIVIDA")] public float PercDivida { get; set; }
        [ColumnName("DIAS_ATRASO")] public float DiasAtraso { get; set; }
        [ColumnName("QTD_PARCELAS")] public float QtdParcelas { get; set; }
        [ColumnName("QTD_CTT")] public float QtdCtt { get; set; }
        [ColumnName("QTDE_TEL_CARGA")] public float QtdeTelCarga { get; set; }
        [ColumnName("VL_CONSOLIDADO")] public float VlConsolidado { get; set; }
        [ColumnName("MEDIA_TEMPO_FALANDO_NORMALIZADA")] public float MediaTempoFalandoNormalizada { get; set; }
        [ColumnName("ATRASO_LOG")] public float AtrasoLog { get; set; }
        [ColumnName("QTD_ALO_MESES_CONSEC")] public float QtdAloMesesConsec { get; set; }
        [ColumnName("MEDIA_TEMPO_FALANDO")] public float MediaTempoFalando { get; set; }
        [ColumnName("DIAS_DT_CPC")] public float DiasDtCpc { get; set; }
        [ColumnName("DIAS_DT_CE")] public float DiasDtCe { get; set; }
        [ColumnName("QTD_CPC_MESES_CONSEC")] public float QtdCpcMesesConsec { get; set; }

        [ColumnName("TOTALTENTATIVAS")] public float TotalTentativas { get; set; }
        [ColumnName("DIASCOMTENTATIVA")] public float DiasComTentativa { get; set; }
        [ColumnName("MEDIATENTATIVASDIA")] public float MediaTentativasDia { get; set; }
        [ColumnName("RAZAO_VALOR_DIVIDA")] public float RazaoValorDivida { get; set; }
        [ColumnName("RAZAO_PARCELA_DIVIDA")] public float RazaoParcelaDivida { get; set; }
        [ColumnName("RAZAO_CTT_TEL")] public float RazaoCttTel { get; set; }
        [ColumnName("RAZAO_TENTATIVA_DIA")] public float RazaoTentativaDia { get; set; }
        [ColumnName("RAZAO_VALOR_CONSOLIDADO")] public float RazaoValorConsolidado { get; set; }
        [ColumnName("VALOR_MEDIO_PARCELA")] public float ValorMedioParcela { get; set; }
        [ColumnName("VALOR_POR_CONTATO")] public float ValorPorContato { get; set; }
        [ColumnName("DIVIDA_POR_CONTATO")] public float DividaPorContato { get; set; }
        [ColumnName("DIVIDA_POR_PARCELA")] public float DividaPorParcela { get; set; }
        [ColumnName("RAZAO_TENTATIVA_CONTATO")] public float RazaoTentativaContato { get; set; }
        [ColumnName("RAZAO_TENTATIVA_TEL")] public float RazaoTentativaTel { get; set; }
        [ColumnName("TENTATIVA_POR_DIVIDA")] public float TentativaPorDivida { get; set; }
        [ColumnName("CONTATO_POR_DIA_ATRASO")] public float ContatoPorDiaAtraso { get; set; }
        [ColumnName("VALOR_POR_DIA_ATRASO")] public float ValorPorDiaAtraso { get; set; }
        [ColumnName("PARCELA_POR_DIA_ATRASO")] public float ParcelaPorDiaAtraso { get; set; }
        [ColumnName("RAZAO_DIVIDA_CONSOLIDADO")] public float RazaoDividaConsolidado { get; set; }
        [ColumnName("TAXA_CONTATO_EFETIVO")] public float TaxaContatoEfetivo { get; set; }
        [ColumnName("PERC_DIAS_COM_TENTATIVA")] public float PercDiasComTentativa { get; set; }
        [ColumnName("CONTATOS_POR_ATRASO_PERC")] public float ContatosPorAtrasoPerc { get; set; }
        [ColumnName("PERC_DIVIDA_CONSOLIDADO")] public float PercDividaConsolidado { get; set; }
        [ColumnName("INTENSIDADE_TENTATIVA_TEL")] public float IntensidadeTentativaTel { get; set; }
        [ColumnName("RECENCIA_MIN")] public float RecenciaMin { get; set; }
        [ColumnName("RECENCIA_MAX")] public float RecenciaMax { get; set; }
        [ColumnName("MEDIA_RECENCIA")] public float MediaRecencia { get; set; }
        [ColumnName("LOG_VALOR")] public float LogValor { get; set; }
        [ColumnName("LOG_DIVIDA")] public float LogDivida { get; set; }
        [ColumnName("LOG_QTD_CTT")] public float LogQtdCtt { get; set; }
        [ColumnName("LOG_MEDIA_TEMPO_FAL")] public float LogMediaTempoFal { get; set; }
        [ColumnName("SCORE_REATIVACAO")] public float ScoreReativacao { get; set; }
        [ColumnName("SCORE_CONTATO_RECENTE")] public float ScoreContatoRecente { get; set; }
        [ColumnName("SCORE_DISPONIBILIDADE")] public float ScoreDisponibilidade { get; set; }
        [ColumnName("PERC_TEMPO_FAL_NORM")] public float PercTempoFalNorm { get; set; }
        [ColumnName("PERC_DIVIDA_NORM")] public float PercDividaNorm { get; set; }

        // ===== CATEGÓRICAS =====
        [ColumnName("PRODUTO")] public string Produto { get; set; }
        [ColumnName("TP_PESSOA")] public string TpPessoa { get; set; }
        [ColumnName("COD_UF")] public string CodUf { get; set; }
        [ColumnName("GRUPO")] public string Grupo { get; set; }
        [ColumnName("DT_PRESTACAO")] public string DtPrestacao { get; set; }
        [ColumnName("PERIODO_PARCELA")] public string PeriodoParcela { get; set; }
        [ColumnName("DIA_CPC")] public string DiaCpc { get; set; }
        [ColumnName("DIA_CE")] public string DiaCe { get; set; }
        [ColumnName("ULT_DT_CPC")] public string UltDtCpc { get; set; }
        [ColumnName("ULT_PA_CPC")] public string UltPaCpc { get; set; }
        [ColumnName("ULT_TAB_CPC")] public string UltTabCpc { get; set; }
        [ColumnName("ULT_DT_CE")] public string UltDtCe { get; set; }
        [ColumnName("ULT_PA_CE")] public string UltPaCe { get; set; }
        [ColumnName("ULT_TAB_CE")] public string UltTabCe { get; set; }
        [ColumnName("FAIXA_TENTATIVA_DIA")] public string FaixaTentativaDia { get; set; }
        [ColumnName("FAIXA_ATRASO")] public string FaixaAtraso { get; set; }

        // ===== BOOLEANAS =====
        [ColumnName("POSSUI_COOBRIGADO")] public bool PossuiCoobrigado { get; set; }
        [ColumnName("HISTORICO_POSITIVO")] public bool HistoricoPositivo { get; set; }
        [ColumnName("GEROU_CPC")] public bool GerouCpc { get; set; }
    }
    public class ClientePropensao
    {
        [ColumnName("PredictedLabel")]
        public bool PredictedLabel { get; set; }

        [ColumnName("Probability")]
        public float Probability { get; set; }

        [ColumnName("Score")]
        public float Score { get; set; }

        [ColumnName("NR_CPF")]
        public string Cpf { get; set; }

        [ColumnName("ID_CLIENTE")]
        public string IdCliente { get; set; }
    }
}
