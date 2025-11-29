(async () => {
  // ===== REGISTER FORM FRONT VALIDATION =====
  const registerForm = document.querySelector("#register-form");
  if (registerForm) {
    const regMsg = document.querySelector("#register-message");

    registerForm.addEventListener("submit", (e) => {
      e.preventDefault();
      regMsg && (regMsg.textContent = "");
      regMsg && regMsg.classList.remove("error", "success");

      const form = registerForm;
      const username = (form.username.value || "").trim();
      const pass = form.password.value || "";
      const pass2 = form.password_confirm.value || "";

      if (username.length < 3) {
        if (regMsg) {
          regMsg.textContent = "Username must be at least 3 characters long.";
          regMsg.classList.add("error");
        }
        return;
      }

      if (pass.length < 8) {
        if (regMsg) {
          regMsg.textContent = "Password must be at least 8 characters long.";
          regMsg.classList.add("error");
        }
        return;
      }

      if (
        !/[a-z]/.test(pass) ||
        !/[A-Z]/.test(pass) ||
        !/\d/.test(pass) ||
        !/[^A-Za-z0-9]/.test(pass)
      ) {
        if (regMsg) {
          regMsg.textContent =
            "Password must contain a lowercase, uppercase, digit and special character.";
          regMsg.classList.add("error");
        }
        return;
      }

      if (pass !== pass2) {
        if (regMsg) {
          regMsg.textContent = "Passwords do not match.";
          regMsg.classList.add("error");
        }
        return;
      }

      registerForm.submit();
    });
  }

  // ===== PASSWORD CHANGE FORM FRONT VALIDATION =====
  const passwordChangeForm = document.querySelector("#password-form");
  if (passwordChangeForm) {
    const passwordChangeMsg = document.querySelector("#password-message");

    passwordChangeForm.addEventListener("submit", (e) => {
      e.preventDefault();
      passwordChangeMsg && (passwordChangeMsg.textContent = "");
      passwordChangeMsg &&
        passwordChangeMsg.classList.remove("error", "success");

      const form = passwordChangeForm;
      const pass = form.password.value || "";

      if (pass.length < 8) {
        if (passwordChangeMsg) {
          passwordChangeMsg.textContent =
            "Password must be at least 8 characters long.";
          passwordChangeMsg.classList.add("error");
        }
        return;
      }

      if (
        !/[a-z]/.test(pass) ||
        !/[A-Z]/.test(pass) ||
        !/\d/.test(pass) ||
        !/[^A-Za-z0-9]/.test(pass)
      ) {
        if (passwordChangeMsg) {
          passwordChangeMsg.textContent =
            "Password must contain a lowercase, uppercase, digit and special character.";
          passwordChangeMsg.classList.add("error");
        }
        return;
      }

      passwordChangeForm.submit();
    });
  }

  // ===== USERNAME CHANGE FORM FRONT VALIDATION =====
  const usernameChangeForm = document.querySelector("#username-form");
  if (usernameChangeForm) {
    const usernameChangeMsg = document.querySelector("#username-message");

    usernameChangeForm.addEventListener("submit", (e) => {
      e.preventDefault();
      usernameChangeMsg && (usernameChangeMsg.textContent = "");
      usernameChangeMsg &&
        usernameChangeMsg.classList.remove("error", "success");

      const form = usernameChangeForm;
      const username = form.username.value || "";

      if (username.length < 3) {
        if (usernameChangeMsg) {
          usernameChangeMsg.textContent =
            "Username must be at least 3 characters long.";
          usernameChangeMsg.classList.add("error");
        }
        return;
      }

      usernameChangeForm.submit();
    });
  }

  // ===== FORGOT PASSWORD =====
  const forgotForm = document.querySelector("#forgot-form");
  if (forgotForm) {
    forgotForm.addEventListener("submit", async (e) => {
      e.preventDefault();
      const msg = document.querySelector("#forgot-message");
      msg.textContent = "";

      const fd = new FormData(forgotForm);

      const res = await fetch("/password/forgot", {
        method: "POST",
        body: fd,
        credentials: "include",
      });

      const data = await res.json();

      if (res.ok) {
        msg.textContent = "If this email exists, a reset link was sent.";
        msg.style.color = "green";
      } else {
        msg.textContent = data.error || "Unknown error";
        msg.style.color = "red";
      }
    });
  }

  // ===== RESET PASSWORD =====
  const resetForm = document.querySelector("#reset-form");
  if (resetForm) {
    resetForm.addEventListener("submit", async (e) => {
      e.preventDefault();
      const msg = document.querySelector("#reset-message");
      msg.textContent = "";

      const fd = new FormData(resetForm);

      const res = await fetch("/password/reset", {
        method: "POST",
        body: fd,
        credentials: "include",
      });

      const data = await res.json();

      if (res.ok) {
        msg.style.color = "green";
        msg.textContent = "Password has been updated. You may now log in.";

        setTimeout(() => {
          window.location.href = "/login";
        }, 1500);
      } else {
        msg.style.color = "red";
        msg.textContent = data.error || "Unknown error";
      }
    });
  }

  // ===== Helpers =====
  let togglePasswordVisibilityCheckbox = document.querySelector(
    "#togglePasswordVisibilityCheckbox"
  );

  togglePasswordVisibilityCheckbox?.addEventListener("change", () => {
    var passwordInput = document.querySelector("#passwordInput");
    if (passwordInput.type === "password") {
      passwordInput.type = "text";
    } else {
      passwordInput.type = "password";
    }
  });
})();
