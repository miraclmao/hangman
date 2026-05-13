<?php
session_start();
require_once 'hangedman.php';

// Verbinding maken met de database
$db = new mysqli("localhost", "root", "", "galgje");
if ($db->connect_error) die("Kan geen verbinding maken met de database.");

// ── Hulpfuncties ──────────────────────────────────────────────────

// Kiest een willekeurig woord uit de database
function randomWord($db) {
    $row = $db->query("SELECT woord FROM woorden ORDER BY RAND() LIMIT 1")->fetch_assoc();
    return strtoupper($row['woord'] ?? 'FOUT');
}

// Bouwt de weergave van het woord: letters die geraden zijn worden getoond,
// de rest wordt vervangen door een underscore (_)
function buildDisplay($word, $guessed) {
    return trim(implode(' ', array_map(
        fn($l) => str_contains($guessed, $l) ? $l : '_',
        str_split($word)
    )));
}

// Toont de spelpagina met de galg, het woord en de geraden letters
function gamePage($gallows, $display, $guessed) {
    $d = htmlspecialchars($display,  ENT_QUOTES, 'UTF-8');
    $g = htmlspecialchars($guessed, ENT_QUOTES, 'UTF-8');
    echo <<<HTML
        <!DOCTYPE html><html>
        <head><title>Galgje</title><link rel="stylesheet" href="style.css"></head>
        <body>
        <h1>Galgje</h1>
        <pre>$gallows</pre>
        <p><strong>Woord:</strong> $d</p>
        <p><strong>Geraden letters:</strong> $g</p>
        <form method="post">
            <input type="text" name="letter" maxlength="1" required autofocus>
            <input type="submit" value="Raad">
        </form>
        </body></html>
        HTML;
}

// Toont de eindpagina met een bericht (gewonnen of verloren) en het juiste woord
function endPage($message, $word) {
    $w = htmlspecialchars($word, ENT_QUOTES, 'UTF-8');
    echo "<h1>$message</h1><p>Het woord was: <strong>$w</strong></p><a href=''>Opnieuw spelen</a>";
}

// ── Nieuw spel starten ──────────────────────────────────────────────────────

// Als er geen formulier verstuurd is, start een nieuw spel
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Sla een nieuw woord, 0 fouten en lege geraden letters op in de sessie
    $_SESSION = ['woord' => randomWord($db), 'fouten' => 0, 'geraden' => ''];
    // Toon de beginpagina met een lege galg en underscores voor het woord
    gamePage($hang[0], str_repeat('_ ', strlen($_SESSION['woord'])), '');
    $db->close();
    exit;
}

// ── Verwerken van de geraden letter ─────────────────────────────────────────

global $hang;

// Haal de spelgegevens op uit de sessie
['woord' => $word, 'fouten' => $errors, 'geraden' => $guessed] = $_SESSION;

// Lees de ingevoerde letter in en controleer of het een geldige letter is
$input = $_POST['letter'] ?? '';
if (!preg_match('/^[a-zA-Z]$/', $input)) die('Ongeldige invoer.');

// Zet de letter om naar hoofdletter zodat vergelijking altijd klopt
$letter = strtoupper($input);

// Voeg de letter toe als die nog niet geraden is
if (!str_contains($guessed, $letter)) {
    $guessed .= $letter;
    // Als de letter niet in het woord zit, telt het als een fout
    if (!str_contains($word, $letter)) $errors++;
}

// Sla de bijgewerkte spelgegevens op in de sessie
$_SESSION = ['woord' => $word, 'fouten' => $errors, 'geraden' => $guessed];

// Maak de weergave van het woord met de tot nu toe geraden letters
$display = buildDisplay($word, $guessed);

// Controleer of de speler gewonnen heeft (geen underscores meer over)
if (!str_contains($display, '_')) {
    endPage('Je hebt gewonnen!', $word);
    session_destroy(); // Sessie verwijderen na afloop van het spel
// Controleer of de speler verloren heeft (6 fouten gemaakt)
} elseif ($errors >= 6) {
    endPage('Je hebt verloren.', $word);
    session_destroy(); // Sessie verwijderen na afloop van het spel
// Anders: spel gaat door, toon de bijgewerkte spelpagina
} else {
    gamePage($hang[$errors], $display, $guessed);
}

// Sluit de databaseverbinding
$db->close();