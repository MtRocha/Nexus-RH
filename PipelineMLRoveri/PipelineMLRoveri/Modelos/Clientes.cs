using Microsoft.ML.Data;
using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;

namespace PipelineMLRoveri.Modelos
{
    public class Clientes
    {
        [ColumnName("NR_CPF")] public string NrCpf { get; set; }
        [ColumnName("ID_CLIENTE")] public string IdCliente { get; set; }
        [ColumnName("ETAPA_ATUACAO")] public string EtapaAtuacao { get; set; }

        // Categóricas adicionais da nova query
        [ColumnName("PRODUTO")] public string Produto { get; set; }
        [ColumnName("TP_PESSOA")] public string TpPessoa { get; set; }
        [ColumnName("DIA_CPC")] public string DiaCpc { get; set; }
        [ColumnName("ULT_PA_CPC")] public string UltPaCpc { get; set; }
        [ColumnName("DIA_CE")] public string DiaCe { get; set; }
        [ColumnName("ULT_PA_CE")] public string UltPaCe { get; set; }

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
        [ColumnName("MEDIA_TEMPO_FALANDO_NORMALIZADA")] public float MEDIA_TEMPO_FALANDO_NORMALIZADA { get; }
        [ColumnName("ATRASO_LOG")] public float ATRASO_LOG { get; set; }
        [ColumnName("QTD_ALO_MESES_CONSEC")] public float QtdAloMesesConsec { get; set; }
        [ColumnName("QTD_CPC_MESES_CONSEC")] public float QtdCpcMesesConsec { get; set; }
        [ColumnName("DIAS_DT_CE")] public float DIAS_DT_CE { get; set; }
        [ColumnName("DIAS_DT_CPC")] public float DIAS_DT_CPC { get; set; }
        [ColumnName("HISTORICO_POSITIVO")] public bool HISTORICO_POSITIVO { get; set; }
        [ColumnName("GEROU_CPC")] public bool GerouCpc { get; set; }

    }

    internal class ClientePropensao
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

