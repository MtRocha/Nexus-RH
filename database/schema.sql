-- Nexus RH - DDL MySQL
-- Schema completo adaptado para phpMyAdmin / MySQL.

CREATE DATABASE IF NOT EXISTS  if0_42083119_db_nexusrh
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE  if0_42083119_db_nexusrh;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS ConsumoApiLog;
DROP TABLE IF EXISTS LogOperacaoSistema;
DROP TABLE IF EXISTS FuncionarioMfa;
DROP TABLE IF EXISTS ConfiguracaoSistema;
DROP TABLE IF EXISTS LogStatusPagamento;
DROP TABLE IF EXISTS LogAlteracaoSalarial;
DROP TABLE IF EXISTS LogDesligamento;
DROP TABLE IF EXISTS LogTransferenciaHierarquia;
DROP TABLE IF EXISTS LogTransferenciaCentroCusto;
DROP TABLE IF EXISTS FolhaPagamento;
DROP TABLE IF EXISTS AfastamentoAtestado;
DROP TABLE IF EXISTS SolicitacaoFerias;
DROP TABLE IF EXISTS RegistroPonto;
DROP TABLE IF EXISTS Funcionario;
DROP TABLE IF EXISTS Cargo;
DROP TABLE IF EXISTS CentroCusto;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE CentroCusto (
    CentroCustoID INT NOT NULL AUTO_INCREMENT,
    Codigo VARCHAR(20) NOT NULL,
    Nome VARCHAR(100) NOT NULL,
    PRIMARY KEY (CentroCustoID),
    UNIQUE KEY UQ_CentroCusto_Codigo (Codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE Cargo (
    CargoID INT NOT NULL AUTO_INCREMENT,
    Nome VARCHAR(100) NOT NULL,
    NivelHierarquico VARCHAR(50) NULL,
    PRIMARY KEY (CargoID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE Funcionario (
    FuncionarioID INT NOT NULL AUTO_INCREMENT,
    Nome VARCHAR(150) NOT NULL,
    CPF VARCHAR(11) NOT NULL,
    Email VARCHAR(100) NULL,
    SenhaHash VARCHAR(255) NOT NULL,
    PerfilAcesso VARCHAR(20) NOT NULL DEFAULT 'Usuario',
    CargoID INT NOT NULL,
    CentroCustoID INT NOT NULL,
    SupervisorID INT NULL,
    SalarioAtual DECIMAL(10,2) NOT NULL,
    DataAdmissao DATE NOT NULL,
    DataDesligamento DATE NULL,
    Status VARCHAR(20) NOT NULL DEFAULT 'Ativo',
    PRIMARY KEY (FuncionarioID),
    UNIQUE KEY UQ_Funcionario_CPF (CPF),
    UNIQUE KEY UQ_Funcionario_Email (Email),
    KEY IX_Funcionario_CargoID (CargoID),
    KEY IX_Funcionario_CentroCustoID (CentroCustoID),
    KEY IX_Funcionario_SupervisorID (SupervisorID),
    CONSTRAINT FK_Funcionario_Cargo FOREIGN KEY (CargoID) REFERENCES Cargo (CargoID),
    CONSTRAINT FK_Funcionario_CentroCusto FOREIGN KEY (CentroCustoID) REFERENCES CentroCusto (CentroCustoID),
    CONSTRAINT FK_Funcionario_Supervisor FOREIGN KEY (SupervisorID) REFERENCES Funcionario (FuncionarioID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE RegistroPonto (
    PontoID INT NOT NULL AUTO_INCREMENT,
    FuncionarioID INT NOT NULL,
    DataHoraRegistro DATETIME NOT NULL,
    TipoBatida VARCHAR(20) NOT NULL,
    Origem VARCHAR(20) NOT NULL DEFAULT 'Sistema',
    JustificativaInclusao VARCHAR(255) NULL,
    StatusAprovacao VARCHAR(20) NOT NULL DEFAULT 'Aprovado',
    PRIMARY KEY (PontoID),
    KEY IX_RegistroPonto_FuncionarioID (FuncionarioID),
    CONSTRAINT FK_RegistroPonto_Funcionario FOREIGN KEY (FuncionarioID) REFERENCES Funcionario (FuncionarioID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE SolicitacaoFerias (
    FeriasID INT NOT NULL AUTO_INCREMENT,
    FuncionarioID INT NOT NULL,
    DataInicio DATE NOT NULL,
    DataFim DATE NOT NULL,
    DataSolicitacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    StatusAprovacao VARCHAR(20) NOT NULL DEFAULT 'Pendente',
    PRIMARY KEY (FeriasID),
    KEY IX_SolicitacaoFerias_FuncionarioID (FuncionarioID),
    CONSTRAINT FK_SolicitacaoFerias_Funcionario FOREIGN KEY (FuncionarioID) REFERENCES Funcionario (FuncionarioID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE AfastamentoAtestado (
    AfastamentoID INT NOT NULL AUTO_INCREMENT,
    FuncionarioID INT NOT NULL,
    Tipo VARCHAR(20) NOT NULL,
    DataInicio DATE NOT NULL,
    DataFim DATE NOT NULL,
    Motivo VARCHAR(255) NULL,
    CaminhoArquivo VARCHAR(255) NULL,
    StatusAprovacao VARCHAR(20) NOT NULL DEFAULT 'Pendente',
    PRIMARY KEY (AfastamentoID),
    KEY IX_AfastamentoAtestado_FuncionarioID (FuncionarioID),
    CONSTRAINT FK_AfastamentoAtestado_Funcionario FOREIGN KEY (FuncionarioID) REFERENCES Funcionario (FuncionarioID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE FolhaPagamento (
    FolhaID INT NOT NULL AUTO_INCREMENT,
    FuncionarioID INT NOT NULL,
    MesReferencia TINYINT NOT NULL,
    AnoReferencia SMALLINT NOT NULL,
    SalarioBase DECIMAL(10,2) NOT NULL,
    TotalProventos DECIMAL(10,2) NOT NULL,
    TotalDescontos DECIMAL(10,2) NOT NULL,
    ValorLiquido DECIMAL(10,2) NOT NULL,
    DataPagamento DATE NOT NULL,
    FechadaPor INT NULL,
    PRIMARY KEY (FolhaID),
    KEY IX_FolhaPagamento_FuncionarioID (FuncionarioID),
    CONSTRAINT FK_FolhaPagamento_Funcionario FOREIGN KEY (FuncionarioID) REFERENCES Funcionario (FuncionarioID),
    CONSTRAINT FK_FolhaPagamento_FechadaPor FOREIGN KEY (FechadaPor) REFERENCES Funcionario (FuncionarioID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE LogTransferenciaCentroCusto (
    LogCentroCustoID INT NOT NULL AUTO_INCREMENT,
    FuncionarioID INT NOT NULL,
    CentroCustoAnteriorID INT NULL,
    CentroCustoNovoID INT NOT NULL,
    DataTransferencia DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    Motivo VARCHAR(255) NULL,
    RegistradoPor INT NOT NULL,
    PRIMARY KEY (LogCentroCustoID),
    CONSTRAINT FK_LogTransferenciaCentroCusto_Funcionario FOREIGN KEY (FuncionarioID) REFERENCES Funcionario (FuncionarioID),
    CONSTRAINT FK_LogTransferenciaCentroCusto_Anterior FOREIGN KEY (CentroCustoAnteriorID) REFERENCES CentroCusto (CentroCustoID),
    CONSTRAINT FK_LogTransferenciaCentroCusto_Novo FOREIGN KEY (CentroCustoNovoID) REFERENCES CentroCusto (CentroCustoID),
    CONSTRAINT FK_LogTransferenciaCentroCusto_RegistradoPor FOREIGN KEY (RegistradoPor) REFERENCES Funcionario (FuncionarioID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE LogTransferenciaHierarquia (
    LogHierarquiaID INT NOT NULL AUTO_INCREMENT,
    FuncionarioID INT NOT NULL,
    SupervisorAnteriorID INT NULL,
    SupervisorNovoID INT NULL,
    DataTransferencia DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    Motivo VARCHAR(255) NULL,
    RegistradoPor INT NOT NULL,
    PRIMARY KEY (LogHierarquiaID),
    CONSTRAINT FK_LogTransferenciaHierarquia_Funcionario FOREIGN KEY (FuncionarioID) REFERENCES Funcionario (FuncionarioID),
    CONSTRAINT FK_LogTransferenciaHierarquia_SupAnterior FOREIGN KEY (SupervisorAnteriorID) REFERENCES Funcionario (FuncionarioID),
    CONSTRAINT FK_LogTransferenciaHierarquia_SupNovo FOREIGN KEY (SupervisorNovoID) REFERENCES Funcionario (FuncionarioID),
    CONSTRAINT FK_LogTransferenciaHierarquia_RegistradoPor FOREIGN KEY (RegistradoPor) REFERENCES Funcionario (FuncionarioID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE LogDesligamento (
    LogDesligamentoID INT NOT NULL AUTO_INCREMENT,
    FuncionarioID INT NOT NULL,
    DataDesligamento DATE NOT NULL,
    TipoDesligamento VARCHAR(50) NOT NULL,
    MotivoDetalhado VARCHAR(500) NULL,
    DataRegistro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    RegistradoPor INT NOT NULL,
    PRIMARY KEY (LogDesligamentoID),
    CONSTRAINT FK_LogDesligamento_Funcionario FOREIGN KEY (FuncionarioID) REFERENCES Funcionario (FuncionarioID),
    CONSTRAINT FK_LogDesligamento_RegistradoPor FOREIGN KEY (RegistradoPor) REFERENCES Funcionario (FuncionarioID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE LogAlteracaoSalarial (
    LogSalarioID INT NOT NULL AUTO_INCREMENT,
    FuncionarioID INT NOT NULL,
    SalarioAnterior DECIMAL(10,2) NOT NULL,
    SalarioNovo DECIMAL(10,2) NOT NULL,
    Motivo VARCHAR(100) NOT NULL,
    DataAlteracao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    RegistradoPor INT NOT NULL,
    PRIMARY KEY (LogSalarioID),
    CONSTRAINT FK_LogAlteracaoSalarial_Funcionario FOREIGN KEY (FuncionarioID) REFERENCES Funcionario (FuncionarioID),
    CONSTRAINT FK_LogAlteracaoSalarial_RegistradoPor FOREIGN KEY (RegistradoPor) REFERENCES Funcionario (FuncionarioID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE LogStatusPagamento (
    LogPagamentoID INT NOT NULL AUTO_INCREMENT,
    FolhaID INT NOT NULL,
    StatusAnterior VARCHAR(50) NULL,
    StatusNovo VARCHAR(50) NOT NULL,
    DataAlteracao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    Observacao VARCHAR(255) NULL,
    RegistradoPor INT NOT NULL,
    PRIMARY KEY (LogPagamentoID),
    CONSTRAINT FK_LogStatusPagamento_Folha FOREIGN KEY (FolhaID) REFERENCES FolhaPagamento (FolhaID),
    CONSTRAINT FK_LogStatusPagamento_RegistradoPor FOREIGN KEY (RegistradoPor) REFERENCES Funcionario (FuncionarioID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ConfiguracaoSistema (
    ConfiguracaoID INT NOT NULL AUTO_INCREMENT,
    Chave VARCHAR(80) NOT NULL,
    Valor VARCHAR(500) NOT NULL,
    Categoria VARCHAR(50) NOT NULL DEFAULT 'Geral',
    Descricao VARCHAR(255) NULL,
    TipoCampo VARCHAR(30) NOT NULL DEFAULT 'texto',
    Editavel TINYINT(1) NOT NULL DEFAULT 1,
    Ativo TINYINT(1) NOT NULL DEFAULT 1,
    AtualizadoEm DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (ConfiguracaoID),
    UNIQUE KEY UQ_ConfiguracaoSistema_Chave (Chave),
    KEY IX_ConfiguracaoSistema_Categoria (Categoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE FuncionarioMfa (
    FuncionarioMfaID INT NOT NULL AUTO_INCREMENT,
    FuncionarioID INT NOT NULL,
    SecretBase32 VARCHAR(128) NOT NULL,
    Provedor VARCHAR(30) NOT NULL DEFAULT 'TOTP',
    Ativo TINYINT(1) NOT NULL DEFAULT 1,
    CriadoEm DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UltimoUsoEm DATETIME NULL,
    PRIMARY KEY (FuncionarioMfaID),
    UNIQUE KEY UQ_FuncionarioMfa_Funcionario (FuncionarioID),
    CONSTRAINT FK_FuncionarioMfa_Funcionario FOREIGN KEY (FuncionarioID) REFERENCES Funcionario (FuncionarioID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE LogOperacaoSistema (
    LogOperacaoID INT NOT NULL AUTO_INCREMENT,
    TipoOperacao VARCHAR(50) NOT NULL,
    Entidade VARCHAR(80) NULL,
    EntidadeID VARCHAR(50) NULL,
    FuncionarioID INT NULL,
    Sucesso TINYINT(1) NOT NULL,
    Mensagem VARCHAR(255) NOT NULL,
    DetalhesJson LONGTEXT NULL,
    IpOrigem VARCHAR(60) NULL,
    UserAgent VARCHAR(255) NULL,
    DataOperacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (LogOperacaoID),
    KEY IX_LogOperacaoSistema_DataOperacao (DataOperacao),
    CONSTRAINT FK_LogOperacaoSistema_Funcionario FOREIGN KEY (FuncionarioID) REFERENCES Funcionario (FuncionarioID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ConsumoApiLog (
    ConsumoApiLogID INT NOT NULL AUTO_INCREMENT,
    Endpoint VARCHAR(180) NOT NULL,
    Metodo VARCHAR(10) NOT NULL,
    StatusHttp SMALLINT NOT NULL,
    FuncionarioID INT NULL,
    TempoRespostaMs INT NULL,
    IpOrigem VARCHAR(60) NULL,
    UserAgent VARCHAR(255) NULL,
    DataConsumo DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (ConsumoApiLogID),
    KEY IX_ConsumoApiLog_DataConsumo (DataConsumo),
    CONSTRAINT FK_ConsumoApiLog_Funcionario FOREIGN KEY (FuncionarioID) REFERENCES Funcionario (FuncionarioID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX IX_LogOperacaoSistema_DataOperacao ON LogOperacaoSistema (DataOperacao);
CREATE INDEX IX_ConsumoApiLog_DataConsumo ON ConsumoApiLog (DataConsumo);