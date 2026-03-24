-- ============================================================
-- AgroManager — BavAur-AgroTech
-- Schema del database v2 (con autenticazione)
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS DETTAGLIO_VENDITA;
DROP TABLE IF EXISTS VENDITA;
DROP TABLE IF EXISTS CONFEZIONE;
DROP TABLE IF EXISTS PRODUZIONE;
DROP TABLE IF EXISTS PRODOTTO;
DROP TABLE IF EXISTS CATEGORIA;
DROP TABLE IF EXISTS CLIENTE;
DROP TABLE IF EXISTS SEDE;
DROP TABLE IF EXISTS UTENTE;
SET FOREIGN_KEY_CHECKS = 1;

-- ── SEDE ─────────────────────────────────────────────────────
-- admin_email e admin_password_hash vengono impostati via phpMyAdmin
-- dall'amministratore del sistema. NON sono modificabili dall'app.
CREATE TABLE SEDE (
    idSede              INT AUTO_INCREMENT PRIMARY KEY,
    nomeSede            VARCHAR(100) NOT NULL,
    indirizzo           TEXT,
    admin_email         VARCHAR(200) DEFAULT NULL COMMENT 'Impostare via phpMyAdmin',
    admin_password_hash VARCHAR(255) DEFAULT NULL COMMENT 'Hash bcrypt, impostare via phpMyAdmin'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── UTENTE ───────────────────────────────────────────────────
-- ruolo:
--   'superadmin'  → accesso completo a tutto
--   'sede_admin'  → creato via phpMyAdmin, gestisce una sola sede
--   'cliente'     → si registra autonomamente, accesso solo homepage
CREATE TABLE UTENTE (
    idUtente        INT AUTO_INCREMENT PRIMARY KEY,
    nome            VARCHAR(150) NOT NULL,
    email           VARCHAR(200) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    ruolo           ENUM('superadmin','sede_admin','cliente') NOT NULL DEFAULT 'cliente',
    idSede          INT DEFAULT NULL COMMENT 'Solo per sede_admin',
    createdAt       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (idSede) REFERENCES SEDE(idSede) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── CATEGORIA ────────────────────────────────────────────────
CREATE TABLE CATEGORIA (
    idCategoria   INT AUTO_INCREMENT PRIMARY KEY,
    nomeCategoria VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── CLIENTE ──────────────────────────────────────────────────
CREATE TABLE CLIENTE (
    idCliente INT AUTO_INCREMENT PRIMARY KEY,
    nome      VARCHAR(150) NOT NULL,
    nickname  VARCHAR(80),
    contatti  TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── PRODOTTO ─────────────────────────────────────────────────
CREATE TABLE PRODOTTO (
    idProdotto   INT AUTO_INCREMENT PRIMARY KEY,
    nome         VARCHAR(100) NOT NULL,
    unitaMisura  ENUM('kg','g','litro','pezzo') NOT NULL,
    tipoProdotto ENUM('fresco','lavorato') NOT NULL,
    descrizione  TEXT DEFAULT NULL,
    idCategoria  INT,
    FOREIGN KEY (idCategoria) REFERENCES CATEGORIA(idCategoria) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── VENDITA ──────────────────────────────────────────────────
CREATE TABLE VENDITA (
    idVendita        INT AUTO_INCREMENT PRIMARY KEY,
    dataVendita      DATE NOT NULL,
    totaleCalcolato  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    totalePagato     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    note             TEXT,
    idCliente        INT NOT NULL,
    idSede           INT NOT NULL,
    FOREIGN KEY (idCliente) REFERENCES CLIENTE(idCliente) ON DELETE RESTRICT,
    FOREIGN KEY (idSede)    REFERENCES SEDE(idSede)       ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── PRODUZIONE ───────────────────────────────────────────────
CREATE TABLE PRODUZIONE (
    idProduzione        INT AUTO_INCREMENT PRIMARY KEY,
    dataProduzione      DATE NOT NULL,
    quantitaProdotta    DECIMAL(10,2) NOT NULL,
    idProdottoProdotto  INT NOT NULL,
    idProdottoLavorato  INT NOT NULL,
    idSede              INT NOT NULL,
    CONSTRAINT chk_prod_q CHECK (quantitaProdotta > 0),
    FOREIGN KEY (idProdottoProdotto) REFERENCES PRODOTTO(idProdotto) ON DELETE RESTRICT,
    FOREIGN KEY (idProdottoLavorato) REFERENCES PRODOTTO(idProdotto) ON DELETE RESTRICT,
    FOREIGN KEY (idSede)             REFERENCES SEDE(idSede)         ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── CONFEZIONE ───────────────────────────────────────────────
CREATE TABLE CONFEZIONE (
    idConfezione        INT AUTO_INCREMENT PRIMARY KEY,
    dataConfezionamento DATE NOT NULL,
    pesoNetto           DECIMAL(10,3) NOT NULL,
    numeroConfezioni    INT NOT NULL,
    giacenza            INT NOT NULL,
    idProduzione        INT NOT NULL,
    CONSTRAINT chk_conf_g   CHECK (giacenza >= 0),
    CONSTRAINT chk_conf_max CHECK (giacenza <= numeroConfezioni),
    FOREIGN KEY (idProduzione) REFERENCES PRODUZIONE(idProduzione) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── DETTAGLIO_VENDITA ────────────────────────────────────────
CREATE TABLE DETTAGLIO_VENDITA (
    idDettaglio    INT AUTO_INCREMENT PRIMARY KEY,
    quantita       DECIMAL(10,2) NOT NULL,
    prezzoUnitario DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    omaggio        TINYINT(1)    NOT NULL DEFAULT 0,
    idVendita      INT NOT NULL,
    idProdotto     INT NOT NULL,
    idConfezione   INT,
    CONSTRAINT chk_det_q CHECK (quantita > 0),
    FOREIGN KEY (idVendita)    REFERENCES VENDITA(idVendita)       ON DELETE CASCADE,
    FOREIGN KEY (idProdotto)   REFERENCES PRODOTTO(idProdotto)     ON DELETE RESTRICT,
    FOREIGN KEY (idConfezione) REFERENCES CONFEZIONE(idConfezione) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
