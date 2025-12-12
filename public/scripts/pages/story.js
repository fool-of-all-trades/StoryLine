const storyFull = document.querySelector(".story-full");

async function handleStoryLike(e) {
  const btn = e.target.closest("[data-like]");
  if (!btn) return;

  const storyPublicId = btn.getAttribute("data-story");

  try {
    const res = await fetch(
      `/api/story/${encodeURIComponent(storyPublicId)}/flower`,
      {
        method: "POST",
        credentials: "include",
        headers: {
          "X-CSRF-Token": window.CSRF_TOKEN,
        },
      }
    );

    const data = await res.json();

    if (res.ok) {
      const countElement = storyFull.querySelector("[data-count]");
      countElement.textContent = data.count;
    } else if (data?.error === "unauthorized") {
      location.href = "/login";
    } else {
      alert(`Error: ${data.error || "unknown"}`);
    }
  } catch (error) {
    console.error("Failed to update story:", error);
    alert("An unexpected error occurred");
  }
}

function initStoryLikes() {
  if (!storyFull) return;
  storyFull.addEventListener("click", handleStoryLike);
}

initStoryLikes();
