using RvMoni.Entities;
using System;
using System.Collections.Generic;
using System.Diagnostics;
using System.Linq;
using System.Text;
using System.Threading.Tasks;

namespace RvMoni.Service
{
    internal class VoiceTranscriptService
    {
        private int numInstances = 10;
        private static string model = @"C:\\Processo\Whisper\models\ggml-medium.bin";
        private string textDirectory = @"C:\\Processo\36 - Rv_Moni_Text";
        private string modelArgs = $@"--model {model} --language pt --output-txt";

        public async Task GetVoiceTranscriptAsync(TmpLigMoni Call)
        {
            string arguments = $"{modelArgs} \"{Call.NmCaminhoInterno}\"";

            var psi = new ProcessStartInfo
            {
                FileName = @"C:\\Processo\Whisper\whisper-cli.exe",
                Arguments = arguments,
                UseShellExecute = false,
                RedirectStandardOutput = true,
                RedirectStandardError = true,
                CreateNoWindow = true
            };

            using var process = Process.Start(psi);
            string output = await process.StandardOutput.ReadToEndAsync();
            string error = await process.StandardError.ReadToEndAsync();

            await process.WaitForExitAsync();

            Console.WriteLine($"[Instância] Processado '{Path.GetFileName(Call.NmCaminhoInterno)}'");
            if (!string.IsNullOrWhiteSpace(error))
            {
                Console.WriteLine($"[Instância] Erro: {error}");
            }
        }

        public async Task MoveTranscription(List<TmpLigMoni> callList)
        {
            foreach (TmpLigMoni call in callList)
            {
                FileInfo txtCall = new FileInfo(call.NmCaminhoInterno.Replace("mp3","txt"));
                string newDirectory = Path.Combine(textDirectory, call.NmArquivo);

                File.Move(txtCall.FullName,newDirectory);
            }
        }
    }
}
