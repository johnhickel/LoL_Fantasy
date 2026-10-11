<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';
$user = require_login();
render_header('Fantasy home');
?>
<main class="dashboard">
    <h1>Welcome, <?= escape($user['username']) ?></h1>
    <section class="dashboard-grid" aria-label="Fantasy sections">
        <button class="dashboard-tile" type="button" aria-pressed="false">My Team</button>
        <button class="dashboard-tile" type="button" aria-pressed="false">Matchups</button>
        <button class="dashboard-tile" type="button" aria-pressed="false">Draft</button>
        <button class="dashboard-tile" type="button" aria-pressed="false">Available Players</button>
    </section>
</main>
<script>
    document.querySelectorAll('.dashboard-tile').forEach((tile) => {
        tile.addEventListener('click', () => {
            document.querySelectorAll('.dashboard-tile').forEach((item) => {
                item.classList.remove('is-selected');
                item.setAttribute('aria-pressed', 'false');
            });
            tile.classList.add('is-selected');
            tile.setAttribute('aria-pressed', 'true');
        });
    });
</script>
<?php render_footer(); ?>
