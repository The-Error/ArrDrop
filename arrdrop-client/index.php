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
$night_mode = false;
$theme_param = $_GET["theme"] ?? "";
if ($theme_param === "night") {
    $night_mode = true;
} elseif ($theme_param === "day") {
    $night_mode = false;
}

$theme_day = [
    "bg" => "#F9F8F6",
    "text" => "#3B3030",
    "muted" => "#3B3030",
    "border" => "#D9CFC7",
    "accent" => "#24B23B",
    "danger" => "#F63049",
    "surface" => "#FFFFFF",
    "tooltip_bg" => "#3B3030",
    "tooltip_text" => "#F9F8F6",
    "sheen_clear" => "rgba(255,255,255,0)",
    "sheen_mid" => "rgba(255,255,255,0.35)",
];

$theme_night = [
    "bg" => "#3B3030",
    "text" => "#F9F8F6",
    "muted" => "#EFE9E3",
    "border" => "#D9CFC7",
    "accent" => "#D9CFC7",
    "danger" => "#C9B59C",
    "surface" => "#EFE9E3",
    "tooltip_bg" => "#F9F8F6",
    "tooltip_text" => "#594545",
    "sheen_clear" => "rgba(249,248,246,0)",
    "sheen_mid" => "rgba(249,248,246,0.35)",
];

$theme = $night_mode ? $theme_night : $theme_day;

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
:root {
    --bg: <?php echo $theme["bg"]; ?>;
    --text: <?php echo $theme["text"]; ?>;
    --muted: <?php echo $theme["muted"]; ?>;
    --border: <?php echo $theme["border"]; ?>;
    --accent: <?php echo $theme["accent"]; ?>;
    --danger: <?php echo $theme["danger"]; ?>;
    --surface: <?php echo $theme["surface"]; ?>;
    --tooltip-bg: <?php echo $theme["tooltip_bg"]; ?>;
    --tooltip-text: <?php echo $theme["tooltip_text"]; ?>;
    --sheen-clear: <?php echo $theme["sheen_clear"]; ?>;
    --sheen-mid: <?php echo $theme["sheen_mid"]; ?>;
}

body {
    font-family: sans-serif;
    max-width: 44rem;
    margin: 3.125rem auto;
    background: var(--bg);
    padding: 0 1rem;
    color: var(--text);
}

h1 {
    color: var(--text);
}

p {
    color: var(--text);
}

textarea {
    width: 100%;
    min-height: 7.5rem;
    font-family: monospace;
    border: 0.0625rem solid var(--accent);
    background: var(--surface);
    padding: 0.625rem;
    box-sizing: border-box;
    color: var(--text);
}

button {
    border: none;
    padding: 0.625rem 1rem;
    font-size: 1rem;
    cursor: pointer;
    border-radius: 1.25rem;
    transition: transform 0.15s ease;
    position: relative;
    overflow: hidden;
}

.add-btn {
    background: var(--accent);
    color: var(--bg);
}

.delete-all {
    background: var(--danger);
    color: var(--bg);
    margin-bottom: 0.625rem;
}

button:hover {
    transform: scale(1.1);
}

button::after {
    content: "";
    position: absolute;
    top: 0;
    left: -30%;
    width: 30%;
    height: 100%;
    background: linear-gradient(120deg, var(--sheen-clear) 0%, var(--sheen-mid) 50%, var(--sheen-clear) 100%);
    transform: skewX(-20deg);
    opacity: 0;
    transition: opacity 0.15s ease;
}

button:hover::after {
    opacity: 1;
    animation: sheen 0.6s ease;
}

@keyframes sheen {
    from { left: -30%; }
    to { left: 110%; }
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
    color: var(--accent);
    text-decoration: none;
    font-weight: bold;
}

.show-more:hover {
    text-decoration: underline;
}

.total-inline {
    font-size: 0.9rem;
    color: var(--accent);
    font-weight: normal;
    display: inline-block;
}

.tip {
    position: relative;
}

.tip::after {
    content: attr(data-tip);
    position: absolute;
    bottom: 135%;
    left: 50%;
    transform: translateX(-50%);
    background: var(--tooltip-bg);
    color: var(--tooltip-text);
    font-size: 0.85rem;
    line-height: 1.1;
    font-weight: normal;
    padding: 0.3rem 0.5rem;
    border-radius: 0.4rem;
    white-space: nowrap;
    opacity: 0;
    pointer-events: none;
}

.tip::before {
    content: "";
    position: absolute;
    bottom: 115%;
    left: 50%;
    transform: translateX(-50%);
    border-width: 0.35rem 0.35rem 0;
    border-style: solid;
    border-color: var(--tooltip-bg) transparent transparent;
    opacity: 0;
    pointer-events: none;
}

.tip:hover::after,
.tip:hover::before {
    opacity: 1;
}

.bounce {
    animation: bounce 0.6s ease;
}

@keyframes bounce {
    0% { transform: translateY(0); }
    30% { transform: translateY(-0.25rem); }
    60% { transform: translateY(0.1rem); }
    100% { transform: translateY(0); }
}

a.delete {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.25rem;
    height: 1.25rem;
    margin-right: 0.0625rem;
    border-radius: 50%;
    background: transparent;
    color: var(--danger);
    font-weight: bold;
    font-size: 1rem;
    font-family: Arial, sans-serif;
    text-decoration: none;
    line-height: 1;
}

a.delete:hover {
    background: var(--danger);
    color: var(--bg);
}

a.imdb {
    color: var(--accent);
    text-decoration: none;
    display: inline-block;
    padding: 0.125rem 0.375rem;
    border-radius: 0.75rem;
    transition: transform 0.15s ease, background-color 0.15s ease, color 0.15s ease;
}

a.imdb:visited {
    color: var(--accent);
}

a.imdb:hover {
    text-decoration: underline;
}

a.imdb:focus-visible,
a.imdb:active {
    background: var(--danger);
    color: var(--bg);
    border-radius: 0.75rem;
    padding: 0.125rem 0.375rem;
    text-decoration: none;
    transform: scale(1.05);
}

a.delete:hover + a.imdb {
    background: var(--danger);
    color: var(--bg);
    text-decoration: none;
    transform: none;
}

.message {
    margin: 0.625rem 0;
    color: var(--text);
}

.message-warn {
    color: var(--danger);
}

.progress-wrap {
    margin: 0.625rem 0 1rem;
}

.progress-track {
    position: relative;
    width: 100%;
    background: transparent;
    border-radius: 0.5rem;
    overflow: hidden;
    border: 0;
    height: 1.25rem;
}

.progress-fill {
    height: 100%;
    width: 0%;
    background: var(--accent);
    border-radius: 0.5rem;
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
    color: var(--bg);
    text-shadow: none;
    padding: 0 0.25rem 0 0.4rem;
    text-align: center;
    font-size: 0.9rem;
}

hr {
    border: 0;
    border-top: 0.0625rem solid var(--border);
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

<h1>ArrDrop</h1>

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
<?php
    $visible_limit = 9;
    $total_movies = count($movies);
    $visible_count = min($visible_limit, $total_movies);
    $display_total = $total_movies;
    if ($message_class === "message-success" && $added_count > 0) {
        $display_total = max(0, $total_movies - $added_count);
    }
?>
<h1>Queue <span class="total-inline tip" data-total="<?php echo $total_movies; ?>" data-tip="Total number of IMDb IDs on your list.">(<?php echo $display_total; ?>)</span></h1>

<form method="post" onsubmit="return confirm('Delete ALL movie IDs?');">
    <button class="delete-all" type="submit" name="delete_all">Delete all</button>
</form>

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

        if (total > 0 && fill) {
            const totalInline = document.querySelector(".total-inline");
            if (totalInline) {
                const baseTotal = parseInt(totalInline.dataset.total || "0", 10);
                const styles = getComputedStyle(fill);
                const durations = styles.transitionDuration.split(",").map(s => s.trim());
                const delays = styles.transitionDelay.split(",").map(s => s.trim());
                const toMs = (v) => v.endsWith("ms") ? parseFloat(v) : parseFloat(v) * 1000;
                const maxDuration = Math.max(...durations.map(toMs));
                const maxDelay = Math.max(...delays.map(toMs));
                const wait = Math.max(0, maxDuration + maxDelay);

                setTimeout(() => {
                    totalInline.textContent = `(${baseTotal})`;
                    totalInline.classList.remove("bounce");
                    void totalInline.offsetWidth;
                    totalInline.classList.add("bounce");
                }, wait);
            }
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
