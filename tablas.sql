CREATE DATABASE IF NOT EXISTS HospitalTalca;
USE HospitalTalca;

-- 1. Tabla de Usuarios (NUEVA)
CREATE TABLE Usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(50) NOT NULL,
    rol VARCHAR(20) NOT NULL -- 'JEFE' (Admin) o 'TEC' (Medico)
);

-- Insertamos los usuarios por defecto
INSERT INTO Usuarios (username, password, rol) VALUES 
('admin', 'admin', 'JEFE'),
('medico', 'medico', 'TEC');

-- 2. Tabla Pacientes
CREATE TABLE Pacientes (
    id_paciente INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    rut VARCHAR(12) NOT NULL UNIQUE, 
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Tabla Biopsias
CREATE TABLE Biopsias (
    id_biopsia INT AUTO_INCREMENT PRIMARY KEY,
    id_paciente INT NOT NULL,
    organo VARCHAR(100) NOT NULL,
    fecha_ingreso DATE NOT NULL,
    fecha_expiracion DATE,
    observaciones TEXT,
    CONSTRAINT fk_paciente FOREIGN KEY (id_paciente) REFERENCES Pacientes(id_paciente) ON DELETE CASCADE
);

-- 4. Trigger para fecha de expiración (30 días)
DELIMITER //
CREATE TRIGGER calcular_expiracion_biopsia
BEFORE INSERT ON Biopsias
FOR EACH ROW
BEGIN
    SET NEW.fecha_expiracion = DATE_ADD(NEW.fecha_ingreso, INTERVAL 30 DAY);
END;
//

DELIMITER //

CREATE TRIGGER calcular_expiracion_biopsia_actualizacion
BEFORE UPDATE ON biopsias
FOR EACH ROW
BEGIN
    IF NEW.fecha_ingreso <> OLD.fecha_ingreso THEN
        SET NEW.fecha_expiracion = DATE_ADD(NEW.fecha_ingreso, INTERVAL 30 DAY);
    END IF;
END;
//

DELIMITER ;
-- 5. Procedimiento para datos de prueba (Opcional, igual que antes)
DELIMITER //
CREATE PROCEDURE GenerarDatosMasivos()
BEGIN
    DECLARE i INT DEFAULT 0;
    DECLARE rand_nombre VARCHAR(100);
    DECLARE ape1 VARCHAR(50);
    DECLARE ape2 VARCHAR(50);
    DECLARE rand_apellido_completo VARCHAR(100);
    DECLARE rand_rut VARCHAR(12);
    DECLARE rand_organo VARCHAR(100);
    DECLARE rand_fecha DATE;
    DECLARE last_id INT;

    WHILE i < 1500 DO

        SET rand_nombre = ELT(FLOOR(1 + (RAND() * 50)), 
            'Juan', 'María', 'Pedro', 'Ana', 'Luis', 'Carmen', 'José', 'Francisca', 
            'Diego', 'Camila', 'Jorge', 'Valentina', 'Carlos', 'Daniela', 'Manuel',
            'Sofía', 'Andrés', 'Javiera', 'Miguel', 'Carolina', 'David', 'Paula', 
            'Roberto', 'Isidora', 'Fernando', 'Antonia', 'Felipe', 'Gabriela', 'Ricardo', 
            'Constanza', 'Pablo', 'Catalina', 'Francisco', 'Fernanda', 'Gabriel', 'Natalia', 
            'Tomás', 'Victoria', 'Alejandro', 'Beatriz', 'Héctor', 'Teresa', 'Sergio', 
            'Patricia', 'Eduardo', 'Monserrat', 'Matías', 'Estefanía', 'Nicolás', 'Alejandra');

        SET ape1 = ELT(FLOOR(1 + (RAND() * 35)), 
            'González', 'Muñoz', 'Rojas', 'Díaz', 'Pérez', 'Soto', 'Contreras', 'Silva', 
            'Martínez', 'Sepúlveda', 'Morales', 'Rodríguez', 'López', 'Fuentes', 'Hernández', 
            'Torres', 'Araya', 'Flores', 'Espinoza', 'Valenzuela', 'Castillo', 'Tapia', 
            'Reyes', 'Gutiérrez', 'Castro', 'Pizarro', 'Álvarez', 'Vásquez', 'Sánchez', 
            'Fernández', 'Ramírez', 'Carrasco', 'Gómez', 'Cortés', 'Herrera');

 
        SET ape2 = ELT(FLOOR(1 + (RAND() * 35)), 
            'González', 'Muñoz', 'Rojas', 'Díaz', 'Pérez', 'Soto', 'Contreras', 'Silva', 
            'Martínez', 'Sepúlveda', 'Morales', 'Rodríguez', 'López', 'Fuentes', 'Hernández', 
            'Torres', 'Araya', 'Flores', 'Espinoza', 'Valenzuela', 'Castillo', 'Tapia', 
            'Reyes', 'Gutiérrez', 'Castro', 'Pizarro', 'Álvarez', 'Vásquez', 'Sánchez', 
            'Fernández', 'Ramírez', 'Carrasco', 'Gómez', 'Cortés', 'Herrera');

    
        SET rand_apellido_completo = CONCAT(ape1, ' ', ape2);

       
        SET rand_rut = CONCAT(FLOOR(5000000 + (RAND() * 20000000)), '-', FLOOR(0 + (RAND() * 9)));
        
     
        INSERT IGNORE INTO Pacientes (nombre, apellido, rut) 
        VALUES (rand_nombre, rand_apellido_completo, rand_rut);
        
     
        IF ROW_COUNT() > 0 THEN
            SET last_id = LAST_INSERT_ID();
            SET rand_organo = ELT(FLOOR(1 + (RAND() * 8)), 'Hígado', 'Riñón', 'Estómago', 'Piel', 'Pulmón', 'Colon', 'Próstata', 'Tiroides');
            SET rand_fecha = DATE_ADD('2023-01-01', INTERVAL FLOOR(RAND() * 365) DAY);
            
            INSERT INTO Biopsias (id_paciente, organo, fecha_ingreso, observaciones) 
            VALUES (last_id, rand_organo, rand_fecha, 'Biopsia generada automáticamente por sistema');
            
            SET i = i + 1;
        END IF;
    END WHILE;
END //
DELIMITER ;

CALL GenerarDatosMasivos();


SELECT COUNT(*) as Total_Pacientes FROM Pacientes;
