<?php

session_start();
require_once 'hangedman.php';

// ─── Database ────────────────────────────────────────────────────────────────

$conn = new mysqli("localhost", "root", "", "galgje");

if ($conn->connect_error) {
    die("Database connection failed");
}

// ─── Spellogica ──────────────────────────────────────────────────────────────

/**
 * Haalt een willekeurig woord op uit de database (altijd hoofdletters).
 */
function getRandomWord(mysqli $conn): string
{
    $result = $conn->query("SELECT woord FROM woorden ORDER BY RAND() LIMIT 1");

    if ($result && $result->num_rows > 0) {
        return strtoupper($result->fetch_assoc()['woord']);
    }

    return "ERROR";
}

/**
 * Bouwt het weergavepatroon van het woord.
 * Geraadde letters worden getoond, de rest als underscore.
 * Voorbeeld: "_ A _ G _ E"
 */
function buildWordDisplay(string $word, string $guessedLetters): string
{
    $display = '';

    for ($i = 0; $i < strlen($word); $i++) {
        $letter = $word[$i];
        $display .= str_contains($guessedLetters, $letter) ? "$letter " : '_ ';
    }

    return trim($display);
}

/**
 * Controleert of het woord volledig geraden is.
 */
function wordIsComplete(string $display): bool
{
    return !str_contains($display, '_');
}

// ─── Sessiebeheer ────────────────────────────────────────────────────────────

function startNewGame(mysqli $conn): void
{
    $_SESSION['word']    = getRandomWord($conn);
    $_SESSION['wrong']   = 0;
    $_SESSION['guessed'] = '';
}

function getSessionWord(): string    { return $_SESSION['word']    ?? ''; }
function getWrongCount(): int        { return $_SESSION['wrong']   ?? 0;  }
function getGuessedLetters(): string { return $_SESSION['guessed'] ?? ''; }

function saveSessionState(int $wrongCount, string $guessedLetters): void
{
    $_SESSION['wrong']   = $wrongCount;
    $_SESSION['guessed'] = $guessedLetters;
}

// ─── HTML ────────────────────────────────────────────────────────────

/**
 * Toont de hoofdpagina van het spel (galg, woord, geraden letters, formulier).
 */
function printGamePage(string $gallowsImage, string $wordDisplay, string $guessedLetters): void
{
    $safeDisplay  = htmlspecialchars($wordDisplay,     ENT_QUOTES, 'UTF-8');
    $safeGuessed  = htmlspecialchars($guessedLetters,  ENT_QUOTES, 'UTF-8');

    echo <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <title>Hangman</title>
        </head>
        <body>

        <div class="container">

            <h1>Hangman Game</h1>

            <pre>$gallowsImage</pre>

            <p><strong>Word:</strong> $safeDisplay</p>

            <p><strong>Guessed letters:</strong> $safeGuessed</p>

            <form method="post">
                <input type="text" name="letter" maxlength="1" required autofocus>
                <input type="submit" value="Guess">
            </form>

        </div>

        </body>
        </html>
        HTML;
}

/**
 * Toont het eindscherm (gewonnen of verloren) met een herstart-link.
 */
function printEndScreen(string $outcome, string $word): void
{
    $safeWord = htmlspecialchars($word, ENT_QUOTES, 'UTF-8');

    echo "<h1>$outcome</h1>";
    echo "<p>The word was: <strong>$safeWord</strong></p>";
    echo '<a href="">Play again</a>';
}

// ─── Spelverloop ─────────────────────────────────────────────────────────────

/**
 * Verwerkt een ingevoerde letter en werkt de spelstaat bij.
 */
function handleGuess(): void
{
    global $hang;

    $word           = getSessionWord();
    $wrongCount     = getWrongCount();
    $guessedLetters = getGuessedLetters();

    // Valideer invoer
    $rawInput = $_POST['letter'] ?? '';
    if (!preg_match('/^[a-zA-Z]$/', $rawInput)) {
        die('Invalid input');
    }

    $letter = strtoupper($rawInput);

    // Voeg letter toe als die nog niet geraden was
    $isNewGuess = !str_contains($guessedLetters, $letter);
    if ($isNewGuess) {
        $guessedLetters .= $letter;

        $isWrongGuess = !str_contains($word, $letter);
        if ($isWrongGuess) {
            $wrongCount++;
        }
    }

    saveSessionState($wrongCount, $guessedLetters);

    $wordDisplay = buildWordDisplay($word, $guessedLetters);

    // Gewonnen
    if (wordIsComplete($wordDisplay)) {
        printEndScreen('You Win!', $word);
        session_destroy();
        return;
    }

    // Verloren
    if ($wrongCount >= 6) {
        printEndScreen('You Lost!', $word);
        session_destroy();
        return;
    }

    // Spel gaat verder
    printGamePage($hang[$wrongCount], $wordDisplay, $guessedLetters);
}

// ─── Startpunt ───────────────────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    handleGuess();
} else {
    startNewGame($conn);

    $word        = getSessionWord();
    $emptyDisplay = trim(str_repeat('_ ', strlen($word)));
    printGamePage($hang[0], $emptyDisplay, '');
}

$conn->close();