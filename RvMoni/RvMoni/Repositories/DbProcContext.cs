using System;
using System.Collections.Generic;
using Microsoft.EntityFrameworkCore;
using RvMoni.Entities;

namespace RvMoni.Repositories;

public partial class DbProcContext : DbContext
{
    public DbProcContext()
    {
    }

    public DbProcContext(DbContextOptions<DbProcContext> options)
        : base(options)
    {
    }

    public virtual DbSet<TmpLigMoni> TmpLigMonis { get; set; }

    protected override void OnConfiguring(DbContextOptionsBuilder optionsBuilder)
#warning To protect potentially sensitive information in your connection string, you should move it out of source code. You can avoid scaffolding the connection string by using the Name= syntax to read it from configuration - see https://go.microsoft.com/fwlink/?linkid=2131148. For more guidance on storing connection strings, see https://go.microsoft.com/fwlink/?LinkId=723263.
        => optionsBuilder.UseSqlServer("Server=172.20.1.248;Database=DB_PROC;User Id=SisIntranet;Password=_P@ssw0rdEX!T0;Max Pool Size=600;TrustServerCertificate=True");

    protected override void OnModelCreating(ModelBuilder modelBuilder)
    {
        modelBuilder.UseCollation("Latin1_General_CI_AI");

        modelBuilder.Entity<TmpLigMoni>(entity =>
        {
            entity
                .ToTable("TMP_LIG_MONI");

            entity.HasKey(e => e.CdClienteAcao);

            entity.Property(e => e.CdClienteAcao).HasColumnName("CD_CLIENTE_ACAO");
            entity.Property(e => e.DtAcao)
                .HasColumnType("datetime")
                .HasColumnName("DT_ACAO");
            entity.Property(e => e.NmArquivo)
                .HasMaxLength(200)
                .IsUnicode(false)
                .HasColumnName("NM_ARQUIVO");
            entity.Property(e => e.NmCaminho)
                .HasMaxLength(400)
                .IsUnicode(false)
                .HasColumnName("NM_CAMINHO");
            entity.Property(e => e.NmColaborador)
                .HasMaxLength(300)
                .IsUnicode(false)
                .HasColumnName("NM_COLABORADOR");
            entity.Property(e => e.NrCallid)
                .HasMaxLength(50)
                .IsUnicode(false)
                .HasColumnName("NR_CALLID");
            entity.Property(e => e.NrCpfCliente).HasColumnName("NR_CPF_CLIENTE");
            entity.Property(e => e.NrCpfOperador).HasColumnName("NR_CPF_OPERADOR");
            entity.Property(e => e.ResultadoProcessamento)
                .HasMaxLength(120)
                .IsUnicode(false)
                .HasColumnName("RESULTADO_PROCESSAMENTO");
            entity.Property(e => e.Supervisor)
                .HasMaxLength(300)
                .IsUnicode(false)
                .HasColumnName("SUPERVISOR");
            entity.Property(e => e.TpProcessamento).HasColumnName("TP_PROCESSAMENTO");
            entity.Property(e => e.NmCaminhoInterno)
                .HasMaxLength(200)
                .HasColumnName("NM_CAMINHO_INTERNO");
        });
        modelBuilder.HasSequence<byte>("SEQ01_Teste")
            .HasMin(1L)
            .HasMax(200L);

        OnModelCreatingPartial(modelBuilder);
    }

    partial void OnModelCreatingPartial(ModelBuilder modelBuilder);
}
