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
    const form = qs("#story-form");
    form?.addEventListener("submit", async (e) => {
      e.preventDefault();
      const fd = new FormData(form);
      // validation of whether the quote is included is done in the db for now
      const res = await fetch("/api/story", {
        method: "POST",
        body: fd,
        credentials: "include",
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
        item.title ? escapeHtml(item.title) : "(no title)"
      }</a>
        <div class="meta">
          ${
            item.user_id
              ? `Author: <a href="/user/${item.user_id}">${item.user_id}</a> · `
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
