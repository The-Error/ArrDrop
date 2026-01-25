# ArrDrop

ArrDrop is a tiny two-part tool that lets you drop IMDb links/IDs into a simple web page and have them show up in Radarr.  
This lets you add movies to Radarr even when your home media server is offline and also without exposing it directly to the internet. ArrDrop can be self-hosted and it's free.

Project: https://github.com/The-Error/ArrDrop
Version: 2026.01

## What it does
- PHP client: lets you paste IMDb IDs into a web page.
- Backend sync: reads the list and adds movies to Radarr.

## Structure
- `arrdrop-client/` — PHP client (web server)
- `arrdrop-backend/` — Python sync (backend)

## Requirements
- PHP 7+ (client)
- Python 3.9+ (backend)
- Radarr v3 with API access

## Quick start
1) Place the PHP client on your web server.
2) Make sure the client can write `movies.txt` in its folder.
3) Set the backend config in `arrdrop-backend/arrdrop-sync.py`:
   - `MOVIES_URL` — Public URL to your `movies.txt` (one IMDb ID per line).
   - `RADARR_URL` — Base URL of your Radarr instance (e.g., http://localhost:7878).
   - `RADARR_API_KEY` — Your Radarr API key (Settings → General).
   - `QUALITY_PROFILE_ID` — Numeric ID of the quality profile you want to use.
   - `ROOT_FOLDER` — Root folder where Radarr should store movies.
4) Run the backend:
   - `python3 arrdrop-sync.py`

## Usage
- Add IDs in the web UI (any format; it extracts valid IDs).
- Run the backend to sync into Radarr.
- Use `--dry-run` to test without adding.
- Use `--log` to show movies added in the last sync.

## Theme (day/night)
The client supports a built-in day/night theme without any UI toggle.  
Edit `arrdrop-client/index.php` and set:
- `$night_mode = true;` for night mode
- `$night_mode = false;` for day mode

You can also override it in the URL:
- `?theme=night`
- `?theme=day`

## Files created by the backend
- `processed.txt` — IDs already added
- `arrdrop.log` — sync log

## License
See `LICENSE`.

## Author
Zoran Karavla
