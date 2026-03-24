# BavAur-AgroTech
<a name="readme-top"></a>

<!-- SHIELDS -->
<div align="center">

[![Status](https://img.shields.io/badge/status-in%20sviluppo-brightgreen?style=for-the-badge)](https://github.com/your_username/agromanager)
[![Versione](https://img.shields.io/badge/versione-1.0.0-blue?style=for-the-badge)](https://github.com/your_username/agromanager)
[![Licenza](https://img.shields.io/badge/licenza-MIT-yellow?style=for-the-badge)](LICENSE.txt)
[![Database](https://img.shields.io/badge/database-relazionale-orange?style=for-the-badge&logo=mysql&logoColor=white)](https://github.com/your_username/agromanager)

</div>

<!-- LOGO E TITOLO -->
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
    <br />
    <br />
    <a href="https://drive.google.com/file/d/1mZxfmnd_IXcovyFWqYBGdYFGCsdZgcPs/view?usp=sharing">Visualizza lo Schema ER</a>
    ·
    <a href="https://github.com/RubenBavaro/BavAur-AgroTech/issues/new?labels=bug">Segnala un Bug</a>
    ·
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
    <li><a href="#-schema-entità-relazione">Schema Entità-Relazione</a></li>
    <li><a href="#-modello-dei-dati">Modello dei Dati</a>
      <ul>
        <li><a href="#entità">Entità</a></li>
        <li><a href="#associazioni">Associazioni</a></li>
      </ul>
    </li>
    <li><a href="#-tecnologie">Tecnologie</a></li>
    <li><a href="#-per-iniziare">Per Iniziare</a>
      <ul>
        <li><a href="#prerequisiti">Prerequisiti</a></li>
        <li><a href="#installazione">Installazione</a></li>
      </ul>
    </li>
    <li><a href="#-struttura-del-progetto">Struttura del Progetto</a></li>
    <li><a href="#-roadmap">Roadmap</a></li>
    <li><a href="#-contribuire">Contribuire</a></li>
    <li><a href="#-licenza">Licenza</a></li>
    <li><a href="#-contatti">Contatti</a></li>
  </ol>
</details>

---

## 🌿 Il Progetto

**AgroManager** è un sistema informativo progettato per digitalizzare e razionalizzare la gestione operativa di una piccola azienda agricola che produce, trasforma, confeziona e vende prodotti agricoli.

Il sistema centralizza tutte le informazioni in un **database relazionale strutturato**, garantendo:

- 📦 **Tracciabilità completa** — dalla materia prima alla confezione venduta
- 📊 **Integrità dei dati** — vincoli logici su giacenze, totali e riferimenti
- 🏠 **Gestione multi-sede** — coordinamento di più sedi operative
- 🔗 **Coerenza referenziale** — ogni vendita è collegata al prodotto, alla confezione e alla produzione di origine

> Il modello nasce dall'analisi dei requisiti di una reale azienda agricola, con focus su semplicità, estensibilità e correttezza semantica.

<p align="right">(<a href="#readme-top">↑ torna su</a>)</p>

---

## ✅ Funzionalità Principali

| Area | Descrizione |
|------|-------------|
| 🥬 **Classificazione Prodotti** | Gestione unificata con attributo `tipoProdotto` (fresco / lavorato) |
| ⚙️ **Gestione Produzione** | Tracciamento cicli produttivi con doppia associazione al prodotto |
| 📦 **Gestione Confezioni** | Lotti confezionati con controllo giacenza in tempo reale |
| 👤 **Gestione Clienti** | Anagrafica clienti con nickname e recapiti |
| 🧾 **Gestione Vendite** | Vendite con dettaglio righe, omaggi e totali calcolati |
| 🏠 **Gestione Sedi** | Più sedi per produzione e vendita, collegate a ogni operazione |
| 🗂️ **Categorie** | Classificazione merceologica dei prodotti |

<p align="right">(<a href="#readme-top">↑ torna su</a>)</p>

---

## 🗺️ Schema Entità-Relazione

Lo schema ER segue la **notazione Chen classica** (rettangoli, rombi, ellissi) ed è disponibile nel file precedemente fornito, apribile su [app.diagrams.net](https://app.diagrams.net).

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

> 💡 La **doppia associazione** tra PRODOTTO e PRODUZIONE (`VIENE PRODOTTO` + `VIENE LAVORATO`) rappresenta i due ruoli del prodotto: quello generato e quello utilizzato come materia prima.

<p align="right">(<a href="#readme-top">↑ torna su</a>)</p>

---

## 🗃️ Modello dei Dati

### Entità

| Entità | Attributi chiave | Descrizione |
|--------|-----------------|-------------|
| `CLIENTE` | `idCliente` PK, nome, nickname, password | Anagrafica clienti |
| `VENDITA` | `idVendita` PK, dataVendita, totaleCalcolato, totalePagato, note | Transazione commerciale |
| `DETTAGLIO_VENDITA` | `idDettaglio` PK, quantita, prezzoUnitario, omaggio | Riga della vendita |
| `PRODOTTO` | `idProdotto` PK, nome, unitaMisura, **tipoProdotto** | Prodotto (fresco o lavorato) |
| `CATEGORIA` | `idCategoria` PK, nomeCategoria | Categoria merceologica |
| `PRODUZIONE` | `idProduzione` PK, dataProduzione, quantitaProdotta | Ciclo produttivo |
| `CONFEZIONE` | `idConfezione` PK, dataConfezionamento, pesoNetto, numeroConfezioni, giacenza | Lotto confezionato |
| `SEDE` | `idSede` PK, nomeSede, indirizzo | Sede operativa |

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

- **Backend**: PHP 8+ — logica applicativa e interazione con il database
- **Database**: MySQL 8 — storage relazionale centralizzato
- **Infrastruttura**: Docker + Docker Compose — ambiente containerizzato, nessuna installazione manuale richiesta
- **Frontend**: HTML5 + CSS3 — interfaccia di gestione

<p align="right">(<a href="#readme-top">↑ torna su</a>)</p>

---

## 🚀 Per Iniziare

### Prerequisiti

Prima di procedere, assicurati di avere installato sul tuo sistema:

- **[Docker Desktop](https://www.docker.com/products/docker-desktop/)** (include Docker Compose) — versione 24.0 o superiore
- **[Git](https://git-scm.com/)** — per clonare il repository
- Un client MySQL per importare i file SQL, ad esempio:
  - [VisualStudio]

  - [phpMyAdmin](https://www.phpmyadmin.net/) _(disponibile nel container)_

> ⚠️ **Non è necessario** installare PHP o MySQL sul proprio sistema: tutto gira all'interno dei container Docker.

---

### Installazione e avvio

Segui questi passi nell'ordine indicato per avere il progetto funzionante in pochi minuti.

**1. Clona il repository**

```bash
git clone https://github.com/RubenBavaro/BavAur-AgroTech.git

**2. Entra nella cartella root del progetto**

```bash
cd BavAur-AgroTech
```

**3. Avvia i container Docker**

Dalla cartella root, lancia il seguente comando nel terminale:

```bash
docker compose up -d
```

Docker scaricherà le immagini necessarie (PHP, MySQL, ecc.) e avvierà tutti i servizi in background. La prima esecuzione potrebbe richiedere qualche minuto.

Per verificare che i container siano attivi:

```bash
docker compose ps
```

Dovresti vedere tutti i servizi con stato `running`.

**4. Importa il database**

> ⚠️ Questo passaggio è **manuale** e va eseguito una sola volta dopo il primo avvio.

Una volta che i container sono in esecuzione, connettiti al database MySQL tramite il tuo client preferito con le credenziali configurate nel file `docker-compose.yml`, quindi importa i file SQL nella seguente cartella del progetto:

```
agromanager/
└── SQL/
    ├── schema.sql    ← importa prima questo (crea le tabelle)
    └── seed.sql      ← importa dopo questo (dati di esempio, opzionale)
```

In alternativa, puoi importare da riga di comando con:

```bash
# Importa lo schema (struttura del database)
docker exec -i agromanager-db mysql -u root -psecret agromanager < SQL/schema.sql

# Importa i dati di esempio (opzionale)
docker exec -i agromanager-db mysql -u root -psecret agromanager < SQL/seed.sql
```

**5. Apri l'applicazione**

Una volta completata l'importazione, apri il browser e vai su:

```
http://localhost:8080
```

---

### Fermare il progetto

Per fermare tutti i container senza perdere i dati:

```bash
docker compose stop
```

Per fermare e rimuovere i container (i dati nel volume MySQL vengono conservati):

```bash
docker compose down
```

<p align="right">(<a href="#readme-top">↑ torna su</a>)</p>

---

## 📁 Struttura del Progetto

```
agromanager/
│
│
├── sql/
│   ├── schema.sql                          # DDL: CREATE TABLE e vincoli
│   └── seed.sql                            # Dati di esempio
│
├── README.md
└── LICENSE.txt
```

<p align="right">(<a href="#readme-top">↑ torna su</a>)</p>

---

## 🗺️ Roadmap

- [x] Analisi dei requisiti
- [x] Schema Entità-Relazione (notazione Chen)
- [x] Tabella delle associazioni e regole di lettura
- [x] Vincoli di integrità logica
- [ ] Generazione DDL SQL (`schema.sql`)
- [ ] Dati di esempio (`seed.sql`)
- [ ] Interfaccia web di gestione

Consulta le [issue aperte](https://github.com/RubenBavaro/BavAur-AgroTech/issues) per la lista completa delle feature proposte.

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

Risorse utili utilizzate durante lo sviluppo del progetto:

- [Best-README-Template](https://github.com/othneildrew/Best-README-Template) — template di riferimento per questo README
- [draw.io](https://app.diagrams.net) — strumento gratuito per diagrammi ER
- [Shields.io](https://shields.io) — badge per il README
- [MySQL Documentation](https://dev.mysql.com/doc/) — riferimento per la progettazione del database
- [Chen's ER Model](https://en.wikipedia.org/wiki/Entity%E2%80%93relationship_model) — notazione utilizzata nello schema ER

<p align="right">(<a href="#readme-top">↑ torna su</a>)</p>

---

<div align="center">
  <sub>Fatto con 🌿 per la gestione digitale dell'agricoltura</sub>
</div>