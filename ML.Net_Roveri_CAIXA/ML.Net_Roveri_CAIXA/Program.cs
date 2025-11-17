using Microsoft.Data.SqlClient;
using Microsoft.ML;
using Microsoft.ML.AutoML;
using Microsoft.ML.Data;
using ML.Net_Roveri_CAIXA.Modelos;
using ScottPlot;
using System.Data;
using System.Linq; // <-- adicionado

public class Program
{
    public static void Main(string[] args)
    {

        var ml = new MLContext(seed: 1);

        string strConexao = "Server=172.20.1.248; Database=DB_PROC_ROVERI; User Id=SisIntranet; Password=_P@ssw0rdEX!T0;TrustServerCertificate=True; Max Pool Size=600;Connection Timeout=540";

        string cmd = @"
SELECT 
    CAST([NR_CPF] AS VARCHAR(20)) AS [NR_CPF],
    CAST([ID_CLIENTE] AS VARCHAR(20)) AS [ID_CLIENTE],

    -- ==========================
    -- 🧩 CATEGÓRICAS
    -- ==========================
    CAST([TP_CONTRATO] AS VARCHAR(50)) AS [TP_CONTRATO],
    CAST([ETAPA_ATUACAO] AS VARCHAR(50)) AS [ETAPA_ATUACAO],
    CAST([PRODUTO] AS VARCHAR(50)) AS [PRODUTO],
    CAST([TP_PESSOA] AS VARCHAR(50)) AS [TP_PESSOA],
    CAST([COD_UF] AS VARCHAR(10)) AS [COD_UF],
    CAST([GRUPO] AS VARCHAR(50)) AS [GRUPO],
    CAST([DIA_CPC] AS VARCHAR(20)) AS [DIA_CPC],
    CAST([ULT_PA_CPC] AS VARCHAR(20)) AS [ULT_PA_CPC],
    CAST([DIA_CE] AS VARCHAR(20)) AS [DIA_CE],
    CAST([ULT_PA_CE] AS VARCHAR(20)) AS [ULT_PA_CE],
    CAST([FAIXA_TENTATIVA_DIA] AS VARCHAR(50)) AS [FAIXA_TENTATIVA_DIA],
    CAST([FAIXA_ATRASO] AS VARCHAR(50)) AS [FAIXA_ATRASO],

    -- ==========================
    -- 🔢 NUMÉRICAS
    -- ==========================
    CAST(ISNULL([QTD_CTT],0) AS REAL) AS [QTD_CTT],
    CAST(ISNULL([QTDE_TEL_CARGA],0) AS REAL) AS [QTDE_TEL_CARGA],
    CAST(ISNULL([VALOR],0) AS REAL) AS [VALOR],
    CAST(ISNULL([VL_TOTAL_DIVIDA],0) AS REAL) AS [VL_TOTAL_DIVIDA],
    CAST(ISNULL([PERC_DIVIDA],0) AS REAL) AS [PERC_DIVIDA],
    CAST(ISNULL([DIAS_ATRASO],0) AS REAL) AS [DIAS_ATRASO],
    CAST(ISNULL([QTD_PARCELAS],0) AS REAL) AS [QTD_PARCELAS],
    CAST(ISNULL([VL_CONSOLIDADO],0) AS REAL) AS [VL_CONSOLIDADO],
    CAST(ISNULL([ATRASO_LOG],0) AS REAL) AS [ATRASO_LOG],
    CAST(ISNULL([QTD_ALO_MESES_CONSEC],0) AS REAL) AS [QTD_ALO_MESES_CONSEC],
    CAST(ISNULL([MEDIA_TEMPO_FALANDO],0) AS REAL) AS [MEDIA_TEMPO_FALANDO],
    CAST(ISNULL([MEDIA_TEMPO_FALANDO_NORMALIZADA],0) AS REAL) AS [MEDIA_TEMPO_FALANDO_NORMALIZADA],
    CAST(ISNULL([QTD_CPC_MESES_CONSEC],0) AS REAL) AS [QTD_CPC_MESES_CONSEC],
    CAST(ISNULL([DIAS_DT_CE],0) AS REAL) AS [DIAS_DT_CE],
    CAST(ISNULL([DIAS_DT_CPC],0) AS REAL) AS [DIAS_DT_CPC],
    CAST(ISNULL([HISTORICO_POSITIVO],0) AS BIT) AS [HISTORICO_POSITIVO],
    CAST(ISNULL([GEROU_CPC],0) AS BIT) AS [GEROU_CPC],
    CAST(ISNULL([POSSUI_COOBRIGADO],0) AS BIT) AS [POSSUI_COOBRIGADO],

    -- ==========================
    -- 📊 FEATURES DERIVADAS
    -- ==========================
    CAST(ISNULL([RAZAO_TEL_CTT],0) AS REAL) AS [RAZAO_TEL_CTT],
    CAST(ISNULL([RAZAO_VALOR_DIVIDA],0) AS REAL) AS [RAZAO_VALOR_DIVIDA],
    CAST(ISNULL([RAZAO_PARCELA_DIVIDA],0) AS REAL) AS [RAZAO_PARCELA_DIVIDA],
    CAST(ISNULL([RAZAO_CTT_TEL],0) AS REAL) AS [RAZAO_CTT_TEL],
    CAST(ISNULL([RAZAO_TENTATIVA_DIA],0) AS REAL) AS [RAZAO_TENTATIVA_DIA],
    CAST(ISNULL([RAZAO_VALOR_CONSOLIDADO],0) AS REAL) AS [RAZAO_VALOR_CONSOLIDADO],
    CAST(ISNULL([VALOR_MEDIO_PARCELA],0) AS REAL) AS [VALOR_MEDIO_PARCELA],
    CAST(ISNULL([VALOR_POR_CONTATO],0) AS REAL) AS [VALOR_POR_CONTATO],
    CAST(ISNULL([DIVIDA_POR_CONTATO],0) AS REAL) AS [DIVIDA_POR_CONTATO],
    CAST(ISNULL([DIVIDA_POR_PARCELA],0) AS REAL) AS [DIVIDA_POR_PARCELA],
    CAST(ISNULL([TENTATIVA_POR_DIVIDA],0) AS REAL) AS [TENTATIVA_POR_DIVIDA],
    CAST(ISNULL([CONTATO_POR_DIA_ATRASO],0) AS REAL) AS [CONTATO_POR_DIA_ATRASO],
    CAST(ISNULL([VALOR_POR_DIA_ATRASO],0) AS REAL) AS [VALOR_POR_DIA_ATRASO],
    CAST(ISNULL([PARCELA_POR_DIA_ATRASO],0) AS REAL) AS [PARCELA_POR_DIA_ATRASO],
    CAST(ISNULL([RAZAO_DIVIDA_CONSOLIDADO],0) AS REAL) AS [RAZAO_DIVIDA_CONSOLIDADO],
    CAST(ISNULL([TAXA_CONTATO_EFETIVO],0) AS REAL) AS [TAXA_CONTATO_EFETIVO],
    CAST(ISNULL([PERC_DIAS_COM_TENTATIVA],0) AS REAL) AS [PERC_DIAS_COM_TENTATIVA],
    CAST(ISNULL([CONTATOS_POR_ATRASO_PERC],0) AS REAL) AS [CONTATOS_POR_ATRASO_PERC],
    CAST(ISNULL([PERC_DIVIDA_CONSOLIDADO],0) AS REAL) AS [PERC_DIVIDA_CONSOLIDADO],
    CAST(ISNULL([INTENSIDADE_TENTATIVA_TEL],0) AS REAL) AS [INTENSIDADE_TENTATIVA_TEL],
    CAST(ISNULL([RECENCIA_MIN],0) AS REAL) AS [RECENCIA_MIN],
    CAST(ISNULL([RECENCIA_MAX],0) AS REAL) AS [RECENCIA_MAX],
    CAST(ISNULL([MEDIA_RECENCIA],0) AS REAL) AS [MEDIA_RECENCIA],
    CAST(ISNULL([LOG_VALOR],0) AS REAL) AS [LOG_VALOR],
    CAST(ISNULL([LOG_DIVIDA],0) AS REAL) AS [LOG_DIVIDA],
    CAST(ISNULL([LOG_QTD_CTT],0) AS REAL) AS [LOG_QTD_CTT],
    CAST(ISNULL([LOG_MEDIA_TEMPO_FAL],0) AS REAL) AS [LOG_MEDIA_TEMPO_FAL],
    CAST(ISNULL([SCORE_REATIVACAO],0) AS REAL) AS [SCORE_REATIVACAO],
    CAST(ISNULL([SCORE_CONTATO_RECENTE],0) AS REAL) AS [SCORE_CONTATO_RECENTE],
    CAST(ISNULL([SCORE_DISPONIBILIDADE],0) AS REAL) AS [SCORE_DISPONIBILIDADE],
    CAST(ISNULL([PERC_TEMPO_FAL_NORM],0) AS REAL) AS [PERC_TEMPO_FAL_NORM],
    CAST(ISNULL([PERC_DIVIDA_NORM],0) AS REAL) AS [PERC_DIVIDA_NORM],
    CAST(ISNULL([TOTALTENTATIVAS],0) AS REAL) AS [TOTALTENTATIVAS],
    CAST(ISNULL([DIASCOMTENTATIVA],0) AS REAL) AS [DIASCOMTENTATIVA], 
    CAST(ISNULL([MEDIATENTATIVASDIA],0) AS REAL) AS [MEDIATENTATIVASDIA],
    CAST([RAZAO_TENTATIVA_CONTATO] AS REAL) AS [RAZAO_TENTATIVA_CONTATO],
    CAST([RAZAO_TENTATIVA_TEL] AS REAL) AS [RAZAO_TENTATIVA_TEL],
    CAST([ULT_DT_CPC] AS VARCHAR(20)) AS [ULT_DT_CPC],
    CAST([ULT_TAB_CPC] AS VARCHAR(50)) AS [ULT_TAB_CPC],
    CAST([ULT_DT_CE] AS VARCHAR(20)) AS [ULT_DT_CE],
    CAST([ULT_TAB_CE] AS VARCHAR(50)) AS [ULT_TAB_CE],
    CAST([DT_PRESTACAO] AS VARCHAR(20)) AS [DT_PRESTACAO],
    CAST([PERIODO_PARCELA] AS VARCHAR(50)) AS [PERIODO_PARCELA]


FROM [DB_PROC_ROVERI].[dbo].[TBL_TESTE_ML]
    WHERE TP_CONTRATO IN ('C')
";


        string[] categoricalColumns = {
            "TP_CONTRATO",
            "ETAPA_ATUACAO",
            "PRODUTO",
            "TP_PESSOA",
            "COD_UF",
            "GRUPO",
            "DIA_CPC",
            "ULT_PA_CPC",
            "DIA_CE",
            "ULT_PA_CE",
            "FAIXA_TENTATIVA_DIA",
            "FAIXA_ATRASO"
        };

        string[] numericColumns = {
            "[TOTALTENTATIVAS]",
            "QTD_CTT",
            "QTDE_TEL_CARGA",
            "VALOR",
            "VL_TOTAL_DIVIDA",
            "PERC_DIVIDA",
            "DIAS_ATRASO",
            "QTD_PARCELAS",
            "VL_CONSOLIDADO",
            "ATRASO_LOG",
            "QTD_ALO_MESES_CONSEC",
            "MEDIA_TEMPO_FALANDO",
            "MEDIA_TEMPO_FALANDO_NORMALIZADA",
            "QTD_CPC_MESES_CONSEC",
            "DIAS_DT_CE",
            "DIAS_DT_CPC",
            "HISTORICO_POSITIVO",
            "GEROU_CPC",
            "POSSUI_COOBRIGADO",
            "RAZAO_TEL_CTT",
            "RAZAO_VALOR_DIVIDA",
            "RAZAO_PARCELA_DIVIDA",
            "RAZAO_CTT_TEL",
            "RAZAO_TENTATIVA_DIA",
            "RAZAO_VALOR_CONSOLIDADO",
            "VALOR_MEDIO_PARCELA",
            "VALOR_POR_CONTATO",
            "DIVIDA_POR_CONTATO",
            "DIVIDA_POR_PARCELA",
            "TENTATIVA_POR_DIVIDA",
            "CONTATO_POR_DIA_ATRASO",
            "VALOR_POR_DIA_ATRASO",
            "PARCELA_POR_DIA_ATRASO",
            "RAZAO_DIVIDA_CONSOLIDADO",
            "TAXA_CONTATO_EFETIVO",
            "PERC_DIAS_COM_TENTATIVA",
            "CONTATOS_POR_ATRASO_PERC",
            "PERC_DIVIDA_CONSOLIDADO",
            "INTENSIDADE_TENTATIVA_TEL",
            "RECENCIA_MIN",
            "RECENCIA_MAX",
            "MEDIA_RECENCIA",
            "LOG_VALOR",
            "LOG_DIVIDA",
            "LOG_QTD_CTT",
            "LOG_MEDIA_TEMPO_FAL",
            "SCORE_REATIVACAO",
            "SCORE_CONTATO_RECENTE",
            "SCORE_DISPONIBILIDADE",
            "PERC_TEMPO_FAL_NORM",
            "PERC_DIVIDA_NORM"
};
    


        string interacao, modelName;
        Console.WriteLine("O que deseja Fazer ? \n1- Treinar um novo modelo.\n2- Utilizar um Modelo Existente");
        interacao = Console.ReadLine();
        
        switch(interacao)
        {
            case "1":
                Console.WriteLine("Qual o Nome do Novo Modelo ?");
                modelName = Console.ReadLine(); ;
                TreinarModelo(cmd, strConexao, categoricalColumns, numericColumns, modelName);
                Console.WriteLine("Iniciando o Treinamento de um novo modelo...");
                break;

            case "2":
                Console.WriteLine("Qual o Nome Modelo ?");
                modelName = Console.ReadLine(); 
                Console.WriteLine("Qual o Threshold aplicado ?");
                float threshold = float.Parse(Console.ReadLine()); 
                CalcularPropensao(cmd, strConexao, categoricalColumns, numericColumns, modelName,threshold);
                break;
        }
    }

    public static void CalcularPropensao(string cmd, string strConexao, string[] categoricalColumns, string[] numericColumns,string modelName,float threshold)
    {
        SqlCommand command = new SqlCommand("TRUNCATE TABLE  [DB_PROC_ROVERI].[dbo].[TBL_CARTEIRA_ROVERI_SCORE]");
        using (SqlConnection connection = new SqlConnection(strConexao))
        {
            command.Connection = connection;
            connection.Open();
            command.ExecuteNonQuery();
            connection.Close();
        }

        var modelPath = Path.Combine(
            @"C:\Users\mathrocsilva\source\repos\ML.Net_Roveri_CAIXA\ML.Net_Roveri_CAIXA\Modelo",
            modelName
        );

        var ml = new MLContext(seed: 1);

        DataViewSchema modelSchema;
        ITransformer trainedModel = ml.Model.Load(modelPath, out modelSchema);
        Console.WriteLine($"Modelo Carregado :{modelName}");

        var loader = ml.Data.CreateDatabaseLoader<Clientes>();
        var dbSource = new DatabaseSource(SqlClientFactory.Instance, strConexao, cmd);
        IDataView data = loader.Load(dbSource);
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
            PredictedLabel = p.Probability > threshold,
            Cpf = p.Cpf,
            IdCliente = p.IdCliente
        }).ToList();

        var probs = scored.Select(p => p.Probability);
        Console.WriteLine($"Média: {probs.Average():F4}");
        Console.WriteLine($"Mínimo: {probs.Min():F4}");
        Console.WriteLine($"Máximo: {probs.Max():F4}");


        var thresholds = Enumerable.Range(0, 101).Select(x => x / 100.0f).ToArray();
        var f1Scores = new double[thresholds.Length];
        var precisionScores = new double[thresholds.Length];
        var recallScores = new double[thresholds.Length];

        var yTrue = scored.Select(p => p.PredictedLabel).ToArray();
        var yProb = scored.Select(p => p.Probability).ToArray();

        GeraGraficoMetricas(thresholds, f1Scores, precisionScores, recallScores, yTrue, yProb);
        GerarLiftChart(scored, "LiftChart_CarteiraTotal.png");

        // Insere resultados no banco
        InsertScoredResults(scored, strConexao, "TBL_CARTEIRA_ROVERI_SCORE");

        Console.WriteLine("Processo concluído.");
    }

    public static void TreinarModelo(
        string cmd,
        string strConexao,
        string[] categoricalColumns,
        string[] numericColumns,
        string modelName)
    {
        var ml = new MLContext(seed: 1);

        // ==========================
        // 🔹 Carrega dados do banco
        // ==========================
        var loader = ml.Data.CreateDatabaseLoader<Clientes>();
        var dbSource = new DatabaseSource(SqlClientFactory.Instance, strConexao, cmd);
        IDataView data = loader.Load(dbSource);

        
        var dataEnumerable = ml.Data.CreateEnumerable<Clientes>(data, reuseRowObject: false).ToList();

        // ==========================
        // 🔹 Oversampling manual
        // ==========================
        var positivos = dataEnumerable.Where(x => x.GerouCpc).ToList();
        var negativos = dataEnumerable.Where(x => !x.GerouCpc).ToList();

        if (!positivos.Any())
            throw new InvalidOperationException("Não há exemplos positivos no dataset.");

        var rnd = new Random();
        int replicateCount = (int)Math.Ceiling((double)negativos.Count / positivos.Count);
        var positivosAumentados = new List<Clientes>();

        for (int i = 0; i < replicateCount; i++)
            positivosAumentados.AddRange(positivos.OrderBy(x => rnd.Next()));

        if (positivosAumentados.Count > negativos.Count)
            positivosAumentados = positivosAumentados.Take(negativos.Count).ToList();

        var balanceado = negativos.Concat(positivosAumentados).OrderBy(x => rnd.Next()).ToList();
        var balancedDataView = ml.Data.LoadFromEnumerable(balanceado);

        // ==========================
        // 🔹 Split treino/teste
        // ==========================
        var split = ml.Data.TrainTestSplit(balancedDataView, testFraction: 0.2);

        // Salva dataset de teste
        string testSetPath = Path.Combine(Environment.CurrentDirectory, $"{modelName}_TestSet.csv");
        using (var fileStream = File.Create(testSetPath))
            ml.Data.SaveAsText(split.TestSet, fileStream, separatorChar: ',', headerRow: true, schema: true);

        Console.WriteLine($"💾 Dataset de teste salvo em: {testSetPath}");

        // ==========================
        // 🔹 Pipeline de transformação
        // ==========================
        var processPipeline = ml.Transforms.Categorical.OneHotEncoding(
                categoricalColumns.Select(c => new InputOutputColumnPair($"{c}_enc", c)).ToArray())
            .Append(ml.Transforms.NormalizeMeanVariance(
                numericColumns.Select(c => new InputOutputColumnPair($"{c}_scl", c)).ToArray()))
            .Append(ml.Transforms.Concatenate("Features",
                categoricalColumns.Select(c => $"{c}_enc")
                .Concat(numericColumns.Select(c => $"{c}_scl"))
                .Concat(new[] { "POSSUI_COOBRIGADO", "HISTORICO_POSITIVO" })
                .ToArray()))
            .Append(ml.Transforms.CopyColumns("Cpf", "NR_CPF"))
            .Append(ml.Transforms.CopyColumns("IdCliente", "ID_CLIENTE"));

        // ==========================
        // 🔹 AutoML Binary Classification
        // ==========================
        var experimentSettings = new BinaryExperimentSettings
        {
            MaxExperimentTimeInSeconds = 120000,
            OptimizingMetric = BinaryClassificationMetric.PositiveRecall
        };

        var experiment = ml.Auto().CreateBinaryClassificationExperiment(experimentSettings);
        var result = experiment.Execute(split.TrainSet, labelColumnName: "GEROU_CPC");

        var bestModel = result.BestRun.Model;
        string bestTrainer = result.BestRun.TrainerName.Split("=>").Last().Trim();

        Console.WriteLine($"🏆 Melhor modelo: {bestTrainer}");
        Console.WriteLine($"Acurácia: {result.BestRun.ValidationMetrics.Accuracy:P2}");
        Console.WriteLine($"Precisão: {result.BestRun.ValidationMetrics.PositivePrecision:P2}");
        Console.WriteLine($"Recall: {result.BestRun.ValidationMetrics.PositiveRecall:P2}");
        Console.WriteLine($"F1Score: {result.BestRun.ValidationMetrics.F1Score:P2}");

        // ==========================
        // 🔹 Salva modelo
        // ==========================
        var modelPath = Path.Combine(Environment.CurrentDirectory, $"{modelName}_{bestTrainer}.zip");
        ml.Model.Save(bestModel, balancedDataView.Schema, modelPath);
        Console.WriteLine($"💾 Modelo salvo em: {modelPath}");

        //// ==========================
        //// 🔹 Permutation Feature Importance (PFI)
        //// ==========================
        //Console.WriteLine("\n=== Cálculo da Importância das Features ===");

        //// Aplica modelo completo no TestSet
        //var transformedTestSet = bestModel.Transform(split.TestSet);

        //CalcularImportanciaDasFeatures(ml, bestModel, transformedTestSet, labelColumnName: "GEROU_CPC", permutationCount: 30, topK: 20);

        Console.WriteLine("\n✅ Treinamento concluído com sucesso!");
    }

    // Novo método reutilizável para calcular e exibir a importância das features (PFI)
    private static void CalcularImportanciaDasFeatures(MLContext ml, ITransformer trainedModel, IDataView transformedData, string labelColumnName, int permutationCount = 30, int topK = 20)
    {
        try
        {
            // Encontrar a coluna Features no schema transformado
            int featureIndex = -1;
            for (int i = 0; i < transformedData.Schema.Count; i++)
            {
                if (transformedData.Schema[i].Name.Equals("__Features__", StringComparison.OrdinalIgnoreCase))
                {
                    featureIndex = i;
                    break;
                }
            }

            if (featureIndex < 0)
            {
                Console.WriteLine("⚠️ Coluna 'Features' não encontrada no schema. PFI não será calculado.");
                return;
            }

            var featureColumn = transformedData.Schema[featureIndex];

            if (!featureColumn.Annotations.Schema.Any(a => a.Name == "SlotNames"))
            {
                Console.WriteLine("⚠️ Nenhuma anotação de SlotNames encontrada. PFI não será calculado.");
                return;
            }

            var slotNames = default(VBuffer<ReadOnlyMemory<char>>);
            featureColumn.Annotations.GetValue("SlotNames", ref slotNames);
            var featureNames = slotNames.DenseValues().Select(x => x.ToString()).ToArray();

            try
            {
                var permutationMetricsNC = ml.BinaryClassification.PermutationFeatureImportanceNonCalibrated(
                    trainedModel, transformedData, labelColumnName: labelColumnName, permutationCount: permutationCount);

                Console.WriteLine("\n🏆 Importância das Features (Δ AUC - NonCalibrated):");
                Console.WriteLine("----------------------------------------");

                var importancesNC = permutationMetricsNC
                    .Select((metric, index) => new
                    {
                        Feature = featureNames.Length > index ? featureNames[index] : $"Feature_{index}",
                        Importance = metric.Value.AreaUnderRocCurve.Mean
                    })
                    .OrderByDescending(x => Math.Abs(x.Importance))
                    .Take(topK);

                int rankNC = 1;
                foreach (var imp in importancesNC)
                {
                    Console.WriteLine($"{rankNC,2}. {imp.Feature.PadRight(30)} | ΔAUC: {imp.Importance:F6}");
                    rankNC++;
                }

                return;
            }
            catch
            {
                Console.WriteLine("⚠️ PFI NonCalibrated indisponível neste modelo/versão.");
                return;
            }
        }
        catch (Exception e)
        {
            Console.WriteLine($"❌ Erro ao calcular PermutationFeatureImportance: {e.Message}");
        }
    }



    public static void GeraGraficoMetricas(float[] thresholds, double[] f1Scores, double[] precisionScores, double[] recallScores, bool[] yTrue, float[] yProb)
    {
        for (int i = 0; i < thresholds.Length; i++)
        {
            float t = thresholds[i];
            int tp = yTrue.Zip(yProb, (label, prob) => new { label, prob }).Count(x => x.prob >= t && x.label);
            int fp = yTrue.Zip(yProb, (label, prob) => new { label, prob }).Count(x => x.prob >= t && !x.label);
            int fn = yTrue.Zip(yProb, (label, prob) => new { label, prob }).Count(x => x.prob < t && x.label);

            double precision = (tp + fp) == 0 ? 0 : (double)tp / (tp + fp);
            double recall = (tp + fn) == 0 ? 0 : (double)tp / (tp + fn);
            double f1 = (precision + recall) == 0 ? 0 : 2 * (precision * recall) / (precision + recall);

            precisionScores[i] = precision;
            recallScores[i] = recall;
            f1Scores[i] = f1;
        }

        var plt = new ScottPlot.Plot();

        var f1Plot = plt.Add.Scatter(
            thresholds.Select(x => (double)x).ToArray(),
            f1Scores
        );
        f1Plot.Color = Color.FromColor(System.Drawing.Color.Red);
        f1Plot.LegendText = "F1 Score";

        var precisionPlot = plt.Add.Scatter(
            thresholds.Select(x => (double)x).ToArray(),
            precisionScores
        );
        precisionPlot.Color = Color.FromColor(System.Drawing.Color.Blue);
        precisionPlot.LegendText = "Precision";

        var recallPlot = plt.Add.Scatter(
            thresholds.Select(x => (double)x).ToArray(),
            recallScores
        );
        recallPlot.Color = Color.FromColor(System.Drawing.Color.Green);
        recallPlot.LegendText = "Recall";

        plt.Title("Precision, Recall e F1 vs Threshold");
        plt.XLabel("Threshold");
        plt.YLabel("Valor");
        plt.ShowLegend();
        plt.SavePng("CARTEIRA_ANALISE.png", 1200, 900);
    }

    public static void GerarLiftChart(List<ClientePropensao> previsoes, string caminhoImagem)
    {
        var ordered = previsoes.OrderByDescending(p => p.Probability).ToList();

        int total = ordered.Count;
        int totalPositivos = ordered.Count(p => p.PredictedLabel);

        int grupos = 20;
        int tamanhoGrupo = total / grupos;

        var percentis = new List<double>();
        var ganhosAcumulados = new List<double>();
        var aleatorio = new List<double>();

        for (int i = 1; i <= grupos; i++)
        {
            var subset = ordered.Take(i * tamanhoGrupo).ToList();
            int positivosAcumulados = subset.Count(p => p.PredictedLabel);

            double ganho = (double)positivosAcumulados / totalPositivos;
            double pct = (double)i / grupos;

            percentis.Add(pct * 100);
            ganhosAcumulados.Add(ganho * 100);
            aleatorio.Add(pct * 100);
        }

        var plt = new ScottPlot.Plot();

        var modeloPlot = plt.Add.Scatter(
            xs: percentis.ToArray(),
            ys: ganhosAcumulados.ToArray()
        );
        modeloPlot.LegendText = "Modelo";
        modeloPlot.Color = Color.FromColor(System.Drawing.Color.Blue);
        modeloPlot.MarkerShape = MarkerShape.FilledCircle;

        var aleatorioPlot = plt.Add.Scatter(
            xs: percentis.ToArray(),
            ys: aleatorio.ToArray()
        );
        aleatorioPlot.LegendText = "Aleatório";
        aleatorioPlot.Color = Color.FromColor(System.Drawing.Color.Gray);
        aleatorioPlot.LineStyle = LineStyle.None;

        plt.Title("Lift Chart (Ganho Acumulado)");
        plt.XLabel("Percentual da base ordenada (%)");
        plt.YLabel("Percentual de CPCs capturados (%)");
        plt.ShowLegend();

        plt.SavePng(caminhoImagem, 1200, 900);

        Console.WriteLine($"📊 Lift Chart salvo em: {caminhoImagem}");
    }



private static void InsertScoredResults(List<ClientePropensao> scored, string connectionString, string tableName)
    {
        if (scored == null || scored.Count == 0)
        {
            Console.WriteLine("Nenhum registro para inserir.");
            return;
        }

        using var conn = new SqlConnection(connectionString);
        conn.Open();

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

        using var bulk = new SqlBulkCopy(conn)
        {
            DestinationTableName = $"dbo.{tableName}",
            BulkCopyTimeout = 0
        };
        bulk.ColumnMappings.Add("NR_CPF", "NR_CPF");
        bulk.ColumnMappings.Add("ID_CLIENTE", "ID_CLIENTE");
        bulk.ColumnMappings.Add("SCORE", "SCORE");
        bulk.ColumnMappings.Add("PROBABILITY", "PROBABILITY");
        bulk.ColumnMappings.Add("PREDICTED_LABEL", "PREDICTED_LABEL");

        bulk.WriteToServer(dt);

        Console.WriteLine($"Inseridos {dt.Rows.Count} registros em {tableName}.");
    }

}