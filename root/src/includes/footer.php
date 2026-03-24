  </div><!-- /content-area -->
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ── Theme toggle ──────────────────────────────────────────────
const html      = document.documentElement;
const themeBtn  = document.getElementById('themeToggle');
const themeIcon = document.getElementById('themeIcon');

function applyTheme(t) {
  html.setAttribute('data-theme', t);
  themeIcon.className = t === 'dark' ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
  localStorage.setItem('ag_theme', t);
}
// Apply saved theme
applyTheme(localStorage.getItem('ag_theme') || 'light');

themeBtn.addEventListener('click', () => {
  applyTheme(html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
});

// ── Sidebar mobile ────────────────────────────────────────────
const sidebar = document.getElementById('sidebar');
const toggle  = document.getElementById('sidebarToggle');
const overlay = document.getElementById('sidebarOverlay');

if (toggle) {
  toggle.addEventListener('click', () => {
    sidebar.classList.toggle('open');
    overlay.classList.toggle('show');
  });
}
if (overlay) {
  overlay.addEventListener('click', () => {
    sidebar.classList.remove('open');
    overlay.classList.remove('show');
  });
}

// ── Auto-dismiss alerts ───────────────────────────────────────
document.querySelectorAll('.alert').forEach(el => {
  setTimeout(() => bootstrap.Alert.getOrCreateInstance(el)?.close(), 4500);
});
</script>

<?php if (isset($extraJs)) echo $extraJs; ?>
</body>
</html>
