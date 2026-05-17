<?php

session_start();

require_once 'hangedman.php';

// Verbinding maken met de database
$db = new mysqli("localhost", "root", "", "galgje");

if ($db->connect_error) {
    die("Kan geen verbinding maken met de database.");
}

// Kiest een willekeurig woord uit de database
function willekeurigWoord(mysqli $db): string
{
    $rij = $db->query(
        "SELECT woord FROM woorden ORDER BY RAND() LIMIT 1"
    )->fetch_assoc();

    return strtoupper($rij['woord']);
}

// Toont het woord met underscores voor nog niet geraden letters
function bouwWeergave(string $woord, string $geraden): string
{
    return trim(
        implode(
            ' ',
            array_map(
                fn($letter) => str_contains($geraden, $letter)
                    ? $letter
                    : '_',
                str_split($woord)
            )
        )
    );
}

// Toont de spelpagina
function spelPagina(string $galg, string $weergave, string $geraden): void
{
    echo <<<HTML
    <!DOCTYPE html>
    <html>
    <head>
        <title>Galgje</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <h1>Galgje</h1>

        <pre>$galg</pre>

        <p><strong>Woord:</strong> $weergave</p>
        <p><strong>Geraden letters:</strong> $geraden</p>

        <form method="post">
            <input
                type="text"
                name="letter"
                maxlength="1"
                required
                autofocus
            >

            <input type="submit" value="Raad">
        </form>
    </body>
    </html>
    HTML;
}

// Toont de eindpagina
function eindPagina(string $bericht, string $woord): void
{
    echo "
        <h1>$bericht</h1>
        <p>Het woord was: <strong>$woord</strong></p>
        <a href=''>Opnieuw spelen</a>
    ";
}

// Nieuw spel starten
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION = [
        'woord' => willekeurigWoord($db),
        'fouten' => 0,
        'geraden' => ''
    ];

    spelPagina(
        $hang[0],
        str_repeat('_', strlen($_SESSION['woord'])),
        ''
    );

    $db->close();

    exit;
}

global $hang;

// Haal sessiegegevens op
[
    'woord' => $woord,
    'fouten' => $fouten,
    'geraden' => $geraden
] = $_SESSION;

// Ingevoerde letter
$letter = strtoupper($_POST['letter']);

// Controleer letter
if (!str_contains($geraden, $letter)) {
    $geraden .= $letter;

    if (!str_contains($woord, $letter)) {
        $fouten++;
    }
}

// Sessie bijwerken
$_SESSION = [
    'woord' => $woord,
    'fouten' => $fouten,
    'geraden' => $geraden
];

// Nieuwe woordweergave bouwen
$weergave = bouwWeergave($woord, $geraden);

// Gewonnen
if (!str_contains($weergave, '_')) {
    eindPagina('Je hebt gewonnen!', $woord);

    session_destroy();
} elseif ($fouten >= 6) {
    // Verloren
    eindPagina('Je hebt verloren.', $woord);

    session_destroy();
} else {
    // Verder spelen
    spelPagina($hang[$fouten], $weergave, $geraden);
}

// Database sluiten
$db->close();