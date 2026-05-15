'use strict';

const API = 'api/auth.php';

// State
let _customer = null;
let _bookings = [];
let _promotions = [];

// ===== INIT =====
(async function init() {
  try {
    const r = await fetch(API + '?action=me', { credentials: 'include' });
    if (!r.ok) throw new Error('HTTP ' + r.status);
    const d = await r.json();

    if (!d.authenticated) {
      window.location.replace('login.html');
      return;
    }

    _customer   = d.customer;
    _bookings   = (d.bookings   || []).sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
    _promotions = d.promotions  || [];

    renderProfile(d);
  } catch (err) {
    console.error('Failed to load profile:', err);
    window.location.replace('login.html');
    return;
  }

  // Hide loading overlay
  const loader = document.getElementById('pageLoading');
  if (loader) {
    loader.classList.add('hidden');
    setTimeout(() => loader.remove(), 600);
  }
})();

// ===== RENDER PROFILE =====
function renderProfile(data) {
  const c = data.customer;

  // Sidebar
  const initials = getInitials(c.first_name, c.last_name);
  const el = id => document.getElementById(id);

  el('sidebarAvatar').textContent = initials;
  el('sidebarName').textContent   = [c.last_name, c.first_name].filter(Boolean).join(' ') || c.email;
  el('sidebarEmail').textContent  = c.email;
  el('navUsername').textContent   = c.first_name || c.email.split('@')[0];

  if (c.vip == 1 || c.vip === true || c.vip === '1') {
    el('vipBadge').style.display = 'inline-flex';
  }

  // Booking count badge
  if (_bookings.length > 0) {
    const bc = el('bookingCount');
    bc.textContent = _bookings.length;
    bc.style.display = 'inline-block';
  }

  // Promo count badge
  if (_promotions.length > 0) {
    const pc = el('promoCount');
    pc.textContent = _promotions.length;
    pc.style.display = 'inline-block';
  }

  // Fill profile form
  el('fLastName').value    = c.last_name   || '';
  el('fFirstName').value   = c.first_name  || '';
  el('fEmail').value       = c.email       || '';
  el('fPhone').value       = c.phone       || '';
  const natSelect = el('fNationality');
  if (natSelect) {
    const opt = natSelect.querySelector(`option[value="${c.nationality}"]`);
    if (opt) opt.selected = true;
  }

  // Render sub-sections
  renderBookings(_bookings);
  renderPromotions(_promotions);
}

function getInitials(first, last) {
  const parts = [last, first].filter(Boolean);
  if (!parts.length) return 'MT';
  return parts.map(p => p.charAt(0).toUpperCase()).join('').slice(0, 2);
}

// ===== RENDER BOOKINGS =====
function renderBookings(bookings) {
  const container = document.getElementById('bookingsList');
  if (!container) return;

  if (!bookings || bookings.length === 0) {
    container.innerHTML = `
      <div class="empty-state">
        <div class="empty-icon">🏨</div>
        <div class="empty-title">Bạn chưa có đặt phòng nào</div>
        <div class="empty-sub">Khám phá hơn 63 khách sạn Mường Thanh trên khắp Việt Nam<br/>và bắt đầu hành trình của bạn.</div>
        <a href="search.html" class="btn-ghost-link">Tìm kiếm phòng ngay</a>
      </div>`;
    return;
  }

  const statusMap = {
    pending:     { label: 'Chờ xác nhận', cls: 'status-pending'    },
    confirmed:   { label: 'Đã xác nhận',  cls: 'status-confirmed'  },
    cancelled:   { label: 'Đã hủy',       cls: 'status-cancelled'  },
    checked_in:  { label: 'Đang lưu trú', cls: 'status-checked_in' },
    checked_out: { label: 'Đã trả phòng', cls: 'status-checked_out'},
  };

  const cards = bookings.map(b => {
    const st    = statusMap[b.status] || { label: b.status, cls: 'status-pending' };
    const nights = b.nights || nightsBetween(b.check_in, b.check_out);
    return `
      <article class="booking-card">
        <div class="booking-info">
          <div class="booking-ref"># ${escHtml(b.ref_code)}</div>
          <div class="booking-hotel">${escHtml(b.hotel_name)}</div>
          <div class="booking-room">${escHtml(b.room_type)}</div>
          <div class="booking-meta">
            <div class="booking-meta-item">
              <span>Check-in:</span>
              <strong>${formatDate(b.check_in)}</strong>
            </div>
            <div class="booking-meta-item">
              <span>Check-out:</span>
              <strong>${formatDate(b.check_out)}</strong>
            </div>
            <div class="booking-meta-item">
              <span>Số đêm:</span>
              <strong>${nights}</strong>
            </div>
            <div class="booking-meta-item">
              <span>Khách:</span>
              <strong>${b.guests}</strong>
            </div>
          </div>
        </div>
        <div class="booking-right">
          <div class="booking-total">${formatPrice(b.grand_total)}</div>
          <span class="status-badge ${st.cls}">${st.label}</span>
          <div class="booking-created">Đặt lúc ${formatDate(b.created_at)}</div>
        </div>
      </article>`;
  }).join('');

  container.innerHTML = `<div class="bookings-list">${cards}</div>`;
}

// ===== RENDER PROMOTIONS =====
function renderPromotions(promotions) {
  const container = document.getElementById('promosList');
  if (!container) return;

  if (!promotions || promotions.length === 0) {
    container.innerHTML = `
      <div class="empty-state" style="grid-column:1/-1">
        <div class="empty-icon">🎁</div>
        <div class="empty-title">Chưa có khuyến mãi</div>
        <div class="empty-sub">Các ưu đãi đặc biệt sẽ xuất hiện tại đây.<br/>Hãy theo dõi để không bỏ lỡ!</div>
      </div>`;
    return;
  }

  const copyIcon = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>`;

  const cards = promotions.map(p => {
    const discountLabel = p.discount_type === 'percent'
      ? `${p.discount_value}%`
      : formatPrice(p.discount_value);

    const validStr = [
      p.valid_from ? 'Từ ' + formatDate(p.valid_from) : '',
      p.valid_to   ? 'đến ' + formatDate(p.valid_to)  : '',
    ].filter(Boolean).join(' ');

    const minNightsStr = p.min_nights > 0
      ? `Tối thiểu ${p.min_nights} đêm`
      : 'Không yêu cầu số đêm tối thiểu';

    const appliesToStr = appliesLabel(p.applies_to);

    return `
      <div class="promo-card">
        <div class="promo-code-wrap">
          <span class="promo-code" onclick="copyCode('${escAttr(p.code)}')" title="Nhấn để sao chép">${escHtml(p.code)}</span>
          <button class="copy-btn" onclick="copyCode('${escAttr(p.code)}')" aria-label="Sao chép mã">
            ${copyIcon}
            Sao chép
          </button>
        </div>
        <div class="promo-name">${escHtml(p.name)}</div>
        <div class="promo-discount">${discountLabel}</div>
        <div class="promo-details">
          ${validStr ? `<div class="promo-detail-item"><span>Hiệu lực:</span> <span>${validStr}</span></div>` : ''}
          <div class="promo-detail-item"><span>Số đêm:</span> <span>${minNightsStr}</span></div>
          <div class="promo-detail-item"><span>Áp dụng:</span> <span>${appliesToStr}</span></div>
        </div>
      </div>`;
  }).join('');

  container.innerHTML = cards;
}

function appliesLabel(val) {
  if (!val || val === 'all') return 'Tất cả phòng';
  if (val === 'standard')   return 'Phòng tiêu chuẩn';
  if (val === 'deluxe')     return 'Phòng Deluxe';
  if (val === 'suite')      return 'Phòng Suite';
  return escHtml(val);
}

// ===== SAVE PROFILE =====
async function saveProfile(event) {
  if (event) event.preventDefault();

  const payload = {
    last_name:   document.getElementById('fLastName').value.trim(),
    first_name:  document.getElementById('fFirstName').value.trim(),
    phone:       document.getElementById('fPhone').value.trim(),
    nationality: document.getElementById('fNationality').value,
  };

  const btn = document.getElementById('btnSaveProfile');
  setLoading(btn, true, 'Lưu thay đổi');

  try {
    const r = await fetch(API + '?action=update', {
      method: 'PUT',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });
    const d = await r.json();

    if (d.success) {
      // Update sidebar display
      const newName = [payload.last_name, payload.first_name].filter(Boolean).join(' ');
      if (newName) {
        document.getElementById('sidebarName').textContent = newName;
        document.getElementById('navUsername').textContent = payload.first_name || newName;
        document.getElementById('sidebarAvatar').textContent = getInitials(payload.first_name, payload.last_name);
      }
      showToast('Cập nhật thành công!', 'ok');
    } else {
      showToast(d.error || 'Cập nhật thất bại.', 'err');
    }
  } catch (err) {
    showToast('Lỗi kết nối. Vui lòng thử lại.', 'err');
  }

  setLoading(btn, false, 'Lưu thay đổi');
}

// ===== CHANGE PASSWORD =====
async function changePassword(event) {
  if (event) event.preventDefault();

  const old_password = document.getElementById('pwdOld').value;
  const new_password = document.getElementById('pwdNew').value;
  const confirm      = document.getElementById('pwdConfirm').value;
  const msgEl        = document.getElementById('pwdMsg');

  // Clear old message
  msgEl.className = 'inline-msg';
  msgEl.textContent = '';

  if (!old_password) { showPwdMsg('Vui lòng nhập mật khẩu hiện tại.', 'err'); return; }
  if (new_password.length < 6) { showPwdMsg('Mật khẩu mới phải ít nhất 6 ký tự.', 'err'); return; }
  if (new_password !== confirm) { showPwdMsg('Mật khẩu xác nhận không khớp.', 'err'); return; }

  const btn = document.getElementById('btnChangePassword');
  setLoading(btn, true, 'Đổi Mật Khẩu');

  try {
    const r = await fetch(API + '?action=change_password', {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ old_password, new_password }),
    });
    const d = await r.json();

    if (d.success) {
      showPwdMsg('Đổi mật khẩu thành công!', 'ok');
      document.getElementById('passwordForm').reset();
      showToast('Mật khẩu đã được cập nhật.', 'ok');
    } else {
      showPwdMsg(d.error || 'Đổi mật khẩu thất bại.', 'err');
    }
  } catch (err) {
    showPwdMsg('Lỗi kết nối. Vui lòng thử lại.', 'err');
  }

  setLoading(btn, false, 'Đổi Mật Khẩu');
}

function showPwdMsg(text, type) {
  const el = document.getElementById('pwdMsg');
  el.className = 'inline-msg ' + type;
  el.textContent = text;
}

// ===== LOGOUT =====
async function logout() {
  try {
    await fetch(API + '?action=logout', { method: 'POST', credentials: 'include' });
  } catch (_) {}
  window.location.replace('index.html');
}

// ===== SHOW SECTION =====
function showSection(name) {
  // Hide all sections
  document.querySelectorAll('.profile-section').forEach(s => s.classList.remove('active'));
  // Show target
  const target = document.getElementById('section-' + name);
  if (target) target.classList.add('active');

  // Update sidebar nav
  document.querySelectorAll('.sidebar-nav-item').forEach(item => {
    item.classList.toggle('active', item.dataset.section === name);
  });

  // Update mobile tabs
  document.querySelectorAll('.mobile-tab').forEach(tab => {
    tab.classList.toggle('active', tab.dataset.section === name);
  });

  // Scroll to top of main on mobile
  if (window.innerWidth <= 768) {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
}

// ===== TOGGLE MOBILE NAV =====
function toggleMobileNav() {
  const menu   = document.getElementById('mobileNavMenu');
  const burger = document.getElementById('navBurger');
  const open   = menu.classList.toggle('open');
  burger.setAttribute('aria-expanded', open);
}

// ===== TOAST =====
let _toastTimer = null;
function showToast(msg, type) {
  const toast = document.getElementById('toast');
  if (!toast) return;

  clearTimeout(_toastTimer);
  toast.textContent = msg;
  toast.className = 'toast ' + (type === 'ok' ? 'ok' : 'err');
  toast.classList.add('show');

  _toastTimer = setTimeout(() => {
    toast.classList.remove('show');
  }, 3500);
}

// ===== COPY CODE =====
function copyCode(code) {
  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(code).then(() => {
      showToast(`Đã sao chép mã ${code}`, 'ok');
    }).catch(() => fallbackCopy(code));
  } else {
    fallbackCopy(code);
  }
}

function fallbackCopy(code) {
  const ta = document.createElement('textarea');
  ta.value = code;
  ta.style.cssText = 'position:fixed;top:0;left:0;opacity:0;';
  document.body.appendChild(ta);
  ta.focus(); ta.select();
  try {
    document.execCommand('copy');
    showToast(`Đã sao chép mã ${code}`, 'ok');
  } catch (_) {
    showToast('Không thể sao chép. Vui lòng sao chép thủ công.', 'err');
  }
  document.body.removeChild(ta);
}

// ===== TOGGLE PASSWORD EYE =====
function toggleEye(inputId, btn) {
  const input = document.getElementById(inputId);
  if (!input) return;
  const isText = input.type === 'text';
  input.type = isText ? 'password' : 'text';

  const eyeOpen = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>`;
  const eyeOff  = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>`;
  btn.innerHTML = isText ? eyeOpen : eyeOff;
}

// ===== HELPERS =====
function formatPrice(n) {
  if (n == null || n === '') return '—';
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(Number(n));
}

function formatDate(s) {
  if (!s) return '—';
  // Handles "YYYY-MM-DD" and "YYYY-MM-DD HH:mm:ss"
  const d = new Date(s.replace(' ', 'T'));
  if (isNaN(d)) return s;
  const dd = String(d.getDate()).padStart(2, '0');
  const mm = String(d.getMonth() + 1).padStart(2, '0');
  const yyyy = d.getFullYear();
  return `${dd}/${mm}/${yyyy}`;
}

function nightsBetween(checkIn, checkOut) {
  if (!checkIn || !checkOut) return '—';
  const a = new Date(checkIn);
  const b = new Date(checkOut);
  const diff = Math.round((b - a) / (1000 * 60 * 60 * 24));
  return diff > 0 ? diff : '—';
}

function escHtml(str) {
  if (str == null) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function escAttr(str) {
  if (str == null) return '';
  return String(str).replace(/'/g, "\\'").replace(/"/g, '&quot;');
}

function setLoading(btn, loading, originalText) {
  if (!btn) return;
  if (loading) {
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner"></span>${originalText}`;
  } else {
    btn.disabled = false;
    btn.textContent = originalText;
  }
}
