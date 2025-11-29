const { escapeHtml } = window.App;

(async () => {
  const list = document.querySelector("#stories-list");
  const loadMoreBtn = document.querySelector("[data-loadmore]");

  if (list) {
    const params = new URLSearchParams(location.search);
    const sort = params.get("sort") || "new";
    const dateParam = params.get("date");

    const dateFromUrl =
      dateParam && /^\d{4}-\d{2}-\d{2}$/.test(dateParam) ? dateParam : "today";

    // --- quote of the day on /stories ---
    const quoteWrapper = document.querySelector("[data-quote-wrapper]");
    const quoteTextEl = document.querySelector("[data-quote-of-day]");
    const quoteMetaEl = document.querySelector("[data-quote-of-day-meta]");

    async function loadQuoteForStoriesDate() {
      if (!quoteTextEl || !quoteMetaEl) return;

      try {
        let res;

        if (dateFromUrl === "today") {
          res = await fetch("/api/quote/today", { credentials: "include" });
          if (res.status === 404) {
            await fetch("/api/quote/ensure", {
              method: "POST",
              credentials: "include",
              headers: { "X-CSRF-Token": window.CSRF_TOKEN },
            });
            res = await fetch("/api/quote/today", { credentials: "include" });
          }
        } else {
          res = await fetch(
            `/api/quote?date=${encodeURIComponent(dateFromUrl)}`,
            {
              credentials: "include",
            }
          );
        }

        if (!res.ok) {
          return;
        }

        const q = await res.json();

        quoteTextEl.textContent = `"${q.sentence}"`;
        quoteMetaEl.textContent =
          [q.source_book, q.source_author].filter(Boolean).join(" — ") || "—";

        if (quoteWrapper) {
          quoteWrapper.hidden = false;
        }
      } catch (err) {
        console.error("Error loading quote for stories day:", err);
      }
    }

    let page = 1;
    const limit = 10;
    let loading = false;
    let noMore = false;

    async function loadPage() {
      if (loading || noMore) return;
      loading = true;

      if (loadMoreBtn) {
        loadMoreBtn.disabled = true;
        loadMoreBtn.textContent = "Loading...";
      }

      const r = await fetch(
        `/api/stories?date=${encodeURIComponent(
          dateFromUrl
        )}&sort=${encodeURIComponent(sort)}&page=${page}&limit=${limit}`,
        { credentials: "include" }
      );
      const j = await r.json();
      const items = j.items || [];

      const totalForDay =
        typeof j.total_for_day === "number" ? j.total_for_day : null;
      const countEl = document.querySelector("[data-stories-count]");
      if (countEl && totalForDay !== null) {
        countEl.textContent = totalForDay;
      }

      if (page === 1) {
        list.innerHTML = "";
      }

      if (!items.length && page === 1) {
        list.innerHTML = "<li>No stories for this day yet.</li>";
        noMore = true;
      } else {
        items.forEach((item) => {
          const li = document.createElement("li");
          li.className = "story";

          // --- author rendering ---
          let authorHtml = "";

          if (item.is_anonymous) {
            // anon – just plain text, no link to profile
            authorHtml = `Author: Anonymous · `;
          } else if (item.user_public_id) {
            // logged-in user, with link to profile
            authorHtml = `Author: <a href="/user/${
              item.user_public_id
            }">${escapeHtml(item.username ?? "user")}</a> · `;
          } else if (item.guest_name) {
            // logged-out user, no link to profile, but we have a nick he provided
            authorHtml = `Author: ${escapeHtml(item.guest_name)} · `;
          } else {
            authorHtml = `Author: Anonymous · `; // no author info at all
          }

          li.innerHTML = `
            <a href="/story/${item.story_public_id}" class="title">${
            item.title ? escapeHtml(item.title) : "(no title)"
          }</a>
            <div class="meta">
              ${authorHtml}
              ${item.word_count ?? 0} words · <span data-count>${
            item.flower_count ?? 0
          }</span> 🌸
            </div>
            <p class="preview">${escapeHtml(item.content).slice(0, 180)}${
            item.content.length > 180 ? "…" : ""
          }</p>
            <button class="like" data-like data-story="${
              item.id
            }">🌸 Flower</button>
          `;

          list.appendChild(li);
        });

        if (items.length < limit) {
          noMore = true;
        } else {
          page += 1;
        }
      }

      if (loadMoreBtn) {
        if (noMore) {
          loadMoreBtn.disabled = true;
          loadMoreBtn.textContent = "No more stories";
        } else {
          loadMoreBtn.disabled = false;
          loadMoreBtn.textContent = "Load more";
        }
      }

      loading = false;
    }

    // first page
    loadPage();

    loadQuoteForStoriesDate();

    loadMoreBtn?.addEventListener("click", () => {
      loadPage();
    });

    // Handle 🌸
    list.addEventListener("click", async (e) => {
      const btn = e.target.closest("[data-like]");
      if (!btn) return;
      const id = btn.getAttribute("data-story");
      const res = await fetch(
        `/api/story/flower?id=${encodeURIComponent(id)}`,
        {
          method: "POST",
          credentials: "include",
          headers: { "X-CSRF-Token": window.CSRF_TOKEN },
        }
      );
      const data = await res.json();
      if (res.ok) {
        btn.parentElement.querySelector("[data-count]").textContent =
          data.count;
      } else if (data?.error === "unauthorized") {
        location.href = "/login";
      } else {
        alert("Error: " + (data.error || "unknown"));
      }
    });
  }
})();
