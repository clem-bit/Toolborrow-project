
const API = '/toolborrow/api';

async function apiFetch(path, options = {}) {
  const token = localStorage.getItem('tb_token');
  let url = API + path;

  const res = await fetch(url, {
    method: options.method ?? 'GET',
    headers: {
      'Content-Type': 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
    },
    body: options.body ? JSON.stringify(options.body) : undefined,
  });

  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.error ?? `HTTP ${res.status}`);
  return data;
}


function getUser()  { try { return JSON.parse(localStorage.getItem('tb_user')); } catch { return null; } }
function getToken() { return localStorage.getItem('tb_token'); }
function isLoggedIn() { return !!getToken() && !!getUser(); }

function setSession(token, user) {
  localStorage.setItem('tb_token', token);
  localStorage.setItem('tb_user', JSON.stringify(user));
}

function clearSession() {
  localStorage.removeItem('tb_token');
  localStorage.removeItem('tb_user');
}

function logout() {
  clearSession();
  window.location.href = '/toolborrow/index.html';
}


function requireAuth() {
  if (!isLoggedIn()) { window.location.href = '/toolborrow/index.html'; return false; }
  return true;
}

function requireAdmin() {
  if (!requireAuth()) return false;
  if (getUser()?.role !== 'admin') { window.location.href = '/toolborrow/user.html'; return false; }
  return true;
}

function requireUser() {
  if (!requireAuth()) return false;
  if (getUser()?.role === 'admin') { window.location.href = '/toolborrow/admin.html'; return false; }
  return true;
}


function showToast(message, type = 'success') {
  document.querySelectorAll('.tb-toast').forEach(t => t.remove());
  const t = document.createElement('div');
  t.className = `tb-toast tb-toast--${type}`;
  t.innerHTML = `<span class="tb-toast__icon">${type === 'error' ? '✕' : '✓'}</span><span>${esc(message)}</span>`;
  document.body.appendChild(t);
  requestAnimationFrame(() => t.classList.add('tb-toast--show'));
  setTimeout(() => { t.classList.remove('tb-toast--show'); setTimeout(() => t.remove(), 320); }, 3800);
}


function openModal(id) {
  const m = document.getElementById(id);
  if (m) { m.classList.add('modal--open'); document.body.style.overflow = 'hidden'; }
}
function closeModal(id) {
  const m = document.getElementById(id);
  if (m) { m.classList.remove('modal--open'); document.body.style.overflow = ''; }
}
document.addEventListener('click', e => {
  if (e.target.classList.contains('modal__backdrop')) {
    e.target.closest('.modal')?.classList.remove('modal--open');
    document.body.style.overflow = '';
  }
});
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal--open').forEach(m => {
      m.classList.remove('modal--open');
      document.body.style.overflow = '';
    });
  }
});


function fmtDate(d) {
  if (!d) return '—';
  return new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
}
function fmtDateTime(d) {
  if (!d) return '—';
  return new Date(d).toLocaleString('en-GB', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}
function todayPlus(days) {
  const d = new Date(); d.setDate(d.getDate() + days);
  return d.toISOString().split('T')[0];
}
function tomorrow() { return todayPlus(1); }


function esc(str) {
  if (str === null || str === undefined) return '';
  const d = document.createElement('div');
  d.textContent = String(str);
  return d.innerHTML;
}
