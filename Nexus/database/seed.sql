-- Nexus RH - Seed inicial de dados
-- Objetivo: popular tabelas base e Funcionario respeitando integridade referencial.

USE NexusRH;
GO

SET XACT_ABORT ON;
GO

BEGIN TRANSACTION;

BEGIN TRY
    -- 1) Tabelas independentes
    -- CentroCusto
    IF NOT EXISTS (SELECT 1 FROM dbo.CentroCusto WHERE Codigo = 'TEC')
        INSERT INTO dbo.CentroCusto (Codigo, Nome) VALUES ('TEC', 'Tecnologia');

    IF NOT EXISTS (SELECT 1 FROM dbo.CentroCusto WHERE Codigo = 'RH')
        INSERT INTO dbo.CentroCusto (Codigo, Nome) VALUES ('RH', 'Recursos Humanos');

    IF NOT EXISTS (SELECT 1 FROM dbo.CentroCusto WHERE Codigo = 'DIR')
        INSERT INTO dbo.CentroCusto (Codigo, Nome) VALUES ('DIR', 'Diretoria');

    -- Cargo
    IF NOT EXISTS (SELECT 1 FROM dbo.Cargo WHERE Nome = 'Diretor')
        INSERT INTO dbo.Cargo (Nome, NivelHierarquico) VALUES ('Diretor', 'Estrategico');

    IF NOT EXISTS (SELECT 1 FROM dbo.Cargo WHERE Nome = 'Gerente de TI')
        INSERT INTO dbo.Cargo (Nome, NivelHierarquico) VALUES ('Gerente de TI', 'Tatico');

    IF NOT EXISTS (SELECT 1 FROM dbo.Cargo WHERE Nome = 'Desenvolvedor Senior')
        INSERT INTO dbo.Cargo (Nome, NivelHierarquico) VALUES ('Desenvolvedor Senior', 'Operacional');

    IF NOT EXISTS (SELECT 1 FROM dbo.Cargo WHERE Nome = 'Analista de RH')
        INSERT INTO dbo.Cargo (Nome, NivelHierarquico) VALUES ('Analista de RH', 'Operacional');

    -- 2) Funcionarios de nivel gerencial (SupervisorID = NULL)
    IF NOT EXISTS (SELECT 1 FROM dbo.Funcionario WHERE CPF = '12345678901')
        INSERT INTO dbo.Funcionario (
            Nome, CPF, Email, SenhaHash, PerfilAcesso,
            CargoID, CentroCustoID, SupervisorID,
            SalarioAtual, DataAdmissao, DataDesligamento, Status
        )
        VALUES (
            'Marcos Vieira',
            '12345678901',
            'marcos.vieira@nexusrh.local',
            '$2y$10$MOGm4wcKqvEeUcMb8RBvY.pY8bOKf1Yg7vlWJvHj6WSh4Q3W8w8fa',
            'Administrador',
            (SELECT TOP 1 CargoID FROM dbo.Cargo WHERE Nome = 'Diretor'),
            (SELECT TOP 1 CentroCustoID FROM dbo.CentroCusto WHERE Codigo = 'DIR'),
            NULL,
            22000.00,
            '2021-03-15',
            NULL,
            'Ativo'
        );

    IF NOT EXISTS (SELECT 1 FROM dbo.Funcionario WHERE CPF = '22345678902')
        INSERT INTO dbo.Funcionario (
            Nome, CPF, Email, SenhaHash, PerfilAcesso,
            CargoID, CentroCustoID, SupervisorID,
            SalarioAtual, DataAdmissao, DataDesligamento, Status
        )
        VALUES (
            'Fernanda Costa',
            '22345678902',
            'fernanda.costa@nexusrh.local',
            '$2y$10$MOGm4wcKqvEeUcMb8RBvY.pY8bOKf1Yg7vlWJvHj6WSh4Q3W8w8fa',
            'Gestor',
            (SELECT TOP 1 CargoID FROM dbo.Cargo WHERE Nome = 'Gerente de TI'),
            (SELECT TOP 1 CentroCustoID FROM dbo.CentroCusto WHERE Codigo = 'TEC'),
            NULL,
            14500.00,
            '2022-01-10',
            NULL,
            'Ativo'
        );

    -- 3) Funcionarios de nivel operacional (com SupervisorID)
    IF NOT EXISTS (SELECT 1 FROM dbo.Funcionario WHERE CPF = '32345678903')
        INSERT INTO dbo.Funcionario (
            Nome, CPF, Email, SenhaHash, PerfilAcesso,
            CargoID, CentroCustoID, SupervisorID,
            SalarioAtual, DataAdmissao, DataDesligamento, Status
        )
        VALUES (
            'Lucas Araujo',
            '32345678903',
            'lucas.araujo@nexusrh.local',
            '$2y$10$MOGm4wcKqvEeUcMb8RBvY.pY8bOKf1Yg7vlWJvHj6WSh4Q3W8w8fa',
            'Usuario',
            (SELECT TOP 1 CargoID FROM dbo.Cargo WHERE Nome = 'Desenvolvedor Senior'),
            (SELECT TOP 1 CentroCustoID FROM dbo.CentroCusto WHERE Codigo = 'TEC'),
            (SELECT TOP 1 FuncionarioID FROM dbo.Funcionario WHERE CPF = '22345678902'),
            9800.00,
            '2023-02-01',
            NULL,
            'Ativo'
        );

    IF NOT EXISTS (SELECT 1 FROM dbo.Funcionario WHERE CPF = '42345678904')
        INSERT INTO dbo.Funcionario (
            Nome, CPF, Email, SenhaHash, PerfilAcesso,
            CargoID, CentroCustoID, SupervisorID,
            SalarioAtual, DataAdmissao, DataDesligamento, Status
        )
        VALUES (
            'Carla Menezes',
            '42345678904',
            'carla.menezes@nexusrh.local',
            '$2y$10$MOGm4wcKqvEeUcMb8RBvY.pY8bOKf1Yg7vlWJvHj6WSh4Q3W8w8fa',
            'Usuario',
            (SELECT TOP 1 CargoID FROM dbo.Cargo WHERE Nome = 'Desenvolvedor Senior'),
            (SELECT TOP 1 CentroCustoID FROM dbo.CentroCusto WHERE Codigo = 'TEC'),
            (SELECT TOP 1 FuncionarioID FROM dbo.Funcionario WHERE CPF = '22345678902'),
            9200.00,
            '2023-06-12',
            NULL,
            'Ativo'
        );

    IF NOT EXISTS (SELECT 1 FROM dbo.Funcionario WHERE CPF = '52345678905')
        INSERT INTO dbo.Funcionario (
            Nome, CPF, Email, SenhaHash, PerfilAcesso,
            CargoID, CentroCustoID, SupervisorID,
            SalarioAtual, DataAdmissao, DataDesligamento, Status
        )
        VALUES (
            'Patricia Lima',
            '52345678905',
            'patricia.lima@nexusrh.local',
            '$2y$10$MOGm4wcKqvEeUcMb8RBvY.pY8bOKf1Yg7vlWJvHj6WSh4Q3W8w8fa',
            'Usuario',
            (SELECT TOP 1 CargoID FROM dbo.Cargo WHERE Nome = 'Analista de RH'),
            (SELECT TOP 1 CentroCustoID FROM dbo.CentroCusto WHERE Codigo = 'RH'),
            (SELECT TOP 1 FuncionarioID FROM dbo.Funcionario WHERE CPF = '12345678901'),
            6100.00,
            '2024-01-08',
            NULL,
            'Ativo'
        );

    IF NOT EXISTS (SELECT 1 FROM dbo.Funcionario WHERE CPF = '62345678906')
        INSERT INTO dbo.Funcionario (
            Nome, CPF, Email, SenhaHash, PerfilAcesso,
            CargoID, CentroCustoID, SupervisorID,
            SalarioAtual, DataAdmissao, DataDesligamento, Status
        )
        VALUES (
            'Renato Souza',
            '62345678906',
            'renato.souza@nexusrh.local',
            '$2y$10$MOGm4wcKqvEeUcMb8RBvY.pY8bOKf1Yg7vlWJvHj6WSh4Q3W8w8fa',
            'Usuario',
            (SELECT TOP 1 CargoID FROM dbo.Cargo WHERE Nome = 'Analista de RH'),
            (SELECT TOP 1 CentroCustoID FROM dbo.CentroCusto WHERE Codigo = 'RH'),
            (SELECT TOP 1 FuncionarioID FROM dbo.Funcionario WHERE CPF = '12345678901'),
            5800.00,
            '2024-03-18',
            NULL,
            'Ativo'
        );

    COMMIT TRANSACTION;
END TRY
BEGIN CATCH
    IF @@TRANCOUNT > 0
        ROLLBACK TRANSACTION;

    THROW;
END CATCH;
GO

-- Consulta rapida de verificacao da hierarquia
SELECT
    f.FuncionarioID,
    f.Nome,
    f.CPF,
    c.Nome AS Cargo,
    cc.Nome AS CentroCusto,
    s.Nome AS Supervisor,
    f.SalarioAtual,
    f.Status
FROM dbo.Funcionario f
INNER JOIN dbo.Cargo c ON c.CargoID = f.CargoID
INNER JOIN dbo.CentroCusto cc ON cc.CentroCustoID = f.CentroCustoID
LEFT JOIN dbo.Funcionario s ON s.FuncionarioID = f.SupervisorID
ORDER BY f.FuncionarioID;
GO
