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

        // Categóricas adicionais da nova query
        [ColumnName("PRODUTO")] public string Produto { get; set; }                     
        [ColumnName("TP_PESSOA")] public string TpPessoa { get; set; }
        [ColumnName("COD_UF")] public string CodUf { get; set; }
        [ColumnName("GRUPO")] public string Grupo { get; set; }
        [ColumnName("DT_PRESTACAO")] public string DtPrestacao { get; set; }
        [ColumnName("PERIODO_PARCELA")] public string PeriodoParcela { get; set; }
        [ColumnName("DIA_CPC")] public string DiaCpc { get; set; }
        [ColumnName("ULT_DT_CPC")] public string UltDtCpc { get; set; }
        [ColumnName("ULT_PA_CPC")] public string UltPaCpc { get; set; }
        [ColumnName("ULT_TAB_CPC")] public string UltTabCpc { get; set; }
        [ColumnName("DIA_CE")] public string DiaCe { get; set; }
        [ColumnName("ULT_DT_CE")] public string UltDtCe { get; set; }
        [ColumnName("ULT_PA_CE")] public string UltPaCe { get; set; }
        [ColumnName("ULT_TAB_CE")] public string UltTabCe { get; set; }

        // Numéricas/booleanas da nova query
        [ColumnName("QTD_CTT")] public float QtdCtt { get; set; }
        [ColumnName("QTDE_TEL_CARGA")] public float QtdeTelCarga { get; set; }
        [ColumnName("VALOR")] public float Valor { get; set; }
        [ColumnName("VL_TOTAL_DIVIDA")] public float ValorTotalDivida { get; set; }
        [ColumnName("PERC_DIVIDA")] public float PercDivida { get; set; }
        [ColumnName("DIAS_ATRASO")] public float DiasAtraso { get; set; }
        [ColumnName("QTD_PARCELAS")] public float QtdParcelas { get; set; }
        [ColumnName("POSSUI_COOBRIGADO")] public bool PossuiCoobrigado { get; set; }
        [ColumnName("VL_CONSOLIDADO")] public float VlConsolidado { get; set; }
        [ColumnName("RAZAO_TEL_CTT")] public float RAZAO_TEL_CTT {get; set;}
        [ColumnName("MEDIA_TEMPO_FALANDO_NORMALIZADA")] public float MEDIA_TEMPO_FALANDO_NORMALIZADA {get;}
        [ColumnName("ATRASO_LOG")] public float ATRASO_LOG { get; set; }
        [ColumnName("QTD_ALO_MESES_CONSEC")] public float QtdAloMesesConsec { get; set; }
        [ColumnName("MEDIA_TEMPO_FALANDO")] public float MediaTempoFalando { get; set; }
        [ColumnName("QTD_CPC_MESES_CONSEC")] public float QtdCpcMesesConsec { get; set; }
        [ColumnName("DIAS_DT_CE")] public float DIAS_DT_CE { get; set; }
        [ColumnName("DIAS_DT_CPC")] public float DIAS_DT_CPC { get; set; }
        [ColumnName("HISTORICO_POSITIVO")] public bool HISTORICO_POSITIVO { get; set; }
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
