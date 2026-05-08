<?php

// Start het geheugen van de server (onthoudt het woord, fouten, etc.)
session_start();

// Laad de ASCII-tekeningen van de galg (opgeslagen in $hang[0] t/m $hang[6])
require_once 'hangedman.php';

// Maak verbinding met de database
$db = new mysqli("localhost", "root", "", "galgje");

// Stop het script als de verbinding mislukt
if ($db->connect_error) {
    die("Kan geen verbinding maken met de database.");
}

// ============================================================
// STAP 1: Haalt een willekeurig woord op uit de database
// ============================================================

function haalWillekeurigWoord($db) {

    // Vraag een willekeurig woord op
    $resultaat = $db->query("SELECT woord FROM woorden ORDER BY RAND() LIMIT 1");

    // Als er een woord gevonden is, geef het terug in hoofdletters
    if ($resultaat && $resultaat->num_rows > 0) {
        $rij = $resultaat->fetch_assoc();
        return strtoupper($rij['woord']);
    }

    // Als er iets mis ging, geef een foutwoord terug
    return "FOUT";
}

// ============================================================
// STAP 2: Bouw de weergave van het woord
// Voorbeeld: woord = "GALGJE", geraden = "GA"
// Resultaat: "G A _ G _ _"
// ============================================================

function maakWoordWeergave($woord, $geradenLetters) {

    $weergave = '';

    // Loop door elke letter van het woord
    for ($i = 0; $i < strlen($woord); $i++) {
        $letter = $woord[$i];

        // Als de letter al geraden is, toon hem — anders een underscore
        if (str_contains($geradenLetters, $letter)) {
            $weergave .= $letter . ' ';
        } else {
            $weergave .= '_ ';
        }
    }

    // Verwijder de spatie aan het einde en geef het terug
    return trim($weergave);
}

// ============================================================
// STAP 3: Controleer of het woord volledig geraden is
// ============================================================

function woordIsKlaar($weergave) {
    // Als er geen underscore meer in zit, is het woord geraden
    return !str_contains($weergave, '_');
}

// ============================================================
// STAP 4: Toon de spelpagina (galg + woord + invoerveld)
// ============================================================

function toonSpelPagina($galgTekening, $woordWeergave, $geradenLetters) {

    // Beveilig de tekst zodat er geen gevaarlijke code in de pagina terechtkomt
    $veiligWoord   = htmlspecialchars($woordWeergave,  ENT_QUOTES, 'UTF-8');
    $veiligGeraden = htmlspecialchars($geradenLetters, ENT_QUOTES, 'UTF-8');

    // Stuur de HTML-pagina naar de browser
    echo <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <title>Galgje</title>
        </head>
        <body>

        <h1>Galgje</h1>

        <pre>$galgTekening</pre>

        <p><strong>Woord:</strong> $veiligWoord</p>

        <p><strong>Geraden letters:</strong> $veiligGeraden</p>

        <form method="post">
            <input type="text" name="letter" maxlength="1" required autofocus>
            <input type="submit" value="Raad">
        </form>

        </body>
        </html>
        HTML;
}

// ============================================================
// STAP 5: Toon het eindscherm (gewonnen of verloren)
// ============================================================

function toonEindscherm($bericht, $woord) {

    $veiligWoord = htmlspecialchars($woord, ENT_QUOTES, 'UTF-8');

    echo "<h1>$bericht</h1>";
    echo "<p>Het woord was: <strong>$veiligWoord</strong></p>";
    echo '<a href="">Opnieuw spelen</a>';
}

// ============================================================
// STAP 6: Verwerk een raadbeurt
// ============================================================

function verwerkRaadbeurt() {

    // $hang komt uit hangedman.php (de galgplaatjes)
    global $hang;

    // Haal de huidige spelstaat op uit het geheugen
    $woord          = $_SESSION['woord']   ?? '';
    $aantalFouten   = $_SESSION['fouten']  ?? 0;
    $geradenLetters = $_SESSION['geraden'] ?? '';

    // Lees de ingevoerde letter uit het formulier
    $invoer = $_POST['letter'] ?? '';

    // Controleer of het één letter is (geen cijfers, geen rare tekens)
    if (!preg_match('/^[a-zA-Z]$/', $invoer)) {
        die('Ongeldige invoer. Voer één letter in.');
    }

    // Zet de letter om naar hoofdletter zodat de vergelijking klopt
    $letter = strtoupper($invoer);

    // Alleen verwerken als de letter nog niet eerder geraden was
    $isNieuweGok = !str_contains($geradenLetters, $letter);

    if ($isNieuweGok) {

        // Voeg de letter toe aan de geraden letters
        $geradenLetters .= $letter;

        // Als de letter niet in het woord zit, is het een fout
        $isFout = !str_contains($woord, $letter);
        if ($isFout) {
            $aantalFouten++;
        }
    }

    // Sla de nieuwe spelstaat op in het geheugen
    $_SESSION['fouten']  = $aantalFouten;
    $_SESSION['geraden'] = $geradenLetters;

    // Bouw de weergave van het woord opnieuw op
    $woordWeergave = maakWoordWeergave($woord, $geradenLetters);

    // Gewonnen? Stop het spel en toon de winnaarspagina
    if (woordIsKlaar($woordWeergave)) {
        toonEindscherm('Je hebt gewonnen!', $woord);
        session_destroy();
        return;
    }

    // Verloren? (6 fouten = galg is compleet)
    if ($aantalFouten >= 6) {
        toonEindscherm('Je hebt verloren.', $woord);
        session_destroy();
        return;
    }

    // Spel gaat verder — toon de bijgewerkte pagina
    toonSpelPagina($hang[$aantalFouten], $woordWeergave, $geradenLetters);
}

// ============================================================
// STARTPUNT: Nieuw spel of raadbeurt?
// ============================================================

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // De speler heeft een letter ingevoerd — verwerk de gok
    verwerkRaadbeurt();

} else {

    // De pagina wordt voor het eerst geopend — start een nieuw spel

    // Kies een woord en sla alles op in het geheugen
    $_SESSION['woord']   = haalWillekeurigWoord($db);
    $_SESSION['fouten']  = 0;
    $_SESSION['geraden'] = '';

    // Maak een beginweergave met alleen underscores (bijv. "_ _ _ _ _ _")
    $woord         = $_SESSION['woord'];
    $beginWeergave = trim(str_repeat('_ ', strlen($woord)));

    // Toon de eerste pagina
    toonSpelPagina($hang[0], $beginWeergave, '');
}

// Sluit de databaseverbinding netjes af
$db->close();