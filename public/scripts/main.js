const meta = document.querySelector('meta[name="csrf-token"]');
window.CSRF_TOKEN = meta ? meta.content : "";

(async () => {
  const qs = (sel, root = document) => root.querySelector(sel);

  // ===== DASHBOARD =====
  const quoteEl = qs("[data-quote]");
  if (quoteEl) {
    // 1) Get today's quote
    let q = await fetch("/api/quote/today", { credentials: "include" });
    if (q.status === 404) {
      await fetch("/api/quote/ensure", {
        method: "POST",
        credentials: "include",
        headers: { "X-CSRF-Token": window.CSRF_TOKEN },
      });
      q = await fetch("/api/quote/today", { credentials: "include" });
    }
    const j = await q.json();
    qs("[data-date]").textContent =
      j.date || new Date().toISOString().slice(0, 10);
    qs("[data-quote]").textContent = `"${j.sentence}"`;
    qs("[data-meta]").textContent =
      [j.source_book, j.source_author].filter(Boolean).join(" — ") || "—";

    // 2) Handle story submission
    const storyForm = qs("#story-form");
    storyForm?.addEventListener("submit", async (e) => {
      e.preventDefault();
      const fd = new FormData(storyForm);
      // validation of whether the quote is included is done in the db for now
      const res = await fetch("/api/story", {
        method: "POST",
        body: fd,
        credentials: "include",
        headers: { "X-CSRF-Token": window.CSRF_TOKEN },
      });
      const data = await res.json();
      if (res.ok) {
        // redirect to the list of today's stories
        location.href = "/stories/today";
      } else {
        alert("Error: " + (data.error || "unknown"));
      }
    });
  }

  // ===== STORIES LIST =====
  const list = qs("#stories-list");
  if (list) {
    const dateFromUrl =
      location.pathname.match(/\/stories\/(\d{4}-\d{2}-\d{2})$/)?.[1] ||
      "today";
    const params = new URLSearchParams(location.search);
    const sort = params.get("sort") || "new";

    const r = await fetch(
      `/api/stories?date=${encodeURIComponent(
        dateFromUrl
      )}&sort=${encodeURIComponent(sort)}&limit=20`,
      { credentials: "include" }
    );
    const j = await r.json();
    list.innerHTML = "";

    (j.items || []).forEach((item) => {
      console.log(item);

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

  // ===== USER PROFILE PANEL =====
  const userMain = document.querySelector("main[data-user-public-id]");
  if (userMain) {
    const userPid = userMain.dataset.userPublicId;
    const wordsEl = document.getElementById("user-word-stats");
    const countEl = document.getElementById("user-stories-count");
    const listEl = document.getElementById("user-stories-list");
    const searchInput = document.getElementById("user-stories-search");

    try {
      const res = await fetch(
        `/api/user/${encodeURIComponent(userPid)}/profile`,
        {
          credentials: "include",
        }
      );
      const payload = await res.json();
      if (!res.ok) {
        wordsEl.textContent = "Couldn't load your stats right now.";
        console.error("Profile error:", payload);
        return;
      }

      const { total_words, total_stories, items } = payload.data || {};

      // 1) number of stories and words
      if (typeof total_stories === "number" && countEl) {
        countEl.textContent = String(total_stories);
      }

      if (typeof total_words === "number" && wordsEl) {
        let label = "words";
        if (total_words === 1) label = "word";
        wordsEl.innerHTML =
          `You've written <strong>${total_words}</strong> ${label} all together!<br/>` +
          `I'm proud of you.`;
      }

      // 2) sotries list
      if (listEl) {
        const stories = Array.isArray(items) ? items : [];
        renderStories(listEl, stories);

        // simple search filter by the title/content
        if (searchInput) {
          searchInput.addEventListener("input", () => {
            const q = searchInput.value.toLowerCase().trim();
            const filtered = stories.filter((s) => {
              const title = (s.title || "").toLowerCase();
              const content = (s.content || "").toLowerCase();
              return !q || title.includes(q) || content.includes(q);
            });
            renderStories(listEl, filtered);
          });
        }
      }
    } catch (e) {
      console.error("Failed to load user profile data", e);
      if (wordsEl) {
        wordsEl.textContent = "Couldn't load your stats right now.";
      }
    }
  }

  function renderStories(container, items) {
    container.innerHTML = "";
    if (!items.length) {
      container.innerHTML =
        "<p>You haven't written any stories yet. Let's fix that!</p>";
      return;
    }

    items.forEach((item) => {
      const card = document.createElement("div");
      card.className = "book-card";

      const title = item.title ? escapeHtml(item.title) : "(no title)";
      const createdAt = item.created_at
        ? new Date(item.created_at).toLocaleDateString()
        : "";
      const words = item.word_count ?? 0;
      const flowers = item.flower_count ?? 0;

      card.innerHTML = `
        <a href="/story/${item.story_public_id}" class="book-card-title">${title}</a>
        <div class="book-card-meta">
          <span>${createdAt}</span> ·
          <span>${words} words</span> ·
          <span>${flowers} 🌸</span>
        </div>
      `;

      container.appendChild(card);
    });
  }

  // ===== FAVORITE QUOTE FORM =====
  const favForm = document.getElementById("favorite-quote-form");
  if (favForm) {
    const msgEl = document.getElementById("favorite-quote-message");

    favForm.addEventListener("submit", async (e) => {
      e.preventDefault();
      msgEl && (msgEl.textContent = "");

      const formData = new FormData(favForm);

      try {
        const res = await fetch("/api/me/favorite-quote", {
          method: "POST",
          body: formData,
          credentials: "include",
          headers: {
            "X-CSRF-Token": window.CSRF_TOKEN || "",
          },
        });

        const data = await res.json();

        if (res.ok) {
          if (msgEl) {
            msgEl.textContent = "Favorite quote saved ✨";
            msgEl.classList.remove("error");
            msgEl.classList.add("success");
          }
        } else {
          const err = data?.error || "unknown_error";
          if (msgEl) {
            msgEl.textContent = "Could not save favorite quote: " + err;
            msgEl.classList.remove("success");
            msgEl.classList.add("error");
          }
        }
      } catch (err) {
        console.error("favorite-quote error", err);
        if (msgEl) {
          msgEl.textContent = "Unexpected error. Please try again.";
          msgEl.classList.remove("success");
          msgEl.classList.add("error");
        }
      }
    });
  }

  // --- Autosave + session keep-alive ---
  const storyTextarea = document.querySelector("#story-textarea");
  const storyForm = document.querySelector("#story-form");

  // works only on the dahsboard
  if (storyTextarea && storyForm) {
    const challengeId = storyTextarea.dataset.challengeId || "unknown";
    const KEY = `storyline:draft:${challengeId}`;

    // Restore draft from localStorage
    if (!storyTextarea.value) {
      const draft = localStorage.getItem(KEY);
      if (draft) storyTextarea.value = draft;
    }

    // Debounced autosave
    let saveTimer = null;
    storyTextarea.addEventListener("input", () => {
      clearTimeout(saveTimer);
      saveTimer = setTimeout(() => {
        try {
          localStorage.setItem(KEY, storyTextarea.value);
        } catch (e) {
          // storage is full? well that's rough buddy
          console.warn("Autosave failed:", e);
        }
      }, 800);
    });

    // After the user submits the form, clear the saved draft
    storyForm.addEventListener("submit", () => localStorage.removeItem(KEY));
  }

  // ===== SINGLE STORY VIEW =====
  const storyFull = qs("article.story-full");
  if (storyFull) {
    storyFull.addEventListener("click", async (e) => {
      const btn = e.target.closest("[data-like]");
      if (!btn) return;
      const id = btn.getAttribute("data-story"); // numeric PK
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
        storyFull.querySelector("[data-count]").textContent = data.count;
      } else if (data?.error === "unauthorized") {
        location.href = "/login";
      } else {
        alert("Error: " + (data.error || "unknown"));
      }
    });
  }

  // ===== ADMIN PANEL =====
  const adminRoot = qs("#admin-charts");
  if (adminRoot && window.Chart) {
    let storiesSeries = [];
    let usersSeries = [];

    try {
      storiesSeries = JSON.parse(adminRoot.dataset.storiesSeries || "[]");
    } catch (e) {
      console.warn("Failed to parse storiesSeries", e);
    }

    try {
      usersSeries = JSON.parse(adminRoot.dataset.usersSeries || "[]");
    } catch (e) {
      console.warn("Failed to parse usersSeries", e);
    }

    const makeDataset = (series) => ({
      labels: series.map((p) => p.bucket),
      data: series.map((p) => p.cnt),
    });

    const s = makeDataset(storiesSeries);
    const u = makeDataset(usersSeries);

    const storiesCanvas = document.getElementById("storiesChart");
    if (storiesCanvas) {
      new Chart(storiesCanvas, {
        type: "line",
        data: {
          labels: s.labels,
          datasets: [
            {
              label: "Stories",
              data: s.data,
              fill: false,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
        },
      });
    }

    const usersCanvas = document.getElementById("usersChart");
    if (usersCanvas) {
      new Chart(usersCanvas, {
        type: "line",
        data: {
          labels: u.labels,
          datasets: [
            {
              label: "Users",
              data: u.data,
              fill: false,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
        },
      });
    }
  }

  // session keep-alive only if the user is actively typing
  // let lastTyping = Date.now();
  // storyTextarea.addEventListener("input", () => {
  //   lastTyping = Date.now();
  // });

  // setInterval(() => {
  //   // if the user typed in the last minute, ping the server in 5 minutes to keep the session alive
  //   if (Date.now() - lastTyping < 60_000) {
  //     fetch("/auth/ping", {
  //       method: "POST",
  //       headers: {
  //         "X-CSRF-Token": window.CSRF_TOKEN || "",
  //       },
  //     }).catch(() => {});
  //   }
  // }, 300_000);

  // ===== Helper =====
  function escapeHtml(s) {
    return String(s).replace(
      /[&<>"']/g,
      (c) =>
        ({
          "&": "&amp;",
          "<": "&lt;",
          ">": "&gt;",
          '"': "&quot;",
          "'": "&#039;",
        }[c])
    );
  }
})();
