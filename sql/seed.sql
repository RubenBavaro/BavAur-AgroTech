-- ============================================================
-- AgroManager — seed.sql v2
-- Importare DOPO schema.sql
--
-- Password di test:
--   superadmin@agro.it  → admin123
--   nord@agro.it        → sede123  (sede admin Podere Nord)
--   sud@agro.it         → sede123  (sede admin Podere Sud)
--   mario@email.it      → cliente123
-- ============================================================

-- ── SEDI (con password admin hash bcrypt) ───────────────────
-- NOTA: in produzione impostare admin_email e admin_password_hash
--       direttamente via phpMyAdmin con hash bcrypt.
--       Generare l'hash con: php -r "echo password_hash('tuapassword', PASSWORD_BCRYPT);"
INSERT INTO SEDE (nomeSede, indirizzo, admin_email, admin_password_hash) VALUES
('Podere Nord',  'Via delle Querce 12, 70031 Andria (BT)',
 'nord@agro.it', '$2b$12$dkw0Aa0p0eT.j5fEkV6V2.joX6tkWalHGRyWXUyw6.VD4n5dKD3sq'),
('Podere Sud',   'Contrada Montagna 45, 70031 Andria (BT)',
 'sud@agro.it',  '$2b$12$dkw0Aa0p0eT.j5fEkV6V2.joX6tkWalHGRyWXUyw6.VD4n5dKD3sq'),
('Laboratorio',  'Via Artigiani 8, 70031 Andria (BT)',
 NULL, NULL);

-- ── UTENTI ──────────────────────────────────────────────────
INSERT INTO UTENTE (nome, email, password_hash, ruolo, idSede) VALUES
-- Superadmin (accesso completo)
('Admin Sistema', 'superadmin@agro.it',
 '$2b$12$xNywLuF86aUhTVd6fQnWx.qfP4Cxb5Fjm2jWNK2L9Y7LQ9xShfPPi', 'superadmin', NULL),
-- Sede admin Podere Nord (creato via phpMyAdmin, non registrabile dall'app)
('Admin Nord', 'nord@agro.it',
 '$2b$12$dkw0Aa0p0eT.j5fEkV6V2.joX6tkWalHGRyWXUyw6.VD4n5dKD3sq', 'sede_admin', 1),
-- Sede admin Podere Sud
('Admin Sud', 'sud@agro.it',
 '$2b$12$dkw0Aa0p0eT.j5fEkV6V2.joX6tkWalHGRyWXUyw6.VD4n5dKD3sq', 'sede_admin', 2),
-- Cliente di esempio (registrato tramite app)
('Mario Rossi', 'mario@email.it',
 '$2b$12$LTHcqit0XMdClp/uueEifeL91YU6V5g1xilU0rW8PklUfZ9zwoviu', 'cliente', NULL);

-- ── CATEGORIE ───────────────────────────────────────────────
INSERT INTO CATEGORIA (nomeCategoria) VALUES
('Frutta'), ('Verdura'), ('Ortaggi'), ('Legumi'),
('Miele e Derivati'), ('Oli e Condimenti'), ('Conserve'), ('Piante Aromatiche');

-- ── CLIENTI ─────────────────────────────────────────────────
INSERT INTO CLIENTE (nome, nickname, contatti) VALUES
('Mario Rossi',       'Mario',   'mario.rossi@email.it | 333-1234567'),
('Giulia Verdi',      'Giuli',   'giulia.v@email.it | 347-9876543'),
('Luca Bianchi',      NULL,      'luca.bianchi@email.it | 320-5556677'),
('Anna De Luca',      'Annetta', 'anna.deluca@email.it | 389-2223344'),
('Roberto Esposito',  'Bobby',   'r.esposito@email.it | 366-8889900'),
('Carmine Ferrara',   NULL,      '080-4455667');

-- ── PRODOTTI ────────────────────────────────────────────────
INSERT INTO PRODOTTO (nome, unitaMisura, tipoProdotto, descrizione, idCategoria) VALUES
('Pomodori San Marzano','kg',    'fresco',  'Pomodori a grappolo coltivati senza pesticidi.',          3),
('Fichi d''India',      'kg',    'fresco',  'Fichi d''India dolcissimi, raccolti a mano.',             1),
('Zucchine',            'kg',    'fresco',  'Zucchine fresche di stagione.',                           3),
('Fagiolini',           'kg',    'fresco',  'Fagiolini teneri, ideali per contorni e insalate.',       4),
('Basilico',            'pezzo', 'fresco',  'Pianta di basilico fresco, profumata e rigogliosa.',      8),
('Rosmarino',           'pezzo', 'fresco',  'Pianta di rosmarino, perfetta per arrosti e grigliate.',  8),
('Miele di Acacia',     'kg',    'lavorato','Miele monofloreale di acacia, delicato e cristallino.',   5),
('Miele Millefiori',    'kg',    'lavorato','Ricco miele millefiori dal profumo intenso.',             5),
('Olio EVO',            'litro', 'lavorato','Olio extravergine di oliva DOP, prima spremitura.',       6),
('Passata di Pomodoro', 'litro', 'lavorato','Passata densa di pomodori San Marzano freschi.',          7),
('Marmellata di Fichi', 'g',     'lavorato','Marmellata artigianale di fichi con zucchero di canna.', 7),
('Olio al Peperoncino', 'litro', 'lavorato','Olio EVO aromatizzato al peperoncino piccante.',         6);

-- ── PRODUZIONI ──────────────────────────────────────────────
INSERT INTO PRODUZIONE (dataProduzione, quantitaProdotta, idProdottoProdotto, idProdottoLavorato, idSede) VALUES
('2024-09-10', 50.00,  7,  7,  3),
('2024-09-15', 35.00,  8,  8,  3),
('2024-10-05', 80.00,  9,  9,  3),
('2024-10-20', 60.00,  10, 1,  3),
('2024-11-01', 20.00,  11, 2,  3),
('2024-11-10', 15.00,  12, 9,  3);

-- ── CONFEZIONI ──────────────────────────────────────────────
INSERT INTO CONFEZIONE (dataConfezionamento, pesoNetto, numeroConfezioni, giacenza, idProduzione) VALUES
('2024-09-12', 0.500, 80, 65, 1),
('2024-09-12', 0.250, 40, 32, 1),
('2024-09-18', 0.500, 50, 44, 2),
('2024-10-08', 0.750, 90, 71, 3),
('2024-10-08', 0.500, 40, 35, 3),
('2024-10-22', 0.700, 70, 55, 4),
('2024-11-03', 0.350, 45, 38, 5),
('2024-11-12', 0.250, 30, 26, 6);

-- ── VENDITE ─────────────────────────────────────────────────
INSERT INTO VENDITA (dataVendita, totaleCalcolato, totalePagato, note, idCliente, idSede) VALUES
('2024-11-20', 47.50, 47.50, NULL,               1, 1),
('2024-11-22', 32.00, 30.00, 'Sconto accordato', 2, 1),
('2024-12-01', 18.50, 18.50, NULL,               3, 2),
('2024-12-05', 63.00, 63.00, 'Cliente fedele',   4, 1),
('2024-12-10', 25.00, 20.00, 'Pag. parziale',    5, 3);

INSERT INTO DETTAGLIO_VENDITA (quantita, prezzoUnitario, omaggio, idVendita, idProdotto, idConfezione) VALUES
(3,8.50,0,1,7,1),(2,7.00,0,1,8,3),(1,7.00,1,1,8,3),
(2,9.00,0,2,9,4),(2,7.00,0,2,8,3),
(1,9.00,0,3,9,4),(2,3.50,0,3,6,NULL),(2,1.00,0,3,5,NULL),
(4,8.50,0,4,7,1),(3,9.00,0,4,9,4),(2,4.50,0,4,11,7),
(2,8.50,0,5,7,2),(1,8.00,0,5,12,8);
