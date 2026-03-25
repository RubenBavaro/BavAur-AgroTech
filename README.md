# BavAur-AgroTech
<a name="readme-top"></a>

<!-- SHIELDS -->
<div align="center">

[![Status](https://img.shields.io/badge/status-in%20sviluppo-brightgreen?style=for-the-badge)](https://github.com/RubenBavaro/BavAur-AgroTech)
[![Versione](https://img.shields.io/badge/versione-1.0.0-blue?style=for-the-badge)](https://github.com/RubenBavaro/BavAur-AgroTech)
[![Licenza](https://img.shields.io/badge/licenza-MIT-yellow?style=for-the-badge)](LICENSE.txt)
[![Database](https://img.shields.io/badge/database-relazionale-orange?style=for-the-badge&logo=mysql&logoColor=white)](https://github.com/RubenBavaro/BavAur-AgroTech)

</div>

<br />
<div align="center">
  <a href="https://github.com/RubenBavaro/BavAur-AgroTech">
    <img src="https://img.shields.io/badge/🌿-AgroManager-2E7D32?style=for-the-badge&labelColor=1B5E20&color=2E7D32" alt="AgroManager" height="60">
  </a>

  <h1 align="center">AgroManager</h1>

  <p align="center">
    <strong>Sistema Informativo per la Gestione Digitale di una Azienda Agricola</strong>
    <br />
    Dalla produzione alla vendita, tutto tracciato in un unico database relazionale.
    <br />
    <br />
    <a href="https://docs.google.com/document/d/1iQn10vGXzG8XahtEoyIvBHJMU6FeDTPvbSqV_C37omU/edit?usp=sharing"><strong>Leggi l'analisi completa »</strong></a>
    &nbsp;·&nbsp;
    <a href="https://drive.google.com/file/d/1mZxfmnd_IXcovyFWqYBGdYFGCsdZgcPs/view?usp=sharing">Visualizza lo Schema ER</a>
    &nbsp;·&nbsp;
    <a href="https://github.com/RubenBavaro/BavAur-AgroTech/issues/new?labels=bug">Segnala un Bug</a>
    &nbsp;·&nbsp;
    <a href="https://github.com/RubenBavaro/BavAur-AgroTech/issues/new?labels=enhancement">Richiedi una Feature</a>
  </p>
</div>

---

<!-- INDICE -->
<details>
  <summary>📋 Indice dei Contenuti</summary>
  <ol>
    <li><a href="#-il-progetto">Il Progetto</a></li>
    <li><a href="#-funzionalità-principali">Funzionalità Principali</a></li>
    <li><a href="#-ruoli-e-accessi">Ruoli e Accessi</a></li>
    <li><a href="#-schema-entità-relazione">Schema Entità-Relazione</a></li>
    <li><a href="#-modello-dei-dati">Modello dei Dati</a></li>
    <li><a href="#-tecnologie">Tecnologie</a></li>
    <li><a href="#-per-iniziare">Per Iniziare</a>
      <ul>
        <li><a href="#prerequisiti">Prerequisiti</a></li>
        <li><a href="#installazione-e-avvio">Installazione e Avvio</a></li>
      </ul>
    </li>
    <li><a href="#-credenziali-di-test">Credenziali di Test</a></li>
    <li><a href="#-struttura-del-progetto">Struttura del Progetto</a></li>
    <li><a href="#-roadmap">Roadmap</a></li>
    <li><a href="#-contribuire">Contribuire</a></li>
    <li><a href="#-licenza">Licenza</a></li>
    <li><a href="#-contatti">Contatti</a></li>
  </ol>
</details>

---

## 🌿 Il Progetto

**AgroManager** è un sistema informativo completo per la gestione digitale di una piccola azienda agricola che produce, trasforma, confeziona e vende prodotti agricoli.

Il sistema è composto da due aree distinte:

- 🌐 **Vetrina pubblica** — homepage accessibile a tutti con catalogo prodotti, immagini reali, filtri e sezione sedi
- 🔒 **Area amministrativa** — dashboard protetta per la gestione completa di prodotti, produzioni, confezioni, vendite e clienti

Tutte le informazioni sono centralizzate in un **database relazionale strutturato**, garantendo:

- 📦 **Tracciabilità completa** — dalla materia prima alla confezione venduta
- 📊 **Integrità dei dati** — vincoli logici su giacenze, totali e riferimenti
- 🏠 **Gestione multi-sede** — ogni admin gestisce solo i dati della propria sede
- 🔗 **Coerenza referenziale** — ogni vendita è collegata al prodotto, alla confezione e alla produzione di origine
- 🌙 **Tema chiaro/scuro** — selezionabile dall'utente e persistente su tutte le pagine

<p align="right">(<a href="#readme-top">↑ torna su</a>)</p>

---

## ✅ Funzionalità Principali

| Area | Descrizione |
|------|-------------|
| 🏠 **Homepage Pubblica** | Vetrina con catalogo prodotti, immagini reali da Unsplash, filtri fresco/lavorato, badge "Esaurito", sezione sedi |
| 🛒 **Carrello & Acquisto** | Acquisto simulato con aggiornamento giacenze reale nel DB, storico ordini personale |
| 🔐 **Autenticazione** | Login multi-ruolo, tab dedicato per admin sede, registrazione clienti, password hashate con bcrypt |
| 🌙 **Tema Chiaro/Scuro** | Toggle sempre disponibile, scelta persistente tramite `localStorage` |
| 🥬 **Classificazione Prodotti** | Attributo `tipoProdotto` (fresco/lavorato), descrizioni, badge disponibilità in tempo reale |
| ⚙️ **Gestione Produzione** | Cicli produttivi con doppia associazione (VIENE PRODOTTO + VIENE LAVORATO) |
| 📦 **Gestione Confezioni** | Lotti confezionati con barra giacenza colorata, badge "Esaurito", decremento automatico all'acquisto |
| 👤 **Gestione Clienti** | Anagrafica con totale speso, numero vendite e link allo storico |
| 🧾 **Gestione Vendite** | Form con righe dinamiche JS, omaggi, totali calcolati in tempo reale, ripristino giacenze su eliminazione |
| 🏢 **Gestione Sedi** | Admin per sede con modifica inline delle giacenze, visualizzazione credenziali admin |
| 🗂️ **Categorie** | Classificazione merceologica con contatore prodotti associati |

<p align="right">(<a href="#readme-top">↑ torna su</a>)</p>

---

## 🔐 Ruoli e Accessi

| Ruolo | Area di accesso | Permessi |
|-------|-----------------|----------|
| **Superadmin** | Dashboard completa | Gestione totale di tutti i dati e tutte le sedi |
| **Sede Admin** | Dashboard filtrata | Solo dati della propria sede (produzioni, confezioni, vendite) |
| **Cliente** | Homepage + Carrello + Ordini | Sfoglia catalogo, acquista (simulato), vede i propri ordini |
| **Ospite** | Solo Homepage | Visualizza il catalogo, non può acquistare |

### Note di sicurezza

- Le password sono hashate con **bcrypt** (`PASSWORD_BCRYPT`)
- La **sessione PHP** viene avviata solo al momento del login, non nelle pagine pubbliche
- Le credenziali degli **admin di sede** si impostano esclusivamente via phpMyAdmin (campo `admin_password_hash` nella tabella `SEDE`), non sono accessibili dall'interfaccia web
- Ogni pagina dell'area admin include un **auth guard** che reindirizza automaticamente gli utenti non autorizzati

<p align="right">(<a href="#readme-top">↑ torna su</a>)</p>

---

## 🗺️ Schema Entità-Relazione

Lo schema ER segue la **notazione Chen classica** (rettangoli, rombi, ellissi) ed è disponibile nel file `.drawio` apribile su [app.diagrams.net](https://app.diagrams.net).

```
CLIENTE ──[EFFETTUA]──> VENDITA ──[AVVIENE IN]──> SEDE
                           │
                      [COMPRENDE]
                           │
                   DETTAGLIO_VENDITA ──[RIGUARDA]──> PRODOTTO ──[APPARTIENE]──> CATEGORIA
                           │                              ║
                        [VENDE]                    [VIENE PRODOTTO]
                           │                         [VIENE LAVORATO]
                       CONFEZIONE <──[CONFEZIONA]── PRODUZIONE ──[AVVIENE IN]──> SEDE
```

> 💡 La **doppia associazione** tra PRODOTTO e PRODUZIONE (`VIENE PRODOTTO` + `VIENE LAVORATO`) rappresenta i due ruoli del prodotto: quello generato dalla produzione e quello utilizzato come materia prima.

<p align="right">(<a href="#readme-top">↑ torna su</a>)</p>

---

## 🗃️ Modello dei Dati

### Entità

| Entità | Attributi chiave | Descrizione |
|--------|-----------------|-------------|
| `UTENTE` | `idUtente` PK, nome, email, password_hash, ruolo, idSede | Account di accesso (superadmin / sede_admin / cliente) |
| `CLIENTE` | `idCliente` PK, nome, nickname, contatti | Anagrafica clienti acquirenti |
| `VENDITA` | `idVendita` PK, dataVendita, totaleCalcolato, totalePagato, note | Transazione commerciale |
| `DETTAGLIO_VENDITA` | `idDettaglio` PK, quantita, prezzoUnitario, omaggio | Riga della vendita |
| `PRODOTTO` | `idProdotto` PK, nome, unitaMisura, **tipoProdotto**, descrizione | Prodotto (fresco o lavorato) |
| `CATEGORIA` | `idCategoria` PK, nomeCategoria | Categoria merceologica |
| `PRODUZIONE` | `idProduzione` PK, dataProduzione, quantitaProdotta | Ciclo produttivo |
| `CONFEZIONE` | `idConfezione` PK, dataConfezionamento, pesoNetto, numeroConfezioni, giacenza | Lotto confezionato |
| `SEDE` | `idSede` PK, nomeSede, indirizzo, admin_email, admin_password_hash | Sede operativa con credenziali admin |

### Associazioni

| Associazione | Entità | Cardinalità |
|---|---|:---:|
| EFFETTUA | CLIENTE → VENDITA | 1 : N |
| COMPRENDE | VENDITA → DETTAGLIO_VENDITA | 1 : N |
| RIGUARDA | DETTAGLIO_VENDITA → PRODOTTO | N : 1 |
| VENDE | DETTAGLIO_VENDITA → CONFEZIONE | N : 1 |
| APPARTIENE | PRODOTTO → CATEGORIA | N : 1 |
| VIENE PRODOTTO | PRODOTTO → PRODUZIONE | 1 : N |
| VIENE LAVORATO | PRODOTTO → PRODUZIONE | 1 : N |
| CONFEZIONA | PRODUZIONE → CONFEZIONE | 1 : N |
| AVVIENE IN | PRODUZIONE → SEDE | N : 1 |
| AVVIENE IN | VENDITA → SEDE | N : 1 |

<p align="right">(<a href="#readme-top">↑ torna su</a>)</p>

---

## 🛠️ Tecnologie

[![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Docker](https://img.shields.io/badge/Docker-2496ED?style=for-the-badge&logo=docker&logoColor=white)](https://www.docker.com/)
[![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white)](https://developer.mozilla.org/en-US/docs/Web/HTML)
[![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)](https://developer.mozilla.org/en-US/docs/Web/CSS)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)](https://getbootstrap.com/)

- **Backend**: PHP 8.2 — logica applicativa, autenticazione, gestione sessioni con PDO
- **Database**: MySQL 8 — storage relazionale con vincoli di integrità e foreign key
- **Infrastruttura**: Docker + Docker Compose — stack completo con PHP, MySQL e phpMyAdmin
- **Frontend**: HTML5 + CSS3 + Bootstrap 5.3 + Font Awesome 6 — interfaccia responsive con tema chiaro/scuro
- **Immagini**: Unsplash — immagini reali dei prodotti caricate dinamicamente tramite URL

<p align="right">(<a href="#readme-top">↑ torna su</a>)</p>

---

## 🚀 Per Iniziare

### Prerequisiti

Prima di procedere, assicurati di avere installato sul tuo sistema:

- **[Docker Desktop](https://www.docker.com/products/docker-desktop/)** (include Docker Compose) — versione 24.0 o superiore
- **[Git](https://git-scm.com/)** — per clonare il repository
- Un client MySQL per importare i file SQL, ad esempio:
  - [phpMyAdmin](https://www.phpmyadmin.net/) _(già disponibile nel container su `http://localhost:8081`)_
  - [DBeaver](https://dbeaver.io/) o [TablePlus](https://tableplus.com/)

> ⚠️ **Non è necessario** installare PHP o MySQL sul proprio sistema: tutto gira all'interno dei container Docker.

---

### Installazione e avvio

**1. Clona il repository**

```bash
git clone https://github.com/RubenBavaro/BavAur-AgroTech.git
```

**2. Entra nella cartella root del progetto**

```bash
cd BavAur-AgroTech
```

**3. Avvia i container Docker**

```bash
docker compose up -d
```

Docker scaricherà le immagini necessarie e avvierà tutti i servizi in background. La prima esecuzione potrebbe richiedere qualche minuto.

Per verificare che i container siano attivi:

```bash
docker compose ps
```

Dovresti vedere tutti i servizi con stato `running`.

**4. Importa il database**

> ⚠️ Questo passaggio è **manuale** e va eseguito una sola volta dopo il primo avvio.

Connettiti a phpMyAdmin su `http://localhost:8081` e importa in ordine i file dalla cartella `SQL/`:

```
BavAur-AgroTech/
└── SQL/
    ├── schema.sql    ← importa PRIMA questo (crea le tabelle)
    └── seed.sql      ← importa DOPO questo (dati e utenti di esempio)
```

In alternativa, da riga di comando:

```bash
docker exec -i BavAur-AgroTech-DB mysql -u myuser -pmypassword myapp_db < SQL/schema.sql
docker exec -i BavAur-AgroTech-DB mysql -u myuser -pmypassword myapp_db < SQL/seed.sql
```

**5. Apri l'applicazione**

```
http://localhost:8080   →  Homepage pubblica (punto di ingresso)
http://localhost:8081   →  phpMyAdmin (gestione database)
```

---

### Fermare il progetto

```bash
docker compose stop    # ferma i container, i dati vengono conservati
docker compose down    # ferma e rimuove i container, i volumi rimangono
```

<p align="right">(<a href="#readme-top">↑ torna su</a>)</p>

---

## 🔑 Credenziali di Test

Dopo aver importato `seed.sql` sono disponibili i seguenti account:

| Ruolo | Email | Password | Come accedere |
|-------|-------|----------|---------------|
| **Superadmin** | `superadmin@agro.it` | `admin123` | Tab "Utente" nella pagina login |
| **Admin Sede Nord** | `nord@agro.it` | `sede123` | Tab **"Admin Sede"** nella pagina login |
| **Admin Sede Sud** | `sud@agro.it` | `sede123` | Tab **"Admin Sede"** nella pagina login |
| **Cliente** | `mario@email.it` | `cliente123` | Tab "Utente" → accede a carrello e ordini |

> 🔒 Per impostare le credenziali admin di una sede in produzione, aggiorna il campo `admin_password_hash` nella tabella `SEDE` via phpMyAdmin con un hash bcrypt:
> ```php
> password_hash('tuapassword', PASSWORD_BCRYPT)
> ```

<p align="right">(<a href="#readme-top">↑ torna su</a>)</p>

---

## 📁 Struttura del Progetto

```
BavAur-AgroTech/
│
├── SQL/
│   ├── schema.sql              # DDL: CREATE TABLE, vincoli, foreign key
│   └── seed.sql                # Dati di esempio + utenti con password hashate bcrypt
│
├── src/                        # Codice sorgente (montato in /var/www/html)
│   ├── assets/css/
│   │   └── style.css           # Tema custom completo con supporto chiaro/scuro
│   │
│   ├── config/
│   │   ├── db.php              # Connessione PDO + helper h(), redirect(), getProductImage()
│   │   └── session.php         # Sessione PHP + helper isLoggedIn(), isSuperAdmin(), userSede()
│   │
│   ├── includes/
│   │   ├── auth.php            # Guard: blocca accessi non autorizzati, reindirizza al login
│   │   ├── header.php          # Sidebar admin + topbar con theme toggle e badge utente
│   │   └── footer.php          # Bootstrap JS + logica tema chiaro/scuro + sidebar mobile
│   │
│   │   ── AREA PUBBLICA ──────────────────────────────────────────────────
│   ├── index.php               # Router intelligente (admin→dashboard, altri→homepage)
│   ├── homepage.php            # Vetrina pubblica con catalogo, immagini, filtri e sedi
│   ├── login.php               # Login con tab Utente / Admin Sede
│   ├── register.php            # Registrazione nuovi clienti
│   ├── logout.php              # Distruzione completa della sessione
│   ├── carrello.php            # Carrello + checkout simulato + aggiornamento giacenze DB
│   └── ordini.php              # Storico ordini personale del cliente
│
│       ── AREA ADMIN (protetta da auth.php) ──────────────────────────────
│   ├── dashboard.php           # Statistiche, azioni rapide, ultime vendite, giacenze basse
│   ├── clienti.php             # CRUD clienti con totale speso e link vendite
│   ├── categorie.php           # CRUD categorie con contatore prodotti
│   ├── prodotti.php            # CRUD prodotti con filtro categoria e badge stock
│   ├── produzioni.php          # CRUD produzioni con doppia associazione prodotto
│   ├── confezioni.php          # CRUD confezioni con barra giacenza e badge esaurito
│   ├── sedi.php                # CRUD sedi + pagina gestione giacenze inline per sede admin
│   └── vendite.php             # CRUD vendite con righe JS dinamiche e ripristino giacenze
│
├── Dockerfile                  # PHP 8.2 + Apache + mysqli + pdo_mysql
├── docker-compose.yaml         # Stack: php-web (8080), mysql, phpmyadmin (8081)
└── README.md
```

<p align="right">(<a href="#readme-top">↑ torna su</a>)</p>

---

## 🗺️ Roadmap

- [x] Analisi dei requisiti e progettazione concettuale
- [x] Schema Entità-Relazione in notazione Chen
- [x] Schema SQL con vincoli di integrità
- [x] Dati di esempio con utenti e password hashate
- [x] Sistema di autenticazione con tre ruoli distinti
- [x] Homepage pubblica con catalogo, immagini reali e sezione sedi
- [x] Carrello acquisti con checkout simulato e aggiornamento giacenze reale
- [x] Storico ordini personale per i clienti
- [x] Dashboard amministrativa con statistiche in tempo reale
- [x] CRUD completo per tutte le entità
- [x] Gestione giacenze inline per gli admin di sede
- [x] Tema chiaro/scuro persistente su tutte le pagine
- [x] Filtro dati per sede negli admin di sede
- [ ] Esportazione report vendite in PDF
- [ ] Notifiche per giacenze basse
- [ ] Ricerca full-text nel catalogo

Consulta le [issue aperte](https://github.com/RubenBavaro/BavAur-AgroTech/issues) per la lista completa.

<p align="right">(<a href="#readme-top">↑ torna su</a>)</p>

---

## 🤝 Contribuire

I contributi rendono la comunità open source un posto straordinario in cui imparare, crescere e creare. Qualsiasi contributo è **molto apprezzato**.

Se hai un suggerimento per migliorare il progetto, fai un fork del repo e crea una pull request, oppure apri un'issue con il tag `enhancement`. Non dimenticare di mettere una ⭐ al progetto!

1. Fai il fork del progetto
2. Crea il tuo branch (`git checkout -b feature/NuovaFunzionalita`)
3. Effettua il commit delle modifiche (`git commit -m 'Aggiunge NuovaFunzionalita'`)
4. Fai il push sul branch (`git push origin feature/NuovaFunzionalita`)
5. Apri una Pull Request

<p align="right">(<a href="#readme-top">↑ torna su</a>)</p>

---

## 📄 Licenza

Distribuito sotto licenza MIT. Vedi `LICENSE.txt` per maggiori informazioni.

<p align="right">(<a href="#readme-top">↑ torna su</a>)</p>

---

## 📬 Contatti

**Ruben Bavaro** — [@RubenBavaro](https://github.com/RubenBavaro)

**Raffaele Auriole** — [@RaffaeleeAuriole](https://github.com/RaffaeleeAuriole)

Link al progetto: [https://github.com/RubenBavaro/BavAur-AgroTech](https://github.com/RubenBavaro/BavAur-AgroTech)

<p align="right">(<a href="#readme-top">↑ torna su</a>)</p>

---

## 🙏 Ringraziamenti

- [Best-README-Template](https://github.com/othneildrew/Best-README-Template) — template di riferimento per questo README
- [Bootstrap 5](https://getbootstrap.com/) — framework CSS per l'interfaccia responsive
- [Font Awesome 6](https://fontawesome.com/) — icone dell'interfaccia
- [Unsplash](https://unsplash.com/) — immagini gratuite dei prodotti
- [draw.io](https://app.diagrams.net) — strumento per il diagramma ER
- [Shields.io](https://shields.io) — badge per il README
- [MySQL Documentation](https://dev.mysql.com/doc/) — riferimento per la progettazione del database
- [Chen's ER Model](https://en.wikipedia.org/wiki/Entity%E2%80%93relationship_model) — notazione utilizzata nello schema ER

<p align="right">(<a href="#readme-top">↑ torna su</a>)</p>

---

<div align="center">
  <sub>Fatto con 🌿 per la gestione digitale dell'agricoltura</sub>
</div>