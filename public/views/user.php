<?php
/** @var \App\Models\User $user */
$title = "StoryLine — User Panel";
include __DIR__."/partials/header.php";
?>
    <main>
        <h1><?= htmlspecialchars($user->username, ENT_QUOTES, 'UTF-8') ?></h1>

        <ul class="story-list" id="user-stories">
        </ul>
    </main>
  </body>
</html>