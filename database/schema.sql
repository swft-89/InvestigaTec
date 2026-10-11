-- BASE INICIAL PROPUESTA PARA POSTGRESQL 16. No ejecutada por el prototipo.
-- El maestro se representa como destinatario externo; no inicia sesión en esta versión.
CREATE TABLE estudiantes (id BIGSERIAL PRIMARY KEY, nombre VARCHAR(160) NOT NULL, correo VARCHAR(180) UNIQUE NOT NULL, password_hash TEXT NOT NULL, matricula VARCHAR(30) UNIQUE, carrera VARCHAR(120), habilidades TEXT, intereses TEXT, imagen_perfil TEXT, creado_en TIMESTAMPTZ DEFAULT now());
CREATE TABLE articulos (id BIGSERIAL PRIMARY KEY, estudiante_id BIGINT NOT NULL REFERENCES estudiantes(id), titulo VARCHAR(180) NOT NULL, autores TEXT NOT NULL, area VARCHAR(120) NOT NULL, fecha_articulo DATE NOT NULL, palabras_clave TEXT NOT NULL, descripcion TEXT NOT NULL, estado VARCHAR(25) NOT NULL DEFAULT 'En revisión' CHECK(estado IN ('En revisión','Aprobado','Rechazado')), visibilidad VARCHAR(15) NOT NULL DEFAULT 'Privado' CHECK(visibilidad IN ('Público','Privado')), docente_correo VARCHAR(180), creado_en TIMESTAMPTZ DEFAULT now());
CREATE TABLE versiones_articulo (id BIGSERIAL PRIMARY KEY, articulo_id BIGINT NOT NULL REFERENCES articulos(id) ON DELETE CASCADE, numero INT NOT NULL, ruta_pdf TEXT NOT NULL, tamano_bytes BIGINT NOT NULL CHECK (tamano_bytes BETWEEN 1 AND 20971520), creado_en TIMESTAMPTZ DEFAULT now(), UNIQUE(articulo_id,numero));
CREATE TABLE resumenes (id BIGSERIAL PRIMARY KEY, articulo_id BIGINT NOT NULL REFERENCES articulos(id) ON DELETE CASCADE, version_id BIGINT NOT NULL REFERENCES versiones_articulo(id), contenido TEXT NOT NULL, generado_en TIMESTAMPTZ DEFAULT now());
CREATE TABLE notificaciones (id BIGSERIAL PRIMARY KEY, estudiante_id BIGINT NOT NULL REFERENCES estudiantes(id), articulo_id BIGINT REFERENCES articulos(id), tipo VARCHAR(50) NOT NULL, mensaje TEXT NOT NULL, correo_enviado BOOLEAN DEFAULT false, leida BOOLEAN DEFAULT false, creada_en TIMESTAMPTZ DEFAULT now());
-- PENDIENTE: tokens de recuperación de un solo uso (almacenar hash, expiración y uso), auditoría y permisos.


-- ============================================================
-- PROYECTO: Investigatec
-- Repositorio de Proyectos de Investigación
-- Esquema unificado: Maestros (FastAPI) + Estudiantes (PHP)+
-- ============================================================

-- ------------------------------------------------------------
-- 1. ENUMS
-- ------------------------------------------------------------
CREATE TYPE tipo_usuario_enum AS ENUM (
    'ESTUDIANTE', 'DOCENTE', 'EGRESADO'
);

CREATE TYPE estado_cuenta_enum AS ENUM (
    'ACTIVA', 'INACTIVA', 'BLOQUEADA'
);

CREATE TYPE estado_proyecto_enum AS ENUM (
    'BORRADOR', 'EN_REVISION', 'REQUIERE_CAMBIOS',
    'APROBADO', 'RECHAZADO', 'PUBLICADO'
);

CREATE TYPE tipo_participacion_enum AS ENUM (
    'AUTOR', 'ASESOR', 'COLABORADOR'
);

CREATE TYPE ext_estado_resumen_enum AS ENUM (
    'PENDIENTE', 'GENERADO', 'ERROR'
);

-- ------------------------------------------------------------
-- 2. NÚCLEO COMPARTIDO (maestros)
-- ------------------------------------------------------------
CREATE TABLE departamentos (
    id_departamento SERIAL PRIMARY KEY,
    nombre          VARCHAR(150) NOT NULL UNIQUE,
    descripcion     VARCHAR(500)
);

CREATE TABLE carreras (
    id_carrera      SERIAL PRIMARY KEY,
    nombre          VARCHAR(150) NOT NULL UNIQUE,
    siglas          VARCHAR(20)  NOT NULL UNIQUE,
    activa          BOOLEAN      NOT NULL DEFAULT TRUE,
    id_departamento INTEGER      NOT NULL
        REFERENCES departamentos(id_departamento)
);

CREATE TABLE usuarios (
    id_usuario       SERIAL PRIMARY KEY,
    nombre           VARCHAR(100) NOT NULL,
    apellido_paterno VARCHAR(100) NOT NULL,
    apellido_materno VARCHAR(100),
    correo           VARCHAR(255) NOT NULL UNIQUE,
    password_hash    VARCHAR(255) NOT NULL,
    tipo_usuario     tipo_usuario_enum   NOT NULL,
    estado_cuenta    estado_cuenta_enum  NOT NULL DEFAULT 'ACTIVA',
    fecha_registro   TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    ultimo_acceso    TIMESTAMPTZ
);

CREATE INDEX ix_usuarios_correo ON usuarios(correo);

CREATE TABLE estudiantes (
    id_usuario  INTEGER PRIMARY KEY
        REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    num_control VARCHAR(20) NOT NULL UNIQUE,
    id_carrera  INTEGER     NOT NULL
        REFERENCES carreras(id_carrera),
    semestre    INTEGER
);

CREATE INDEX ix_estudiantes_num_control ON estudiantes(num_control);

CREATE TABLE docentes (
    id_usuario      INTEGER PRIMARY KEY
        REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    num_empleado    VARCHAR(30) NOT NULL UNIQUE,
    id_departamento INTEGER     NOT NULL
        REFERENCES departamentos(id_departamento)
);

CREATE INDEX ix_docentes_num_empleado ON docentes(num_empleado);

CREATE TABLE egresados (
    id_usuario  INTEGER PRIMARY KEY
        REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    num_control VARCHAR(20) NOT NULL UNIQUE,
    id_carrera  INTEGER     NOT NULL
        REFERENCES carreras(id_carrera),
    anio_egreso INTEGER     NOT NULL
);

CREATE INDEX ix_egresados_num_control ON egresados(num_control);

CREATE TABLE roles (
    id_rol      SERIAL PRIMARY KEY,
    nombre      VARCHAR(50) NOT NULL UNIQUE,
    descripcion VARCHAR(255)
);

CREATE TABLE usuarios_roles (
    id_usuario INTEGER NOT NULL
        REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    id_rol     INTEGER NOT NULL
        REFERENCES roles(id_rol) ON DELETE CASCADE,
    PRIMARY KEY (id_usuario, id_rol)
);

CREATE TABLE areas_tematicas (
    id_area     SERIAL PRIMARY KEY,
    nombre      VARCHAR(150) NOT NULL UNIQUE,
    descripcion VARCHAR(500)
);

CREATE TABLE palabras_clave (
    id_palabra_clave SERIAL PRIMARY KEY,
    nombre           VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE proyectos (
    id_proyecto       SERIAL PRIMARY KEY,
    titulo            VARCHAR(300) NOT NULL,
    resumen           TEXT         NOT NULL,
    fecha_creacion    TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    fecha_publicacion TIMESTAMPTZ,
    estado            estado_proyecto_enum NOT NULL DEFAULT 'BORRADOR',
    id_departamento   INTEGER NOT NULL
        REFERENCES departamentos(id_departamento),
    creado_por        INTEGER NOT NULL
        REFERENCES usuarios(id_usuario)
);

CREATE INDEX ix_proyectos_titulo ON proyectos(titulo);

CREATE TABLE proyectos_autores (
    id_proyecto        INTEGER NOT NULL
        REFERENCES proyectos(id_proyecto) ON DELETE CASCADE,
    id_usuario         INTEGER NOT NULL
        REFERENCES usuarios(id_usuario),
    tipo_participacion tipo_participacion_enum NOT NULL DEFAULT 'AUTOR',
    orden_autoria      INTEGER,
    PRIMARY KEY (id_proyecto, id_usuario)
);

CREATE UNIQUE INDEX uq_orden_autor_por_proyecto
    ON proyectos_autores (id_proyecto, orden_autoria)
    WHERE tipo_participacion = 'AUTOR';

CREATE TABLE proyectos_areas (
    id_proyecto INTEGER NOT NULL
        REFERENCES proyectos(id_proyecto) ON DELETE CASCADE,
    id_area     INTEGER NOT NULL
        REFERENCES areas_tematicas(id_area) ON DELETE CASCADE,
    PRIMARY KEY (id_proyecto, id_area)
);

CREATE TABLE proyectos_palabras_clave (
    id_proyecto      INTEGER NOT NULL
        REFERENCES proyectos(id_proyecto) ON DELETE CASCADE,
    id_palabra_clave INTEGER NOT NULL
        REFERENCES palabras_clave(id_palabra_clave) ON DELETE CASCADE,
    PRIMARY KEY (id_proyecto, id_palabra_clave)
);

CREATE TABLE archivos (
    id_archivo        SERIAL PRIMARY KEY,
    id_proyecto       INTEGER NOT NULL
        REFERENCES proyectos(id_proyecto) ON DELETE CASCADE,
    nombre_original   VARCHAR(255) NOT NULL,
    nombre_almacenado VARCHAR(255) NOT NULL UNIQUE,
    tipo_mime         VARCHAR(100) NOT NULL,
    tamanio           BIGINT       NOT NULL,
    ruta_archivo      VARCHAR(500) NOT NULL,
    fecha_subida      TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    subido_por        INTEGER      NOT NULL
        REFERENCES usuarios(id_usuario)
);

CREATE TABLE historial_estados (
    id_historial    SERIAL PRIMARY KEY,
    id_proyecto     INTEGER NOT NULL
        REFERENCES proyectos(id_proyecto) ON DELETE CASCADE,
    estado_anterior estado_proyecto_enum,
    estado_nuevo    estado_proyecto_enum NOT NULL,
    cambiado_por    INTEGER NOT NULL
        REFERENCES usuarios(id_usuario),
    fecha_cambio    TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    comentario      TEXT
);

CREATE TABLE responsables_departamento (
    id_responsable     SERIAL PRIMARY KEY,
    id_departamento    INTEGER NOT NULL
        REFERENCES departamentos(id_departamento),
    id_usuario_docente INTEGER NOT NULL
        REFERENCES docentes(id_usuario),
    fecha_inicio       DATE NOT NULL,
    fecha_fin          DATE,
    activo             BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE UNIQUE INDEX uq_responsable_activo_departamento
    ON responsables_departamento (id_departamento)
    WHERE activo IS TRUE;

-- ------------------------------------------------------------
-- 3. EXTENSIONES (estudiantes / PHP)
-- ------------------------------------------------------------
CREATE TABLE ext_recuperacion_acceso (
    id_recuperacion  SERIAL PRIMARY KEY,
    id_usuario       INTEGER NOT NULL
        REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    token_hash       TEXT NOT NULL,
    fecha_expiracion TIMESTAMPTZ NOT NULL,
    usado            BOOLEAN NOT NULL DEFAULT FALSE
);

CREATE TABLE ext_version_proyecto (
    id_version     SERIAL PRIMARY KEY,
    id_proyecto    INTEGER NOT NULL
        REFERENCES proyectos(id_proyecto) ON DELETE CASCADE,
    numero_version INTEGER NOT NULL,
    ruta_pdf       TEXT,
    tamano_bytes   BIGINT,
    fecha_subida   TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (id_proyecto, numero_version)
);

CREATE TABLE ext_resumen_generado (
    id_resumen       SERIAL PRIMARY KEY,
    id_proyecto      INTEGER NOT NULL
        REFERENCES proyectos(id_proyecto) ON DELETE CASCADE,
    id_version       INTEGER
        REFERENCES ext_version_proyecto(id_version) ON DELETE SET NULL,
    contenido        TEXT,
    correo_destino   VARCHAR(255),
    fecha_generacion TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    estado_envio     ext_estado_resumen_enum NOT NULL DEFAULT 'PENDIENTE'
);

CREATE TABLE ext_notificacion (
    id_notificacion SERIAL PRIMARY KEY,
    id_usuario      INTEGER NOT NULL
        REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    id_proyecto     INTEGER
        REFERENCES proyectos(id_proyecto) ON DELETE CASCADE,
    tipo            VARCHAR(40),
    mensaje         TEXT NOT NULL,
    fecha_envio     TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    estado_envio    VARCHAR(20) NOT NULL DEFAULT 'PENDIENTE'
);

CREATE TABLE ext_estudiante_perfil (
    id_usuario    INTEGER PRIMARY KEY
        REFERENCES estudiantes(id_usuario) ON DELETE CASCADE,
    generacion    VARCHAR(20),
    habilidad     TEXT,
    intereses     TEXT,
    imagen_perfil TEXT
);

-- ============================================================
-- FIN DEL SCRIPT
-- ============================================================
