(function () {
  'use strict';

  const state = { conversations: [], activeConversation: null, lastAudit: null };

  const $ = (selector) => document.querySelector(selector);

  function api(url, options = {}) {
    const opts = { ...options, headers: { ...(options.headers || {}), 'X-CSRF-Token': window.DEAFCONNECT.csrf } };
    if (opts.body && typeof opts.body !== 'string') {
      opts.headers['Content-Type'] = 'application/json';
      opts.body = JSON.stringify(opts.body);
    }
    return fetch(url, opts).then(async (response) => {
      const data = await response.json().catch(() => ({ success: false, message: 'Unexpected server response.' }));
      if (!response.ok || data.success === false) throw new Error(data.message || 'Request failed.');
      return data;
    });
  }

  function esc(value) {
    const div = document.createElement('div'); div.textContent = value ?? ''; return div.innerHTML;
  }

  function showAlert(type, message) {
    const box = $('#admin-alert');
    box.className = 'admin-alert show ' + type;
    box.textContent = message;
  }

  const serviceNames = { interpreter: 'BSL Interpreter', audiology: 'Audiology appointment', counselling: 'Deaf counselling session', community: 'Community event registration', relay: 'Relay service setup' };

  async function loadOverview() {
    const data = await api('../api/admin/stats.php');
    const c = data.counts;
    $('#metrics-grid').innerHTML = [
      ['Total bookings', c.bookings, 'ti-calendar-event'],
      ['Pending requests', c.pending, 'ti-clock'],
      ['Guest messages', c.messages, 'ti-message-circle'],
      ['Saved audits', c.audit, 'ti-shield-check']
    ].map(([label, value, icon]) => `<div class="metric-card"><span><i class="ti ${icon}"></i> ${label}</span><strong>${value}</strong></div>`).join('');
    $('#overview-bookings').innerHTML = data.bookings.length ? data.bookings.map((b) => `<div class="data-row"><div><strong>${esc(b.first_name)} ${esc(b.last_name)}</strong><span>${esc(serviceNames[b.service_type] || b.service_type)} · ${esc(b.preferred_date)}</span></div><span class="pill ${esc(b.status)}">${esc(b.status)}</span></div>`).join('') : '<p class="empty-state">No booking requests yet.</p>';
    $('#overview-audit').innerHTML = data.latest_audit ? `<div class="data-list"><div class="data-row"><div><strong>${data.latest_audit.baseline_score}/100</strong><span>Recorded checklist baseline</span></div><span>${esc(data.latest_audit.created_at)}</span></div><p class="empty-state">Live Lighthouse/axe results should be re-run on the deployed site before use in formal reporting.</p></div>` : '<p class="empty-state">No checklist baseline saved yet.</p>';
  }

  async function loadBookings() {
    const data = await api('../api/admin/bookings.php');
    const body = $('#bookings-body');
    body.innerHTML = data.bookings.length ? data.bookings.map((b) => `<tr><td><strong>${esc(b.first_name)} ${esc(b.last_name)}</strong></td><td>${esc(serviceNames[b.service_type] || b.service_type)}</td><td>${esc(b.preferred_date)}<br>${esc(b.preferred_time)}</td><td>${esc(b.email)}</td><td><select class="status-select" data-id="${b.id}"><option value="pending" ${b.status==='pending'?'selected':''}>Pending</option><option value="confirmed" ${b.status==='confirmed'?'selected':''}>Confirmed</option><option value="completed" ${b.status==='completed'?'selected':''}>Completed</option><option value="cancelled" ${b.status==='cancelled'?'selected':''}>Cancelled</option></select></td><td><button class="small-btn" data-notes="${esc(b.notes || '')}" title="View notes"><i class="ti ti-notes"></i></button></td></tr>`).join('') : '<tr><td colspan="6">No booking requests yet.</td></tr>';
    body.querySelectorAll('.status-select').forEach((select) => select.addEventListener('change', updateBooking));
    body.querySelectorAll('[data-notes]').forEach((button) => button.addEventListener('click', () => showAlert('success', button.dataset.notes || 'No additional notes were provided.')));
  }

  async function updateBooking(event) {
    try { await api('../api/admin/bookings.php', { method:'POST', body:{ id:Number(event.target.dataset.id), status:event.target.value } }); showAlert('success','Booking status updated.'); await loadOverview(); }
    catch (error) { showAlert('error', error.message); }
  }

  function renderConversations() {
    const container = $('#admin-conversations');
    container.innerHTML = state.conversations.length ? state.conversations.map((c) => `<button class="conversation ${state.activeConversation?.conversation_key === c.conversation_key ? 'active' : ''}" data-key="${esc(c.conversation_key)}" data-contact="${c.contact_id}"><strong>${esc(c.contact_name)}</strong><span>${esc(c.latest)}</span><span>${esc(c.latest_time)}</span></button>`).join('') : '<p class="empty-state">No guest conversations yet.</p>';
    container.querySelectorAll('.conversation').forEach((button) => button.addEventListener('click', () => selectConversation(button.dataset.key, Number(button.dataset.contact))));
  }

  function renderReplyThread() {
    const thread = $('#reply-thread');
    if (!state.activeConversation) { thread.innerHTML='<p class="empty-state">Select a conversation to view its messages.</p>'; return; }
    thread.innerHTML = [...state.activeConversation.messages].map((m) => `<div class="reply-bubble ${m.sender_type==='admin'?'admin':''}">${esc(m.body)}<div class="reply-time">${esc(m.sender_name)} · ${esc(m.created_at)}</div></div>`).join('');
    thread.scrollTop = thread.scrollHeight;
    $('#reply-title').textContent = state.activeConversation.contact_name;
  }

  function selectConversation(key, contactId) {
    state.activeConversation = state.conversations.find((c) => c.conversation_key === key && c.contact_id === contactId) || null;
    $('#reply-contact').value = contactId;
    $('#reply-guest').value = key;
    $('#reply-form').hidden = !state.activeConversation;
    renderConversations(); renderReplyThread();
  }

  async function loadAdminMessages() {
    const data = await api('../api/admin/messages.php');
    state.conversations = data.conversations;
    renderConversations();
    if (state.activeConversation) {
      const fresh = state.conversations.find((c) => c.conversation_key === state.activeConversation.conversation_key && c.contact_id === state.activeConversation.contact_id);
      state.activeConversation = fresh || null;
      $('#reply-form').hidden = !state.activeConversation; renderReplyThread();
    }
  }

  async function sendReply(event) {
    event.preventDefault();
    if (!state.activeConversation) return;
    const body = $('#reply-body').value.trim(); if (!body) return;
    const button = event.submitter; button.disabled = true;
    try { await api('../api/admin/reply.php', { method:'POST', body:{ contact_id:Number($('#reply-contact').value), conversation_key:$('#reply-guest').value, body } }); $('#reply-body').value=''; await loadAdminMessages(); showAlert('success','Reply sent and saved to the conversation.'); }
    catch(error){showAlert('error',error.message)} finally {button.disabled=false;}
  }

  function renderAudit(report) {
    state.lastAudit = report;
    $('#sc-lh').textContent = report.baseline_score;
    $('#sc-crit').textContent = report.critical_count;
    $('#sc-ser').textContent = report.serious_count;
    $('#sc-mod').textContent = report.moderate_count;
    $('#criteria-list').innerHTML = report.criteria.map((c) => `<div class="criterion"><span class="sc-pill">${esc(c[0])}</span><span class="cr-text">${esc(c[1])}</span><span class="cr-lvl">Level ${esc(c[2])}</span><span class="pass-pill">${c[3] ? 'PASS' : 'FAIL'}</span></div>`).join('');
    $('#vio-log').innerHTML = `<div>${report.violations.map((v) => `<div class="vio-row"><span class="sev-pill ${v[0]==='moderate'?'sev-moderate':'sev-none'}">${esc(v[0])}</span><span>${esc(v[1])} <small>(SC ${esc(v[2])})</small></span></div>`).join('')}</div>`;
    $('#export-audit').disabled = false;
  }

  async function loadLatestAudit() { try { const data=await api('../api/audit/latest.php'); if(data.report) renderAudit(data.report); } catch(error){ /* panel will remain empty */ } }

  async function runAudit() {
    const button=$('#run-audit'); button.disabled=true; button.innerHTML='<i class="ti ti-loader-2"></i> Saving checklist…';
    try { const data=await api('../api/audit/run.php',{method:'POST',body:{}}); renderAudit(data.report); showAlert('success','Accessibility checklist baseline saved. Verify the deployed site with Lighthouse and axe-DevTools before treating the score as a live measurement.'); await loadOverview(); }
    catch(error){showAlert('error',error.message)} finally {button.disabled=false;button.innerHTML='<i class="ti ti-player-play"></i> Run checklist';}
  }

  function activateSection(section) {
    document.querySelectorAll('.admin-section').forEach((el)=>el.classList.remove('active'));
    document.querySelectorAll('.side-nav').forEach((el)=>el.classList.remove('active'));
    $('#section-'+section).classList.add('active');
    document.querySelector(`.side-nav[data-section="${section}"]`)?.classList.add('active');
    $('#section-title').textContent = {overview:'Overview',bookings:'Bookings',messages:'Messages',audit:'Accessibility audit'}[section] || 'Overview';
    if(section==='overview')loadOverview();
    if(section==='bookings')loadBookings();
    if(section==='messages')loadAdminMessages();
    if(section==='audit')loadLatestAudit();
  }

  document.querySelectorAll('.side-nav').forEach((button)=>button.addEventListener('click',()=>activateSection(button.dataset.section)));
  document.querySelectorAll('[data-jump]').forEach((button)=>button.addEventListener('click',()=>activateSection(button.dataset.jump)));
  $('#reply-form')?.addEventListener('submit', sendReply);
  $('#run-audit')?.addEventListener('click', runAudit);
  $('#export-audit')?.addEventListener('click',()=>{window.location.href='../api/audit/export.php';});
  loadOverview().catch((error)=>showAlert('error',error.message));
})();
