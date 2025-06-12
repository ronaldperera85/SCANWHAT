SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Table structure for licencias

DROP TABLE IF EXISTS licencias;
CREATE TABLE licencias (
id int NOT NULL AUTO_INCREMENT,
uid varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL UNIQUE,
tipo_licencia varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
limite_mensajes int NOT NULL,
mensajes_enviados int NOT NULL DEFAULT 0,
estado_licencia enum('ACTIVA','BLOQUEADA') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'ACTIVA',
fecha_inicio timestamp NOT NULL DEFAULT current_timestamp,
fecha_fin datetime NULL DEFAULT NULL,
PRIMARY KEY (id) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 8 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- Table structure for mensajes_en_cola

DROP TABLE IF EXISTS mensajes_en_cola;
CREATE TABLE mensajes_en_cola (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uid VARCHAR(20) NOT NULL,
    custom_uid VARCHAR(50) NULL,
    token TEXT NULL,
    tipo VARCHAR(20) NOT NULL,
    cuerpo_mensaje TEXT NULL,
    remitente_uid VARCHAR(20) NULL DEFAULT NULL,
    destinatario_uid VARCHAR(20) NULL DEFAULT NULL,
    es_entrante BOOLEAN NOT NULL,
    payload JSON NOT NULL,
    estado_entrega_endpoint ENUM('PENDIENTE', 'REINTENTANDO', 'EXITOSO', 'FALLIDO') NOT NULL DEFAULT 'PENDIENTE',
    estado_whatsapp ENUM('enviado','recibido','visto','entregado') NULL,
    conteo_reintentos INT NOT NULL DEFAULT 0,
    fecha_ultimo_intento TIMESTAMP NULL,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    mensaje_error TEXT NULL,
    INDEX (estado_entrega_endpoint, fecha_ultimo_intento),
    INDEX (uid, custom_uid),
    INDEX (remitente_uid),
    INDEX (destinatario_uid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for numeros

DROP TABLE IF EXISTS numeros;
CREATE TABLE numeros (
id int NOT NULL AUTO_INCREMENT,
usuario_id int NOT NULL,
numero varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL UNIQUE,
token varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
estado VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pendiente',
hooks_url varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
fecha_registro timestamp NOT NULL DEFAULT current_timestamp,
PRIMARY KEY (id) USING BTREE,
INDEX usuario_id(usuario_id ASC) USING BTREE,
CONSTRAINT numeros_ibfk_1 FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 27 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- Table structure for usuarios

DROP TABLE IF EXISTS usuarios;
CREATE TABLE usuarios (
id int NOT NULL AUTO_INCREMENT,
nombre varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
email varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL UNIQUE,
password varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
fecha_creacion timestamp NOT NULL DEFAULT current_timestamp,
admin TINYINT(1) NOT NULL DEFAULT 0,
PRIMARY KEY (id) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 6 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

SET FOREIGN_KEY_CHECKS = 1;