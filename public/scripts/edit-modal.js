(() => {
  const openBtn = document.querySelector("[data-open-profile-edit]");
  const modal = document.getElementById("profile-edit-modal");
  const backdrop = document.querySelector("[data-modal-backdrop]");
  const closeBtn = document.querySelector("[data-close-profile-edit]");

  if (!openBtn || !modal || !backdrop || !closeBtn) return;

  const open = () => {
    modal.hidden = false;
    backdrop.hidden = false;
    openBtn.setAttribute("aria-expanded", "true");
    document.body.style.overflow = "hidden";
    closeBtn.focus();
  };

  const close = () => {
    modal.hidden = true;
    backdrop.hidden = true;
    openBtn.setAttribute("aria-expanded", "false");
    document.body.style.overflow = "";
    openBtn.focus();
  };

  openBtn.addEventListener("click", open);
  closeBtn.addEventListener("click", close);
  backdrop.addEventListener("click", close);
  document.addEventListener("keydown", (e) => {
    if (!modal.hidden && e.key === "Escape") close();
  });
})();
