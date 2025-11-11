<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="csrf-token" content="<?= htmlspecialchars(App\Security\Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">

  <title><?= $title ?? 'StoryLine' ?></title>

  <!-- <link rel="stylesheet" href="/public/styles/main.css?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'].'/public/styles/main.css') ?>">
  <script src="/public/scripts/main.js?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'].'/public/scripts/main.js') ?>" defer></script> -->

  <script defer src="/scripts/chart.js"></script>
  <link rel="stylesheet" href="/styles/main.css">
  <script defer src="/scripts/main.js"></script>

</head>
<body>
  <nav class="nav">
    <a href="/" class="logo">StoryLine</a>
    <div class="spacer"></div>
    <a href="/stories/today">Stories</a>

    <!-- if logged in, show logout button, if not then show login -->
    <?php if (current_user()): ?>
      <form method="post" action="/logout">
        <?= App\Security\Csrf::inputField() ?>
        <button class="linklike">Logout (<?= htmlspecialchars(current_user()['username']) ?>)</button>
      </form>
    <?php else: ?>
      <a href="/login">Login</a>
    <?php endif; ?>

    <?php if (is_admin()): ?>
      <a href="/admin">Admin</a>
    <?php endif; ?>

  </nav>
  <main class="container">
