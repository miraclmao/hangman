<?php
session_start();
require_once 'hangedman.php';

// Verbinding maken met de database
$db = new mysqli("localhost", "root", "", "galgje");
if ($db->connect_error) die("Kan geen verbinding maken met de database.");

// Kiest een willekeurig woord uit de database
function willekeurigWoord($db) {
    $rij = $db->query("SELECT woord FROM woorden ORDER BY RAND() LIMIT 1")->fetch_assoc();
    return strtoupper($rij['woord']);
}

// Toont het woord met underscores voor nog niet geraden letters
function bouwWeergave($woord, $geraden) {
    return trim(implode(' ', array_map(
        fn($letter) => str_contains($geraden, $letter) ? $letter : '_',
        str_split($woord)
    )));
}

// Toont de spelpagina met de galg, het woord en de geraden letters
function spelPagina($galg, $weergave, $geraden) {
    echo <<<HTML
        <!DOCTYPE html><html>
        <head><title>Galgje</title><link rel="stylesheet" href="style.css"></head>
        <body>
        <h1>Galgje</h1>
        <pre>$galg</pre>
        <p><strong>Woord:</strong> $weergave</p>
        <p><strong>Geraden letters:</strong> $geraden</p>
        <form method="post">
            <input type="text" name="letter" maxlength="1" required autofocus>
            <input type="submit" value="Raad">
        </form>
        </body></html>
        HTML;
}

// Toont de eindpagina met of de speler gewonnen of verloren heeft
function eindPagina($bericht, $woord) {
    echo "<h1>$bericht</h1><p>Het woord was: <strong>$woord</strong></p><a href=''>Opnieuw spelen</a>";
}

// Als de pagina voor het eerst geladen wordt, start een nieuw spel
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Sla het woord, aantal fouten en geraden letters op in de sessie
    $_SESSION = ['woord' => willekeurigWoord($db), 'fouten' => 0, 'geraden' => ''];
    spelPagina($hang[0], str_repeat('_', strlen($_SESSION['woord'])), '');
    $db->close();
    exit;
}

global $hang;

// Haal de spelgegevens op uit de sessie
['woord' => $woord, 'fouten' => $fouten, 'geraden' => $geraden] = $_SESSION;

// Zet de ingevoerde letter om naar hoofdletter
$letter = strtoupper($_POST['letter']);

// Voeg de letter toe als die nog niet eerder geraden is
if (!str_contains($geraden, $letter)) {
    $geraden .= $letter;
    // Fout als de letter niet in het woord zit
    if (!str_contains($woord, $letter)) $fouten++;
}

// Sla de bijgewerkte spelgegevens op in de sessie
$_SESSION = ['woord' => $woord, 'fouten' => $fouten, 'geraden' => $geraden];

// Bouw de weergave van het woord met de geraden letters
$weergave = bouwWeergave($woord, $geraden);

// Gewonnen: geen underscores meer over
if (!str_contains($weergave, '_')) {
    eindPagina('Je hebt gewonnen!', $woord);
    session_destroy();
// Verloren: 6 fouten gemaakt
} elseif ($fouten >= 6) {
    eindPagina('Je hebt verloren.', $woord);
    session_destroy();
// Spel gaat verder
} else {
    spelPagina($hang[$fouten], $weergave, $geraden);
}

// Sluit de databaseverbinding
$db->close();