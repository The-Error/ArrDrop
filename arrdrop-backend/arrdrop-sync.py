#!/usr/bin/env python3
import argparse
import configparser
import sys
from datetime import datetime
from pathlib import Path
import json
from urllib.parse import urlencode
from urllib.parse import urlparse, parse_qs
from urllib.request import Request, urlopen

# ArrDrop Sync Backend
# License: MIT
# Version: 2026.01.29
# Codename: Baumkuchen
# Purpose: Pull IMDb IDs from a public list and add them to Radarr.
# Requires: Radarr v3 API access and a reachable movies list URL.
# Author: Zoran Karavla
# Tooling: Codex (GPT-5)
# Project: https://github.com/The-Error/ArrDrop

# ================= CONFIG =================
# Config is loaded from arrdrop.conf (created by --setup)
CONFIG_FILE = Path("arrdrop.conf")

# STATE_FILE: Local file that tracks which IMDb IDs were already processed.
STATE_FILE = Path("processed.txt")
# LOG_FILE: Local log file written by this script.
LOG_FILE = Path("arrdrop.log")
# =========================================

VERSION = "2026.01.29"

MOVIES_URL = ""
RADARR_URL = ""
RADARR_API_KEY = ""
QUALITY_PROFILE_ID = 0
ROOT_FOLDER = ""


def log(message):
    timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    line = f"[{timestamp}] {message}"
    print(line)
    with LOG_FILE.open("a") as f:
        f.write(line + "\n")

def color(text, tone):
    colors = {
        "green": "\033[92m",
        "cyan": "\033[96m",
        "yellow": "\033[93m",
        "reset": "\033[0m",
    }
    if tone not in colors:
        return text
    return f"{colors[tone]}{text}{colors['reset']}"

def http_get_text(url, headers=None, timeout=10):
    req = Request(url, headers=headers or {})
    with urlopen(req, timeout=timeout) as resp:
        return resp.read().decode("utf-8", errors="ignore")

def http_get_json(url, headers=None, params=None, timeout=10):
    if params:
        url = url + ("&" if "?" in url else "?") + urlencode(params)
    text = http_get_text(url, headers=headers, timeout=timeout)
    return json.loads(text)

def http_post_json(url, payload, headers=None, timeout=10):
    data = json.dumps(payload).encode("utf-8")
    hdrs = {"Content-Type": "application/json"}
    if headers:
        hdrs.update(headers)
    req = Request(url, data=data, headers=hdrs, method="POST")
    with urlopen(req, timeout=timeout) as resp:
        return resp.getcode(), resp.read().decode("utf-8", errors="ignore")

def load_config():
    if not CONFIG_FILE.exists():
        return None
    cfg = configparser.ConfigParser()
    cfg.read(CONFIG_FILE)
    if "arrdrop" not in cfg or "radarr" not in cfg:
        return None
    return cfg

def apply_config(cfg):
    global MOVIES_URL, RADARR_URL, RADARR_API_KEY, QUALITY_PROFILE_ID, ROOT_FOLDER
    MOVIES_URL = cfg["arrdrop"].get("movies_url", "").strip()
    MOVIES_URL = normalize_movies_url(MOVIES_URL)
    RADARR_URL = cfg["radarr"].get("url", "").strip().rstrip("/")
    RADARR_API_KEY = cfg["radarr"].get("api_key", "").strip()
    ROOT_FOLDER = cfg["radarr"].get("root_folder", "").strip()
    QUALITY_PROFILE_ID = cfg["radarr"].getint("quality_profile_id", fallback=0)

def config_ready():
    return all([
        MOVIES_URL,
        RADARR_URL,
        RADARR_API_KEY,
        ROOT_FOLDER,
        QUALITY_PROFILE_ID > 0,
    ])

def print_not_configured():
    print("ArrDrop Sync isn’t configured yet.")
    print("Run: arrdrop-sync --setup")

def is_valid_key(token):
    return bool(token) and len(token) == 32 and all(c.isalnum() or c in "-_" for c in token)

def normalize_movies_url(url):
    try:
        parsed = urlparse(url)
    except Exception:
        return url

    # If URL already points to /key/<a>/<b>/<token>, leave it as-is
    if "/key/" in parsed.path:
        return url

    qs = parse_qs(parsed.query)
    key = qs.get("key", [""])[0]
    if not is_valid_key(key):
        return url

    base_path = parsed.path
    if base_path.endswith("index.php"):
        base_path = base_path.rsplit("/", 1)[0] + "/"
    elif not base_path.endswith("/"):
        base_path = base_path.rsplit("/", 1)[0] + "/"

    shard = f"key/{key[0]}/{key[1]}/{key}"
    rebuilt = f"{parsed.scheme}://{parsed.netloc}{base_path}{shard}"
    return rebuilt

def normalize_http_url(url):
    url = url.strip()
    if not url:
        return url
    if not url.startswith("http://") and not url.startswith("https://"):
        return "http://" + url
    return url

def fetch_root_folders(radarr_url, api_key):
    return http_get_json(
        f"{radarr_url}/api/v3/rootfolder",
        headers={"X-Api-Key": api_key},
        timeout=10
    )

def fetch_quality_profiles(radarr_url, api_key):
    return http_get_json(
        f"{radarr_url}/api/v3/qualityprofile",
        headers={"X-Api-Key": api_key},
        timeout=10
    )

def validate_movies_url(url):
    text = http_get_text(url, timeout=10)
    ids = {line.strip() for line in text.splitlines() if line.strip()}
    imdb_ids = {x for x in ids if x.startswith("tt") and x[2:].isdigit()}
    return imdb_ids

def run_dry_run_summary():
    try:
        remote_movies = fetch_movie_list()
        processed = load_processed()
    except Exception as e:
        print(f"Could not run dry-run: {e}")
        return

    new_movies = sorted(remote_movies - processed)
    print("")
    print(color(f"+++ {len(remote_movies)} movies found! +++", "green"))
    print(color("Everything looks good.", "cyan"))
    print("")
    print("Your ship is ready, Captain.")
    print("")
    print("Sync your list now by running:")
    print(f"  {color('arrdrop-sync', 'cyan')}")
    print("")
    print("Tip: You can also make a CRON job to sync daily or on boot.")
    print("Check out the official FAQ for help.")
    print("")
    print(f"You may run {color('arrdrop-sync --setup', 'cyan')} at any time if you need to reconfigure.")


def setup():
    print(color("ArrDrop Sync", "green"))
    print(color(f"Version {VERSION}", "cyan"))
    print("")
    print("Welcome aboard!")
    print("This is Captain’s Quickstart. We’ll set up ArrDrop Sync in just a moment.")
    print("")
    print(f"Your configuration will be saved here:")
    print(f"{CONFIG_FILE.resolve()}")
    print("")
    print("Let’s get started.")
    print("")

    # Screen 2 — ArrDrop IMDb List
    while True:
        movies_url = normalize_http_url(input("Enter your ArrDrop IMDb list URL: ").strip())
        if not movies_url:
            print(color("Please paste a valid URL.", "yellow"))
            continue
        try:
            movies_url = normalize_movies_url(movies_url)
            imdb_ids = validate_movies_url(movies_url)
            if not imdb_ids:
                print(color("I couldn’t find any IMDb IDs at that URL. Add at least one ID and try again.", "yellow"))
                continue
            break
        except Exception:
            print(color("I couldn’t reach that URL. Check it and try again.", "yellow"))

    # Screen 3 — Radarr Connection
    while True:
        radarr_url = normalize_http_url(input("Radarr URL (you can paste LAN IP too): ").strip()).rstrip("/")
        api_key = input(f"Radarr API key ({color('Settings → General → API Key', 'cyan')}): ").strip()
        if not radarr_url or not api_key:
            print(color("Please provide both Radarr URL and API key.", "yellow"))
            continue
        try:
            _ = fetch_root_folders(radarr_url, api_key)
            _ = fetch_quality_profiles(radarr_url, api_key)
            break
        except Exception:
            print(color("I couldn’t reach Radarr or the API key didn’t work. Please try again.", "yellow"))

    # Screen 4 — Root Folder
    roots = fetch_root_folders(radarr_url, api_key)
    if len(roots) == 1:
        root_folder = roots[0]["path"]
    else:
        print("")
        print("Choose a root folder:")
        for i, r in enumerate(roots, start=1):
            print(f"  {i}) {color(r['path'], 'cyan')}")
        while True:
            choice = input("Pick a number: ").strip()
            if choice.isdigit() and 1 <= int(choice) <= len(roots):
                root_folder = roots[int(choice) - 1]["path"]
                break
            print(color("Please pick a valid number.", "yellow"))

    # Screen 5 — Quality Profile
    profiles = fetch_quality_profiles(radarr_url, api_key)
    if len(profiles) == 1:
        quality_profile_id = profiles[0]["id"]
        quality_profile_name = profiles[0]["name"]
    else:
        print("")
        print("Choose a quality profile:")
        for i, p in enumerate(profiles, start=1):
            print(f"  {i}) {color(p['name'], 'cyan')}")
        while True:
            choice = input("Pick a number: ").strip()
            if choice.isdigit() and 1 <= int(choice) <= len(profiles):
                picked = profiles[int(choice) - 1]
                quality_profile_id = picked["id"]
                quality_profile_name = picked["name"]
                break
            print(color("Please pick a valid number.", "yellow"))

    # Write config
    cfg = configparser.ConfigParser()
    cfg["arrdrop"] = {"movies_url": movies_url}
    cfg["radarr"] = {
        "url": radarr_url,
        "api_key": api_key,
        "root_folder": root_folder,
        "quality_profile_id": str(quality_profile_id),
    }

    tmp = CONFIG_FILE.with_suffix(".tmp")
    with tmp.open("w") as f:
        cfg.write(f)
    tmp.replace(CONFIG_FILE)

    # Final screen
    print("")
    print("Summary:")
    print(f"  ArrDrop list URL: {color(movies_url, 'cyan')}")
    print(f"  Radarr: {color(radarr_url, 'cyan')}")
    print(f"  Root folder: {color(root_folder, 'cyan')}")
    if len(profiles) == 1:
        print(f"  Quality profile: {color(quality_profile_name, 'cyan')}")
    else:
        print(f"  Quality profile: {color(quality_profile_name, 'cyan')}")

    apply_config(cfg)
    if config_ready():
        run_dry_run_summary()
    else:
        print("")
        print("Setup completed, but the configuration is incomplete.")
        print("Run: arrdrop-sync --setup to try again.")
def show_last_added_from_log():
    if not LOG_FILE.exists():
        print("No log file found.")
        return

    lines = LOG_FILE.read_text().splitlines()
    start_marker = "===== Radarr sync started ====="
    start_index = None

    for i in range(len(lines) - 1, -1, -1):
        if start_marker in lines[i]:
            start_index = i
            break

    if start_index is None:
        print("No sync run found in log.")
        return

    added = []
    for line in lines[start_index:]:
        if "Added movie:" in line:
            added.append(line)

    if not added:
        print("No movies were added in the last sync.")
        return

    for line in added:
        print(line)


def fetch_movie_list():
    text = http_get_text(MOVIES_URL, timeout=10)
    return {line.strip() for line in text.splitlines() if line.strip()}


def load_processed():
    if not STATE_FILE.exists():
        return set()
    return {line.strip() for line in STATE_FILE.read_text().splitlines() if line.strip()}


def mark_processed(imdb_id):
    STATE_FILE.open("a").write(imdb_id + "\n")


def lookup_movie(imdb_id):
    data = http_get_json(
        f"{RADARR_URL}/api/v3/movie/lookup/imdb",
        headers={"X-Api-Key": RADARR_API_KEY},
        params={"imdbId": imdb_id},
        timeout=10
    )

    # Radarr may return either a dict (single match) or a list (multiple matches)
    if isinstance(data, dict):
        return data
    if isinstance(data, list) and data:
        return data[0]
    return None

def add_movie(movie, dry_run):
    payload = dict(movie)  # start from Radarr's lookup object

    payload["qualityProfileId"] = QUALITY_PROFILE_ID
    payload["rootFolderPath"] = ROOT_FOLDER
    payload["monitored"] = True
    payload["minimumAvailability"] = "released"
    payload["addOptions"] = {"searchForMovie": True}

    # Optional but often helpful: explicitly set a path
    payload["path"] = f"{ROOT_FOLDER}/{movie['title']} ({movie['year']})"

    title = f"{movie['title']} ({movie['year']})"

    if dry_run:
        log(f"[DRY-RUN] Would add: {title}")
        return True

    status_code, body = http_post_json(
        f"{RADARR_URL}/api/v3/movie",
        payload,
        headers={"X-Api-Key": RADARR_API_KEY},
        timeout=10
    )

    if status_code == 201:
        log(f"Added movie: {title}")
        return True

    if status_code == 400 and "exists" in body.lower():
        log(f"Already exists in Radarr: {title}")
        return True

    log(f"ERROR adding {title}: {body}")
    return False


def main():
    parser = argparse.ArgumentParser(description="Sync movies.txt with Radarr")
    parser.add_argument("--dry-run", action="store_true", help="Do not add movies, only log actions")
    parser.add_argument("--log", action="store_true", help="Show movies added in the last sync and exit")
    parser.add_argument("--setup", action="store_true", help="Run Captain’s Quickstart setup")
    args = parser.parse_args()

    if args.setup:
        setup()
        return

    cfg = load_config()
    if not cfg:
        print_not_configured()
        return
    apply_config(cfg)
    if not config_ready():
        print_not_configured()
        return

    if args.log:
        show_last_added_from_log()
        return

    log("===== Radarr sync started =====")
    if args.dry_run:
        log("Running in DRY-RUN mode")

    try:
        remote_movies = fetch_movie_list()
        processed = load_processed()
    except Exception as e:
        log(f"Fatal error fetching data: {e}")
        return

    new_movies = sorted(remote_movies - processed)

    if not new_movies:
        log("No new movies to process")
        return

    log(f"Found {len(new_movies)} new movie(s)")

    for imdb_id in new_movies:
        log(f"Processing {imdb_id}")

        try:
            movie = lookup_movie(imdb_id)
            if not movie:
                log(f"Movie not found via Radarr lookup: {imdb_id}")
                continue

            if add_movie(movie, args.dry_run):
                if not args.dry_run:
                    mark_processed(imdb_id)

        except Exception as e:
            log(f"ERROR processing {imdb_id}: {e}")

    log("===== Radarr sync finished =====")


if __name__ == "__main__":
    main()
