<?php
// ── Database connection ──────────────────────────────────────
define('DB_HOST',    'db');
define('DB_NAME',    'myapp_db');
define('DB_USER',    'root');
define('DB_PASS',    'rootpassword');
define('DB_CHARSET', 'utf8mb4');

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET),
        DB_USER, DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die('<div style="font-family:sans-serif;padding:2rem;color:#c0392b;">
        <h2>❌ Errore di connessione al database</h2>
        <p>' . htmlspecialchars($e->getMessage()) . '</p>
        <p>Verifica che i container Docker siano attivi: <code>docker compose ps</code></p>
    </div>');
}

// ── Helpers ──────────────────────────────────────────────────
function h(mixed $v): string {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): never {
    header("Location: $url");
    exit;
}

// ── Image helper ─────────────────────────────────────────────
// Restituisce l'URL immagine per un prodotto.
// Priorità:
//   1. immagineUrl della riga PRODOTTO (URL custom impostata in prodotti.php)
//   2. keyword-match sul nome del prodotto → Unsplash
//   3. fallback categoria → Unsplash
//   4. fallback finale per tipo (fresco/lavorato)
function getProductImage(string $nome, string $categoria = '', string $tipo = 'fresco', ?string $immagineUrl = null): string {
    // 1. URL custom salvata nel DB — ha la precedenza assoluta
    if ($immagineUrl !== null && $immagineUrl !== '') {
        return $immagineUrl;
    }

    $n = strtolower($nome);
    $c = strtolower($categoria);

    // Mappa keyword → photo ID Unsplash
    $keywords = [
        'miele'        => 'photo-1558642452-9d2a7deb7f62',
        'acacia'       => 'photo-1558642452-9d2a7deb7f62',
        'millefiori'   => 'photo-1471943038580-7042dce2b1ab',
        'olio'         => 'photo-1474979266404-7eaacbcd87c5',
        'oliva'        => 'photo-1474979266404-7eaacbcd87c5',
        'peperoncino'  => 'photo-1583095208891-71ba7a3f42be',
        'pomodor'      => 'photo-1546094096-0df4bcabd337',
        'passata'      => 'photo-1558618666-fcd25c85cd64',
        'fichi'        => 'photo-1601493700630-a9a4ebe90c1f',
        'marmellata'   => 'photo-1562805040-2c6d9c42a0e3',
        'confettura'   => 'photo-1562805040-2c6d9c42a0e3',
        'zucchin'      => 'photo-1587411768315-b109e8d11b79',
        'fagiolini'    => 'photo-1597362925123-77861d3fbac7',
        'cetriolo'     => 'photo-1568702846914-96b305d2aaeb',
        'basilico'     => 'photo-1628556270448-4d4e4148e1b1',
        'rosmarino'    => 'photo-1530991472021-23e3601b49a9',
        'lavanda'      => 'photo-1500479694472-551d1fb6258d',
        'limone'       => 'photo-1571771894821-ce9b6c11b08e',
        'arancio'      => 'photo-1582979512210-99b6a53386f9',
        'mela'         => 'photo-1570913149827-d2ac84ab3f9a',
        'pera'         => 'photo-1590005354167-6da97870c757',
        'noce'         => 'photo-1606676539940-12768ce0e762',
        'mandorla'     => 'photo-1590005354167-6da97870c757',
        'uva'          => 'photo-1537640538966-79f369143f8f',
        'vino'         => 'photo-1510812431401-41d2bd2722f3',
        'aceto'        => 'photo-1474979266404-7eaacbcd87c5',
        'pollo'        => 'photo-1587383378702-b3ecc7b4d0d6',
        'erba'         => 'photo-1591857177580-dc82b9ac4e1e',
        'timo'         => 'photo-1573481078060-9a6ef7af8e16',
        'salvia'       => 'photo-1591857177580-dc82b9ac4e1e',
        'mentuccia'    => 'photo-1628556270448-4d4e4148e1b1',
        'cipolla'      => 'photo-1587049352846-4a222e784d38',
        'aglio'        => 'photo-1540420773420-3366772f4999',
        'carota'       => 'photo-1447175008436-054170c2e979',
        'patata'       => 'photo-1518977676601-b53f82aba655',
        'melanzana'    => 'photo-1615484478614-f38d3eb9f0c7',
        'peperone'     => 'photo-1563565375-f3fdfdbefa83',
        'carciofo'     => 'photo-1587411768315-b109e8d11b79',
        'lattuga'      => 'photo-1540420773420-3366772f4999',
        'spinaci'      => 'photo-1540420773420-3366772f4999',
        'piselli'      => 'photo-1515543904379-3d757afe72e4',
        'fagioli'      => 'photo-1515543904379-3d757afe72e4',
        'lenticchie'   => 'photo-1515543904379-3d757afe72e4',
        'ceci'         => 'photo-1515543904379-3d757afe72e4',
    ];

    foreach ($keywords as $keyword => $photoId) {
        if (str_contains($n, $keyword)) {
            return "https://images.unsplash.com/{$photoId}?w=500&h=360&fit=crop&auto=format&q=80";
        }
    }

    // Fallback per categoria
    $catFallback = [
        'miele'      => 'photo-1558642452-9d2a7deb7f62',
        'oli'        => 'photo-1474979266404-7eaacbcd87c5',
        'conserve'   => 'photo-1562805040-2c6d9c42a0e3',
        'frutta'     => 'photo-1619566636858-adf3ef46400b',
        'verdura'    => 'photo-1540420773420-3366772f4999',
        'ortaggi'    => 'photo-1566385101042-1a0aa0c1268c',
        'legumi'     => 'photo-1515543904379-3d757afe72e4',
        'aromatich'  => 'photo-1628556270448-4d4e4148e1b1',
    ];
    foreach ($catFallback as $key => $photoId) {
        if (str_contains($c, $key)) {
            return "https://images.unsplash.com/{$photoId}?w=500&h=360&fit=crop&auto=format&q=80";
        }
    }

    // Fallback finale per tipo
    if ($tipo === 'lavorato') {
        return "https://images.unsplash.com/photo-1562805040-2c6d9c42a0e3?w=500&h=360&fit=crop&auto=format&q=80";
    }
    return "https://images.unsplash.com/photo-1500937386664-56d1dfef3854?w=500&h=360&fit=crop&auto=format&q=80";
}
