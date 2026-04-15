
let _tools      = [];
let _loans      = [];
let _categories = [];
let _rentToolId = null;
let _searchTimer = null;


document.addEventListener('DOMContentLoaded', async () => {
  if (!requireUser()) return;

  const u = getUser();
  document.getElementById('topName').textContent    = u.name;
  document.getElementById('topInitial').textContent = u.name.charAt(0).toUpperCase();

  await loadCategories();
  await Promise.all([loadTools(), loadMyLoans()]);
  showTab('browse');
});


async function loadCategories() {
  try {
    _categories = await apiFetch('/categories');
    const sel = document.getElementById('catFilter');
    sel.innerHTML = '<option value="">All Categories</option>' +
      _categories.map(c => `<option value="${c.id}">${esc(c.icon)} ${esc(c.name)}</option>`).join('');
  } catch (e) {}
}


async function loadTools() {
  try {
    const params = new URLSearchParams();
    const q    = document.getElementById('searchInput')?.value.trim();
    const cat  = document.getElementById('catFilter')?.value;
    const cond = document.getElementById('condFilter')?.value;
    if (q)    params.set('name', q);
    if (cat)  params.set('category_id', cat);
    if (cond) params.set('condition_rating', cond);

    _tools = await apiFetch('/tools?' + params.toString());
    renderGrid();
  } catch (e) { showToast('Failed to load equipment: ' + e.message, 'error'); }
}

function renderGrid() {
  const grid = document.getElementById('toolGrid');
  if (!_tools.length) {
    grid.innerHTML = '<div class="grid__empty">No equipment matches your search</div>';
    return;
  }

  const activeCount = _loans.filter(l => l.status !== 'returned').length;
  const limitReached = activeCount >= 3;

  grid.innerHTML = _tools.map(t => {
    const avail = t.status === 'available' && t.quantity_available > 0;
    const canRent = avail && !limitReached;
    return `
      <div class="tool-card ${avail ? 'tool-card--available' : 'tool-card--unavailable'} ${canRent ? 'tool-card--clickable' : ''}"
           ${canRent ? `onclick="openRentModal(${t.id},'${esc(t.name)}')"` : ''}>
        <div class="tool-card__icon">${esc(t.category_icon ?? '🔧')}</div>
        <div class="tool-card__name">${esc(t.name)}</div>
        <div class="tool-card__cat">${esc(t.category_name ?? '')}</div>
        <div class="tool-card__desc">${esc(t.description ?? '')}</div>
        <div class="tool-card__footer">
          <span class="tool-card__qty">${t.quantity_available}/${t.quantity} available</span>
          <span class="pill ${avail ? 'pill--green' : t.status === 'maintenance' ? 'pill--blue' : 'pill--red'}">
            ${t.status === 'maintenance' ? '🔧 Maintenance' : avail ? '✓ Available' : '✗ Unavailable'}
          </span>
        </div>
      </div>`;
  }).join('');
}


function handleSearch() {
  clearTimeout(_searchTimer);
  _searchTimer = setTimeout(loadTools, 300);
}


function openRentModal(toolId, toolName) {
  _rentToolId = toolId;
  document.getElementById('rentToolName').textContent = toolName;
  document.getElementById('rentDue').value = todayPlus(7);
  document.getElementById('rentDue').min   = tomorrow();
  document.getElementById('rentNotes').value = '';
  openModal('rentModal');
}

async function confirmRent() {
  const due   = document.getElementById('rentDue').value;
  const notes = document.getElementById('rentNotes').value.trim();
  if (!due) { showToast('Please select a return due date', 'error'); return; }

  try {
    await apiFetch('/loans', { method: 'POST', body: { tool_id: _rentToolId, due_date: due, notes } });
    showToast('Equipment rented successfully!');
    closeModal('rentModal');
    await Promise.all([loadTools(), loadMyLoans()]);
    showTab('rentals');
  } catch (e) { showToast(e.message, 'error'); }
}


async function loadMyLoans() {
  try {
    _loans = await apiFetch('/loans');
    renderMyLoans();
    updateLimitBar();
  } catch (e) {}
}

function renderMyLoans() {
  const active  = _loans.filter(l => l.status !== 'returned');
  const history = _loans.filter(l => l.status === 'returned');


  const ab = document.getElementById('activeBody');
  if (!active.length) {
    ab.innerHTML = '<tr><td colspan="6" class="tbl__empty">You have no active rentals</td></tr>';
  } else {
    ab.innerHTML = active.map(l => `
      <tr class="${l.status === 'overdue' ? 'tbl__row--alert' : ''}">
        <td>${esc(l.tool_name)}</td>
        <td>${esc(l.category_name ?? '—')}</td>
        <td>${fmtDate(l.borrowed_at)}</td>
        <td>${fmtDate(l.due_date)}</td>
        <td><span class="pill ${l.status === 'overdue' ? 'pill--red' : 'pill--green'}">${esc(l.status)}</span></td>
        <td><button class="btn btn--sm btn--primary" onclick="returnTool(${l.id},'${esc(l.tool_name)}')">Return</button></td>
      </tr>`).join('');
  }

  
  const hb = document.getElementById('historyBody');
  if (!history.length) {
    hb.innerHTML = '<tr><td colspan="5" class="tbl__empty">No rental history yet</td></tr>';
  } else {
    hb.innerHTML = history.map(l => `
      <tr>
        <td>${esc(l.tool_name)}</td>
        <td>${fmtDate(l.borrowed_at)}</td>
        <td>${fmtDate(l.due_date)}</td>
        <td>${fmtDate(l.returned_at)}</td>
        <td><span class="pill pill--grey">returned</span></td>
      </tr>`).join('');
  }

  
  document.getElementById('activeCount').textContent = active.length;
}

function updateLimitBar() {
  const active  = _loans.filter(l => l.status !== 'returned').length;
  const bar     = document.getElementById('limitFill');
  const label   = document.getElementById('limitLabel');
  const pct     = Math.min(100, Math.round((active / 3) * 100));
  if (bar)   { bar.style.width = pct + '%'; bar.className = `limit__fill ${active >= 3 ? 'limit__fill--full' : active >= 2 ? 'limit__fill--warn' : ''}`; }
  if (label) label.textContent = `${active} of 3 rentals used`;
}


async function returnTool(loanId, toolName) {
  if (!confirm(`Return "${toolName}"?`)) return;
  try {
    await apiFetch(`/loans/${loanId}/return`, { method: 'PUT' });
    showToast(`"${toolName}" returned successfully`);
    await Promise.all([loadTools(), loadMyLoans()]);
  } catch (e) { showToast(e.message, 'error'); }
}


function showTab(name) {
  document.querySelectorAll('.tab__content').forEach(t => t.classList.remove('tab__content--active'));
  document.querySelectorAll('.tab__btn').forEach(t => t.classList.remove('tab__btn--active'));
  document.getElementById(`tab-${name}`)?.classList.add('tab__content--active');
  document.querySelector(`[data-tab="${name}"]`)?.classList.add('tab__btn--active');
}
