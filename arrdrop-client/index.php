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
$textarea_value = "";

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
        $new_ids = [];
        foreach ($matches[0] as $id) {
            $id = strtolower($id);
            if (!in_array($id, $movies) && !in_array($id, $new_ids)) {
                $new_ids[] = $id;
            }
        }
        if ($new_ids) {
            $movies = array_merge($new_ids, $movies);
            file_put_contents($filename, implode("\n", $movies));
            $message = "Added " . count($new_ids) . " new ID(s)";
        } else {
            $message = "No new IDs found";
            $textarea_value = $raw;
        }
    } else {
        $message = "No valid IMDb IDs found";
        $textarea_value = $raw;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Add Movie</title>
<style>
body {
    font-family: sans-serif;
    max-width: 44rem;
    margin: 3.125rem auto;
    background: #F9F8F6;
    padding: 0 1rem;
}

h1 {
    color: #3B3030;
}

p {
    color: #3B3030;
}

textarea {
    width: 100%;
    min-height: 7.5rem;
    font-family: monospace;
    border: 0.0625rem solid #24B23B;
    background: white;
    padding: 0.625rem;
    box-sizing: border-box;
}

button {
    border: none;
    padding: 0.625rem 1rem;
    font-size: 1rem;
    cursor: pointer;
    border-radius: 1.25rem;
    transition: transform 0.15s ease;
}

.add-btn {
    background: #24B23B;
    color: white;
}

.delete-all {
    background: #F63049;
    color: white;
    margin-bottom: 0.625rem;
}

button:hover {
    transform: scale(1.1);
}

ul {
    list-style: none;
    padding-left: 0;
}

li {
    margin: 0.375rem 0;
}

a.delete {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.25rem;
    height: 1.25rem;
    margin-right: 0.625rem;
    border-radius: 50%;
    background: transparent;
    color: #F63049;
    font-weight: bold;
    font-size: 1rem;
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

a.imdb:focus-visible,
a.imdb:active {
    background: #F63049;
    color: white;
    border-radius: 0.75rem;
    padding: 0.125rem 0.375rem;
    text-decoration: none;
    transform: scale(1.05);
}

a.delete:hover + a.imdb {
    background: #F63049;
    color: white;
    border-radius: 0.75rem;
    padding: 0.125rem 0.375rem;
    text-decoration: none;
    transform: scale(1.05);
}

.message {
    margin: 0.625rem 0;
    color: #3B3030;
}

hr {
    border: 0;
    border-top: 0.0625rem solid #D9CFC7;
    height: 0;
    margin: 2.5rem 0;
}

@media (max-width: 40rem) {
    body {
        margin: 1.5rem auto;
    }

    h1 {
        font-size: 1.375rem;
    }

    button {
        width: 100%;
    }

    textarea {
        min-height: 10rem;
    }

    li {
        display: flex;
        align-items: center;
        gap: 0.375rem;
        flex-wrap: wrap;
        word-break: break-word;
    }
}
</style>
</head>
<body>

<h1>Add Movie</h1>

<?php if ($message): ?>
<div class="message"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<form method="post">
    <textarea name="movies" placeholder="Drop all links or IMDB IDs (e.g. tt0133093) here and press Add."><?php echo htmlspecialchars($textarea_value); ?></textarea><br><br>
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
