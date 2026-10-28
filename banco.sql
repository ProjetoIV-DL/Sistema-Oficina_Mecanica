CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    telefone VARCHAR(20) NOT NULL,
    email VARCHAR(100)
);

CREATE TABLE IF NOT EXISTS veiculos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    placa VARCHAR(10) NOT NULL UNIQUE,
    marca VARCHAR(50) NOT NULL,
    modelo VARCHAR(50) NOT NULL,
    ano INT,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id)
);

CREATE TABLE IF NOT EXISTS ordens_servico (
    id INT AUTO_INCREMENT PRIMARY KEY,
    veiculo_id INT NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'Aberta',
    data_entrada DATE NOT NULL,
    FOREIGN KEY (veiculo_id) REFERENCES veiculos(id)
);

INSERT INTO clientes (nome, telefone, email) VALUES
('João Silva', '18 99999-1111', 'joao@email.com'),
('Maria Souza', '18 98888-2222', 'maria@email.com');

INSERT INTO veiculos (cliente_id, placa, marca, modelo, ano) VALUES
(1, 'ABC1D23', 'Fiat', 'Uno', 2015),
(1, 'XYZ9K88', 'Honda', 'Civic', 2019),
(2, 'QWE4R56', 'Chevrolet', 'Onix', 2021);

INSERT INTO ordens_servico (veiculo_id, descricao, valor, status, data_entrada) VALUES
(1, 'Troca de óleo e filtros', 250.00, 'Concluída', '2026-10-20'),
(2, 'Revisão de freios', 480.50, 'Em andamento', '2026-10-25'),
(3, 'Alinhamento e balanceamento', 160.00, 'Aberta', '2026-10-26');