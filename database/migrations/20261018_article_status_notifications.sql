ALTER TABLE ext_notificacion
    ADD COLUMN IF NOT EXISTS leida BOOLEAN NOT NULL DEFAULT FALSE;

CREATE INDEX IF NOT EXISTS ix_ext_notificacion_usuario_fecha
    ON ext_notificacion (id_usuario, fecha_envio DESC);

UPDATE ext_notificacion
SET estado_envio = 'NO_APLICA'
WHERE tipo = 'CAMBIO_ESTADO'
  AND estado_envio <> 'NO_APLICA';

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
               'NO_APLICA'
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
