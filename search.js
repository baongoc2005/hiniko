// ===== CONSTANTS (trước đây trong data/hotels.js) =====
function getAmenityLabels() {
  return {
    pool: t('search.pool'), spa: t('search.spa'), gym: t('search.gym'),
    restaurant: t('search.rest'), bar:"Bar", wifi: t('search.wifi') || "WiFi",
    parking: t('search.parking') || "Bãi đỗ xe",
    beach: t('search.beach'), conference: t('search.conf')
  };
}
function getRegionLabel(r) {
  const map = { north: t('search.north'), central: t('search.central'), south: t('search.south') };
  return map[r] || r;
}
const BRAND_LABELS = { luxury:"Luxury", grand:"Grand", holiday:"Holiday", muongthanh:"Mường Thanh" };
const REGIONS = { north:"Miền Bắc", central:"Miền Trung", south:"Miền Nam" };

let HOTELS_DATA = [];

// ===== STATE =====
let state = {
  dest: '', checkIn: '', checkOut: '', guests: 2,
  region: '', brands: [], stars: [], maxPrice: 10000000,
  amenities: [], sort: 'rating', view: 'grid', page: 1
};
const PER_PAGE = 8;

// ===== DEST HERO CONFIG =====
const DEST_HERO = {
  'Hạ Long':    { img: 'img/halong.png',  tag: 'QUẢNG NINH',   title: 'Hạ Long' },
  'Đà Nẵng':   { img: 'img/danang.png',  tag: 'MIỀN TRUNG',   title: 'Đà Nẵng' },
  'Sa Pa':      { img: 'img/sapa.png',    tag: 'LÀO CAI',      title: 'Sa Pa' },
  'Nha Trang':  { img: 'img/room.png',    tag: 'KHÁNH HÒA',    title: 'Nha Trang' },
  'Hà Nội':    { img: 'img/halong.png',  tag: 'THỦ ĐÔ',       title: 'Hà Nội' },
  'Hồ Chí Minh': { img: 'img/halong.png', tag: 'MIỀN NAM',   title: 'Hồ Chí Minh' },
  'Đà Lạt':    { img: 'img/sapa.png',    tag: 'LÂM ĐỒNG',     title: 'Đà Lạt' },
  'Phú Quốc':  { img: 'img/danang.png',  tag: 'KIÊN GIANG',   title: 'Phú Quốc' },
};

function applyDestHero(dest) {
  const cfg = DEST_HERO[dest];
  if (!dest || !cfg) return;

  // Đổi ảnh nền
  document.getElementById('heroBgImg').src = cfg.img;

  // Đổi label + tiêu đề
  document.getElementById('heroLabel').textContent = cfg.tag;
  document.getElementById('heroTitle').innerHTML =
    `Khách Sạn Tại <em>${cfg.title}</em>`;

  // Thêm breadcrumb nếu chưa có
  if (!document.getElementById('heroBreadcrumb')) {
    const bc = document.createElement('a');
    bc.id = 'heroBreadcrumb';
    bc.href = 'search.html';
    bc.textContent = `← ${t('search.all_dest')}`;
    bc.style.cssText = 'display:block;font-size:0.72rem;letter-spacing:0.14em;color:rgba(245,240,234,0.55);text-transform:uppercase;text-decoration:none;margin-bottom:0.8rem;transition:color 0.2s';
    bc.onmouseenter = () => bc.style.color = 'rgba(201,169,110,0.9)';
    bc.onmouseleave = () => bc.style.color = 'rgba(245,240,234,0.55)';
    const label = document.getElementById('heroLabel');
    label.parentNode.insertBefore(bc, label);
  }

  // Đếm số khách sạn phù hợp và cập nhật tiêu đề trang
  const count = HOTELS_DATA.filter(h => h.city === dest || h.province === dest).length;
  document.title = `${cfg.title} — ${count} Khách Sạn Mường Thanh`;
}

// ===== INIT =====
document.addEventListener('DOMContentLoaded', async () => {
  try {
    const res = await fetch('api/hotels.php');
    const data = await res.json();
    HOTELS_DATA = data.hotels || [];
  } catch (_) {
    HOTELS_DATA = [];
  }
  loadParamsFromURL();
  populateCitySelect();
  setDefaultDates();
  attachFilters();
  if (state.dest) applyDestHero(state.dest);
  render();
});

function loadParamsFromURL() {
  const p = new URLSearchParams(window.location.search);
  if (p.get('dest')) { state.dest = p.get('dest'); document.getElementById('qsDest').value = state.dest || ''; }
  if (p.get('checkIn')) { state.checkIn = p.get('checkIn'); document.getElementById('qsCheckIn').value = state.checkIn; }
  if (p.get('checkOut')) { state.checkOut = p.get('checkOut'); document.getElementById('qsCheckOut').value = state.checkOut; }
  if (p.get('guests')) { state.guests = parseInt(p.get('guests')) || 2; document.getElementById('qsGuests').value = state.guests; }
}

function populateCitySelect() {
  const cities = [...new Set(HOTELS_DATA.map(h => h.city))].sort();
  const sel = document.getElementById('qsDest');
  cities.forEach(city => {
    const o = document.createElement('option');
    o.value = city; o.textContent = city;
    sel.appendChild(o);
  });
  if (state.dest) sel.value = state.dest;
}

function setDefaultDates() {
  const today = new Date();
  const d1 = new Date(today); d1.setDate(d1.getDate() + 1);
  const d2 = new Date(today); d2.setDate(d2.getDate() + 2);
  const fmt = d => d.toISOString().split('T')[0];
  const ci = document.getElementById('qsCheckIn');
  const co = document.getElementById('qsCheckOut');
  ci.min = fmt(today); co.min = fmt(d1);
  if (!ci.value) ci.value = state.checkIn || fmt(d1);
  if (!co.value) co.value = state.checkOut || fmt(d2);
}

function attachFilters() {
  // Quick search
  document.getElementById('quickSearchForm').addEventListener('submit', e => {
    e.preventDefault();
    state.dest = document.getElementById('qsDest').value;
    state.checkIn = document.getElementById('qsCheckIn').value;
    state.checkOut = document.getElementById('qsCheckOut').value;
    state.guests = parseInt(document.getElementById('qsGuests').value);
    state.page = 1;
    render();
  });

  // Region radios
  document.querySelectorAll('input[name="region"]').forEach(r => {
    r.addEventListener('change', () => { state.region = r.value; state.page = 1; render(); });
  });
  // Brand checkboxes
  document.querySelectorAll('input[name="brand"]').forEach(r => {
    r.addEventListener('change', () => {
      state.brands = [...document.querySelectorAll('input[name="brand"]:checked')].map(i => i.value);
      state.page = 1; render();
    });
  });
  // Stars checkboxes
  document.querySelectorAll('input[name="stars"]').forEach(r => {
    r.addEventListener('change', () => {
      state.stars = [...document.querySelectorAll('input[name="stars"]:checked')].map(i => parseInt(i.value));
      state.page = 1; render();
    });
  });
  // Price range
  document.getElementById('priceRange').addEventListener('input', e => {
    state.maxPrice = parseInt(e.target.value);
    document.getElementById('priceValue').textContent = formatVND(state.maxPrice / 1000) + 'k';
    state.page = 1; render();
  });
  // Amenities
  document.querySelectorAll('input[name="amenity"]').forEach(r => {
    r.addEventListener('change', () => {
      state.amenities = [...document.querySelectorAll('input[name="amenity"]:checked')].map(i => i.value);
      state.page = 1; render();
    });
  });
  // Sort
  document.getElementById('sortSelect').addEventListener('change', e => {
    state.sort = e.target.value; state.page = 1; render();
  });
  // View toggle
  document.getElementById('gridBtn').addEventListener('click', () => {
    state.view = 'grid';
    document.getElementById('gridBtn').classList.add('active');
    document.getElementById('listBtn').classList.remove('active');
    document.getElementById('hotelGrid').classList.remove('list-view');
  });
  document.getElementById('listBtn').addEventListener('click', () => {
    state.view = 'list';
    document.getElementById('listBtn').classList.add('active');
    document.getElementById('gridBtn').classList.remove('active');
    document.getElementById('hotelGrid').classList.add('list-view');
  });
  // Clear filters
  document.getElementById('clearFilters').addEventListener('click', clearAllFilters);
  // Mobile filter toggle
  document.getElementById('mobileFilterBtn').addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('open');
  });
}

function clearAllFilters() {
  state.region = ''; state.brands = []; state.stars = [];
  state.maxPrice = 10000000; state.amenities = []; state.dest = ''; state.page = 1;
  document.querySelectorAll('input[name="region"]')[0].checked = true;
  document.querySelectorAll('input[name="brand"], input[name="stars"], input[name="amenity"]').forEach(i => i.checked = false);
  document.getElementById('priceRange').value = 10000000;
  document.getElementById('priceValue').textContent = '10.000k';
  document.getElementById('qsDest').value = '';
  render();
}

// ===== FILTER & SORT =====
function getFiltered() {
  let hotels = [...HOTELS_DATA];
  if (state.dest) hotels = hotels.filter(h => h.city === state.dest || h.province === state.dest);
  if (state.region) hotels = hotels.filter(h => h.region === state.region);
  if (state.brands.length) hotels = hotels.filter(h => state.brands.includes(h.brand));
  if (state.stars.length) hotels = hotels.filter(h => state.stars.includes(h.stars));
  hotels = hotels.filter(h => h.price <= state.maxPrice);
  if (state.amenities.length) hotels = hotels.filter(h => state.amenities.every(a => h.amenities.includes(a)));

  switch (state.sort) {
    case 'rating': hotels.sort((a,b) => b.rating - a.rating); break;
    case 'price_asc': hotels.sort((a,b) => a.price - b.price); break;
    case 'price_desc': hotels.sort((a,b) => b.price - a.price); break;
    case 'name': hotels.sort((a,b) => a.name.localeCompare(b.name)); break;
  }
  return hotels;
}

// ===== RENDER =====
function render() {
  const filtered = getFiltered();
  const total = filtered.length;
  const totalPages = Math.ceil(total / PER_PAGE);
  if (state.page > totalPages) state.page = 1;
  const slice = filtered.slice((state.page - 1) * PER_PAGE, state.page * PER_PAGE);

  // Count
  document.getElementById('resultsCount').innerHTML =
    t('search.found', total);

  // Grid
  const grid = document.getElementById('hotelGrid');
  const empty = document.getElementById('emptyState');
  if (state.view === 'list') grid.classList.add('list-view');

  if (slice.length === 0) {
    grid.innerHTML = ''; empty.classList.remove('hidden');
  } else {
    empty.classList.add('hidden');
    grid.innerHTML = slice.map((h, i) => hotelCard(h, i)).join('');
  }

  // Active tags
  renderActiveTags();

  // Pagination
  renderPagination(totalPages);
}

function hotelCard(h, i) {
  const delay = (i % PER_PAGE) * 0.06;
  const stars = '★'.repeat(h.stars);
  const amenityShow = h.amenities.slice(0, 4).map(a =>
    `<span class="amenity-icon">${(getAmenityLabels()[a]) || a}</span>`
  ).join('');
  return `
  <div class="hotel-card" style="animation-delay:${delay}s" onclick="viewHotel('${h.slug}')">
    <div class="hotel-card-img">
      <img src="${h.image}" alt="${h.name}" loading="lazy"/>
      <span class="hotel-brand-badge">${BRAND_LABELS[h.brand]}</span>
      <span class="hotel-stars">${stars}</span>
    </div>
    <div class="hotel-card-body">
      <h3 class="hotel-name">${h.name}</h3>
      <p class="hotel-location">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
        ${h.city}, ${h.province}
      </p>
      <div class="hotel-amenity-icons">${amenityShow}</div>
      <div class="hotel-card-footer">
        <div class="hotel-rating">
          <span class="rating-score">${h.rating}</span>
          <div>
            <div style="font-size:0.7rem;color:var(--white)">${t('search.excellent')}</div>
            <div class="rating-text">${h.reviews} ${t('search.reviews_lbl')}</div>
          </div>
        </div>
        <div class="hotel-price">
          <span class="price-from">${t('search.from')}</span>
          <span class="price-amount">${formatPrice(h.price)}</span>
          <span class="price-night">/${t('search.night')}</span>
        </div>
      </div>
    </div>
    <a class="btn-view-hotel" href="hotel.html?id=${h.slug}&checkIn=${state.checkIn}&checkOut=${state.checkOut}&guests=${state.guests}" onclick="event.stopPropagation()">${t('search.view_detail')}</a>
  </div>`;
}

function renderActiveTags() {
  const cont = document.getElementById('activeTags');
  const tags = [];
  if (state.dest) tags.push({ label: `📍 ${state.dest}`, key: 'dest' });
  if (state.region) tags.push({ label: `🗺 ${getRegionLabel(state.region)}`, key: 'region' });
  state.brands.forEach(b => tags.push({ label: BRAND_LABELS[b], key: `brand:${b}` }));
  state.stars.forEach(s => tags.push({ label: `${s} ${t('search.stars_unit')}`, key: `stars:${s}` }));
  state.amenities.forEach(a => tags.push({ label: (getAmenityLabels()[a] || a), key: `amenity:${a}` }));
  if (state.maxPrice < 10000000) tags.push({ label: `≤ ${formatPrice(state.maxPrice)}`, key: 'price' });

  cont.innerHTML = tags.map(t =>
    `<span class="filter-tag">${t.label} <span class="tag-remove" onclick="removeTag('${t.key}')">✕</span></span>`
  ).join('');
}

function removeTag(key) {
  if (key === 'dest') { state.dest = ''; document.getElementById('qsDest').value = ''; }
  else if (key === 'region') { state.region = ''; document.querySelector('input[name="region"]').checked = true; }
  else if (key === 'price') { state.maxPrice = 10000000; document.getElementById('priceRange').value = 10000000; document.getElementById('priceValue').textContent = '10.000k'; }
  else if (key.startsWith('brand:')) {
    const b = key.split(':')[1]; state.brands = state.brands.filter(x => x !== b);
    document.querySelector(`input[name="brand"][value="${b}"]`).checked = false;
  } else if (key.startsWith('stars:')) {
    const s = parseInt(key.split(':')[1]); state.stars = state.stars.filter(x => x !== s);
    document.querySelector(`input[name="stars"][value="${s}"]`).checked = false;
  } else if (key.startsWith('amenity:')) {
    const a = key.split(':')[1]; state.amenities = state.amenities.filter(x => x !== a);
    document.querySelector(`input[name="amenity"][value="${a}"]`).checked = false;
  }
  state.page = 1; render();
}

function renderPagination(totalPages) {
  const cont = document.getElementById('pagination');
  if (totalPages <= 1) { cont.innerHTML = ''; return; }
  let html = '';
  for (let i = 1; i <= totalPages; i++) {
    html += `<button class="page-btn${i === state.page ? ' active' : ''}" onclick="goPage(${i})">${i}</button>`;
  }
  cont.innerHTML = html;
}

function goPage(p) {
  state.page = p;
  render();
  document.querySelector('.results-panel').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function viewHotel(slug) {
  const url = `hotel.html?id=${slug}&checkIn=${state.checkIn}&checkOut=${state.checkOut}&guests=${state.guests}`;
  window.location.href = url;
}

// ===== HELPERS =====
function formatPrice(n) {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND', maximumFractionDigits: 0 }).format(n);
}
function formatVND(n) {
  return new Intl.NumberFormat('vi-VN').format(n);
}
function calcNights() {
  const ci = document.getElementById('qsCheckIn').value;
  const co = document.getElementById('qsCheckOut').value;
  if (!ci || !co) return 1;
  return Math.max(1, Math.round((new Date(co) - new Date(ci)) / 86400000));
}
