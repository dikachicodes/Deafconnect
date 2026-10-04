(function () {
  'use strict';

  const state = {
    ccOn: true,
    playing: false,
    tsOpen: true,
    capIdx: 0,
    capTimer: null,
    contacts: [],
    activeContact: null
  };

  const captions = [
    'Welcome to DeafConnect — a platform built for Deaf and Hard-of-Hearing communities.',
    'Every video includes synchronised captions using the WebVTT open standard.',
    'You can read the full transcript without playing the video at all — no audio is required.',
    'Our booking system lets you schedule BSL interpreters, audiology appointments and community sessions.',
    'All notifications appear as colour-coded visual banners.',
    'Every interactive element is designed to remain operable using keyboard navigation.',
    'Thank you for using DeafConnect. Accessibility is built into every primary interaction.'
  ];

  function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
  }

  window.showPage = function (id) {
    document.querySelectorAll('.page').forEach((page) => page.classList.remove('active'));
    document.querySelectorAll('.nav-link').forEach((button) => button.classList.remove('active'));
    const page = document.getElementById('page-' + id);
    const nav = document.getElementById('nav-' + id);
    if (page) page.classList.add('active');
    if (nav) nav.classList.add('active');
    dismissAlert();
    window.scrollTo({ top: 0, behavior: 'smooth' });
    if (id === 'messaging') loadContacts();
  };

  window.showAlert = function (type, message) {
    const banner = document.getElementById('alert-banner');
    if (!banner) return;
    const icon = { success: '✓', error: '×', warning: '!' }[type] || 'i';
    banner.className = type;
    document.getElementById('alert-icon').textContent = icon;
    document.getElementById('alert-msg').textContent = message;
    banner.style.display = 'flex';
  };

  window.dismissAlert = function () {
    const banner = document.getElementById('alert-banner');
    if (!banner) return;
    banner.style.display = 'none';
    banner.className = '';
  };

  async function api(url, options = {}) {
    const opts = { ...options, headers: { ...(options.headers || {}), 'X-CSRF-Token': window.DEAFCONNECT.csrf } };
    if (opts.body && typeof opts.body !== 'string') {
      opts.headers['Content-Type'] = 'application/json';
      opts.body = JSON.stringify(opts.body);
    }
    const response = await fetch(url, opts);
    const data = await response.json().catch(() => ({ success: false, message: 'Unexpected server response.' }));
    if (!response.ok || data.success === false) throw new Error(data.message || 'Request failed.');
    return data;
  }

  function renderMessages(messages) {
    const thread = document.getElementById('msg-thread');
    thread.innerHTML = messages.map((message) => {
      const me = message.sender_type === 'guest';
      return `<div class="message-row ${me ? 'me' : ''}"><div class="bubble ${me ? 'me' : 'them'}">${escapeHtml(message.body)}</div><div class="bub-time ${me ? 'right' : ''}">${escapeHtml(message.sender_name)} · ${escapeHtml(message.display_time)}</div></div>`;
    }).join('') || '<p class="empty-state">No messages yet. Start the conversation below.</p>';
    thread.scrollTop = thread.scrollHeight;
  }

  function renderContacts() {
    const list = document.getElementById('contacts-list');
    list.innerHTML = state.contacts.map((contact, index) => `
      <button class="contact-item ${state.activeContact?.id === contact.id ? 'active' : ''}" data-contact-id="${contact.id}" aria-pressed="${state.activeContact?.id === contact.id}">
        <span class="avatar">${escapeHtml(contact.initials)}</span>
        <span class="contact-info"><span class="contact-name">${escapeHtml(contact.name)}</span><span class="contact-preview">${escapeHtml(contact.preview || 'Text-based support')}</span></span>
        ${contact.unread ? `<span class="unread">${contact.unread}</span>` : ''}
      </button>`).join('');
    list.querySelectorAll('.contact-item').forEach((button) => {
      button.addEventListener('click', () => selectContact(Number(button.dataset.contactId)));
    });
  }

  async function loadContacts() {
    try {
      const data = await api('api/messages/contacts.php');
      state.contacts = data.contacts;
      if (!state.activeContact && state.contacts.length) state.activeContact = state.contacts[0];
      renderContacts();
      if (state.activeContact) await loadConversation(state.activeContact.id);
    } catch (error) {
      document.getElementById('contacts-list').innerHTML = '<p class="empty-state">Unable to load contacts. Make sure MySQL is running and the database is imported.</p>';
    }
  }
  window.loadContacts = loadContacts;

  async function selectContact(id) {
    state.activeContact = state.contacts.find((contact) => contact.id === id) || null;
    renderContacts();
    if (!state.activeContact) return;
    document.getElementById('th-name').textContent = state.activeContact.name;
    document.getElementById('th-av').textContent = state.activeContact.initials;
    await loadConversation(id);
  }

  async function loadConversation(contactId) {
    try {
      const data = await api(`api/messages/list.php?contact_id=${encodeURIComponent(contactId)}`);
      renderMessages(data.messages);
    } catch (error) {
      showAlert('error', error.message);
    }
  }

  async function sendMessage() {
    const input = document.getElementById('msg-input');
    const nameInput = document.getElementById('msg-name');
    const body = input.value.trim();
    const name = nameInput.value.trim() || 'Guest';
    if (!body) { showAlert('warning', 'Please type a message before sending.'); return; }
    if (!state.activeContact) { showAlert('warning', 'Select a contact first.'); return; }
    const button = document.getElementById('send-msg');
    button.disabled = true;
    try {
      const data = await api('api/messages/send.php', { method: 'POST', body: { contact_id: state.activeContact.id, sender_name: name, body } });
      input.value = '';
      renderMessages(data.messages);
      showAlert('success', 'Message saved. Your conversation is text-based and no audio notification is used.');
    } catch (error) {
      showAlert('error', error.message);
    } finally { button.disabled = false; input.focus(); }
  }

  function setFieldError(id, show) {
    const input = document.getElementById(id);
    const error = document.getElementById('err-' + id.replace('b-', ''));
    if (!input || !error) return;
    error.classList.toggle('show', show);
    input.setAttribute('aria-invalid', show ? 'true' : 'false');
  }

  async function submitBooking(event) {
    event.preventDefault();
    const form = event.currentTarget;
    const values = Object.fromEntries(new FormData(form).entries());
    let valid = true;
    ['b-first','b-last','b-email','b-service','b-date','b-time'].forEach((id) => {
      const value = document.getElementById(id).value.trim();
      let ok = value.length > 0;
      if (id === 'b-email') ok = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
      setFieldError(id, !ok);
      valid = valid && ok;
    });
    if (!valid) { showAlert('error', 'Some required fields need attention.'); return; }

    const button = document.getElementById('booking-submit');
    button.disabled = true;
    button.innerHTML = '<i class="ti ti-loader-2"></i> Saving request…';
    try {
      await api('api/booking/create.php', { method: 'POST', body: values });
      form.reset();
      document.querySelectorAll('.field-err').forEach((el) => el.classList.remove('show'));
      document.querySelectorAll('[aria-invalid]').forEach((el) => el.removeAttribute('aria-invalid'));
      showAlert('success', 'Booking request received. We will follow up visually/textually within 24 hours.');
    } catch (error) { showAlert('error', error.message); }
    finally { button.disabled = false; button.innerHTML = '<i class="ti ti-send"></i> Submit booking request'; }
  }

  window.togglePlay = function () {
    state.playing = !state.playing;
    const btn = document.getElementById('play-btn');
    btn.innerHTML = state.playing ? '<i class="ti ti-player-pause"></i> Pause' : '<i class="ti ti-player-play"></i> Play';
    btn.setAttribute('aria-pressed', state.playing ? 'true' : 'false');
    btn.setAttribute('aria-label', state.playing ? 'Pause video' : 'Play video');
    if (state.playing) advanceCaption(); else clearTimeout(state.capTimer);
  };
  function advanceCaption() {
    if (!state.playing) return;
    if (state.capIdx >= captions.length) {
      state.capIdx = 0; state.playing = false;
      document.getElementById('play-btn').innerHTML = '<i class="ti ti-player-play"></i> Play';
      document.getElementById('play-btn').setAttribute('aria-pressed', 'false');
      document.getElementById('caption-display').textContent = 'Media ended. Press Play to replay.';
      return;
    }
    if (state.ccOn) document.getElementById('caption-display').textContent = captions[state.capIdx];
    state.capIdx += 1;
    state.capTimer = setTimeout(advanceCaption, 3500);
  }
  window.toggleCC = function () {
    state.ccOn = !state.ccOn;
    const button = document.getElementById('cc-btn');
    const status = document.getElementById('cc-status');
    const display = document.getElementById('caption-display');
    button.classList.toggle('active', state.ccOn);
    button.innerHTML = `<i class="ti ti-subtitles"></i> ${state.ccOn ? 'CC On' : 'CC Off'}`;
    button.setAttribute('aria-pressed', state.ccOn ? 'true' : 'false');
    status.innerHTML = state.ccOn ? '<i class="ti ti-circle-check"></i> Caption display active' : '<i class="ti ti-circle-x"></i> Captions hidden';
    display.style.display = state.ccOn ? 'block' : 'none';
  };
  window.toggleTranscript = function () {
    state.tsOpen = !state.tsOpen;
    document.getElementById('ts-body').classList.toggle('closed', !state.tsOpen);
    document.getElementById('ts-chev').style.transform = state.tsOpen ? '' : 'rotate(-90deg)';
    document.getElementById('ts-toggle').setAttribute('aria-expanded', state.tsOpen ? 'true' : 'false');
  };

  document.addEventListener('keydown', (event) => { if (event.key === 'Escape') dismissAlert(); });
  document.getElementById('booking-form')?.addEventListener('submit', submitBooking);
  document.getElementById('send-msg')?.addEventListener('click', sendMessage);
  document.getElementById('msg-input')?.addEventListener('keydown', (event) => { if (event.key === 'Enter') { event.preventDefault(); sendMessage(); } });
  document.getElementById('msg-name')?.addEventListener('input', (event) => localStorage.setItem('deafconnect_guest_name', event.target.value));
  const savedName = localStorage.getItem('deafconnect_guest_name');
  if (savedName) document.getElementById('msg-name').value = savedName;
  document.getElementById('b-date')?.setAttribute('min', new Date().toISOString().slice(0, 10));
})();
