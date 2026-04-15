
let _tools      = [];
let _users      = [];
let _loans      = [];
let _categories = [];
let _editToolId = null;
let _editUserId = null;


document.addEventListener('DOMContentLoaded', async () => {
  if (!requireAdmin()) return;

  const u = getUser();
  document.getElementById('sidebarName').textContent  = u.name;
  document.getElementById('sidebarEmail').textContent = u.email;
  document.getElementById('sidebarInitial').textContent = u.name.charAt(0).toUpperCase();

  await loadCategories();
  showSection('dashboard');
});


async function showSection(name) {
  document.querySelectorAll('.section').forEach(s => s.classList.remove('section--active'));
  document.querySelectorAll('.nav__item').forEach(n => n.classList.remove('nav__item--active'));

  const sec = document.getElementById(`sec-${name}`);
  const nav = document.querySelector(`[data-section="${name}"]`);
  if (sec) sec.classList.add('section--active');
  if (nav) nav.classList.add('nav__item--active');

  const titles = { dashboard: 'Dashboard', equipment: 'Equipment Management', users: 'User Management', loans: 'All Loans', overdue: 'Overdue Returns' };
  document.getElementById('pageTitle').textContent = titles[name] ?? name;

  
  if (name === 'dashboard') await loadDashboard();
  if (name === 'equipment') await loadTools();
  if (name === 'users')     await loadUsers();
  if (name === 'loans')     await loadLoans();
  if (name === 'overdue')   await loadOverdue();

  
  document.getElementById('sidebar').classList.remove('sidebar--open');
}


async function loadDashboard() {
  try {
    const [tools, users, loans] = await Promise.all([
      apiFetch('/tools'),
      apiFetch('/users'),
      apiFetch('/loans'),
    ]);

    const available   = tools.filter(t => t.status === 'available').length;
    const onLoan      = tools.filter(t => t.status === 'borrowed').length;
    const maintenance = tools.filter(t => t.status === 'maintenance').length;
    const overdue     = loans.filter(l => l.status === 'overdue').length;
    const active      = loans.filter(l => l.status === 'active').length;

    setText('stat-total',       tools.length);
    setText('stat-available',   available);
    setText('stat-on-loan',     active + onLoan);
    setText('stat-overdue',     overdue);
    setText('stat-maintenance', maintenance);
    setText('stat-users',       users.length);

    
    const recent = loans.filter(l => l.status !== 'returned').slice(0, 8);
    const tbody  = document.getElementById('recentLoansBody');
    if (!recent.length) {
      tbody.innerHTML = '<tr><td colspan="6" class="tbl__empty">No active loans</td></tr>';
    } else {
      tbody.innerHTML = recent.map(l => `
        <tr class="${l.status === 'overdue' ? 'tbl__row--alert' : ''}">
          <td><span class="tbl__id">#${pad(l.id)}</span></td>
          <td>${esc(l.user_name)}</td>
          <td>${esc(l.tool_name)}</td>
          <td>${fmtDate(l.due_date)}</td>
          <td>${statusBadge(l.status)}</td>
          <td>${l.status !== 'returned' ? `<button class="btn btn--xs btn--ghost" onclick="quickReturn(${l.id})">Return</button>` : ''}</td>
        </tr>`).join('');
    }
  } catch (e) { showToast('Failed to load dashboard: ' + e.message, 'error'); }
}

async function quickReturn(id) {
  try {
    await apiFetch(`/loans/${id}/return`, { method: 'PUT' });
    showToast('Marked as returned');
    loadDashboard();
  } catch (e) { showToast(e.message, 'error'); }
}


async function loadCategories() {
  try {
    _categories = await apiFetch('/categories');
    const opts = '<option value="">— Select category —</option>' +
      _categories.map(c => `<option value="${c.id}">${esc(c.icon)} ${esc(c.name)}</option>`).join('');
    document.querySelectorAll('.js-cat-select').forEach(s => s.innerHTML = opts);
  } catch (e) {}
}


async function loadTools() {
  try {
    _tools = await apiFetch('/tools');
    renderToolsTable();
  } catch (e) { showToast('Failed to load equipment: ' + e.message, 'error'); }
}

function renderToolsTable() {
  const q     = (document.getElementById('toolSearch')?.value ?? '').toLowerCase();
  const catId = document.getElementById('toolCatFilter')?.value ?? '';
  const cond  = document.getElementById('toolCondFilter')?.value ?? '';
  const tbody = document.getElementById('toolsBody');

  let rows = _tools;
  if (q)     rows = rows.filter(t => t.name.toLowerCase().includes(q) || (t.description ?? '').toLowerCase().includes(q) || (t.serial_number ?? '').toLowerCase().includes(q));
  if (catId) rows = rows.filter(t => String(t.category_id) === catId);
  if (cond)  rows = rows.filter(t => t.condition_rating === cond);

  if (!rows.length) { tbody.innerHTML = '<tr><td colspan="8" class="tbl__empty">No equipment found</td></tr>'; return; }

  tbody.innerHTML = rows.map(t => `
    <tr>
      <td><span class="tbl__icon">${esc(t.category_icon ?? '🔧')}</span>${esc(t.name)}</td>
      <td class="text--mono text--sm">${esc(t.serial_number ?? '—')}</td>
      <td>${esc(t.category_name ?? '—')}</td>
      <td class="tbl__desc">${esc(t.description ?? '—')}</td>
      <td><span class="qty">${t.quantity_available}/${t.quantity}</span></td>
      <td>${condBadge(t.condition_rating)}</td>
      <td>${statusBadge(t.status)}</td>
      <td>
        <div class="tbl__actions">
          <button class="btn btn--xs btn--outline" onclick="openEditTool(${t.id})">Edit</button>
          <button class="btn btn--xs btn--danger"  onclick="deleteTool(${t.id},'${esc(t.name)}')">Delete</button>
        </div>
      </td>
    </tr>`).join('');
}

function openAddTool() {
  _editToolId = null;
  document.getElementById('toolModalTitle').textContent = 'Add Equipment';
  document.getElementById('toolForm').reset();
  document.getElementById('toolQty').value = 1;
  document.getElementById('toolSerial').value = '';
  document.getElementById('toolStatusGroup').style.display = 'none';
  openModal('toolModal');
}

function openEditTool(id) {
  _editToolId = id;
  const t = _tools.find(x => x.id === id);
  if (!t) return;
  document.getElementById('toolModalTitle').textContent = 'Edit Equipment';
  document.getElementById('toolName').value        = t.name;
  document.getElementById('toolCat').value         = t.category_id ?? '';
  document.getElementById('toolSerial').value      = t.serial_number ?? '';
  document.getElementById('toolDesc').value        = t.description ?? '';
  document.getElementById('toolQty').value         = t.quantity;
  document.getElementById('toolCond').value        = t.condition_rating ?? 'good';
  document.getElementById('toolStatusSel').value   = t.status;
  document.getElementById('toolStatusGroup').style.display = '';
  openModal('toolModal');
}

async function saveTool() {
  const body = {
    name:             document.getElementById('toolName').value.trim(),
    category_id:      document.getElementById('toolCat').value || null,
    serial_number:    document.getElementById('toolSerial').value.trim() || null,
    description:      document.getElementById('toolDesc').value.trim(),
    quantity:         parseInt(document.getElementById('toolQty').value) || 1,
    condition_rating: document.getElementById('toolCond').value,
  };
  if (_editToolId) body.status = document.getElementById('toolStatusSel').value;
  if (!body.name) { showToast('Equipment name is required', 'error'); return; }

  try {
    if (_editToolId) {
      await apiFetch(`/tools/${_editToolId}`, { method: 'PUT', body });
      showToast('Equipment updated');
    } else {
      await apiFetch('/tools', { method: 'POST', body });
      showToast('Equipment added');
    }
    closeModal('toolModal');
    await loadTools();
  } catch (e) { showToast(e.message, 'error'); }
}

async function deleteTool(id, name) {
  if (!confirm(`Delete "${name}"? This cannot be undone.`)) return;
  try {
    await apiFetch(`/tools/${id}`, { method: 'DELETE' });
    showToast(`"${name}" deleted`);
    await loadTools();
  } catch (e) { showToast(e.message, 'error'); }
}


async function loadUsers() {
  try {
    _users = await apiFetch('/users');
    renderUsersTable();
  } catch (e) { showToast('Failed to load users: ' + e.message, 'error'); }
}

function renderUsersTable() {
  const q     = (document.getElementById('userSearch')?.value ?? '').toLowerCase();
  const tbody = document.getElementById('usersBody');
  const me    = getUser();

  let rows = _users;
  if (q) rows = rows.filter(u => u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q));

  if (!rows.length) { tbody.innerHTML = '<tr><td colspan="5" class="tbl__empty">No users found</td></tr>'; return; }

  tbody.innerHTML = rows.map(u => `
    <tr>
      <td>
        <div class="user-cell">
          <span class="user-cell__initial">${esc(u.name.charAt(0).toUpperCase())}</span>
          <span>${esc(u.name)}</span>
        </div>
      </td>
      <td>${esc(u.email)}</td>
      <td>${roleBadge(u.role)}</td>
      <td>${fmtDate(u.created_at)}</td>
      <td>
        <div class="tbl__actions">
          <button class="btn btn--xs btn--outline" onclick="openEditUser(${u.id})">Edit</button>
          ${u.id != me.id
            ? `<button class="btn btn--xs btn--danger" onclick="deleteUser(${u.id},'${esc(u.name)}')">Delete</button>`
            : '<span class="tbl__you">you</span>'}
        </div>
      </td>
    </tr>`).join('');
}

function openAddUser() {
  _editUserId = null;
  document.getElementById('userModalTitle').textContent = 'Add User';
  document.getElementById('userForm').reset();
  document.getElementById('userPwLabel').textContent = 'Password *';
  openModal('userModal');
}

function openEditUser(id) {
  _editUserId = id;
  const u = _users.find(x => x.id === id);
  if (!u) return;
  document.getElementById('userModalTitle').textContent = 'Edit User';
  document.getElementById('uName').value  = u.name;
  document.getElementById('uEmail').value = u.email;
  document.getElementById('uRole').value  = u.role;
  document.getElementById('uPw').value    = '';
  document.getElementById('userPwLabel').textContent = 'New Password (leave blank to keep current)';
  openModal('userModal');
}

async function saveUser() {
  const body = {
    name:  document.getElementById('uName').value.trim(),
    email: document.getElementById('uEmail').value.trim(),
    role:  document.getElementById('uRole').value,
  };
  const pw = document.getElementById('uPw').value;
  if (!_editUserId && !pw)  { showToast('Password is required for new users', 'error'); return; }
  if (pw) body.password = pw;
  if (!body.name || !body.email) { showToast('Name and email are required', 'error'); return; }

  try {
    if (_editUserId) {
      await apiFetch(`/users/${_editUserId}`, { method: 'PUT', body });
      showToast('User updated');
    } else {
      await apiFetch('/users', { method: 'POST', body });
      showToast('User created');
    }
    closeModal('userModal');
    await loadUsers();
  } catch (e) { showToast(e.message, 'error'); }
}

async function deleteUser(id, name) {
  if (!confirm(`Delete user "${name}"? This cannot be undone.`)) return;
  try {
    await apiFetch(`/users/${id}`, { method: 'DELETE' });
    showToast(`User "${name}" deleted`);
    await loadUsers();
  } catch (e) { showToast(e.message, 'error'); }
}


async function loadLoans() {
  try {
    _loans = await apiFetch('/loans');
    const active = _loans.filter(l => l.status !== 'returned');
    const tbody  = document.getElementById('loansBody');
    if (!active.length) { tbody.innerHTML = '<tr><td colspan="7" class="tbl__empty">No active loans</td></tr>'; return; }
    tbody.innerHTML = active.map(l => `
      <tr class="${l.status === 'overdue' ? 'tbl__row--alert' : ''}">
        <td><span class="tbl__id">#${pad(l.id)}</span></td>
        <td>${esc(l.user_name)}<br><small class="text--muted">${esc(l.user_email)}</small></td>
        <td>${esc(l.tool_name)}</td>
        <td>${fmtDate(l.borrowed_at)}</td>
        <td>${fmtDate(l.due_date)}</td>
        <td>${statusBadge(l.status)}</td>
        <td><button class="btn btn--xs btn--outline" onclick="adminReturn(${l.id})">Mark Returned</button></td>
      </tr>`).join('');
  } catch (e) { showToast('Failed to load loans: ' + e.message, 'error'); }
}

async function loadOverdue() {
  try {
    const data  = await apiFetch('/loans/overdue');
    const tbody = document.getElementById('overdueBody');
    if (!data.length) { tbody.innerHTML = '<tr><td colspan="6" class="tbl__empty">No overdue loans 🎉</td></tr>'; return; }
    tbody.innerHTML = data.map(l => `
      <tr class="tbl__row--alert">
        <td><span class="tbl__id">#${pad(l.id)}</span></td>
        <td>${esc(l.user_name)}<br><small class="text--muted">${esc(l.user_email)}</small></td>
        <td>${esc(l.tool_name)}</td>
        <td>${fmtDate(l.due_date)}</td>
        <td>${daysOverdue(l.due_date)} days overdue</td>
        <td><button class="btn btn--xs btn--primary" onclick="adminReturn(${l.id})">Mark Returned</button></td>
      </tr>`).join('');
  } catch (e) { showToast('Failed to load overdue loans: ' + e.message, 'error'); }
}

async function adminReturn(id) {
  try {
    await apiFetch(`/loans/${id}/return`, { method: 'PUT' });
    showToast('Marked as returned');
    loadLoans(); loadOverdue(); loadDashboard();
  } catch (e) { showToast(e.message, 'error'); }
}


let _searchTimer = null;
function debounceSearch(fn) {
  clearTimeout(_searchTimer);
  _searchTimer = setTimeout(fn, 280);
}


function pad(n) { return String(n).padStart(3, '0'); }
function setText(id, val) { const el = document.getElementById(id); if (el) el.textContent = val; }

function daysOverdue(date) {
  const diff = Date.now() - new Date(date).getTime();
  return Math.max(0, Math.floor(diff / 86400000));
}

function statusBadge(s) {
  const map = { available: 'badge--green', borrowed: 'badge--orange', maintenance: 'badge--blue', active: 'badge--green', returned: 'badge--grey', overdue: 'badge--red' };
  return `<span class="badge ${map[s] ?? ''}">${esc(s)}</span>`;
}

function condBadge(c) {
  const map = { excellent: 'badge--green', good: 'badge--blue', fair: 'badge--orange', poor: 'badge--red' };
  return `<span class="badge ${map[c] ?? ''}">${esc(c ?? 'good')}</span>`;
}

function roleBadge(r) {
  return `<span class="badge ${r === 'admin' ? 'badge--red' : 'badge--blue'}">${esc(r)}</span>`;
}
