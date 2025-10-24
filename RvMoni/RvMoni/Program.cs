



using Microsoft.Identity.Client;
using RvMoni.Entities;
using RvMoni.Repositories;
using RvMoni.Service;

namespace RvMoni
{

    public class  Program
    {
        public static void Main(string[] args)
        {
            try
            {
                Console.WriteLine("Iniciando Processamento de Chamadas...");
                using var dbContext = DbProcContextFactory.Create();
                var service = new MonitorService(dbContext);
                var transcript = new VoiceTranscriptService();
                Console.WriteLine("Listando Ligações...");
                List<TmpLigMoni> callList = service.GetAllCallsAsync().Result;

                if (callList != null && callList.Any())
                {
                    if (callList.Any(e => e.TpProcessamento == 0))
                    {
                        Console.WriteLine("Buscando Arquivos de Audio...");
                        service.DownloadCalls(callList).Wait();
                    }

                    var pendingCalls = callList.Count();

                    Console.WriteLine($"Iniciando Transcrição dos Audios... \n Total de Ligações Restantes {pendingCalls}");
                    foreach (var item in callList)
                    {
                        
                        Console.WriteLine($"Processando Audio : {item.CdClienteAcao}...");
                        transcript.GetVoiceTranscriptAsync(item).Wait();
                        pendingCalls--;
                        Console.Clear();


 



                    }

                }
                else
                {
                    Console.WriteLine("Nenhuma chamada encontrada para processamento.");
                }

            }
            catch (Exception ex)
            {
                Console.WriteLine(ex.Message.ToString());
                throw new Exception(ex.Message.ToString());
            }

        }
    }

}