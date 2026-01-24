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
$message_class = "";
$added_count = 0;
$message_suffix = "";
$textarea_value = "";
$version = "2026.01";
$codename = "Baumkuchen";

function get_success_messages() {
    return [
        "Nice drop!",
        "Arright!",
        "Smooth haul, captain.",
        "Yo-ho-ho, that’s a fine catch.",
        "Clean drop. Respect.",
        "Your list grows stronger.",
        "Aye, another gem aboard.",
        "That was slick.",
        "Nice pick. Solid taste.",
        "Treasure secured.",
        "Mission accomplished.",
        "Nice work.",
        "All set.",
        "Looking good.",
        "Well done.",
        "I'll try not to spoil the ending.",
        "Hey I know that one.",
        "Good progress.",
        "Fresh loot on deck.",
        "The chest gets heavier.",
        "Fair winds and fine finds.",
        "Straight to the stash.",
        "You’re on a roll.",
        "Collection leveled up.",
        "Oh, this is a banger.",
        "Keep 'em coming.",
        "That one's a keeper.",
        "Loot acquired.",
        "Solid find, matey.",
        "Certified good taste.",
        "Not bad, not bad.",
        "A fine addition, captain.",
        "That's a classic.",
        "No spoilers... maybe.",
        "Top-tier pick.",
        "Good catch, sailor.",
        "Fresh sparkle in the chest.",
        "Another relic recovered.",
        "May your drops be legendary.",
        "+1 Legendary added to your inventory.",
        "Arrr-quired.",
        "Sea-curred and stored.",
        "Well played, mate-y.",
        "That's a net gain.",
        "Knot bad at all.",
        "Sailed straight into the stash.",
        "A Legendary prize joins yer stash.",
        "Hey... I'm not judging.",
        "Bold choice, captain.",
        "We've all plundered worse.",
    ];
}

function random_success_message() {
    $messages = get_success_messages();
    return $messages[array_rand($messages)];
}

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
            $added_count = count($new_ids);
            $message_suffix = random_success_message();
            $message = "+" . $added_count . " added. " . $message_suffix;
            $message_class = "message-success";
        } else {
            $message = "No new IDs found";
            $message_class = "message-warn";
            $textarea_value = $raw;
        }
    } else {
        $message = "No valid IMDb IDs found";
        $message_class = "message-warn";
        $textarea_value = $raw;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ArrDrop</title>
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

.hidden-item {
    display: none;
}

.fade-70 { opacity: 0.7; }
.fade-50 { opacity: 0.5; }
.fade-25 { opacity: 0.25; }

.show-more {
    display: inline-block;
    margin-top: 0.75rem;
    color: #24B23B;
    text-decoration: none;
    font-weight: bold;
}

.show-more:hover {
    text-decoration: underline;
}

.total-count {
    margin-top: 0.5rem;
    color: #24B23B;
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

.message-warn {
    color: #F63049;
}

.progress-wrap {
    margin: 0.625rem 0 1rem;
}

.progress-track {
    position: relative;
    width: 100%;
    background: transparent;
    border-radius: 0;
    overflow: hidden;
    border: 0;
    height: 1.25rem;
}

.progress-fill {
    height: 100%;
    width: 0%;
    background: #24B23B;
    transition: width 2.0s cubic-bezier(0, 0, 0.2, 1);
    position: relative;
}

.progress-fill::after {
    content: "";
}

.progress-text {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: flex-start;
    font-weight: normal;
    color: #F9F8F6;
    text-shadow: none;
    padding: 0 0.75rem;
    text-align: center;
    font-size: 0.9rem;
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

@media (prefers-reduced-motion: reduce) {
    * {
        animation: none !important;
        transition: none !important;
        scroll-behavior: auto !important;
    }
}
</style>
</head>
<body>

<h1 title="<?php echo htmlspecialchars($version); ?>&#10;<?php echo htmlspecialchars($codename); ?>">ArrDrop</h1>

<?php if ($message): ?>
    <?php if ($message_class === "message-success"): ?>
        <div class="progress-wrap" data-added="<?php echo $added_count; ?>" data-suffix="<?php echo htmlspecialchars($message_suffix); ?>">
            <div class="progress-track">
                <div class="progress-fill"></div>
                <div class="progress-text"><?php echo htmlspecialchars("+0 added. " . $message_suffix); ?></div>
            </div>
        </div>
    <?php else: ?>
        <div class="message <?php echo htmlspecialchars($message_class); ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<form method="post">
    <textarea name="movies" placeholder="Drop all IMDb links or IDs (e.g. tt0133093) here and press Add."><?php echo htmlspecialchars($textarea_value); ?></textarea><br><br>
    <button class="add-btn" type="submit"><strong>+</strong> Add</button>
</form>

<hr>

<?php if ($movies): ?>
<h1>Queue</h1>

<form method="post" onsubmit="return confirm('Delete ALL movie IDs?');">
    <button class="delete-all" type="submit" name="delete_all">Delete all</button>
</form>

<?php
    $visible_limit = 9;
    $total_movies = count($movies);
    $visible_count = min($visible_limit, $total_movies);
?>
<ul>
<?php foreach ($movies as $i => $id): ?>
    <?php
        $classes = ["movie-item"];
        if ($i >= $visible_limit) {
            $classes[] = "hidden-item";
        }
        if ($visible_count === $visible_limit) {
            if ($i === $visible_count - 3) {
                $classes[] = "fade-70";
            } elseif ($i === $visible_count - 2) {
                $classes[] = "fade-50";
            } elseif ($i === $visible_count - 1) {
                $classes[] = "fade-25";
            }
        }
    ?>
    <li class="<?php echo implode(" ", $classes); ?>">
        <a class="delete" href="?delete=<?php echo urlencode($id); ?>" onclick="return confirm('Delete <?php echo $id; ?>?');">&times;</a>
        <a class="imdb" href="https://www.imdb.com/title/<?php echo rawurlencode($id); ?>/"
           target="_blank" rel="nofollow noopener noreferrer"><?php echo htmlspecialchars($id); ?></a>
    </li>
<?php endforeach; ?>
</ul>
<p class="total-count">Total IDs: <?php echo $total_movies; ?></p>
<?php if ($total_movies >= $visible_limit): ?>
    <a class="show-more" href="#" onclick="return showMoreMovies();">Show all</a>
<?php endif; ?>
<?php else: ?>
<p>No movies in the list.</p>
<?php endif; ?>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const wrap = document.querySelector(".progress-wrap");
    if (wrap) {
        const fill = wrap.querySelector(".progress-fill");
        const text = wrap.querySelector(".progress-text");
        const total = parseInt(wrap.dataset.added || "0", 10);
        const suffix = wrap.dataset.suffix || "";
        const duration = 2000;
        const start = performance.now();
        if (fill) {
            requestAnimationFrame(() => {
                fill.style.width = "100%";
            });
        }
        if (text && total > 0) {
            const tick = (now) => {
                const t = Math.min(1, (now - start) / duration);
                const eased = 1 - Math.pow(1 - t, 3);
                const delayed = Math.max(0, (eased - 0.33) / 0.67);
                const current = Math.round(total * delayed);
                text.textContent = `+${current} added. ${suffix}`;
                if (t < 1) requestAnimationFrame(tick);
            };
            requestAnimationFrame(tick);
        }
    }
});

function showMoreMovies() {
    const hidden = document.querySelectorAll(".hidden-item");
    hidden.forEach(el => { el.style.display = "list-item"; });
    document.querySelectorAll(".fade-70, .fade-50, .fade-25").forEach(el => {
        el.classList.remove("fade-70", "fade-50", "fade-25");
    });
    const trigger = document.querySelector(".show-more");
    if (trigger) trigger.style.display = "none";
    return false;
}
</script>

</body>
</html>
