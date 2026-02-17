-- ============================================================
-- MIGRACIÓN: Agregar columna prorrateo a tabla contracts
-- ============================================================
-- Archivo: 001_add_prorrateo_column.sql
-- Descripción: Agrega columna 'prorrateo' a la tabla 'contracts'
--              para marcar contratos con cálculo prorrateado
-- 
-- IMPORTANTE: Esta migración es IDEMPOTENTE
--             No genera error si la columna ya existe
-- ============================================================

-- Procedimiento para agregar columna solo si no existe
-- Esto evita errores en ejecuciones repetidas
DELIMITER //

DROP PROCEDURE IF EXISTS add_prorrateo_column_if_not_exists//

CREATE PROCEDURE add_prorrateo_column_if_not_exists()
BEGIN
    -- Verificar si la columna ya existe
    IF NOT EXISTS (
        SELECT 1 
        FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'contracts' 
        AND COLUMN_NAME = 'prorrateo'
    ) THEN
        -- La columna no existe, agregarla
        ALTER TABLE contracts 
        ADD COLUMN prorrateo INT NOT NULL DEFAULT 0 AFTER public_id;
        
        SELECT 'Columna prorrateo agregada exitosamente' AS resultado;
    ELSE
        -- La columna ya existe
        SELECT 'La columna prorrateo ya existe, no se realizaron cambios' AS resultado;
    END IF;
END//

DELIMITER ;

-- Ejecutar el procedimiento
CALL add_prorrateo_column_if_not_exists();

-- Limpiar el procedimiento temporal
DROP PROCEDURE IF EXISTS add_prorrateo_column_if_not_exists;

-- ============================================================
-- FIN DE MIGRACIÓN
-- ============================================================
