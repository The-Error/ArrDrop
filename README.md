# ArrDrop

Short description: TODO

Versioning: CalVer (e.g., 2026.01 or 2026.01.18)
Current codename: Baumkuchen

Autor: Zoran Karavla
Tools: Codex (GPT-5)
Project: https://github.com/The-Error/ArrDrop

Plain-English overview:
- The PHP client is a simple web page where you paste IMDb IDs.
- It saves those IDs into a local text file called `movies.txt`.
- The Python backend reads that list from a public URL.
- It looks up each ID in Radarr and adds the movie.
- A local `processed.txt` keeps track so movies are not added twice.

Structure:
- arrdrop-client/ (PHP client)
- arrdrop-backend/ (Python backend)
