using Microsoft.ML;
using Microsoft.ML.Data;
using PipelineMLRoveri.DAO;
using PipelineMLRoveri.Modelos;
using System;
using System.Collections.Generic;
using System.Data;
using Microsoft.Data.SqlClient;
using System.IO;
using System.Linq;
using System.Reflection;

namespace CamadaNegocio.ML
{
    internal class ModeloCarteira
    {
        public string Modelo { get; set; }
        public string Carteira { get; set; }
        public float Threshold { get; set; }

        public ModeloCarteira(string modelo, string carteira, float threshold)
        {
            Modelo = modelo;
            Carteira = carteira;
            Threshold = threshold;
        }

    }
    public class MachineLearning
    {

        public static void CalcularPropensao()
        {
            try
            {

                DAL_PROC_ROVERI dao = new DAL_PROC_ROVERI();

                SqlCommand truncateScoreCmd = new SqlCommand("TRUNCATE TABLE  [DB_PROC_ROVERI].[dbo].[TBL_CARTEIRA_ROVERI_SCORE]");
                SqlCommand atualizacaoBase = new SqlCommand("SP_ATUALIZA_BASE_ML");
                SqlCommand baseCmd = new SqlCommand("SP_GERA_BASE_PROPENSAO");
                baseCmd.CommandType = CommandType.StoredProcedure;
                atualizacaoBase.CommandType = CommandType.StoredProcedure;
                dao.ConsultaSQL(truncateScoreCmd);
                //dao.ConsultaSQL(atualizacaoBase);

                List<ModeloCarteira> ModeloCarteiraList = new List<ModeloCarteira>();

                ModeloCarteiraList.Add(new ModeloCarteira("MODELO_CARTAO_OVERSAMPLING_SdcaLogisticRegressionBinary.zip", "K", 0.8f));
                ModeloCarteiraList.Add(new ModeloCarteira("MODELO_COMER_OVERSAMPLING_LightGbmBinary.zip", "C", 0.6f));
                ModeloCarteiraList.Add(new ModeloCarteira("MODELO_HABIT_OVERSAMPLING_LightGbmBinary.zip", "H", 0.6f));

                foreach (var modelo in ModeloCarteiraList)
                {
                    baseCmd.Parameters.Clear();
                    baseCmd.Parameters.AddWithValue("@TP_CONTRATO", modelo.Carteira);
                    var modelPath = Path.Combine(
                        @"C:\Processo\36 - Machine Learning\Modelos\",
                        modelo.Modelo
                    );

                    var ml = new MLContext(seed: 1);
                    DataViewSchema modelSchema;
                    var trainedModel = ml.Model.Load(modelPath, out modelSchema);
                    DataTable carteiraBase = dao.ConsultaSQL(baseCmd).Tables[0]; 
                    var loader = ml.Data.CreateDatabaseLoader<Clientes>();
                    List<Clientes> carteiraBaseList = ToClientesList(carteiraBase);
                    IDataView data = ml.Data.LoadFromEnumerable(carteiraBaseList);
                    var predictions = trainedModel.Transform(data);
                    var schema = predictions.Schema;
                    bool hasProbability = schema.Any(c => c.Name == "Probability");

                    //// === Importância das Features (PFI) no fluxo de scoring ===
                    //CalcularImportanciaDasFeatures(ml, trainedModel, predictions, labelColumnName: "GEROU_CPC", permutationCount: 30, topK: 20);

                    var preAjustData = ml.Data.CreateEnumerable<ClientePropensao>(
                        predictions,
                        reuseRowObject: false,
                        ignoreMissingColumns: true

                    ).ToList();

                    var scored = preAjustData.Select(p => new ClientePropensao
                    {
                        Score = p.Score,
                        Probability = p.Probability,
                        PredictedLabel = p.Probability > modelo.Threshold,
                        Cpf = p.Cpf,
                        IdCliente = p.IdCliente
                    }).ToList();

                    InsertScoredResults(scored, "TBL_CARTEIRA_ROVERI_SCORE");

                }
            }
            catch (Exception ex)
            {
                CalcularPropensao();
                //Telegram
                CamadaDados.TELEGRAM tel = new CamadaDados.TELEGRAM();
                //tel.SendTelegram($"Erro no Processo de Scoragem ML - ConverterDataTable() : {ex.Message.ToString()}");
            }
        }

        public static List<Clientes> ToClientesList(DataTable table)
        {
            try
            {
                var lista = new List<Clientes>();

                foreach (DataRow row in table.Rows)
                {
                    var c = new Clientes
                    {
                        NrCpf = row["NR_CPF"]?.ToString(),
                        IdCliente = row["ID_CLIENTE"]?.ToString(),
                        EtapaAtuacao = row["ETAPA_ATUACAO"]?.ToString(),
                        Produto = row["PRODUTO"]?.ToString(),
                        TpPessoa = row["TP_PESSOA"]?.ToString(),
                        DiaCpc = row["DIA_CPC"]?.ToString(),
                        UltPaCpc = row["ULT_PA_CPC"]?.ToString(),
                        DiaCe = row["DIA_CE"]?.ToString(),
                        UltPaCe = row["ULT_PA_CE"]?.ToString(),
                        QtdCtt = Convert.ToSingle(row["QTD_CTT"] ?? 0),
                        QtdeTelCarga = Convert.ToSingle(row["QTDE_TEL_CARGA"] ?? 0),
                        Valor = Convert.ToSingle(row["VALOR"] ?? 0),
                        ValorTotalDivida = Convert.ToSingle(row["VL_TOTAL_DIVIDA"] ?? 0),
                        PercDivida = Convert.ToSingle(row["PERC_DIVIDA"] ?? 0),
                        DiasAtraso = Convert.ToSingle(row["DIAS_ATRASO"] ?? 0),
                        QtdParcelas = Convert.ToSingle(row["QTD_PARCELAS"] ?? 0),
                        PossuiCoobrigado = Convert.ToBoolean(row["POSSUI_COOBRIGADO"] ?? false),
                        VlConsolidado = Convert.ToSingle(row["VL_CONSOLIDADO"] ?? 0),
                        ATRASO_LOG = Convert.ToSingle(row["ATRASO_LOG"] ?? 0),
                        QtdAloMesesConsec = Convert.ToSingle(row["QTD_ALO_MESES_CONSEC"] ?? 0),
                        QtdCpcMesesConsec = Convert.ToSingle(row["QTD_CPC_MESES_CONSEC"] ?? 0),
                        DIAS_DT_CE = Convert.ToSingle(row["DIAS_DT_CE"] ?? 0),
                        DIAS_DT_CPC = Convert.ToSingle(row["DIAS_DT_CPC"] ?? 0),
                        HISTORICO_POSITIVO = Convert.ToBoolean(row["HISTORICO_POSITIVO"] ?? false),
                        GerouCpc = Convert.ToBoolean(row["GEROU_CPC"] ?? false)
                    };

                    lista.Add(c);
                }

                return lista;

            }
            catch (Exception ex)
            {
                //Telegram
                CamadaDados.TELEGRAM tel = new CamadaDados.TELEGRAM();
                //tel.SendTelegram($"Erro no Processo de Scoragem ML - ConverterDataTable() : {ex.Message.ToString()}");
                return null;
            }
        }

        

        private static void InsertScoredResults(List<ClientePropensao> scored, string tableName)
        {
            try
            {

            if (scored == null || scored.Count == 0)
            {
                Console.WriteLine("Nenhum registro para inserir.");
                return;
            }

            DAL_PROC_ROVERI dao = new DAL_PROC_ROVERI();

            var dt = new DataTable();
            dt.Columns.Add("NR_CPF", typeof(string));
            dt.Columns.Add("ID_CLIENTE", typeof(string));
            dt.Columns.Add("SCORE", typeof(float));
            dt.Columns.Add("PROBABILITY", typeof(float));
            dt.Columns.Add("PREDICTED_LABEL", typeof(bool));

            foreach (var r in scored)
            {
                dt.Rows.Add(r.Cpf, r.IdCliente, r.Score, r.Probability, r.PredictedLabel);
            }

            dao.ExecutaBulkCopySQL(dt,tableName);

            Console.WriteLine($"Inseridos {dt.Rows.Count} registros em {tableName}.");
            }
            catch (Exception ex)
            {
                //Telegram
                CamadaDados.TELEGRAM tel = new CamadaDados.TELEGRAM();
                //tel.SendTelegram($"Erro no Processo de Scoragem ML - InsertScoredResults() : {ex.Message.ToString()}");
            }
        }

    }
}
