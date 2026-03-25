-- ============================================================
-- AgroManager — seed.sql v3
-- Importare DOPO schema.sql
--
-- Password di test:
--   superadmin@agro.it  → admin123
--   nord@agro.it        → sede123  (tab "Admin Sede")
--   sud@agro.it         → sede123  (tab "Admin Sede")
--   mario@email.it      → cliente123
--
-- Relazione REGISTRA:
--   Mario Rossi (idUtente=4) ha un record CLIENTE collegato.
--   Gli altri 5 clienti sono walk-in (idUtente = NULL).
--
-- Normalizzazione 3NF:
--   SEDE non ha più admin_email/admin_password_hash.
--   L'auth sede_admin avviene solo tramite UTENTE.
--   DETTAGLIO_VENDITA: idProdotto=NULL per lavorati (derivabile),
--                      idProdotto=NOT NULL per freschi (necessario).
--   VENDITA: totaleCalcolato rimosso, calcolato dalla VIEW V_VENDITA.
-- ============================================================

-- ── SEDI ────────────────────────────────────────────────────
INSERT INTO SEDE (nomeSede, indirizzo) VALUES
('Podere Nord',  'Via delle Querce 12, 70031 Andria (BT)'),
('Podere Sud',   'Contrada Montagna 45, 70031 Andria (BT)'),
('Laboratorio',  'Via Artigiani 8, 70031 Andria (BT)');

-- ── UTENTI ──────────────────────────────────────────────────
INSERT INTO UTENTE (nome, email, password_hash, ruolo, idSede) VALUES
('Admin Sistema', 'superadmin@agro.it',
 '$2b$12$xNywLuF86aUhTVd6fQnWx.qfP4Cxb5Fjm2jWNK2L9Y7LQ9xShfPPi', 'superadmin', NULL),
('Admin Nord',    'nord@agro.it',
 '$2b$12$dkw0Aa0p0eT.j5fEkV6V2.joX6tkWalHGRyWXUyw6.VD4n5dKD3sq', 'sede_admin', 1),
('Admin Sud',     'sud@agro.it',
 '$2b$12$dkw0Aa0p0eT.j5fEkV6V2.joX6tkWalHGRyWXUyw6.VD4n5dKD3sq', 'sede_admin', 2),
('Mario Rossi',   'mario@email.it',
 '$2b$12$LTHcqit0XMdClp/uueEifeL91YU6V5g1xilU0rW8PklUfZ9zwoviu',  'cliente',   NULL);

-- ── CATEGORIE ───────────────────────────────────────────────
INSERT INTO CATEGORIA (nomeCategoria) VALUES
('Frutta'), ('Verdura'), ('Ortaggi'), ('Legumi'),
('Miele e Derivati'), ('Oli e Condimenti'), ('Conserve'), ('Piante Aromatiche');

-- ── CLIENTI ─────────────────────────────────────────────────
INSERT INTO CLIENTE (nome, nickname, contatti, idUtente) VALUES
('Mario Rossi',      'Mario',   'mario.rossi@email.it | 333-1234567', 4),
('Giulia Verdi',     'Giuli',   'giulia.v@email.it | 347-9876543',    NULL),
('Luca Bianchi',     NULL,      'luca.bianchi@email.it | 320-5556677',NULL),
('Anna De Luca',     'Annetta', 'anna.deluca@email.it | 389-2223344', NULL),
('Roberto Esposito', 'Bobby',   'r.esposito@email.it | 366-8889900',  NULL),
('Carmine Ferrara',  NULL,      '080-4455667',                        NULL);

-- ── PRODOTTI ────────────────────────────────────────────────
-- immagineUrl = NULL → usa il fallback keyword in getProductImage()
-- immagineUrl = 'https://...' → usa quell'URL direttamente
INSERT INTO PRODOTTO (nome, unitaMisura, tipoProdotto, descrizione, immagineUrl, idCategoria) VALUES
('Pomodori San Marzano','kg',    'fresco',  'Pomodori a grappolo coltivati senza pesticidi.',                                     NULL, 3),
('Fichi d''India',      'kg',    'fresco',  'Fichi d''India dolcissimi, raccolti a mano.',                                        NULL, 1),
('Zucchine',            'kg',    'fresco',  'Zucchine fresche di stagione.',                                                      NULL, 3),
('Fagiolini',           'kg',    'fresco',  'Fagiolini teneri, ideali per contorni e insalate.',                                  NULL, 4),
('Basilico',            'pezzo', 'fresco',  'Pianta di basilico fresco, profumata e rigogliosa.',                                 NULL, 8),
('Rosmarino',           'pezzo', 'fresco',  'Pianta di rosmarino, perfetta per arrosti e grigliate.',                             NULL, 8),
('Cetriolo Greco',      'kg',    'fresco',  'Cetriolo greco dalla buccia sottile e polpa croccante, ideale per insalate e tzatziki.', NULL, 3),
('Miele di Acacia',     'kg',    'lavorato','Miele monofloreale di acacia, delicato e cristallino.',                              NULL, 5),
('Miele Millefiori',    'kg',    'lavorato','Ricco miele millefiori dal profumo intenso.',                                        NULL, 5),
('Olio EVO',            'litro', 'lavorato','Olio extravergine di oliva DOP, prima spremitura.',                                  NULL, 6),
('Passata di Pomodoro', 'litro', 'lavorato','Passata densa di pomodori San Marzano freschi.',                                     NULL, 7),
('Marmellata di Fichi', 'g',     'lavorato','Marmellata artigianale di fichi con zucchero di canna.',                            NULL, 7),
('Olio al Peperoncino', 'litro', 'lavorato','Olio EVO aromatizzato al peperoncino piccante.',                                    NULL, 6);

-- ── PRODUZIONI ──────────────────────────────────────────────
-- idProdotti lavorati: Miele Acacia=8, Miele Millefiori=9, Olio EVO=10,
--                      Passata=11, Marmellata=12, Olio Peperoncino=13
INSERT INTO PRODUZIONE (dataProduzione, quantitaProdotta, idProdottoProdotto, idProdottoLavorato, idSede) VALUES
('2024-09-10', 50.00,  8,  8,  3),
('2024-09-15', 35.00,  9,  9,  3),
('2024-10-05', 80.00,  10, 10, 3),
('2024-10-20', 60.00,  11, 1,  3),
('2024-11-01', 20.00,  12, 2,  3),
('2024-11-10', 15.00,  13, 10, 3);

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
-- totaleCalcolato rimosso: viene calcolato dalla VIEW V_VENDITA
INSERT INTO VENDITA (dataVendita, totalePagato, note, idCliente, idSede) VALUES
('2024-11-20', 47.50, NULL,               1, 1),
('2024-11-22', 30.00, 'Sconto accordato', 2, 1),
('2024-12-01', 18.50, NULL,               3, 2),
('2024-12-05', 63.00, 'Cliente fedele',   4, 1),
('2024-12-10', 20.00, 'Pag. parziale',    5, 3);

-- ── DETTAGLIO_VENDITA ────────────────────────────────────────
-- Lavorati (idConfezione IS NOT NULL): idProdotto = NULL (derivabile via JOIN)
-- Freschi  (idConfezione IS NULL):     idProdotto = NOT NULL (necessario)
-- Freschi nel seed: Basilico=5, Rosmarino=6, Cetriolo Greco=7
INSERT INTO DETTAGLIO_VENDITA (quantita, prezzoUnitario, omaggio, idVendita, idProdotto, idConfezione) VALUES
-- Vendita 1: miele acacia x3, miele millefiori x2 (omaggio x1)
(3, 8.50, 0, 1, NULL, 1),
(2, 7.00, 0, 1, NULL, 3),
(1, 0.00, 1, 1, NULL, 3),
-- Vendita 2: olio evo x2, miele millefiori x2
(2, 9.00, 0, 2, NULL, 4),
(2, 7.00, 0, 2, NULL, 3),
-- Vendita 3: olio evo x1, rosmarino x2 (fresco), basilico x2 (fresco)
(1, 9.00, 0, 3, NULL, 4),
(2, 3.50, 0, 3, 6,    NULL),
(2, 1.00, 0, 3, 5,    NULL),
-- Vendita 4: miele acacia x4, olio evo x3, marmellata x2
(4, 8.50, 0, 4, NULL, 1),
(3, 9.00, 0, 4, NULL, 4),
(2, 4.50, 0, 4, NULL, 7),
-- Vendita 5: miele acacia x2, olio peperoncino x1
(2, 8.50, 0, 5, NULL, 2),
(1, 8.00, 0, 5, NULL, 8);
