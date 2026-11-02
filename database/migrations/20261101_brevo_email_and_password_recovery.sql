ALTER TABLE ext_notificacion
    ALTER COLUMN estado_envio SET DEFAULT 'PENDIENTE';

ALTER TABLE ext_recuperacion_acceso
    ADD COLUMN IF NOT EXISTS solicitado_en TIMESTAMPTZ NOT NULL DEFAULT NOW();

CREATE INDEX IF NOT EXISTS ix_ext_recuperacion_usuario_solicitado
    ON ext_recuperacion_acceso (id_usuario, solicitado_en DESC);

CREATE INDEX IF NOT EXISTS ix_ext_recuperacion_token_activo
    ON ext_recuperacion_acceso (token_hash)
    WHERE usado IS FALSE;

CREATE OR REPLACE FUNCTION notificar_cambio_estado_proyecto()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $$
DECLARE
    estado_anterior_texto TEXT;
    estado_nuevo_texto TEXT;
BEGIN
    IF OLD.estado IS DISTINCT FROM NEW.estado THEN
        estado_anterior_texto := CASE OLD.estado
            WHEN 'BORRADOR' THEN 'Borrador'
            WHEN 'EN_REVISION' THEN 'En revisión'
            WHEN 'REQUIERE_CAMBIOS' THEN 'Requiere cambios'
            WHEN 'APROBADO' THEN 'Aprobado'
            WHEN 'RECHAZADO' THEN 'Rechazado'
            WHEN 'PUBLICADO' THEN 'Publicado'
            ELSE OLD.estado::TEXT
        END;
        estado_nuevo_texto := CASE NEW.estado
            WHEN 'BORRADOR' THEN 'Borrador'
            WHEN 'EN_REVISION' THEN 'En revisión'
            WHEN 'REQUIERE_CAMBIOS' THEN 'Requiere cambios'
            WHEN 'APROBADO' THEN 'Aprobado'
            WHEN 'RECHAZADO' THEN 'Rechazado'
            WHEN 'PUBLICADO' THEN 'Publicado'
            ELSE NEW.estado::TEXT
        END;

        INSERT INTO ext_notificacion
            (id_usuario, id_proyecto, tipo, mensaje, estado_envio)
        SELECT pa.id_usuario, NEW.id_proyecto, 'CAMBIO_ESTADO',
               'El estado de "' || NEW.titulo || '" cambió de ' ||
               estado_anterior_texto || ' a ' || estado_nuevo_texto || '.',
               'PENDIENTE'
        FROM proyectos_autores pa
        WHERE pa.id_proyecto = NEW.id_proyecto
          AND pa.tipo_participacion = 'AUTOR';
    END IF;
    RETURN NEW;
END;
$$;

DROP TRIGGER IF EXISTS trg_notificar_cambio_estado_proyecto ON proyectos;
CREATE TRIGGER trg_notificar_cambio_estado_proyecto
AFTER UPDATE OF estado ON proyectos
FOR EACH ROW
EXECUTE FUNCTION notificar_cambio_estado_proyecto();
