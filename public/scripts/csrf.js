const meta = document.querySelector('meta[name="csrf-token"]');
window.CSRF_TOKEN = meta ? meta.content : "";
