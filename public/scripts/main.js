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
      const li = document.createElement("li");
      li.className = "story";
      li.innerHTML = `
        <a href="/story/${item.id}" class="title">${
        // also here story id should also be a uuid, preferably, think it would be better
        item.title ? escapeHtml(item.title) : "(no title)"
      }</a>
        <div class="meta">
          ${
            item.id
              ? `Author: <a href="/user/${item.user_public_id}">${escapeHtml(
                  item.username ?? "user"
                )}</a> · `
              : ""
          }
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

  // --- Autosave + session keep-alive ---
  const storyTextarea = document.querySelector("#story-textarea");
  const storyForm = document.querySelector("#story-form");
  if (!storyTextarea || !storyForm) return; // works only on the dahsboard

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
  storyForm.addEventListener("submit", () => {
    localStorage.removeItem(KEY);
  });

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
