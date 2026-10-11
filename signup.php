<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';

if (current_user() !== null) {
    redirect('dashboard.php');
}

$errors = [];
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!verify_csrf_token()) {
        $errors[] = 'Your session expired. Please submit the form again.';
    }
    if (!preg_match('/\A[a-zA-Z0-9_]{3,20}\z/', $username)) {
        $errors[] = 'Choose a username with 3–20 letters, numbers, or underscores.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Use a password with at least 8 characters.';
    }
    if (strlen($password) > 72) {
        $errors[] = 'Use a password no longer than 72 characters.';
    }

    if ($errors === []) {
        $connection = database();
        $statement = $connection->prepare(
            'SELECT 1 FROM users WHERE username = :username COLLATE NOCASE'
        );
        $statement->execute(['username' => $username]);
        if ($statement->fetchColumn() !== false) {
            $errors[] = 'That username is already registered.';
        } else {
            try {
                $statement = $connection->prepare(
                    'INSERT INTO users (username, password_hash) VALUES (:username, :password_hash)'
                );
                $statement->execute([
                    'username' => $username,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                ]);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Your account is ready. Log in to get started.'];
                redirect('login.php');
            } catch (PDOException $exception) {
                if ($exception->getCode() !== '23000') {
                    throw $exception;
                }
                $errors[] = 'That username is already registered.';
            }
        }
    }
}

$flash = take_flash();
render_header('Create account');
?>
<main class="auth-shell">
    <section class="auth-card">
        <h1>Sign up</h1>
        <?php if ($flash !== null): ?>
            <div class="alert alert-success" role="status"><?= escape($flash['message']) ?></div>
        <?php endif; ?>
        <?php if ($errors !== []): ?>
            <div class="alert" role="alert">
                <?php foreach ($errors as $error): ?>
                    <div><?= escape($error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <form method="post" action="signup.php">
            <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
            <label class="form-field" for="username">
                Username
                <input id="username" name="username" type="text" minlength="3" maxlength="20" pattern="[a-zA-Z0-9_]{3,20}" autocomplete="username" placeholder="Choose a username" value="<?= escape($username) ?>" required>
            </label>
            <label class="form-field" for="password">
                Password
                <input id="password" name="password" type="password" minlength="8" maxlength="72" autocomplete="new-password" placeholder="At least 8 characters" required>
            </label>
            <button class="button form-submit" type="submit">Create account <span aria-hidden="true">→</span></button>
        </form>
        <p class="auth-switch">Already registered? <a href="login.php">Log in</a></p>
    </section>
</main>
<?php render_footer(); ?>
