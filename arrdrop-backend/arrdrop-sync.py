#!/usr/bin/env python3
import requests
import argparse
from datetime import datetime
from pathlib import Path

# ArrDrop Sync Backend
# License: MIT
# Version: 2026.01
# Codename: Baumkuchen
# Purpose: Pull IMDb IDs from a public list and add them to Radarr.
# Requires: Radarr v3 API access and a reachable movies list URL.
# Author: Zoran Karavla
# Tooling: Codex (GPT-5)
# Project: https://github.com/The-Error/ArrDrop

# ================= CONFIG =================
# MOVIES_URL: Public URL to the plain-text list of IMDb IDs (one per line).
MOVIES_URL = "https://example.com/movies.txt"
# RADARR_URL: Base URL of your Radarr instance.
RADARR_URL = "http://192.168.0.123:7878"
# RADARR_API_KEY: API key from Radarr Settings > General.
RADARR_API_KEY = "API-KEY-HERE"

# QUALITY_PROFILE_ID: Numeric ID of the Radarr quality profile to use.
QUALITY_PROFILE_ID = 5
# ROOT_FOLDER: Root folder in Radarr where movies should be stored.
ROOT_FOLDER = "/movies"

# STATE_FILE: Local file that tracks which IMDb IDs were already processed.
STATE_FILE = Path("processed.txt")
# LOG_FILE: Local log file written by this script.
LOG_FILE = Path("arrdrop.log")
# =========================================


def log(message):
    timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    line = f"[{timestamp}] {message}"
    print(line)
    with LOG_FILE.open("a") as f:
        f.write(line + "\n")

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
    resp = requests.get(MOVIES_URL, timeout=10)
    resp.raise_for_status()
    return {line.strip() for line in resp.text.splitlines() if line.strip()}


def load_processed():
    if not STATE_FILE.exists():
        return set()
    return {line.strip() for line in STATE_FILE.read_text().splitlines() if line.strip()}


def mark_processed(imdb_id):
    STATE_FILE.open("a").write(imdb_id + "\n")


def lookup_movie(imdb_id):
    resp = requests.get(
        f"{RADARR_URL}/api/v3/movie/lookup/imdb",
        params={"imdbId": imdb_id},
        headers={"X-Api-Key": RADARR_API_KEY},
        timeout=10
    )
    resp.raise_for_status()
    data = resp.json()

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

    resp = requests.post(
        f"{RADARR_URL}/api/v3/movie",
        json=payload,
        headers={"X-Api-Key": RADARR_API_KEY},
        timeout=10
    )

    if resp.status_code == 201:
        log(f"Added movie: {title}")
        return True

    if resp.status_code == 400 and "exists" in resp.text.lower():
        log(f"Already exists in Radarr: {title}")
        return True

    log(f"ERROR adding {title}: {resp.text}")
    return False


def main():
    parser = argparse.ArgumentParser(description="Sync movies.txt with Radarr")
    parser.add_argument("--dry-run", action="store_true", help="Do not add movies, only log actions")
    parser.add_argument("--log", action="store_true", help="Show movies added in the last sync and exit")
    args = parser.parse_args()

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
