-- Nexus RH - SQL Server DDL
-- Criacao completa do schema conforme DER oficial.

SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO

IF DB_ID('NexusRH') IS NULL
BEGIN
    CREATE DATABASE NexusRH;
END;
GO

USE NexusRH;
GO

-- 1. Tabelas de dominio e cadastro base
IF OBJECT_ID('dbo.CentroCusto', 'U') IS NOT NULL DROP TABLE dbo.CentroCusto;
IF OBJECT_ID('dbo.Cargo', 'U') IS NOT NULL DROP TABLE dbo.Cargo;
GO

CREATE TABLE dbo.CentroCusto (
    CentroCustoID INT IDENTITY(1,1) NOT NULL,
    Codigo VARCHAR(20) NOT NULL,
    Nome VARCHAR(100) NOT NULL,
    CONSTRAINT PK_CentroCusto PRIMARY KEY (CentroCustoID),
    CONSTRAINT UQ_CentroCusto_Codigo UNIQUE (Codigo)
);
GO

CREATE TABLE dbo.Cargo (
    CargoID INT IDENTITY(1,1) NOT NULL,
    Nome VARCHAR(100) NOT NULL,
    NivelHierarquico VARCHAR(50) NULL,
    CONSTRAINT PK_Cargo PRIMARY KEY (CargoID)
);
GO

-- 2. Tabela central
IF OBJECT_ID('dbo.Funcionario', 'U') IS NOT NULL DROP TABLE dbo.Funcionario;
GO

CREATE TABLE dbo.Funcionario (
    FuncionarioID INT IDENTITY(1,1) NOT NULL,
    Nome VARCHAR(150) NOT NULL,
    CPF VARCHAR(11) NOT NULL,
    Email VARCHAR(100) NULL,
    SenhaHash VARCHAR(255) NOT NULL,
    PerfilAcesso VARCHAR(20) NOT NULL CONSTRAINT DF_Funcionario_PerfilAcesso DEFAULT ('Usuario'),
    CargoID INT NOT NULL,
    CentroCustoID INT NOT NULL,
    SupervisorID INT NULL,
    SalarioAtual DECIMAL(10,2) NOT NULL,
    DataAdmissao DATE NOT NULL,
    DataDesligamento DATE NULL,
    Status VARCHAR(20) NOT NULL CONSTRAINT DF_Funcionario_Status DEFAULT ('Ativo'),
    CONSTRAINT PK_Funcionario PRIMARY KEY (FuncionarioID),
    CONSTRAINT UQ_Funcionario_CPF UNIQUE (CPF),
    CONSTRAINT UQ_Funcionario_Email UNIQUE (Email),
    CONSTRAINT FK_Funcionario_Cargo FOREIGN KEY (CargoID) REFERENCES dbo.Cargo (CargoID),
    CONSTRAINT FK_Funcionario_CentroCusto FOREIGN KEY (CentroCustoID) REFERENCES dbo.CentroCusto (CentroCustoID),
    CONSTRAINT FK_Funcionario_Supervisor FOREIGN KEY (SupervisorID) REFERENCES dbo.Funcionario (FuncionarioID)
);
GO

-- 3. Tabelas de operacao e ciclo de vida
IF OBJECT_ID('dbo.RegistroPonto', 'U') IS NOT NULL DROP TABLE dbo.RegistroPonto;
IF OBJECT_ID('dbo.SolicitacaoFerias', 'U') IS NOT NULL DROP TABLE dbo.SolicitacaoFerias;
IF OBJECT_ID('dbo.AfastamentoAtestado', 'U') IS NOT NULL DROP TABLE dbo.AfastamentoAtestado;
GO

CREATE TABLE dbo.RegistroPonto (
    PontoID INT IDENTITY(1,1) NOT NULL,
    FuncionarioID INT NOT NULL,
    DataHoraRegistro DATETIME2 NOT NULL,
    TipoBatida VARCHAR(20) NOT NULL,
    Origem VARCHAR(20) NOT NULL CONSTRAINT DF_RegistroPonto_Origem DEFAULT ('Sistema'),
    JustificativaInclusao VARCHAR(255) NULL,
    StatusAprovacao VARCHAR(20) NOT NULL CONSTRAINT DF_RegistroPonto_StatusAprovacao DEFAULT ('Aprovado'),
    CONSTRAINT PK_RegistroPonto PRIMARY KEY (PontoID),
    CONSTRAINT FK_RegistroPonto_Funcionario FOREIGN KEY (FuncionarioID) REFERENCES dbo.Funcionario (FuncionarioID)
);
GO

CREATE TABLE dbo.SolicitacaoFerias (
    FeriasID INT IDENTITY(1,1) NOT NULL,
    FuncionarioID INT NOT NULL,
    DataInicio DATE NOT NULL,
    DataFim DATE NOT NULL,
    DataSolicitacao DATETIME2 NOT NULL CONSTRAINT DF_SolicitacaoFerias_DataSolicitacao DEFAULT (GETDATE()),
    StatusAprovacao VARCHAR(20) NOT NULL CONSTRAINT DF_SolicitacaoFerias_StatusAprovacao DEFAULT ('Pendente'),
    CONSTRAINT PK_SolicitacaoFerias PRIMARY KEY (FeriasID),
    CONSTRAINT FK_SolicitacaoFerias_Funcionario FOREIGN KEY (FuncionarioID) REFERENCES dbo.Funcionario (FuncionarioID)
);
GO

CREATE TABLE dbo.AfastamentoAtestado (
    AfastamentoID INT IDENTITY(1,1) NOT NULL,
    FuncionarioID INT NOT NULL,
    Tipo VARCHAR(20) NOT NULL,
    DataInicio DATE NOT NULL,
    DataFim DATE NOT NULL,
    Motivo VARCHAR(255) NULL,
    CaminhoArquivo VARCHAR(255) NULL,
    StatusAprovacao VARCHAR(20) NOT NULL CONSTRAINT DF_AfastamentoAtestado_StatusAprovacao DEFAULT ('Pendente'),
    CONSTRAINT PK_AfastamentoAtestado PRIMARY KEY (AfastamentoID),
    CONSTRAINT FK_AfastamentoAtestado_Funcionario FOREIGN KEY (FuncionarioID) REFERENCES dbo.Funcionario (FuncionarioID)
);
GO

-- 4. Tabela de remuneracao
IF OBJECT_ID('dbo.FolhaPagamento', 'U') IS NOT NULL DROP TABLE dbo.FolhaPagamento;
GO

CREATE TABLE dbo.FolhaPagamento (
    FolhaID INT IDENTITY(1,1) NOT NULL,
    FuncionarioID INT NOT NULL,
    MesReferencia TINYINT NOT NULL,
    AnoReferencia SMALLINT NOT NULL,
    SalarioBase DECIMAL(10,2) NOT NULL,
    TotalProventos DECIMAL(10,2) NOT NULL,
    TotalDescontos DECIMAL(10,2) NOT NULL,
    ValorLiquido DECIMAL(10,2) NOT NULL,
    DataPagamento DATE NOT NULL,
    FechadaPor INT NULL,
    CONSTRAINT PK_FolhaPagamento PRIMARY KEY (FolhaID),
    CONSTRAINT FK_FolhaPagamento_Funcionario FOREIGN KEY (FuncionarioID) REFERENCES dbo.Funcionario (FuncionarioID),
    CONSTRAINT FK_FolhaPagamento_FechadaPor FOREIGN KEY (FechadaPor) REFERENCES dbo.Funcionario (FuncionarioID)
);
GO

-- 5. Tabelas de log / historico
IF OBJECT_ID('dbo.LogTransferenciaCentroCusto', 'U') IS NOT NULL DROP TABLE dbo.LogTransferenciaCentroCusto;
IF OBJECT_ID('dbo.LogTransferenciaHierarquia', 'U') IS NOT NULL DROP TABLE dbo.LogTransferenciaHierarquia;
IF OBJECT_ID('dbo.LogDesligamento', 'U') IS NOT NULL DROP TABLE dbo.LogDesligamento;
IF OBJECT_ID('dbo.LogAlteracaoSalarial', 'U') IS NOT NULL DROP TABLE dbo.LogAlteracaoSalarial;
IF OBJECT_ID('dbo.LogStatusPagamento', 'U') IS NOT NULL DROP TABLE dbo.LogStatusPagamento;
IF OBJECT_ID('dbo.ConsumoApiLog', 'U') IS NOT NULL DROP TABLE dbo.ConsumoApiLog;
IF OBJECT_ID('dbo.LogOperacaoSistema', 'U') IS NOT NULL DROP TABLE dbo.LogOperacaoSistema;
IF OBJECT_ID('dbo.ConfiguracaoSistema', 'U') IS NOT NULL DROP TABLE dbo.ConfiguracaoSistema;
IF OBJECT_ID('dbo.FuncionarioMfa', 'U') IS NOT NULL DROP TABLE dbo.FuncionarioMfa;
GO

CREATE TABLE dbo.LogTransferenciaCentroCusto (
    LogCentroCustoID INT IDENTITY(1,1) NOT NULL,
    FuncionarioID INT NOT NULL,
    CentroCustoAnteriorID INT NULL,
    CentroCustoNovoID INT NOT NULL,
    DataTransferencia DATETIME2 NOT NULL CONSTRAINT DF_LogTransferenciaCentroCusto_DataTransferencia DEFAULT (GETDATE()),
    Motivo VARCHAR(255) NULL,
    RegistradoPor INT NOT NULL,
    CONSTRAINT PK_LogTransferenciaCentroCusto PRIMARY KEY (LogCentroCustoID),
    CONSTRAINT FK_LogTransferenciaCentroCusto_Funcionario FOREIGN KEY (FuncionarioID) REFERENCES dbo.Funcionario (FuncionarioID),
    CONSTRAINT FK_LogTransferenciaCentroCusto_Anterior FOREIGN KEY (CentroCustoAnteriorID) REFERENCES dbo.CentroCusto (CentroCustoID),
    CONSTRAINT FK_LogTransferenciaCentroCusto_Novo FOREIGN KEY (CentroCustoNovoID) REFERENCES dbo.CentroCusto (CentroCustoID),
    CONSTRAINT FK_LogTransferenciaCentroCusto_RegistradoPor FOREIGN KEY (RegistradoPor) REFERENCES dbo.Funcionario (FuncionarioID)
);
GO

CREATE TABLE dbo.LogTransferenciaHierarquia (
    LogHierarquiaID INT IDENTITY(1,1) NOT NULL,
    FuncionarioID INT NOT NULL,
    SupervisorAnteriorID INT NULL,
    SupervisorNovoID INT NULL,
    DataTransferencia DATETIME2 NOT NULL CONSTRAINT DF_LogTransferenciaHierarquia_DataTransferencia DEFAULT (GETDATE()),
    Motivo VARCHAR(255) NULL,
    RegistradoPor INT NOT NULL,
    CONSTRAINT PK_LogTransferenciaHierarquia PRIMARY KEY (LogHierarquiaID),
    CONSTRAINT FK_LogTransferenciaHierarquia_Funcionario FOREIGN KEY (FuncionarioID) REFERENCES dbo.Funcionario (FuncionarioID),
    CONSTRAINT FK_LogTransferenciaHierarquia_SupAnterior FOREIGN KEY (SupervisorAnteriorID) REFERENCES dbo.Funcionario (FuncionarioID),
    CONSTRAINT FK_LogTransferenciaHierarquia_SupNovo FOREIGN KEY (SupervisorNovoID) REFERENCES dbo.Funcionario (FuncionarioID),
    CONSTRAINT FK_LogTransferenciaHierarquia_RegistradoPor FOREIGN KEY (RegistradoPor) REFERENCES dbo.Funcionario (FuncionarioID)
);
GO

CREATE TABLE dbo.LogDesligamento (
    LogDesligamentoID INT IDENTITY(1,1) NOT NULL,
    FuncionarioID INT NOT NULL,
    DataDesligamento DATE NOT NULL,
    TipoDesligamento VARCHAR(50) NOT NULL,
    MotivoDetalhado VARCHAR(500) NULL,
    DataRegistro DATETIME2 NOT NULL CONSTRAINT DF_LogDesligamento_DataRegistro DEFAULT (GETDATE()),
    RegistradoPor INT NOT NULL,
    CONSTRAINT PK_LogDesligamento PRIMARY KEY (LogDesligamentoID),
    CONSTRAINT FK_LogDesligamento_Funcionario FOREIGN KEY (FuncionarioID) REFERENCES dbo.Funcionario (FuncionarioID),
    CONSTRAINT FK_LogDesligamento_RegistradoPor FOREIGN KEY (RegistradoPor) REFERENCES dbo.Funcionario (FuncionarioID)
);
GO

CREATE TABLE dbo.LogAlteracaoSalarial (
    LogSalarioID INT IDENTITY(1,1) NOT NULL,
    FuncionarioID INT NOT NULL,
    SalarioAnterior DECIMAL(10,2) NOT NULL,
    SalarioNovo DECIMAL(10,2) NOT NULL,
    Motivo VARCHAR(100) NOT NULL,
    DataAlteracao DATETIME2 NOT NULL CONSTRAINT DF_LogAlteracaoSalarial_DataAlteracao DEFAULT (GETDATE()),
    RegistradoPor INT NOT NULL,
    CONSTRAINT PK_LogAlteracaoSalarial PRIMARY KEY (LogSalarioID),
    CONSTRAINT FK_LogAlteracaoSalarial_Funcionario FOREIGN KEY (FuncionarioID) REFERENCES dbo.Funcionario (FuncionarioID),
    CONSTRAINT FK_LogAlteracaoSalarial_RegistradoPor FOREIGN KEY (RegistradoPor) REFERENCES dbo.Funcionario (FuncionarioID)
);
GO

CREATE TABLE dbo.LogStatusPagamento (
    LogPagamentoID INT IDENTITY(1,1) NOT NULL,
    FolhaID INT NOT NULL,
    StatusAnterior VARCHAR(50) NULL,
    StatusNovo VARCHAR(50) NOT NULL,
    DataAlteracao DATETIME2 NOT NULL CONSTRAINT DF_LogStatusPagamento_DataAlteracao DEFAULT (GETDATE()),
    Observacao VARCHAR(255) NULL,
    RegistradoPor INT NOT NULL,
    CONSTRAINT PK_LogStatusPagamento PRIMARY KEY (LogPagamentoID),
    CONSTRAINT FK_LogStatusPagamento_Folha FOREIGN KEY (FolhaID) REFERENCES dbo.FolhaPagamento (FolhaID),
    CONSTRAINT FK_LogStatusPagamento_RegistradoPor FOREIGN KEY (RegistradoPor) REFERENCES dbo.Funcionario (FuncionarioID)
);
GO

CREATE TABLE dbo.ConfiguracaoSistema (
    ConfiguracaoID INT IDENTITY(1,1) NOT NULL,
    Chave VARCHAR(80) NOT NULL,
    Valor VARCHAR(500) NOT NULL,
    Categoria VARCHAR(50) NOT NULL CONSTRAINT DF_ConfiguracaoSistema_Categoria DEFAULT ('Geral'),
    Descricao VARCHAR(255) NULL,
    TipoCampo VARCHAR(30) NOT NULL CONSTRAINT DF_ConfiguracaoSistema_TipoCampo DEFAULT ('texto'),
    Editavel BIT NOT NULL CONSTRAINT DF_ConfiguracaoSistema_Editavel DEFAULT (1),
    Ativo BIT NOT NULL CONSTRAINT DF_ConfiguracaoSistema_Ativo DEFAULT (1),
    AtualizadoEm DATETIME2 NOT NULL CONSTRAINT DF_ConfiguracaoSistema_AtualizadoEm DEFAULT (GETDATE()),
    CONSTRAINT PK_ConfiguracaoSistema PRIMARY KEY (ConfiguracaoID),
    CONSTRAINT UQ_ConfiguracaoSistema_Chave UNIQUE (Chave)
);
GO

CREATE TABLE dbo.FuncionarioMfa (
    FuncionarioMfaID INT IDENTITY(1,1) NOT NULL,
    FuncionarioID INT NOT NULL,
    SecretBase32 VARCHAR(128) NOT NULL,
    Provedor VARCHAR(30) NOT NULL CONSTRAINT DF_FuncionarioMfa_Provedor DEFAULT ('TOTP'),
    Ativo BIT NOT NULL CONSTRAINT DF_FuncionarioMfa_Ativo DEFAULT (1),
    CriadoEm DATETIME2 NOT NULL CONSTRAINT DF_FuncionarioMfa_CriadoEm DEFAULT (GETDATE()),
    UltimoUsoEm DATETIME2 NULL,
    CONSTRAINT PK_FuncionarioMfa PRIMARY KEY (FuncionarioMfaID),
    CONSTRAINT UQ_FuncionarioMfa_Funcionario UNIQUE (FuncionarioID),
    CONSTRAINT FK_FuncionarioMfa_Funcionario FOREIGN KEY (FuncionarioID) REFERENCES dbo.Funcionario (FuncionarioID)
);
GO

CREATE TABLE dbo.LogOperacaoSistema (
    LogOperacaoID INT IDENTITY(1,1) NOT NULL,
    TipoOperacao VARCHAR(50) NOT NULL,
    Entidade VARCHAR(80) NULL,
    EntidadeID VARCHAR(50) NULL,
    FuncionarioID INT NULL,
    Sucesso BIT NOT NULL,
    Mensagem VARCHAR(255) NOT NULL,
    DetalhesJson NVARCHAR(MAX) NULL,
    IpOrigem VARCHAR(60) NULL,
    UserAgent VARCHAR(255) NULL,
    DataOperacao DATETIME2 NOT NULL CONSTRAINT DF_LogOperacaoSistema_DataOperacao DEFAULT (GETDATE()),
    CONSTRAINT PK_LogOperacaoSistema PRIMARY KEY (LogOperacaoID),
    CONSTRAINT FK_LogOperacaoSistema_Funcionario FOREIGN KEY (FuncionarioID) REFERENCES dbo.Funcionario (FuncionarioID)
);
GO

CREATE TABLE dbo.ConsumoApiLog (
    ConsumoApiLogID INT IDENTITY(1,1) NOT NULL,
    Endpoint VARCHAR(180) NOT NULL,
    Metodo VARCHAR(10) NOT NULL,
    StatusHttp SMALLINT NOT NULL,
    FuncionarioID INT NULL,
    TempoRespostaMs INT NULL,
    IpOrigem VARCHAR(60) NULL,
    UserAgent VARCHAR(255) NULL,
    DataConsumo DATETIME2 NOT NULL CONSTRAINT DF_ConsumoApiLog_DataConsumo DEFAULT (GETDATE()),
    CONSTRAINT PK_ConsumoApiLog PRIMARY KEY (ConsumoApiLogID),
    CONSTRAINT FK_ConsumoApiLog_Funcionario FOREIGN KEY (FuncionarioID) REFERENCES dbo.Funcionario (FuncionarioID)
);
GO

-- Indices uteis para consultas frequentes
CREATE INDEX IX_Funcionario_CargoID ON dbo.Funcionario (CargoID);
CREATE INDEX IX_Funcionario_CentroCustoID ON dbo.Funcionario (CentroCustoID);
CREATE INDEX IX_Funcionario_SupervisorID ON dbo.Funcionario (SupervisorID);
CREATE INDEX IX_RegistroPonto_FuncionarioID ON dbo.RegistroPonto (FuncionarioID);
CREATE INDEX IX_SolicitacaoFerias_FuncionarioID ON dbo.SolicitacaoFerias (FuncionarioID);
CREATE INDEX IX_AfastamentoAtestado_FuncionarioID ON dbo.AfastamentoAtestado (FuncionarioID);
CREATE INDEX IX_FolhaPagamento_FuncionarioID ON dbo.FolhaPagamento (FuncionarioID);
CREATE INDEX IX_LogOperacaoSistema_DataOperacao ON dbo.LogOperacaoSistema (DataOperacao DESC);
CREATE INDEX IX_ConsumoApiLog_DataConsumo ON dbo.ConsumoApiLog (DataConsumo DESC);
CREATE INDEX IX_ConfiguracaoSistema_Categoria ON dbo.ConfiguracaoSistema (Categoria);
GO
