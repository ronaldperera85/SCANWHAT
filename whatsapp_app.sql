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
tipo enum('chat','image','video','document','audio','sticker','location','contact','vcard') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
cuerpo_mensaje text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
remitente_uid varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
destinatario_uid varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
replied_to_uid varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
es_entrante tinyint(1) NOT NULL,
payload json NOT NULL,
estado_entrega_endpoint enum('PENDIENTE_SUBIDA', 'PENDIENTE','PROCESANDO','REINTENTANDO','EXITOSO','FALLIDO','NO_APLICA') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'PENDIENTE',
estado_whatsapp enum('enviado','recibido','fallido') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
conteo_reintentos int NOT NULL DEFAULT '0',
mensaje_error text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
fecha_ultimo_intento datetime DEFAULT NULL,
fecha_proximo_intento datetime DEFAULT NULL,
fecha_creacion datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY (id),
UNIQUE KEY custom_uid_unique (custom_uid),
KEY idx_worker_queue (estado_entrega_endpoint,fecha_proximo_intento) 
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;

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

-- Table structure for usuarios
DROP TABLE IF EXISTS contactos;
CREATE TABLE contactos (
id INT NOT NULL AUTO_INCREMENT,
uid VARCHAR(20) NOT NULL, -- El número de WhatsApp del contacto, ej: 584121234567
nombre_notificado VARCHAR(255) NULL, -- El nombre que WhatsApp notifica (notifyName)
pic_url VARCHAR(255) NULL, -- La URL permanente de la foto de perfil
fecha_primera_interaccion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
fecha_ultima_actualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (id),
UNIQUE KEY uid_unique (uid) -- Asegura que cada número exista solo una vez
) ENGINE=InnoDB CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla para gestionar lista negra de UIDs para no recibir mensajes entrantes en api y logs en scanwhat (uid que no registran chats en helpdesk)

CREATE TABLE lista_negra_uid (
    uid VARCHAR(25) PRIMARY KEY,
    motivo VARCHAR(255),
    fecha_agregado TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ---------------------------------------------------------------------------------------------------------------

INSERT INTO lista_negra_uid (uid, motivo) VALUES ('584246356551', 'Recepción de mensajes desactivada.');

-- ---------------------------------------------------------------------------------------------------------------

-- Borramos el trigger anterior para reemplazarlo
DROP TRIGGER IF EXISTS trg_bloquear_mensaje_antes_de_insertar;

-- Creamos el nuevo trigger que revisa al DESTINATARIO
CREATE TRIGGER trg_bloquear_mensaje_antes_de_insertar
BEFORE INSERT ON mensajes
FOR EACH ROW
BEGIN
    -- Comprobamos si el mensaje es ENTRANTE Y si el DESTINATARIO está en la lista negra
    IF NEW.es_entrante = 1 AND EXISTS (SELECT 1 FROM lista_negra_uid WHERE uid = NEW.destinatario_uid) THEN
        -- Si ambas condiciones son ciertas, cancelamos la inserción
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Operación cancelada: El destinatario tiene la recepción de mensajes desactivada.';
    END IF;
END;
