<?php $title = "StoryLine — Dziś"; include __DIR__."/partials/header.php"; ?>

    <section class="prompt-card">
      <small class="date">🗓️ October 15, 2025</small>
      <h1 class="quote">"It was a bright cold day in April, and the clocks were striking thirteen."</h1>
      <small class="meta">1984 — George Orwell</small>
    </section>

    <section id="write" class="writer">
      <form method="post" action="/story">
        <input id="title" name="title" placeholder="Tytuł" maxlength="100" />
        <br>
        <textarea id="content" name="content" rows="12" placeholder="Write your story here. Make sure you include the quote word by word..." data-wordlimit="500"></textarea>

        <div class="row">
          <label><input type="checkbox" name="anonymous" value="1"> Anonimowo</label>
          <br>
          <span class="wordcount" data-count>0 / 500 słów</span>
        </div>

        <button type="submit" class="btn primary">Publikuj</button>
      </form>
    </section>
  </main>
</body>
</html>

