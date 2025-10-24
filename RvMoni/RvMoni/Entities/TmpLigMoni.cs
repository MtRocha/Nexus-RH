using System;
using System.Collections.Generic;
using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace RvMoni.Entities;

public partial class TmpLigMoni
{
    [Key]
    public long CdClienteAcao { get; set; }

    public long? NrCpfOperador { get; set; }

    public long? NrCpfCliente { get; set; }

    public string? NrCallid { get; set; }

    public DateTime DtAcao { get; set; }

    public string? NmColaborador { get; set; }

    public string? Supervisor { get; set; }

    public int TpProcessamento { get; set; }

    public string? ResultadoProcessamento { get; set; }

    public string? NmCaminho { get; set; }

    public string? NmArquivo { get; set; }
    public string? NmCaminhoInterno { get; set; }

}
