<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="csrf-token" content="<?= htmlspecialchars(App\Security\Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">

  <title><?= $title ?? 'StoryLine' ?></title>

  <script defer src="/scripts/chart.js"></script>
  <link rel="stylesheet" href="/styles/main.css">
  <script defer src="/scripts/main.js"></script>

</head>
<body>
  <nav class="nav">
    <a href="/" class="logo">StoryLine</a>
    <div class="spacer"></div>
    <a href="/stories/today">Stories</a>

    <?php $current_user = current_user(); ?>

    <?php if (is_logged_in()): ?>

      <a href="/user/<?= htmlspecialchars($current_user['public_id'], ENT_QUOTES, 'UTF-8') ?>">My profile</a>

      <?php if (is_admin()): ?>
        <a href="/admin">Admin</a>
      <?php endif; ?>

      <form method="post" action="/logout">
        <?= App\Security\Csrf::inputField() ?>
        <button class="linklike">Logout (<?= htmlspecialchars($current_user['username'], ENT_QUOTES, 'UTF-8') ?>)</button>
      </form>

    <?php else: ?>

  <a href="/login">Login</a>

<?php endif; ?>


  </nav>
  <main class="container">
