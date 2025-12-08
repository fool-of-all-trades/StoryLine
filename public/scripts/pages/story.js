const storyFull = document.querySelector(".story-full");
if (storyFull) {
  storyFull.addEventListener("click", async (e) => {
    const btn = e.target.closest("[data-like]");
    if (!btn) return;
    const id = btn.getAttribute("data-story"); // numeric PK
    const res = await fetch(`/api/story/flower?id=${encodeURIComponent(id)}`, {
      method: "POST",
      credentials: "include",
      headers: { "X-CSRF-Token": window.CSRF_TOKEN },
    });
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
