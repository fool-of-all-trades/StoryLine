<?php $title = "StoryLine — Dziś"; include __DIR__."/partials/header.php"; ?>

    <section class="prompt-card">
      <small class="date">data promptu</small>
      <h1 class="quote">Some quote from a book for that particular day.</h1>
      <small class="meta">the book, the author</small>
    </section>

    <section id="write" class="writer">
      <form method="post" action="/story">
        <label class="visually-hidden" for="title">Tytuł</label>
        <input id="title" name="title" placeholder="(opcjonalnie) tytuł" maxlength="100" />

        <label class="visually-hidden" for="content">Treść</label>
        <textarea id="content" name="content" rows="12" placeholder="Write your story here. Make sure you include the quote word by word..." data-wordlimit="500"></textarea>

        <div class="row">
          <label><input type="checkbox" name="anonymous" value="1"> Anonimowo</label>
          <span class="wordcount" data-count>0 / 500 słów</span>
        </div>

        <button type="submit" class="btn primary">Publikuj</button>
      </form>
    </section>
  </main>
</body>
</html>

