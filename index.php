<?php
session_start();
require_once 'hangedman.php';

$db = new mysqli("localhost", "root", "", "galgje");
if ($db->connect_error) die("Kan geen verbinding maken met de database.");

// ── Helpers ──────────────────────────────────────────────────

function randomWord($db) {
    $row = $db->query("SELECT woord FROM woorden ORDER BY RAND() LIMIT 1")->fetch_assoc();
    return strtoupper($row['woord'] ?? 'FOUT');
}

function buildDisplay($word, $guessed) {
    return trim(implode(' ', array_map(
        fn($l) => str_contains($guessed, $l) ? $l : '_',
        str_split($word)
    )));
}

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

function endPage($message, $word) {
    $w = htmlspecialchars($word, ENT_QUOTES, 'UTF-8');
    echo "<h1>$message</h1><p>Het woord was: <strong>$w</strong></p><a href=''>Opnieuw spelen</a>";
}

// ── New game ──────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION = ['woord' => randomWord($db), 'fouten' => 0, 'geraden' => ''];
    gamePage($hang[0], str_repeat('_ ', strlen($_SESSION['woord'])), '');
    $db->close();
    exit;
}

// ── Process guess ─────────────────────────────────────────────

global $hang;
['woord' => $word, 'fouten' => $errors, 'geraden' => $guessed] = $_SESSION;

$input = $_POST['letter'] ?? '';
if (!preg_match('/^[a-zA-Z]$/', $input)) die('Ongeldige invoer.');

$letter = strtoupper($input);

if (!str_contains($guessed, $letter)) {
    $guessed .= $letter;
    if (!str_contains($word, $letter)) $errors++;
}

$_SESSION = ['woord' => $word, 'fouten' => $errors, 'geraden' => $guessed];

$display = buildDisplay($word, $guessed);

if (!str_contains($display, '_')) {
    endPage('Je hebt gewonnen!', $word);
    session_destroy();
} elseif ($errors >= 6) {
    endPage('Je hebt verloren.', $word);
    session_destroy();
} else {
    gamePage($hang[$errors], $display, $guessed);
}

$db->close();