<?php

session_start();
require_once 'hangedman.php';

$conn = new mysqli("localhost", "root", "", "galgje");

if ($conn->connect_error) {
    die("Database connection failed");
}

function getRandomWord($conn) {

    $sql = "SELECT woord FROM woorden ORDER BY RAND() LIMIT 1";

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {

        $row = $result->fetch_assoc();

        return strtoupper($row['woord']);
    }

    return "ERROR";
}

function buildTemplate($word, $guessed) {

    $output = '';

    for ($i = 0; $i < strlen($word); $i++) {

        $char = $word[$i];

        if (strpos($guessed, $char) !== false) {
            $output .= $char . ' ';
        } else {
            $output .= '_ ';
        }
    }

    return trim($output);
}

function printPage($image, $template, $guessed) {

    $safeTemplate = htmlspecialchars($template, ENT_QUOTES, 'UTF-8');
    $safeGuessed = htmlspecialchars($guessed, ENT_QUOTES, 'UTF-8');

    echo <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>Hangman</title>
</head>
<body>

<div class="container">

<h1>Hangman Game</h1>

<pre>$image</pre>

<p><strong>Word:</strong> $safeTemplate</p>

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

function startGame($conn) {

    global $hang;

    $_SESSION['word'] = getRandomWord($conn);
    $_SESSION['wrong'] = 0;
    $_SESSION['guessed'] = '';

    $word = $_SESSION['word'];

    $template = str_repeat('_ ', strlen($word));

    printPage($hang[0], trim($template), '');
}

function handleGuess() {

    global $hang;

    $word = $_SESSION['word'] ?? '';
    $wrong = $_SESSION['wrong'] ?? 0;
    $guessed = $_SESSION['guessed'] ?? '';

    $guess = $_POST['letter'] ?? '';

    if (!preg_match('/^[a-zA-Z]$/', $guess)) {
        die('Invalid input');
    }

    $letter = strtoupper($guess);

    if (strpos($guessed, $letter) === false) {

        $guessed .= $letter;

        if (strpos($word, $letter) === false) {
            $wrong++;
        }
    }

    $_SESSION['wrong'] = $wrong;
    $_SESSION['guessed'] = $guessed;

    $template = buildTemplate($word, $guessed);

    if (strpos($template, '_') === false) {

        $safeWord = htmlspecialchars($word, ENT_QUOTES, 'UTF-8');

        echo "<h1>You Win!</h1>";
        echo "<p>The word was: <strong>$safeWord</strong></p>";
        echo '<a href="">Play again</a>';

        session_destroy();
        return;
    }

    if ($wrong >= 6) {

        $safeWord = htmlspecialchars($word, ENT_QUOTES, 'UTF-8');

        echo "<h1>You Lost!</h1>";
        echo "<p>The word was: <strong>$safeWord</strong></p>";
        echo '<a href="">Play again</a>';

        session_destroy();
        return;
    }

    printPage($hang[$wrong], $template, $guessed);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    handleGuess();
} else {
    startGame($conn);
}

$conn->close();

?>
