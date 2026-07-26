DROP DATABASE IF EXISTS proyecto_final;

-- CREACION DEL USUARIO DE LA BASE DE DATOS

CREATE DATABASE proyecto_final;


USE proyecto_final;

CREATE USER IF NOT EXISTS 'webcore'@'localhost' IDENTIFIED BY '1234';
ALTER USER 'webcore'@'localhost' IDENTIFIED BY '1234';
GRANT ALL PRIVILEGES ON proyecto_final.* TO 'webcore'@'localhost';
FLUSH PRIVILEGES;




-- Tabla que almacena las diferentes sucursales de la empresa
CREATE TABLE Sucursal (
    id INT AUTO_INCREMENT,
    nombre VARCHAR(100),
    PRIMARY KEY (id)
);

-- Tabla que almacena la informacion de las licencias de conducir
CREATE TABLE LicenciaDeConducir (
    id INT AUTO_INCREMENT,
    tipo VARCHAR(100),
    fechaVencimiento DATE,
    PRIMARY KEY (id)
);

-- Tabla de productos de limpieza
CREATE TABLE Productos (
    id INT AUTO_INCREMENT,
    stock INT,
    nombre VARCHAR(100),
    PRIMARY KEY (id)
);

-- Tabla de categorias para clasificar vehiculos
CREATE TABLE Categoria (
    id INT AUTO_INCREMENT,
    nombre VARCHAR(100),
    PRIMARY KEY (id)
);

-- Tabla principal de usuarios del sistema
-- Aqui se guardan los datos de login, rol y demas
CREATE TABLE Usuarios (
    id INT AUTO_INCREMENT,
    idSucursal INT,
    salarioPorHora FLOAT,
    usuario VARCHAR(100),
    contraseña VARCHAR(255),
    diasDeLicencia INT,
    fechaDeContrato DATE,
    diasDeTrabajo INT,
    horarioDeEntrada TIME,
    horarioDeSalida TIME,
    rol VARCHAR(50),
    PRIMARY KEY (id),
    FOREIGN KEY (idSucursal) REFERENCES Sucursal(id)
);

-- Tabla para personal de limpieza
CREATE TABLE Limpieza (
    id INT,
    PRIMARY KEY (id),
    FOREIGN KEY (id) REFERENCES Usuarios(id)
);

-- Tabla para funcionarios generales (vendedores o administradores)
CREATE TABLE Funcionario (
    id INT,
    idLicenciaDeConducir INT,
    PRIMARY KEY (id),
    FOREIGN KEY (id) REFERENCES Usuarios(id),
    FOREIGN KEY (idLicenciaDeConducir) REFERENCES LicenciaDeConducir(id)
);

-- Tabla para vendedores
CREATE TABLE Vendedor (
    id INT,
    PRIMARY KEY (id),
    FOREIGN KEY (id) REFERENCES Funcionario(id)
);

-- Tabla para administradores
CREATE TABLE Administrador (
    id INT,
    PRIMARY KEY (id),
    FOREIGN KEY (id) REFERENCES Funcionario(id)
);

-- Tabla para registrar bajas de empleados (renuncias o despidos)
CREATE TABLE Baja (
    id INT AUTO_INCREMENT,
    idUsuario INT,
    tipo VARCHAR(100),
    fecha DATE,
    PRIMARY KEY (id),
    FOREIGN KEY (idUsuario) REFERENCES Usuarios(id)
);

-- Tabla para registrar aumentos de sueldo
CREATE TABLE AumentosDeSueldo (
    id INT AUTO_INCREMENT,
    idUsuario INT,
    idAdministrador INT,
    tipo VARCHAR(100),
    valor FLOAT,
    fecha DATE,
    PRIMARY KEY (id),
    FOREIGN KEY (idUsuario) REFERENCES Usuarios(id),
    FOREIGN KEY (idAdministrador) REFERENCES Administrador(id)
);

-- Tabla para el registro de marca de empleados (entrada o salida)
CREATE TABLE RegistroMarca (
    id INT AUTO_INCREMENT,
    idUsuario INT,
    hora TIME,
    direccion VARCHAR(100),
    PRIMARY KEY (id),
    FOREIGN KEY (idUsuario) REFERENCES Usuarios(id)
);

-- Tabla de vehiculos
CREATE TABLE Vehiculo (
    id INT AUTO_INCREMENT,
    idSucursal INT,
    marca VARCHAR(100),
    descripcion VARCHAR(255),
    modelo VARCHAR(100),
    potencia INT,
    estado INT,
    enlaceDocOficial VARCHAR(255),
    consumo FLOAT,
    patente VARCHAR(100),
    seguroSOA FLOAT,
    seguroTerceros FLOAT,
    seguroTotal FLOAT,
    anio INT,
    km INT,
    precioMinimo FLOAT,
    precio FLOAT,
    PRIMARY KEY (id),
    FOREIGN KEY (idSucursal) REFERENCES Sucursal(id)
);

-- Tabla de ventas realizadas
CREATE TABLE Ventas (
    idVenta INT AUTO_INCREMENT,
    idVehiculo INT,
    idFuncionario INT,
    fecha DATE,
    PRIMARY KEY (idVenta),
    FOREIGN KEY (idVehiculo) REFERENCES Vehiculo(id),
    FOREIGN KEY (idFuncionario) REFERENCES Funcionario(id)
);

-- Tabla de transacciones de plata que entra o sale
CREATE TABLE Transacciones (
    idTransaccion INT AUTO_INCREMENT,
    idFuncionario INT,
    idSucursal INT,
    tipo VARCHAR(100),
    monto FLOAT,
    justificacion VARCHAR(255),
    fecha DATE,
    PRIMARY KEY (idTransaccion),
    FOREIGN KEY (idFuncionario) REFERENCES Funcionario(id),
    FOREIGN KEY (idSucursal) REFERENCES Sucursal(id)
);

-- Tabla de sanciones a empleados
CREATE TABLE Sanciona (
    idSancion INT AUTO_INCREMENT,
    idAdministrador INT,
    idUsuario INT,
    fecha DATE,
    tipo VARCHAR(100),
    motivo VARCHAR(255),
    consecuencia VARCHAR(255),
    PRIMARY KEY (idSancion),
    FOREIGN KEY (idAdministrador) REFERENCES Administrador(id),
    FOREIGN KEY (idUsuario) REFERENCES Usuarios(id)
);

-- Tabla de gastos de productos de limpieza
CREATE TABLE Gasta (
    idGasto INT AUTO_INCREMENT,
    idLimpieza INT,
    idProducto INT,
    cantidad INT,
    PRIMARY KEY (idGasto),
    FOREIGN KEY (idLimpieza) REFERENCES Limpieza(id),
    FOREIGN KEY (idProducto) REFERENCES Productos(id)
);

-- Tabla intermedia para relacionar vehiculos con categorias
CREATE TABLE Tiene (
    idVehiculo INT,
    idCategoria INT,
    PRIMARY KEY (idVehiculo, idCategoria),
    FOREIGN KEY (idVehiculo) REFERENCES Vehiculo(id),
    FOREIGN KEY (idCategoria) REFERENCES Categoria(id)
);


-- DATOS DE PRUEBA PARA EL SISTEMA DE LOGIN
-- correr actualizar contraseñas para obtener contraseñas las hasheadas


INSERT INTO Sucursal (nombre) VALUES ('Sucursal Central');

INSERT INTO LicenciaDeConducir (tipo, fechaVencimiento) VALUES
('Profesional', '2026-12-31'),
('Profesional', '2027-12-31');


INSERT INTO Usuarios (
    idSucursal,
    salarioPorHora,
    usuario,
    contraseña,
    diasDeLicencia,
    fechaDeContrato,
    diasDeTrabajo,
    horarioDeEntrada,
    horarioDeSalida,
    rol
) VALUES
(1, 500.00, 'admin', 'admin123', 20, '2020-01-15', 5, '08:00:00', '17:00:00', 'admin'),
(1, 350.00, 'vendedor', 'vendedor123', 15, '2021-03-10', 6, '09:00:00', '18:00:00', 'user'),
(1, 300.00, 'limpieza', 'limpieza123', 10, '2022-07-01', 5, '07:00:00', '14:00:00', 'limp');

-- El usuario admin tambien es funcionario y administrador
INSERT INTO Funcionario (id, idLicenciaDeConducir) VALUES (1, 1);
INSERT INTO Administrador (id) VALUES (1);

-- El usuario vendedor es funcionario y vendedor
INSERT INTO Funcionario (id, idLicenciaDeConducir) VALUES (2, 2);
INSERT INTO Vendedor (id) VALUES (2);

-- El usuario limpieza solo se registra en su tabla
INSERT INTO Limpieza (id) VALUES (3);
