using Microsoft.EntityFrameworkCore;
using RvMoni.Entities;
using RvMoni.Repositories;
using System;
using System.Collections.Generic;
using System.Data;
using System.Diagnostics;
using System.Linq;
using System.Net;
using System.Text;
using System.Threading.Tasks;

namespace RvMoni.Service
{
    internal class MonitorService
    {
        private readonly DbProcContext _dbContext;
        private List<int> InvalidCodes = new List<int> {999,200};
        private List<int> ValidCodes = new List<int> {201,0};

        public MonitorService(DbProcContext dbContext)
        {
            _dbContext = dbContext;
        }

        public async Task<List<TmpLigMoni>> GetAllCallsAsync()
        {
            return await _dbContext.TmpLigMonis.Where(Call => ValidCodes.Contains(Call.TpProcessamento) && !InvalidCodes.Contains( Call.TpProcessamento ) ).ToListAsync();
        }

        public async Task<List<TmpLigMoni>> DownloadCalls(List<TmpLigMoni> CallList)
        {
            string baseDestination = @"C:\Processo\35 - Rv_Moni_Calls";

            foreach (TmpLigMoni call in CallList)
            {
                try
                {
                    if (string.IsNullOrEmpty(call.NmCaminho))
                    {
                        await UpdateNotFoundCallAsync(call, "Caminho vazio");
                        continue;
                    }

                    string origin = call.NmCaminho.Trim();
                    string destination = Path.Combine(baseDestination, Path.GetFileName(origin));

                    MapNetworkPath(origin);

                    if (!File.Exists(origin))
                    {
                        await UpdateNotFoundCallAsync(call, "Arquivo não encontrado");
                        continue;
                    }

                    // Tenta copiar o arquivo
                    try
                    {
                        using (FileStream sourceStream = File.Open(origin, FileMode.Open, FileAccess.Read, FileShare.Read))
                        {
                            if (!File.Exists(destination))
                            {
                                using (FileStream destinationStream = File.Create(destination))
                                {
                                        await sourceStream.CopyToAsync(destinationStream);
                                        call.NmCaminhoInterno = destination;
                                        call.TpProcessamento = 1;
                                        call.ResultadoProcessamento = "Download realizado com sucesso";
                                        await UpdateCallAsync(call, "Download realizado com sucesso", 201);
                                }
                            }   
                        }

                    }
                    catch (IOException ioEx)
                    {
                        await UpdateNotFoundCallAsync(call, "Arquivo em uso por outro processo");
                        continue;
                    }
                }
                catch (Exception ex)
                {
                    // Registra erro genérico por segurança
                    await UpdateNotFoundCallAsync(call, $"Erro inesperado: {ex.Message}");
                }
            }

             return CallList;
        }

        public static void MapNetworkPath(string caminhoOrigem)
        {
            try
            {

                string user = ".\\Administrador";
                string password = "75D4mCj6EW7ObJhp08UXTYsN";
                NetworkCredential credenciais = new NetworkCredential(user, password);
                string comando = $"/C net use {caminhoOrigem} /user:{credenciais.UserName} {credenciais.Password}";
                ProcessStartInfo pro = new ProcessStartInfo("cmd", comando);
                pro.WindowStyle = ProcessWindowStyle.Hidden;
                Process.Start(pro);
            }
            catch (Exception ex)
            {
                throw new Exception(ex.Message.ToString());
            }
        }

        private async Task UpdateNotFoundCallAsync(TmpLigMoni call,string reason)
        {
            call.TpProcessamento = 999;
            call.ResultadoProcessamento = reason;
            _dbContext.Update(call);
            await _dbContext.SaveChangesAsync();
        }
        private async Task UpdateCallAsync(TmpLigMoni call,string reason,int status)
        {
            call.TpProcessamento = status;
            call.ResultadoProcessamento = reason;
            _dbContext.Update(call);
            await _dbContext.SaveChangesAsync();
        }

    }
}
