<?php
// ArrDrop Client
// Version: 2026.01
// Codename: Baumkuchen
// Purpose: Manage IMDb IDs stored in a local text file.
// Requires: PHP and write access to this directory.
// Author: Zoran Karavla
// Tooling: Codex (GPT-5)
// Project: https://github.com/The-Error/ArrDrop

// MOVIES_FILE: Local text file with one IMDb ID per line.
$filename = "movies.txt";
// STATUS_MESSAGE: UI feedback after saving.
$message = "";

// Load current list
$movies = [];
if (file_exists($filename)) {
    $movies = array_filter(array_map('trim', file($filename)));
}

// Delete single ID
if (isset($_GET['delete'])) {
    $deleteId = trim($_GET['delete']);
    $movies = array_filter($movies, fn($id) => $id !== $deleteId);
    file_put_contents($filename, implode("\n", $movies));
    header("Location: index.php");
    exit();
}

// Delete all IDs
if (isset($_POST['delete_all'])) {
    file_put_contents($filename, "");
    header("Location: index.php");
    exit();
}

// Add new IDs
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['movies'])) {
    $input = explode("\n", $_POST['movies']);
    foreach ($input as $line) {
        $line = trim($line);
        if (preg_match('/^tt\d{7,8}$/', $line) && !in_array($line, $movies)) {
            $movies[] = $line;
        }
    }
    file_put_contents($filename, implode("\n", $movies));
    $message = "Saved";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Movie</title>
<style>
body {
    font-family: sans-serif;
    max-width: 700px;
    margin: 50px auto;
    background: #F9F8F6;
}

h1 {
    color: #3B3030;
}

p {
    color: #3B3030;
}

textarea {
    width: 100%;
    height: 120px;
    font-family: monospace;
    border: none;
    background: white;
    padding: 10px;
    box-sizing: border-box;
}

button {
    border: none;
    padding: 10px 16px;
    font-size: 16px;
    cursor: pointer;
    border-radius: 20px;
}

.add-btn {
    background: #24B23B;
    color: white;
}

.delete-all {
    background: #F63049;
    color: white;
    margin-bottom: 10px;
}

ul {
    list-style: none;
    padding-left: 0;
}

li {
    margin: 6px 0;
}

a.delete {
    color: #F63049;
    text-decoration: none;
    margin-left: 10px;
}

a.delete:hover {
    text-decoration: underline;
}

.message {
    margin: 10px 0;
    color: #3B3030;
}

hr {
    border: none;
    height: 1px;
    background: #D9CFC7;
    margin: 40px 0;
}
</style>
</head>
<body>

<h1>Add Movie</h1>

<?php if ($message): ?>
<div class="message"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<form method="post">
    <textarea name="movies" placeholder="Enter one IMDB ID per line (e.g. tt0133093)"></textarea><br><br>
    <button class="add-btn" type="submit"><strong>+</strong> Add</button>
</form>

<hr>

<h1>Current IMDB IDs</h1>

<form method="post" onsubmit="return confirm('Delete ALL movie IDs?');">
    <button class="delete-all" type="submit" name="delete_all">Delete all IDs</button>
</form>

<?php if ($movies): ?>
<ul>
<?php foreach ($movies as $id): ?>
    <li>
        <?php echo htmlspecialchars($id); ?>
        <a class="delete" href="?delete=<?php echo urlencode($id); ?>" onclick="return confirm('Delete <?php echo $id; ?>?');">Delete</a>
    </li>
<?php endforeach; ?>
</ul>
<?php else: ?>
<p>No movies in the list.</p>
<?php endif; ?>

</body>
</html>
