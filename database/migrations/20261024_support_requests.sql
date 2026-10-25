-- Solicitudes de ayuda, sugerencias y reportes enviados desde la página de soporte.
CREATE TABLE IF NOT EXISTS ext_ayuda_sugerencias (
    id_solicitud BIGSERIAL PRIMARY KEY,
    id_usuario INTEGER
        REFERENCES usuarios(id_usuario) ON DELETE SET NULL,
    tipo VARCHAR(20) NOT NULL
        CHECK (tipo IN ('AYUDA', 'SUGERENCIA', 'PROBLEMA')),
    nombre_contacto VARCHAR(160) NOT NULL,
    correo_contacto VARCHAR(255) NOT NULL,
    asunto VARCHAR(150) NOT NULL
        CHECK (char_length(asunto) BETWEEN 3 AND 150),
    mensaje TEXT NOT NULL
        CHECK (char_length(mensaje) BETWEEN 10 AND 5000),
    estado VARCHAR(20) NOT NULL DEFAULT 'PENDIENTE'
        CHECK (estado IN ('PENDIENTE', 'EN_REVISION', 'RESPONDIDA', 'CERRADA')),
    respuesta TEXT,
    fecha_creacion TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    fecha_actualizacion TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS ix_ext_ayuda_sugerencias_estado_fecha
    ON ext_ayuda_sugerencias (estado, fecha_creacion DESC);

CREATE INDEX IF NOT EXISTS ix_ext_ayuda_sugerencias_usuario_fecha
    ON ext_ayuda_sugerencias (id_usuario, fecha_creacion DESC)
    WHERE id_usuario IS NOT NULL;

CREATE OR REPLACE FUNCTION actualizar_fecha_ext_ayuda_sugerencias()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $$
BEGIN
    NEW.fecha_actualizacion := NOW();
    RETURN NEW;
END;
$$;

DROP TRIGGER IF EXISTS trg_actualizar_fecha_ext_ayuda_sugerencias
    ON ext_ayuda_sugerencias;

CREATE TRIGGER trg_actualizar_fecha_ext_ayuda_sugerencias
BEFORE UPDATE ON ext_ayuda_sugerencias
FOR EACH ROW
EXECUTE FUNCTION actualizar_fecha_ext_ayuda_sugerencias();
