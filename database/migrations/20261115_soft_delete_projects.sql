ALTER TABLE proyectos
    ADD COLUMN IF NOT EXISTS eliminado BOOLEAN NOT NULL DEFAULT FALSE;

CREATE INDEX IF NOT EXISTS ix_proyectos_eliminado_estado
    ON proyectos (eliminado, estado);
