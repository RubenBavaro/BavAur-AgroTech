-- ============================================================
-- AgroManager — BavAur-AgroTech
-- Schema del database v3 — Terza Forma Normale
-- ============================================================
-- Modifiche dalla v2:
--   1. SEDE: rimossi admin_email e admin_password_hash
--      (dipendenza transitiva: admin_email → admin_password_hash)
--      L'auth sede_admin avviene tramite UTENTE (ruolo='sede_admin')
--   2. DETTAGLIO_VENDITA: idProdotto ora NULLABLE
--      Per prodotti lavorati (idConfezione IS NOT NULL) il prodotto
--      è derivabile via CONFEZIONE → PRODUZIONE → PRODOTTO.
--      Per prodotti freschi (idConfezione IS NULL) idProdotto è NOT NULL.
--      CHECK garantisce mutua esclusività.
--   3. VENDITA: rimosso totaleCalcolato (attributo derivato)
--      Calcolato dalla VIEW V_VENDITA in tempo reale.
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
DROP VIEW  IF EXISTS V_VENDITA;
DROP VIEW  IF EXISTS V_DETTAGLIO;
DROP TABLE IF EXISTS DETTAGLIO_VENDITA;
DROP TABLE IF EXISTS VENDITA;
DROP TABLE IF EXISTS CONFEZIONE;
DROP TABLE IF EXISTS PRODUZIONE;
DROP TABLE IF EXISTS PRODOTTO;
DROP TABLE IF EXISTS CATEGORIA;
DROP TABLE IF EXISTS CLIENTE;
DROP TABLE IF EXISTS UTENTE;
DROP TABLE IF EXISTS SEDE;
SET FOREIGN_KEY_CHECKS = 1;

-- ── SEDE ─────────────────────────────────────────────────────
-- Gli amministratori di sede sono in UTENTE (ruolo='sede_admin', idSede FK).
-- Non esistono credenziali separate nella tabella SEDE.
CREATE TABLE SEDE (
    idSede    INT AUTO_INCREMENT PRIMARY KEY,
    nomeSede  VARCHAR(100) NOT NULL,
    indirizzo TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── UTENTE ───────────────────────────────────────────────────
-- ruolo:
--   'superadmin'  → accesso completo a tutto
--   'sede_admin'  → gestisce una sola sede (idSede NOT NULL)
--   'cliente'     → si registra autonomamente, accesso solo homepage
-- NOTA: il vincolo (ruolo='sede_admin' ↔ idSede IS NOT NULL) è applicato
--       a livello applicativo (register.php, admin panel) perché MySQL
--       non consente CHECK su colonne usate in azioni referenziali FK
--       (ERROR #3823: needed in a foreign key referential action).
CREATE TABLE UTENTE (
    idUtente      INT AUTO_INCREMENT PRIMARY KEY,
    nome          VARCHAR(150) NOT NULL,
    email         VARCHAR(200) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    ruolo         ENUM('superadmin','sede_admin','cliente') NOT NULL DEFAULT 'cliente',
    idSede        INT DEFAULT NULL COMMENT 'NOT NULL per sede_admin, NULL per tutti gli altri',
    createdAt     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (idSede) REFERENCES SEDE(idSede) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── CATEGORIA ────────────────────────────────────────────────
CREATE TABLE CATEGORIA (
    idCategoria   INT AUTO_INCREMENT PRIMARY KEY,
    nomeCategoria VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── CLIENTE ──────────────────────────────────────────────────
-- idUtente è NULL per i clienti walk-in registrati dall'admin.
-- Viene valorizzato alla registrazione (relazione REGISTRA, 0..1 : 1).
CREATE TABLE CLIENTE (
    idCliente INT AUTO_INCREMENT PRIMARY KEY,
    nome      VARCHAR(150) NOT NULL,
    nickname  VARCHAR(80),
    contatti  TEXT,
    idUtente  INT DEFAULT NULL UNIQUE COMMENT 'FK verso UTENTE: null per clienti walk-in',
    FOREIGN KEY (idUtente) REFERENCES UTENTE(idUtente) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── PRODOTTO ─────────────────────────────────────────────────
CREATE TABLE PRODOTTO (
    idProdotto   INT AUTO_INCREMENT PRIMARY KEY,
    nome         VARCHAR(100) NOT NULL,
    unitaMisura  ENUM('kg','g','litro','pezzo') NOT NULL,
    tipoProdotto ENUM('fresco','lavorato') NOT NULL,
    descrizione  TEXT DEFAULT NULL,
    immagineUrl  TEXT DEFAULT NULL COMMENT 'URL immagine custom; se NULL usa il fallback keyword in getProductImage()',
    idCategoria  INT,
    FOREIGN KEY (idCategoria) REFERENCES CATEGORIA(idCategoria) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── VENDITA ──────────────────────────────────────────────────
-- totaleCalcolato è RIMOSSO: attributo derivato da DETTAGLIO_VENDITA.
-- Usare la VIEW V_VENDITA per ottenere il totale calcolato in lettura.
CREATE TABLE VENDITA (
    idVendita    INT AUTO_INCREMENT PRIMARY KEY,
    dataVendita  DATE NOT NULL,
    totalePagato DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    note         TEXT,
    idCliente    INT NOT NULL,
    idSede       INT NOT NULL,
    FOREIGN KEY (idCliente) REFERENCES CLIENTE(idCliente) ON DELETE RESTRICT,
    FOREIGN KEY (idSede)    REFERENCES SEDE(idSede)       ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── PRODUZIONE ───────────────────────────────────────────────
CREATE TABLE PRODUZIONE (
    idProduzione       INT AUTO_INCREMENT PRIMARY KEY,
    dataProduzione     DATE NOT NULL,
    quantitaProdotta   DECIMAL(10,2) NOT NULL,
    idProdottoProdotto INT NOT NULL,
    idProdottoLavorato INT NOT NULL,
    idSede             INT NOT NULL,
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
-- idProdotto è NULLABLE.
-- Per prodotti FRESCHI  (idConfezione IS NULL):  idProdotto IS NOT NULL
-- Per prodotti LAVORATI (idConfezione IS NOT NULL): idProdotto IS NULL
--   (il prodotto si ottiene: idConfezione → idProduzione → idProdottoProdotto)
-- NOTA: il CHECK di mutua esclusività è applicato via codice PHP (vendite.php,
--       carrello.php) perché MySQL #3823 non consente CHECK su colonne FK.
CREATE TABLE DETTAGLIO_VENDITA (
    idDettaglio    INT AUTO_INCREMENT PRIMARY KEY,
    quantita       DECIMAL(10,2) NOT NULL,
    prezzoUnitario DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    omaggio        TINYINT(1)    NOT NULL DEFAULT 0,
    idVendita      INT NOT NULL,
    idProdotto     INT DEFAULT NULL,
    idConfezione   INT DEFAULT NULL,
    CONSTRAINT chk_det_q CHECK (quantita > 0),
    FOREIGN KEY (idVendita)    REFERENCES VENDITA(idVendita)       ON DELETE CASCADE,
    FOREIGN KEY (idProdotto)   REFERENCES PRODOTTO(idProdotto)     ON DELETE RESTRICT,
    FOREIGN KEY (idConfezione) REFERENCES CONFEZIONE(idConfezione) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════
-- VIEWS
-- ══════════════════════════════════════════════════════════════

-- V_DETTAGLIO: risolve sempre il prodotto indipendentemente dal percorso
-- Per freschi:  DETTAGLIO_VENDITA.idProdotto → PRODOTTO
-- Per lavorati: DETTAGLIO_VENDITA.idConfezione → PRODUZIONE → PRODOTTO
CREATE VIEW V_DETTAGLIO AS
SELECT
    dv.idDettaglio,
    dv.idVendita,
    dv.quantita,
    dv.prezzoUnitario,
    dv.omaggio,
    dv.idConfezione,
    dv.idProdotto                                          AS idProdottoFresco,
    COALESCE(dv.idProdotto, pr.idProdottoProdotto)         AS idProdotto,
    COALESCE(pf.nome,       pl.nome)                       AS nomeProdotto,
    COALESCE(pf.unitaMisura,pl.unitaMisura)                AS unitaMisura,
    COALESCE(pf.tipoProdotto,pl.tipoProdotto)              AS tipoProdotto,
    c.pesoNetto,
    c.giacenza
FROM DETTAGLIO_VENDITA dv
LEFT JOIN PRODOTTO   pf ON dv.idProdotto   = pf.idProdotto
LEFT JOIN CONFEZIONE c  ON dv.idConfezione = c.idConfezione
LEFT JOIN PRODUZIONE pr ON c.idProduzione  = pr.idProduzione
LEFT JOIN PRODOTTO   pl ON pr.idProdottoProdotto = pl.idProdotto;

-- V_VENDITA: aggiunge totaleCalcolato derivato dai dettagli
CREATE VIEW V_VENDITA AS
SELECT
    v.*,
    COALESCE(SUM(
        CASE WHEN d.omaggio = 0
             THEN d.quantita * d.prezzoUnitario
             ELSE 0
        END
    ), 0) AS totaleCalcolato
FROM VENDITA v
LEFT JOIN DETTAGLIO_VENDITA d ON d.idVendita = v.idVendita
GROUP BY v.idVendita;
