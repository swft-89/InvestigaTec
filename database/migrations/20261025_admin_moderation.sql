-- Aprobación de cuentas y herramientas de moderación para el panel administrativo.
ALTER TABLE usuarios
    ADD COLUMN IF NOT EXISTS ext_aprobada BOOLEAN NOT NULL DEFAULT TRUE,
    ADD COLUMN IF NOT EXISTS ext_administrador BOOLEAN NOT NULL DEFAULT FALSE;

ALTER TABLE usuarios
    ALTER COLUMN ext_aprobada SET DEFAULT FALSE;

CREATE TABLE IF NOT EXISTS ext_reporte_proyecto (
    id_reporte BIGSERIAL PRIMARY KEY,
    id_proyecto INTEGER NOT NULL
        REFERENCES proyectos(id_proyecto) ON DELETE CASCADE,
    id_reportado_por INTEGER NOT NULL
        REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
    motivo TEXT NOT NULL
        CHECK (char_length(motivo) BETWEEN 10 AND 3000),
    estado VARCHAR(20) NOT NULL DEFAULT 'PENDIENTE'
        CHECK (estado IN ('PENDIENTE', 'EN_REVISION', 'RESUELTO')),
    fecha_creacion TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    fecha_actualizacion TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS ix_ext_reporte_proyecto_estado_fecha
    ON ext_reporte_proyecto (estado, fecha_creacion DESC);

CREATE INDEX IF NOT EXISTS ix_ext_reporte_proyecto_proyecto_fecha
    ON ext_reporte_proyecto (id_proyecto, fecha_creacion DESC);

CREATE UNIQUE INDEX IF NOT EXISTS uq_ext_reporte_abierto_por_docente
    ON ext_reporte_proyecto (id_proyecto, id_reportado_por)
    WHERE estado <> 'RESUELTO';

ALTER TABLE ext_ayuda_sugerencias
    ADD COLUMN IF NOT EXISTS respuesta_por INTEGER
        REFERENCES usuarios(id_usuario) ON DELETE SET NULL;

ALTER TABLE ext_notificacion
    ADD COLUMN IF NOT EXISTS id_solicitud_ayuda BIGINT
        REFERENCES ext_ayuda_sugerencias(id_solicitud) ON DELETE SET NULL;

CREATE INDEX IF NOT EXISTS ix_ext_notificacion_solicitud_ayuda
    ON ext_notificacion (id_solicitud_ayuda)
    WHERE id_solicitud_ayuda IS NOT NULL;

CREATE OR REPLACE FUNCTION actualizar_fecha_ext_reporte_proyecto()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $$
BEGIN
    NEW.fecha_actualizacion := NOW();
    RETURN NEW;
END;
$$;

DROP TRIGGER IF EXISTS trg_actualizar_fecha_ext_reporte_proyecto
    ON ext_reporte_proyecto;

CREATE TRIGGER trg_actualizar_fecha_ext_reporte_proyecto
BEFORE UPDATE ON ext_reporte_proyecto
FOR EACH ROW
EXECUTE FUNCTION actualizar_fecha_ext_reporte_proyecto();
