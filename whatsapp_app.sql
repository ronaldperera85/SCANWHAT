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

-- Table structure for mensajes

DROP TABLE IF EXISTS mensajes;
CREATE TABLE mensajes (
id int NOT NULL AUTO_INCREMENT,
uid varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
custom_uid varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
token text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
tipo varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
cuerpo_mensaje text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
remitente_uid varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL, 
destinatario_uid varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
replied_to_uid varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
es_entrante tinyint(1) NOT NULL,
payload json NOT NULL,
estado_entrega_endpoint enum('PENDIENTE','REINTENTANDO','EXITOSO','FALLIDO','NO_APLICA') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'PENDIENTE',
estado_whatsapp enum('enviado','recibido','fallido') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
conteo_reintentos int NOT NULL DEFAULT 0,
mensaje_error text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
fecha_ultimo_intento timestamp NULL DEFAULT NULL,
fecha_creacion timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY (id),
UNIQUE KEY custom_uid_unique (custom_uid),
KEY idx_estado_entrega (estado_entrega_endpoint, conteo_reintentos, fecha_ultimo_intento)
) ENGINE=InnoDB AUTO_INCREMENT=1 CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;

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