-- Datos mínimos de EJEMPLO para probar registro local.
-- Ejecuta SOLO en tu base local investigatec y modifica estos nombres por los oficiales.
INSERT INTO departamentos(nombre,descripcion) VALUES
('Sistemas y Computación', 'Departamento de ejemplo para desarrollo')
ON CONFLICT (nombre) DO NOTHING;
INSERT INTO carreras(nombre,siglas,id_departamento)
SELECT 'Ingeniería en Sistemas Computacionales','ISC',id_departamento
FROM departamentos WHERE nombre='Sistemas y Computación'
ON CONFLICT DO NOTHING;
