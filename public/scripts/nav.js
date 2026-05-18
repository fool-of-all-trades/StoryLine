const nav = document.querySelector(".div1");
const btn = document.querySelector(".hamburger");
const backdrop = document.querySelector(".backdrop");

function openNav() {
  nav.classList.add("is-open");
  backdrop.hidden = false;
  btn.setAttribute("aria-expanded", "true");
}

function closeNav() {
  nav.classList.remove("is-open");
  backdrop.hidden = true;
  btn.setAttribute("aria-expanded", "false");
}

btn.addEventListener("click", () => {
  nav.classList.contains("is-open") ? closeNav() : openNav();
});

backdrop.addEventListener("click", closeNav);

document.addEventListener("keydown", (e) => {
  if (e.key === "Escape") closeNav();
});
