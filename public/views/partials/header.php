<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= $title ?? 'StoryLine' ?></title>
  <link rel="stylesheet"
      href="/public/styles/main.css?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'].'/public/styles/main.css') ?>">

  <script src="/public/scripts/main.js?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'].'/public/scripts/main.js') ?>" defer></script>

</head>
<body>
  <nav class="nav">
    <a href="/" class="logo">StoryLine</a>
    <div class="spacer"></div>
    <a href="/stories/today">Stories</a>
    <a href="/login">Login</a>
  </nav>
  <main class="container">
