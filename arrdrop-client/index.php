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
    $raw = $_POST['movies'];
    $matches = [];
    preg_match_all('/tt\d{7,8}(?!\d)/i', $raw, $matches);

    if (!empty($matches[0])) {
        foreach ($matches[0] as $id) {
            $id = strtolower($id);
            if (!in_array($id, $movies)) {
                $movies[] = $id;
            }
        }
        file_put_contents($filename, implode("\n", $movies));
        $message = "Saved";
    }
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
    transition: transform 0.15s ease;
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

button:hover {
    transform: scale(1.1);
}

ul {
    list-style: none;
    padding-left: 0;
}

li {
    margin: 6px 0;
}

a.delete {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    margin-right: 10px;
    border-radius: 50%;
    background: transparent;
    color: #F63049;
    font-weight: bold;
    font-size: 16px;
    font-family: Arial, sans-serif;
    text-decoration: none;
    line-height: 1;
}

a.delete:hover {
    background: #F63049;
    color: white;
}

a.imdb {
    color: #24B23B;
    text-decoration: none;
    display: inline-block;
    transition: transform 0.15s ease, background-color 0.15s ease, color 0.15s ease;
}

a.imdb:visited {
    color: #24B23B;
}

a.imdb:hover {
    text-decoration: underline;
}

a.delete:hover + a.imdb {
    background: #F63049;
    color: white;
    border-radius: 12px;
    padding: 2px 6px;
    text-decoration: none;
    transform: scale(1.1);
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
        <a class="delete" href="?delete=<?php echo urlencode($id); ?>" onclick="return confirm('Delete <?php echo $id; ?>?');">&times;</a>
        <a class="imdb" href="https://www.imdb.com/title/<?php echo rawurlencode($id); ?>/"
           target="_blank" rel="nofollow noopener noreferrer"><?php echo htmlspecialchars($id); ?></a>
    </li>
<?php endforeach; ?>
</ul>
<?php else: ?>
<p>No movies in the list.</p>
<?php endif; ?>

</body>
</html>
