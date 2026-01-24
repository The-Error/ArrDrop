# ArrDrop

ArrDrop is a tiny two-part tool for adding IMDb IDs to Radarr.

Project: https://github.com/The-Error/ArrDrop
Version: 2026.01
Versioning: CalVer (e.g., 2026.01 or 2026.01.18)

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
   - `MOVIES_URL` (URL to your client’s `movies.txt`)
   - `RADARR_URL`
   - `RADARR_API_KEY`
   - `QUALITY_PROFILE_ID`
   - `ROOT_FOLDER`
4) Run the backend:
   - `python3 arrdrop-sync.py`

## Usage
- Add IDs in the web UI (any format; it extracts valid IDs).
- Run the backend to sync into Radarr.
- Use `--dry-run` to test without adding.
- Use `--log` to show movies added in the last sync.

## Files created by the backend
- `processed.txt` — IDs already added
- `arrdrop.log` — sync log

## License
See `LICENSE`.

## Author
Zoran Karavla
