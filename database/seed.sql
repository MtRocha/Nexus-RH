-- Nexus RH - Seed inicial de dados
-- Objetivo: popular tabelas base e Funcionario em MySQL.

USE if0_42083119_db_nexusrh;

START TRANSACTION;

INSERT IGNORE INTO CentroCusto (Codigo, Nome) VALUES ('TEC', 'Tecnologia');
INSERT IGNORE INTO CentroCusto (Codigo, Nome) VALUES ('RH', 'Recursos Humanos');
INSERT IGNORE INTO CentroCusto (Codigo, Nome) VALUES ('DIR', 'Diretoria');

INSERT IGNORE INTO Cargo (Nome, NivelHierarquico) VALUES ('Diretor', 'Estrategico');
INSERT IGNORE INTO Cargo (Nome, NivelHierarquico) VALUES ('Gerente de TI', 'Tatico');
INSERT IGNORE INTO Cargo (Nome, NivelHierarquico) VALUES ('Desenvolvedor Senior', 'Operacional');
INSERT IGNORE INTO Cargo (Nome, NivelHierarquico) VALUES ('Analista de RH', 'Operacional');

INSERT IGNORE INTO Funcionario (
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
    (SELECT CargoID FROM Cargo WHERE Nome = 'Diretor' LIMIT 1),
    (SELECT CentroCustoID FROM CentroCusto WHERE Codigo = 'DIR' LIMIT 1),
    NULL,
    22000.00,
    '2021-03-15',
    NULL,
    'Ativo'
);

INSERT IGNORE INTO Funcionario (
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
    (SELECT CargoID FROM Cargo WHERE Nome = 'Gerente de TI' LIMIT 1),
    (SELECT CentroCustoID FROM CentroCusto WHERE Codigo = 'TEC' LIMIT 1),
    NULL,
    14500.00,
    '2022-01-10',
    NULL,
    'Ativo'
);

INSERT IGNORE INTO Funcionario (
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
    (SELECT CargoID FROM Cargo WHERE Nome = 'Desenvolvedor Senior' LIMIT 1),
    (SELECT CentroCustoID FROM CentroCusto WHERE Codigo = 'TEC' LIMIT 1),
    (SELECT FuncionarioID FROM Funcionario WHERE CPF = '22345678902' LIMIT 1),
    9800.00,
    '2023-02-01',
    NULL,
    'Ativo'
);

INSERT IGNORE INTO Funcionario (
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
    (SELECT CargoID FROM Cargo WHERE Nome = 'Desenvolvedor Senior' LIMIT 1),
    (SELECT CentroCustoID FROM CentroCusto WHERE Codigo = 'TEC' LIMIT 1),
    (SELECT FuncionarioID FROM Funcionario WHERE CPF = '22345678902' LIMIT 1),
    9200.00,
    '2023-06-12',
    NULL,
    'Ativo'
);

INSERT IGNORE INTO Funcionario (
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
    (SELECT CargoID FROM Cargo WHERE Nome = 'Analista de RH' LIMIT 1),
    (SELECT CentroCustoID FROM CentroCusto WHERE Codigo = 'RH' LIMIT 1),
    (SELECT FuncionarioID FROM Funcionario WHERE CPF = '12345678901' LIMIT 1),
    6100.00,
    '2024-01-08',
    NULL,
    'Ativo'
);

INSERT IGNORE INTO Funcionario (
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
    (SELECT CargoID FROM Cargo WHERE Nome = 'Analista de RH' LIMIT 1),
    (SELECT CentroCustoID FROM CentroCusto WHERE Codigo = 'RH' LIMIT 1),
    (SELECT FuncionarioID FROM Funcionario WHERE CPF = '12345678901' LIMIT 1),
    5800.00,
    '2024-03-18',
    NULL,
    'Ativo'
);

INSERT IGNORE INTO ConfiguracaoSistema (Chave, Valor, Categoria, Descricao, TipoCampo, Editavel, Ativo)
VALUES ('empresa.nome', 'Nexus RH', 'Geral', 'Nome exibido no sistema', 'texto', 1, 1);

INSERT IGNORE INTO ConfiguracaoSistema (Chave, Valor, Categoria, Descricao, TipoCampo, Editavel, Ativo)
VALUES ('empresa.slogan', 'Gestao de RH, jornadas e compliance em um unico painel.', 'Geral', 'Texto institucional', 'texto', 1, 1);

INSERT IGNORE INTO ConfiguracaoSistema (Chave, Valor, Categoria, Descricao, TipoCampo, Editavel, Ativo)
VALUES ('mapa.latitude', '-23.5629', 'Mapa', 'Latitude do ponto principal', 'numero', 1, 1);

INSERT IGNORE INTO ConfiguracaoSistema (Chave, Valor, Categoria, Descricao, TipoCampo, Editavel, Ativo)
VALUES ('mapa.longitude', '-46.6560', 'Mapa', 'Longitude do ponto principal', 'numero', 1, 1);

INSERT IGNORE INTO ConfiguracaoSistema (Chave, Valor, Categoria, Descricao, TipoCampo, Editavel, Ativo)
VALUES ('mapa.zoom', '15', 'Mapa', 'Nivel de zoom do mapa', 'numero', 1, 1);

INSERT IGNORE INTO ConfiguracaoSistema (Chave, Valor, Categoria, Descricao, TipoCampo, Editavel, Ativo)
VALUES ('mfa.obrigatorio', '0', 'Seguranca', 'Exige MFA no login', 'booleano', 1, 1);

INSERT IGNORE INTO ConfiguracaoSistema (Chave, Valor, Categoria, Descricao, TipoCampo, Editavel, Ativo)
VALUES ('api.publica.habilitada', '1', 'Integracao', 'Disponibiliza consultas por API', 'booleano', 1, 1);

INSERT IGNORE INTO ConfiguracaoSistema (Chave, Valor, Categoria, Descricao, TipoCampo, Editavel, Ativo)
VALUES ('dashboard.atualizacao_segundos', '60', 'Dashboard', 'Intervalo padrao de atualizacao', 'numero', 1, 1);

COMMIT;

SELECT
    f.FuncionarioID,
    f.Nome,
    f.CPF,
    c.Nome AS Cargo,
    cc.Nome AS CentroCusto,
    s.Nome AS Supervisor,
    f.SalarioAtual,
    f.Status
FROM Funcionario f
INNER JOIN Cargo c ON c.CargoID = f.CargoID
INNER JOIN CentroCusto cc ON cc.CentroCustoID = f.CentroCustoID
LEFT JOIN Funcionario s ON s.FuncionarioID = f.SupervisorID
ORDER BY f.FuncionarioID;