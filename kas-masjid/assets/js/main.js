/**
 * SISTEM INFORMASI KAS MASJID BAITURROHMAN
 * main.js – Animasi, Interaksi & Utilitas Global
 */
'use strict';

/* ===== 1. SCROLL ANIMATION ===== */
function initScrollAnimations() {
  const obs = new IntersectionObserver((entries) => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        e.target.style.opacity  = '1';
        e.target.style.transform = 'translateY(0)';
      }
    });
  }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });

  document.querySelectorAll('.card, .stat-card, .saldo-card, .table-wrapper').forEach((el, i) => {
    if (el.style.opacity !== '') return;
    el.style.opacity    = '0';
    el.style.transform  = 'translateY(28px)';
    el.style.transition = `opacity 0.6s ease ${i * 0.07}s, transform 0.6s ease ${i * 0.07}s`;
    obs.observe(el);
  });
}

/* ===== 2. COUNTER ANIMATION ===== */
function animateCounter(el, target, duration = 1800) {
  if (!el) return;
  let current = 0;
  const step = target / (duration / 16);
  const timer = setInterval(() => {
    current += step;
    if (current >= target) { current = target; clearInterval(timer); }
    el.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.floor(current));
  }, 16);
}

/* ===== 3. FORMAT RUPIAH INPUT ===== */
function initRupiahInputs() {
  document.querySelectorAll('input[data-rupiah], #jumlahInput').forEach(inp => {
    inp.addEventListener('input', function () {
      const raw = this.value.replace(/\D/g, '');
      this.value = raw ? new Intl.NumberFormat('id-ID').format(parseInt(raw)) : '';
    });
  });
}

/* ===== 4. ALERT AUTO DISMISS ===== */
function initAlerts() {
  document.querySelectorAll('.alert').forEach(alert => {
    if (!alert.querySelector('.alert-close')) {
      const btn = document.createElement('button');
      btn.innerHTML = '<i class="fas fa-times"></i>';
      btn.style.cssText = 'margin-left:auto;background:none;border:none;opacity:.6;cursor:pointer;font-size:.9rem;padding:2px 6px;border-radius:4px;color:inherit;transition:.15s';
      btn.onmouseenter = () => btn.style.opacity = '1';
      btn.onmouseleave = () => btn.style.opacity = '.6';
      btn.onclick = () => {
        alert.style.transition = 'opacity .4s, transform .4s';
        alert.style.opacity    = '0';
        alert.style.transform  = 'translateY(-8px)';
        setTimeout(() => alert.remove(), 400);
      };
      alert.appendChild(btn);
    }
    setTimeout(() => {
      if (!document.body.contains(alert)) return;
      alert.style.transition = 'opacity .4s, transform .4s';
      alert.style.opacity    = '0';
      alert.style.transform  = 'translateY(-8px)';
      setTimeout(() => { if (alert.parentNode) alert.remove(); }, 400);
    }, 5000);
  });
}

/* ===== 5. MODAL HELPERS ===== */
function openModal(id)  { document.getElementById(id)?.classList.add('active'); }
function closeModal(id) {
  if (id) { document.getElementById(id)?.classList.remove('active'); }
  else { document.querySelectorAll('.modal-overlay.active').forEach(m => m.classList.remove('active')); }
}
document.addEventListener('click', e => { if (e.target.classList.contains('modal-overlay')) e.target.classList.remove('active'); });
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

/* ===== 6. SIDEBAR TOGGLE ===== */
function initSidebar() {
  const toggle  = document.getElementById('sidebarToggle');
  const sidebar = document.getElementById('adminSidebar');
  const overlay = document.getElementById('sidebarOverlay');
  if (!toggle || !sidebar) return;
  toggle.addEventListener('click', () => {
    sidebar.classList.toggle('open');
    overlay?.classList.toggle('active');
  });
  overlay?.addEventListener('click', () => {
    sidebar.classList.remove('open');
    overlay.classList.remove('active');
  });
}

/* ===== 7. NAVBAR PUBLIK ===== */
function initNavbar() {
  const toggle = document.getElementById('navToggle');
  const links  = document.getElementById('navLinks');
  const navbar = document.getElementById('pubNavbar');
  if (toggle && links) {
    toggle.addEventListener('click', () => links.classList.toggle('open'));
    // Tutup menu saat klik link
    links.querySelectorAll('a').forEach(a => {
      a.addEventListener('click', () => links.classList.remove('open'));
    });
  }
  if (navbar) {
    window.addEventListener('scroll', () => {
      navbar.style.background = window.scrollY > 30
        ? 'rgba(15,61,38,1)'
        : 'rgba(15,61,38,.97)';
    });
  }
}

/* ===== 8. SCROLL TO TOP ===== */
function initScrollToTop() {
  const btn = document.createElement('button');
  btn.id = 'scrollTopBtn';
  btn.innerHTML = '<i class="fas fa-chevron-up"></i>';
  btn.title = 'Kembali ke atas';
  document.body.appendChild(btn);
  window.addEventListener('scroll', () => {
    btn.classList.toggle('visible', window.scrollY > 300);
  });
  btn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
}

/* ===== 9. RIPPLE EFFECT ===== */
function initRipple() {
  // Inject keyframe
  if (!document.getElementById('ripple-style')) {
    const s = document.createElement('style');
    s.id = 'ripple-style';
    s.textContent = '@keyframes rippleEffect{to{transform:scale(2.5);opacity:0}}';
    document.head.appendChild(s);
  }
  document.querySelectorAll('.btn').forEach(btn => {
    btn.addEventListener('click', function (e) {
      const ripple = document.createElement('span');
      const rect   = this.getBoundingClientRect();
      const size   = Math.max(rect.width, rect.height);
      ripple.style.cssText = `position:absolute;border-radius:50%;width:${size}px;height:${size}px;left:${e.clientX - rect.left - size/2}px;top:${e.clientY - rect.top - size/2}px;background:rgba(255,255,255,.22);transform:scale(0);animation:rippleEffect .55s ease-out forwards;pointer-events:none;`;
      if (getComputedStyle(this).position === 'static') this.style.position = 'relative';
      this.style.overflow = 'hidden';
      this.appendChild(ripple);
      setTimeout(() => ripple.remove(), 600);
    });
  });
}

/* ===== 10. CHART.JS GLOBAL DEFAULTS ===== */
function initChartDefaults() {
  if (typeof Chart === 'undefined') return;
  Chart.defaults.font.family = "'Poppins', sans-serif";
  Chart.defaults.font.size   = 12;
  Chart.defaults.color       = '#6b7280';
  Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(15,61,38,.93)';
  Chart.defaults.plugins.tooltip.titleColor      = '#fff';
  Chart.defaults.plugins.tooltip.bodyColor       = 'rgba(255,255,255,.85)';
  Chart.defaults.plugins.tooltip.padding         = 12;
  Chart.defaults.plugins.tooltip.cornerRadius    = 8;
  Chart.defaults.animation.duration              = 900;
  Chart.defaults.animation.easing               = 'easeInOutQuart';
}

/* ===== 11. TABLE ROW SEARCH ===== */
function initTableSearch() {
  document.querySelectorAll('[data-table-search]').forEach(inp => {
    const table = document.getElementById(inp.dataset.tableSearch);
    if (!table) return;
    inp.addEventListener('input', () => {
      const q = inp.value.toLowerCase().trim();
      table.querySelectorAll('tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
      });
    });
  });
}

/* ===== INIT ===== */
document.addEventListener('DOMContentLoaded', () => {
  initScrollAnimations();
  initRupiahInputs();
  initAlerts();
  initSidebar();
  initNavbar();
  initScrollToTop();
  initRipple();
  initChartDefaults();
  initTableSearch();

  // Stagger delay untuk stat cards
  document.querySelectorAll('.stat-card, .saldo-card').forEach((el, i) => {
    if (!el.style.animationDelay) el.style.animationDelay = `${i * 0.1}s`;
  });

  console.log(
    '%c 🕌 Kas Masjid Baiturrohman ',
    'background:#1a7a4a;color:#fff;padding:4px 12px;border-radius:4px;font-weight:700',
    '| Sistem Informasi Keuangan Berbasis Web'
  );
});
