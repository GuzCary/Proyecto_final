-- dev/migracion_db_existente.sql
-- Solo si NO quieren volver a correr script.sql completo (que borra y recrea la base).
-- Aplica los cambios de estructura de esta version sobre una base ya existente.

USE proyecto_final;

-- las marcas ahora guardan la fecha ademas de la hora
ALTER TABLE RegistroMarca ADD COLUMN fecha DATE AFTER idUsuario;
