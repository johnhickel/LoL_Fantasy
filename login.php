<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';

if (current_user() !== null) {
    redirect('dashboard.php');
}

$errors = [];
$account = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $account = trim((string) ($_POST['account'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!verify_csrf_token()) {
        $errors[] = 'Your session expired. Please submit the form again.';
    } elseif ($account === '' || $password === '') {
        $errors[] = 'Enter your username and password.';
    } else {
        $statement = database()->prepare(
            'SELECT id, username, password_hash FROM users
             WHERE username = :username COLLATE NOCASE'
        );
        $statement->execute(['username' => $account]);
        $user = $statement->fetch();

        if ($user === false || !password_verify($password, $user['password_hash'])) {
            $errors[] = 'The username or password is incorrect.';
        } else {
            session_regenerate_id(true);
            unset($_SESSION['csrf_token']);
            $_SESSION['user'] = ['id' => (int) $user['id'], 'username' => $user['username']];
            redirect('dashboard.php');
        }
    }
}

$flash = take_flash();
render_header('Log in');
?>
<main class="auth-shell">
    <section class="auth-card">
        <h1>Log in</h1>
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
        <form method="post" action="login.php">
            <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
            <label class="form-field" for="account">
                Username
                <input id="account" name="account" type="text" autocomplete="username" placeholder="Your username" value="<?= escape($account) ?>" required>
            </label>
            <label class="form-field" for="password">
                Password
                <input id="password" name="password" type="password" autocomplete="current-password" placeholder="Your password" required>
            </label>
            <button class="button form-submit" type="submit">Log in <span aria-hidden="true">→</span></button>
        </form>
        <p class="auth-switch">Need an account? <a href="signup.php">Sign up</a></p>
    </section>
</main>
<?php render_footer(); ?>
