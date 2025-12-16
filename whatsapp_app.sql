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
tipo enum('chat','image','video','document','audio','sticker','location','contact') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
cuerpo_mensaje text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
remitente_uid varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL, 
destinatario_uid varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
replied_to_uid varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
es_entrante tinyint(1) NOT NULL,
payload json NOT NULL,
estado_entrega_endpoint enum('PENDIENTE','PENDIENTE_SUBIDA','PROCESANDO','REINTENTANDO','EXITOSO','FALLIDO','NO_APLICA') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'PENDIENTE',
estado_whatsapp enum('enviado','recibido','fallido','entregado','leido') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
conteo_reintentos int NOT NULL DEFAULT 0,
mensaje_error text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
fecha_proximo_intento timestamp NULL DEFAULT NULL,
fecha_ultimo_intento timestamp NULL DEFAULT NULL,
fecha_creacion timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY (id),
UNIQUE KEY custom_uid_unique (custom_uid),
KEY idx_worker_process (estado_entrega_endpoint, fecha_proximo_intento)
) ENGINE=InnoDB AUTO_INCREMENT=1 CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;

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

-- Table structure for contactos
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

-- Table structure for lista_negra_uid
CREATE TABLE IF NOT EXISTS lista_negra_uid (
  uid VARCHAR(25) NOT NULL,
  motivo VARCHAR(255) DEFAULT NULL,
  fecha_agregado TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (uid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Trigger
DELIMITER $$

DROP TRIGGER IF EXISTS before_insert_mensajes$$

CREATE TRIGGER before_insert_mensajes
BEFORE INSERT ON mensajes
FOR EACH ROW
BEGIN
    DECLARE blacklist_count INT;

    -- 1. Solo nos interesa bloquear si es un mensaje ENTRANTE (es_entrante = 1)
    -- Si es saliente (0), el trigger no hace nada y deja pasar el mensaje.
    IF NEW.es_entrante = 1 THEN

        -- 2. Verificamos si EL DUEÑO DE LA SESIÓN (NEW.uid) está en la lista negra
        SELECT COUNT(*) INTO blacklist_count
        FROM lista_negra_uid
        WHERE uid = NEW.uid;

        -- 3. Si está en la lista, bloqueamos la entrada
        IF blacklist_count > 0 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Bloqueado: Este numero tiene la recepcion desactivada.';
        END IF;

    END IF;
END$$

DELIMITER ;

-- Insertar UID en la lista negra
INSERT IGNORE INTO lista_negra_uid (uid, motivo) VALUES ('584146804119', 'Recepción de mensajes desactivada.');

SET FOREIGN_KEY_CHECKS = 1;