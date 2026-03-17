import 'bootstrap';
import PerfectScrollbar from 'perfect-scrollbar';
import ApexCharts from 'apexcharts';

window.ApexCharts = ApexCharts;

// ── CSRF helper per fetch ─────────────────────────────────────────────────────
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

window.apiFetch = (url, options = {}) =>
  fetch(url, {
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-CSRF-TOKEN': csrfToken,
      ...options.headers,
    },
    ...options,
  });

// ── Sidebar scroll ────────────────────────────────────────────────────────────
const menuEl = document.querySelector('.layout-menu');
if (menuEl) {
  new PerfectScrollbar(menuEl, { wheelPropagation: false });
}

// ── Sidebar overlay mobile ────────────────────────────────────────────────────
let overlay = document.querySelector('.layout-overlay');
if (!overlay) {
  overlay = document.createElement('div');
  overlay.className = 'layout-overlay';
  document.body.appendChild(overlay);
}

const toggleSidebar = () => {
  const isOpen = menuEl?.classList.toggle('open');
  overlay.classList.toggle('show', !!isOpen);
  document.body.style.overflow = isOpen ? 'hidden' : '';
};

document.querySelector('[data-sidebar-toggle]')?.addEventListener('click', toggleSidebar);
overlay.addEventListener('click', toggleSidebar);

// ── Active menu link (path-based, ignora query string) ───────────────────────
const currentPath = window.location.pathname;
document.querySelectorAll('.menu-link:not(.menu-toggle)').forEach(link => {
  try {
    const linkPath = new URL(link.href, window.location.origin).pathname;
    if (linkPath !== '/' && currentPath.startsWith(linkPath)) {
      link.classList.add('active');
      // Apri il sottomenu padre se presente
      const sub = link.closest('.menu-sub');
      if (sub) {
        sub.classList.add('show');
        const toggle = document.querySelector(`[href="#${sub.id}"]`);
        if (toggle) {
          toggle.classList.remove('collapsed');
          toggle.setAttribute('aria-expanded', 'true');
        }
      }
    }
  } catch { /* link senza href valido */ }
});

// ── Persistenza sottomenu in sessionStorage ───────────────────────────────────
document.querySelectorAll('.menu-sub').forEach(sub => {
  if (!sub.id) return;
  const key = `menu-open-${sub.id}`;

  if (sessionStorage.getItem(key) === '1' && !sub.classList.contains('show')) {
    sub.classList.add('show');
    const toggle = document.querySelector(`[href="#${sub.id}"]`);
    if (toggle) {
      toggle.classList.remove('collapsed');
      toggle.setAttribute('aria-expanded', 'true');
    }
  }

  sub.addEventListener('show.bs.collapse',  () => sessionStorage.setItem(key, '1'));
  sub.addEventListener('hide.bs.collapse',  () => sessionStorage.removeItem(key));
});

// ── Flash message auto-dismiss ────────────────────────────────────────────────
document.querySelectorAll('.alert[data-auto-dismiss]').forEach(el => {
  const delay = parseInt(el.dataset.autoDismiss, 10) || 4000;
  setTimeout(() => {
    el.classList.remove('show');
    el.addEventListener('transitionend', () => el.remove(), { once: true });
  }, delay);
});

// ── Confirm per azioni distruttive ────────────────────────────────────────────
document.querySelectorAll('[data-confirm]').forEach(btn => {
  btn.addEventListener('click', e => {
    const msg = btn.dataset.confirm || 'Confermi questa azione?';
    if (!confirm(msg)) e.preventDefault();
  });
});
