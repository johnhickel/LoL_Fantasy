# LoL_Fantasy
League of Legends Fantasy Site

## Run locally

The site requires PHP 8.1 or newer with PDO SQLite enabled. In VS Code, run
**Terminal → Run Task → Start LoL Fantasy (PHP)**. Then open
<http://127.0.0.1:8000/> in a browser or VS Code's Simple Browser.

Do not open `index.html` in VS Code's static preview: it does not execute PHP,
so the login and signup pages cannot load there. Keep the server task running
while using the site; stop it with Ctrl+C in its terminal when finished.

Visitors can create an account with a unique username and password, then log
in and out as needed. Passwords are hashed before storage, and usernames are
unique (case-insensitively).

The SQLite database is created automatically at `../var/lol-fantasy.sqlite`,
outside the web root. Set `LOL_FANTASY_DB_PATH` to an absolute path outside the
web root to choose a different location; its parent directory must be writable
by the PHP process. On production, serve the site over HTTPS and keep the
database file and its directory writable only by the application.
