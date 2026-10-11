<?php
declare(strict_types=1);

ini_set('session.use_strict_mode', '1');
session_set_cookie_params([
    'httponly' => true,
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
]);
session_start();

function database(): PDO
{
    static $connection;
    if ($connection instanceof PDO) {
        return $connection;
    }

    $databasePath = getenv('LOL_FANTASY_DB_PATH');
    if ($databasePath === false || $databasePath === '') {
        $databasePath = dirname(__DIR__) . '/var/lol-fantasy.sqlite';
    }
    $directory = dirname($databasePath);
    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
        throw new RuntimeException('Unable to create the database directory: ' . $directory);
    }
    if (!is_writable($directory)) {
        throw new RuntimeException('The database directory is not writable: ' . $directory);
    }

    $connection = new PDO('sqlite:' . $databasePath, options: [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $connection->exec(
        'CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL COLLATE NOCASE UNIQUE,
            password_hash TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )'
    );
    $columns = $connection->query('PRAGMA table_info(users)')->fetchAll(PDO::FETCH_COLUMN, 1);
    if (in_array('email', $columns, true)) {
        $connection->beginTransaction();
        try {
            $connection->exec(
                'CREATE TABLE users_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    username TEXT NOT NULL COLLATE NOCASE UNIQUE,
                    password_hash TEXT NOT NULL,
                    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
                )'
            );
            $connection->exec(
                'INSERT INTO users_new (id, username, password_hash, created_at)
                 SELECT id, username, password_hash, created_at FROM users'
            );
            $connection->exec('DROP TABLE users');
            $connection->exec('ALTER TABLE users_new RENAME TO users');
            $connection->commit();
        } catch (Throwable $exception) {
            $connection->rollBack();
            throw $exception;
        }
    }
    if (file_exists($databasePath) && !chmod($databasePath, 0600)) {
        throw new RuntimeException('Unable to set restrictive permissions on the database file.');
    }

    return $connection;
}

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $destination): never
{
    header('Location: ' . $destination, true, 303);
    exit;
}

function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf_token(): bool
{
    return isset($_SESSION['csrf_token'], $_POST['csrf_token'])
        && is_string($_POST['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

function current_user(): ?array
{
    return isset($_SESSION['user']) && is_array($_SESSION['user'])
        ? $_SESSION['user']
        : null;
}

function require_login(): array
{
    $user = current_user();
    if ($user === null) {
        redirect('login.php');
    }

    return $user;
}

function take_flash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function render_header(string $title): void
{
    $user = current_user();
    $home = $user === null ? 'index.html' : 'dashboard.php';
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= escape($title) ?> — LoL Fantasy</title>
        <link rel="stylesheet" href="styles.css">
    </head>
    <body>
    <header class="site-header">
        <a class="brand" href="<?= escape($home) ?>"><span class="brand-mark">L</span> LoL <span>Fantasy</span></a>
        <nav class="site-nav" aria-label="Main navigation">
            <?php if ($user === null): ?>
                <a href="login.php">Log in</a>
                <a class="button button-small" href="signup.php">Create account</a>
            <?php else: ?>
                <a class="button button-small" href="logout.php">Sign out</a>
            <?php endif; ?>
        </nav>
    </header>
    <?php
}

function render_footer(): void
{
    ?>
    <footer class="site-footer"><span>LoL Fantasy</span></footer>
    </body>
    </html>
    <?php
}
