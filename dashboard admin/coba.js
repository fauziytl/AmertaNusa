/* ==========================================================
   AMERTANUSA DASHBOARD (Hybrid)
   - UI tetap: sesuai HTML yang kamu kirim
   - Data: dari API PHP + Chart.js
   - Base URL: localhost:8080
   ========================================================== */

const API_BASE = "http://localhost/restoran-1.0.0/dashboard%20terbaruu%20smoga%20fix%20bgt/api";

// Helpers
const $ = (s) => document.querySelector(s);
const $$ = (s) => document.querySelectorAll(s);
const sleep = (ms) => new Promise(r => setTimeout(r, ms));

/* ==========================
   BOOTSTRAP
   ========================== */
document.addEventListener("DOMContentLoaded", () => {
  // header date
  const now = new Date();
  $("#current-date").textContent = now.toLocaleString("id-ID", { weekday: "long", day: "2-digit", month: "long", year: "numeric" });

  initSidebarByWidth();
  setupNavigation();
  setupReservationFilters();
  setupTableFilters();

  // tampilkan dashboard sebagai default
  switchPage("dashboard");
});

/* ==========================
   SIDEBAR & NAV
   ========================== */
function setSidebarState(open) {
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebar-overlay');
  const main = document.getElementById('main-content-wrapper');

  if (!sidebar) return;

  // Simpan status di body agar toggle bisa tahu kondisi terkini
  document.body.classList.toggle('sidebar-open', open);
  document.body.classList.toggle('sidebar-closed', !open);

  // Sidebar animasi
  sidebar.style.transform = open ? 'translateX(0)' : 'translateX(-100%)';

  // Geser konten utama (hanya desktop)
  if (main) {
    main.style.marginLeft = open && window.innerWidth >= 768 ? '16rem' : '0';
  }

  // Overlay (hanya di mobile)
  if (overlay) {
    overlay.classList.toggle('hidden', open ? false : true);
  }
}

function toggleSidebar() {
  const isOpen = document.body.classList.contains('sidebar-open');
  setSidebarState(!isOpen);
}

function initSidebarByWidth() {
  if (window.innerWidth >= 768) {
    setSidebarState(true);
  } else {
    setSidebarState(false);
  }
}

// === EVENT ===
window.addEventListener('DOMContentLoaded', () => {
  initSidebarByWidth();

  // Klik menu di mobile → tutup sidebar
  document.querySelectorAll('#sidebar a').forEach(a => {
    a.addEventListener('click', () => {
      if (window.innerWidth < 768) setSidebarState(false);
    });
  });

  // Klik overlay → tutup sidebar
  const overlay = document.getElementById('sidebar-overlay');
  if (overlay) overlay.addEventListener('click', () => setSidebarState(false));

  // Tombol hamburger
  const toggleBtn = document.getElementById('menu-toggle-btn');
  if (toggleBtn) toggleBtn.addEventListener('click', toggleSidebar);
});

window.addEventListener('resize', initSidebarByWidth);

function setupNavigation() {
  $$(".nav-item").forEach(a => {
    a.addEventListener("click", (e) => {
      if (a.getAttribute("onclick")) return; // biarkan tombol logout pakai onclick

      e.preventDefault();
      const page = a.dataset.page;
      if (!page) return;
      // aktifkan style
      $$(".nav-item").forEach(n => {
        n.classList.remove("active");
        n.classList.remove("text-white");
        n.classList.remove("bg-primary");
        n.classList.add("text-gray-300");
      });
      a.classList.remove("text-gray-300");
      a.classList.add("text-white", "bg-primary");

      switchPage(page);
    });
  });
}

window.addEventListener('resize', initSidebarByWidth);

function switchPage(page) {
  // sembunyikan semua .page-content
  $$(".page-content").forEach(p => p.classList.add("hidden"));

  // tampilkan page target
  const id = `#${page}-page`;
  const el = $(id);
  if (el) el.classList.remove("hidden");

  // set title header
  const titleMap = {
    dashboard: "Dashboard",
    tables: "Kelola Meja",
    reservations: "Kelola Reservasi",
    monitoring: "Monitoring"
  };
  $("#page-title").textContent = titleMap[page] || "Dashboard";

  // load data spesifik
  if (page === "dashboard") loadDashboard();
  if (page === "tables") loadTablesPage();
  if (page === "reservations") loadReservationsPage();
  if (page === "monitoring") loadMonitoringPage();
}

/* ==========================
   LOGOUT (popup confirm)
   ========================== */
async function logout() {
  const overlay = document.createElement("div");
  overlay.className = "fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50";
  overlay.innerHTML = `
    <div class="bg-white rounded-lg p-6 max-w-sm mx-4 shadow-lg text-center">
      <h3 class="text-lg font-semibold text-dark mb-3">Konfirmasi Logout</h3>
      <p class="text-gray-600 mb-5">Apakah Anda yakin ingin keluar?</p>
      <div class="flex justify-center gap-2">
        <button id="logout-cancel" class="px-4 py-2 border rounded-lg hover:bg-gray-50">Batal</button>
        <button id="logout-ok" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Logout</button>
      </div>
    </div>
  `;
  document.body.appendChild(overlay);

  $("#logout-cancel").addEventListener("click", () => overlay.remove());
  $("#logout-ok").addEventListener("click", async () => {
    try {
      await fetch(`${API_BASE}/logout.php`, { method: "POST", credentials: "include" });
    } catch(e) {}
    overlay.remove();
    location.href = "loginadmin.php";
  });
}

/* ==========================
   DASHBOARD
   ========================== */
let chartTrend, chartRev, chartStatus, chartPeak;

async function loadDashboard() {
  try {
    const res = await fetch(`${API_BASE}/dashboard_stats.php`, { credentials: "include" });
    if (res.status === 401) return location.href = "loginadmin.php";
    const s = await res.json();

    // angka utama
    $("#total-reservations").textContent = s.total_reservasi ?? 0;
    $("#occupied-tables").textContent = `${s.meja_terisi ?? 0}/${s.total_meja ?? 0}`;
    $("#daily-revenue").textContent = rupiah(s.pendapatan ?? 0);

    await loadDashboardCharts();
    await loadTodayReservations();

  } catch (e) {
    console.error("Dashboard error:", e);
  }
}

async function loadDashboardCharts() {
  try {
    const res = await fetch(`${API_BASE}/dashboard_charts.php`, { credentials: "include" });
    if (!res.ok) throw new Error("charts api error");
    const d = await res.json();

    // pastikan canvas ada tanpa ubah tampilan
    const trendHost = $("#trend-chart");
    const revenueHost = $("#revenue-chart");
    const statusHost = $("#status-pie-chart");
    const peakHost = $("#peak-hours-chart");

    if (!$("#cv-trend")) {
      const c = document.createElement("canvas"); c.id = "cv-trend"; trendHost.appendChild(c);
    }
    if (!$("#cv-revenue")) {
      const c = document.createElement("canvas"); c.id = "cv-revenue"; revenueHost.appendChild(c);
    }
    if (!$("#cv-status")) {
      const c = document.createElement("canvas"); c.id = "cv-status"; statusHost.appendChild(c);
    }
    if (!$("#cv-peak")) {
      const c = document.createElement("canvas"); c.id = "cv-peak"; peakHost.appendChild(c);
    }

    // destroy lama bila ada
    chartTrend?.destroy();
    chartRev?.destroy();
    chartStatus?.destroy();
    chartPeak?.destroy();

    // Tren 7 hari (line)
    {
      const labels = (d.tren_harian || []).map(x => x.tanggal);
      const values = (d.tren_harian || []).map(x => x.total);
      chartTrend = new Chart($("#cv-trend").getContext("2d"), {
        type: "line",
        data: {
          labels,
          datasets: [{
            label: "Reservasi",
            data: values,
            borderColor: "#FEA116",
            backgroundColor: "rgba(254,161,22,0.2)",
            borderWidth: 2,
            fill: true,
            tension: 0.35
          }]
        },
        options: { responsive: true, plugins: { legend: { display: false }}, scales: { y: { beginAtZero: true }}}
      });
    }

    // Pendapatan mingguan (bar)
    {
      const labels = (d.pendapatan_mingguan || []).map(x => x.minggu);
      const values = (d.pendapatan_mingguan || []).map(x => x.total);
      chartRev = new Chart($("#cv-revenue").getContext("2d"), {
        type: "bar",
        data: { labels, datasets: [{ label: "Pendapatan (Rp)", data: values, backgroundColor: "#3b82f6", borderRadius: 8 }]},
        options: { responsive: true, plugins: { legend: { display: false }}, scales: { y: { beginAtZero: true }}}
      });
    }

    // Distribusi status (pie)
    {
      const labels = (d.status_distribusi || []).map(x => x.status);
      const values = (d.status_distribusi || []).map(x => x.jumlah);
      chartStatus = new Chart($("#cv-status").getContext("2d"), {
        type: "pie",
        data: { labels, datasets: [{ data: values, backgroundColor: ["#22c55e","#eab308","#ef4444"] }]},
        options: { responsive: true, plugins: { legend: { position: "bottom" }}}
      });
    }

    // Jam sibuk (bar)
    {
      const labels = (d.jam_sibuk || []).map(x => x.label);
      const values = (d.jam_sibuk || []).map(x => x.total);
      chartPeak = new Chart($("#cv-peak").getContext("2d"), {
        type: "bar",
        data: { labels, datasets: [{ label: "Reservasi", data: values, backgroundColor: "#10b981", borderRadius: 6 }]},
        options: { responsive: true, plugins: { legend: { display: false }}, scales: { y: { beginAtZero: true }}}
      });
    }

  } catch (e) {
    console.error("Charts error:", e);
  }
}

async function loadTodayReservations() {
  // optional: kalau API punya list hari ini. fallback: kosong
  try {
    const res = await fetch(`${API_BASE}/dashboard_today.php`, { credentials: "include" });
    if (!res.ok) return;
    const list = await res.json();
    const host = $("#today-reservations");
    host.innerHTML = "";

    if (!Array.isArray(list) || list.length === 0) {
      host.innerHTML = `<div class="text-gray-500 text-sm">Belum ada reservasi aktif hari ini.</div>`;
      return;
    }
    list.slice(0, 5).forEach(r => {
      const div = document.createElement("div");
      div.className = "flex items-center justify-between p-3 border rounded-lg";
      div.innerHTML = `
        <div>
          <div class="font-medium text-dark">${r.nama || "-"}</div>
          <div class="text-sm text-gray-500">${r.tanggal_reservasi} • ${r.slot_label || r.waktu_reservasi}</div>
        </div>
        <span class="text-xs px-2 py-1 rounded ${badgeClass(r.status)}">${r.status}</span>
      `;
      host.appendChild(div);
    });
  } catch(e) {}
}

/* ==========================
   KELOLA MEJA
   ========================== */
function setupTableFilters() {
  $("#table-date-filter")?.addEventListener("change", refreshTableStatus);
  $("#table-time-filter")?.addEventListener("change", refreshTableStatus);
}

async function loadTablesPage() {
  // set default tanggal = hari ini
  const today = new Date().toISOString().slice(0,10);
  if ($("#table-date-filter") && !$("#table-date-filter").value) $("#table-date-filter").value = today;
  await refreshTableStatus();
}

function slotToId(rangeStr) {
  // mapping sesuai slot_waktu di DB
  const map = {
    "12:00-14:00": 1,
    "14:00-16:00": 2,
    "16:00-18:00": 3,
    "18:00-20:00": 4,
    "20:00-22:00": 5,
  };
  return map[rangeStr] || 1;
}

async function refreshTableStatus() {
  const tgl = $("#table-date-filter")?.value || new Date().toISOString().slice(0,10);
  const slotRange = $("#table-time-filter")?.value || "12:00-14:00";
  const slot = slotToId(slotRange);

  // set banner teks
  $("#selected-date-info").textContent = (tgl === new Date().toISOString().slice(0,10))
    ? "Status meja untuk hari ini"
    : `Status meja untuk ${formatTanggal(tgl)}`;

  try {
    const res = await fetch(`${API_BASE}/tables_status.php?tanggal=${tgl}&slot=${slot}`, { credentials: "include" });
    if (!res.ok) throw new Error("tables api error");
    const d = await res.json();

    // ringkasan
    const terisi = d.meja_terisi ?? 0;
    const total = d.total_meja ?? 0;
    $("#occupied-count").textContent = `${terisi}/${total} meja terisi`;
    $("#reservation-count-info").textContent = `${d.reservasi_diterima ?? 0} reservasi diterima`;

    // grid meja
    const grid = $("#tables-grid");
    grid.innerHTML = "";

    (d.daftar_meja || []).forEach(m => {
      const cell = document.createElement("div");
      const isReservasi = m.status === "reservasi";
      const isManual = m.status === "manual";
      const isKosong = !isReservasi && !isManual;

      let bg = "bg-green-200 border-2 border-green-300"; // tersedia
      if (isReservasi) bg = "bg-red-200 border-2 border-red-300";
      if (isManual) bg = "bg-yellow-200 border-2 border-yellow-300";

      cell.className = `rounded p-3 text-center ${bg}`;
      cell.innerHTML = `
        <div class="font-semibold">Meja ${m.meja_id}</div>
        <div class="text-xs text-gray-700">${isKosong ? "Tersedia" : "Terisi"}</div>
        ${m.nama_pelanggan ? `<div class="mt-1 text-xs text-gray-600">${m.nama_pelanggan} (${m.jumlah_orang} orang)</div>` : ""}
      `;
      grid.appendChild(cell);
    });

  } catch(e) {
    console.error("meja error:", e);
  }
}

/* ==========================
   KELOLA RESERVASI
   ========================== */
let _reservasiCache = [];

function setupReservationFilters() {
  $$(".reservation-filter").forEach(btn => {
    btn.addEventListener("click", () => {
      $$(".reservation-filter").forEach(b => {
        b.classList.remove("bg-white", "text-dark", "shadow-sm");
        b.classList.add("text-gray-600");
      });
      btn.classList.add("bg-white", "text-dark", "shadow-sm");
      const stUI = btn.dataset.status; // all | menunggu | diterima | ditolak
      renderReservationList(filterByUIStatus(_reservasiCache, stUI));
    });
  });
}

async function loadReservationsPage() {
  try {
    const res = await fetch(`${API_BASE}/reservations_list.php`, { credentials: "include" });
    if (!res.ok) throw new Error("reservations api error");
    _reservasiCache = await res.json();
    renderReservationList(_reservasiCache);
  } catch(e) {
    console.error("reservations error:", e);
  }
}

function filterByUIStatus(list, st) {
  if (!Array.isArray(list)) return [];
  if (!st || st === "all") return list;

  // map UI → DB
  // menunggu: pending/unpaid
  // diterima: confirmed
  // ditolak: canceled
  const match = (s) => {
    if (st === "menunggu") return s === "pending" || s === "unpaid";
    if (st === "diterima") return s === "confirmed";
    if (st === "ditolak") return s === "canceled";
    return true;
  };
  return list.filter(r => match((r.status || "").toLowerCase()));
}

function renderReservationList(list) {
  const host = $("#reservations-list");
  host.innerHTML = "";

  if (!Array.isArray(list) || list.length === 0) {
    host.innerHTML = `<div class="text-gray-500 text-sm">Tidak ada data reservasi.</div>`;
    return;
  }

  list.forEach(r => {
    const item = document.createElement("div");
    item.className = "border rounded-lg p-4 flex items-center justify-between";
    item.innerHTML = `
      <div>
        <div class="font-semibold text-dark">${r.nama || "-"}</div>
        <div class="text-sm text-gray-500">${r.kode_booking || "-"} • ${r.tanggal_reservasi} • ${r.slot_label || r.waktu_reservasi}</div>
      </div>
      <div class="flex items-center gap-2">
        <span class="text-xs px-2 py-1 rounded ${badgeClass(r.status)}">${r.status}</span>
        <button class="px-3 py-1 rounded bg-blue-500 text-white hover:bg-blue-600"
          onclick="showReservationDetail(${r.reservasi_id})">Detail</button>
        ${
          (r.status === "pending" || r.status === "unpaid")
          ? `
            <button class="px-3 py-1 rounded bg-green-600 text-white hover:bg-green-700"
              onclick="changeReservationStatus(${r.reservasi_id}, 'confirmed')">Terima</button>
            <button class="px-3 py-1 rounded bg-red-600 text-white hover:bg-red-700"
              onclick="changeReservationStatus(${r.reservasi_id}, 'canceled')">Tolak</button>
            `
          : ""
        }
      </div>
    `;
    host.appendChild(item);
  });
}

function badgeClass(status) {
  const s = (status || "").toLowerCase();
  if (s === "confirmed" || s === "diterima") return "bg-green-100 text-green-700";
  if (s === "pending" || s === "unpaid" || s === "menunggu") return "bg-yellow-100 text-yellow-700";
  if (s === "canceled" || s === "ditolak") return "bg-red-100 text-red-700";
  return "bg-gray-100 text-gray-700";
}

async function changeReservationStatus(id, statusBaru) {
  try {
    const res = await fetch(`${API_BASE}/update_reservation_status.php`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      credentials: "include",
      body: JSON.stringify({ reservasi_id: id, status: statusBaru })
    });
    const out = await res.json();
    if (out.success) {
      // refresh
      await loadReservationsPage();
      await loadDashboard();
      alert("Status reservasi berhasil diperbarui.");
    } else {
      alert(out.error || "Gagal memperbarui status.");
    }
  } catch(e) {
    alert("Tidak dapat menghubungi server.");
  }
}



/* ==========================
   MONITORING
   ========================== */
/* ==========================
   MONITORING - PASTE DI AKHIR FILE COBA.JS
   Hapus dulu semua fungsi monitoring yang lama
   ========================== */

// Variables
let _logTimer = null;
let _currentMonitoringTab = 'sedang-berjalan';
let _monitoringData = null;

// Main loader
async function loadMonitoringPage() {
  console.log("📄 Loading Monitoring Page...");
  
  setupMonitoringTabs();
  await loadMonitoringStats();
  await loadMonitoringHistory();
  await refreshActivityLog();
  
  if (_logTimer) clearInterval(_logTimer);
  _logTimer = setInterval(() => {
    loadMonitoringStats();
    loadMonitoringHistory();
    refreshActivityLog();
  }, 30000);
}

// Setup tab click events
function setupMonitoringTabs() {
  const tabs = document.querySelectorAll('.monitoring-tab');
  if (!tabs || tabs.length === 0) {
    console.warn("⚠️ No monitoring tabs found");
    return;
  }
  
  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      // Remove active from all tabs
      tabs.forEach(t => {
        t.classList.remove('active', 'border-primary', 'text-primary', 'border-b-2');
        t.classList.add('text-gray-500');
      });
      
      // Add active to clicked tab
      tab.classList.add('active', 'border-b-2', 'border-primary', 'text-primary');
      tab.classList.remove('text-gray-500');
      
      _currentMonitoringTab = tab.dataset.tab;
      renderMonitoringTableByTab();
    });
  });
  
  console.log("✅ Monitoring tabs setup complete");
}

// Load 3 card statistics
async function loadMonitoringStats() {
  console.log("🔍 Loading monitoring stats...");
  
  try {
    const url = `${API_BASE}/monitoring_stats.php`;
    console.log("📡 Fetching from:", url);
    
    const res = await fetch(url, { credentials: "include" });
    console.log("📥 Response status:", res.status);
    
    if (res.status === 401) {
      console.error("❌ Unauthorized - redirecting to login");
      location.href = "loginadmin.php";
      return;
    }
    
    if (!res.ok) {
      const text = await res.text();
      console.error("❌ Response error:", text);
      throw new Error(`HTTP error! status: ${res.status}`);
    }
    
    const data = await res.json();
    console.log("📊 Data received:", data);

    // Update card values
    const elMenunggu = document.getElementById('count-menunggu');
    const elDiterima = document.getElementById('count-diterima');
    const elDitolak = document.getElementById('count-ditolak');
    
    console.log("🎯 Elements found:", {
      menunggu: !!elMenunggu,
      diterima: !!elDiterima,
      ditolak: !!elDitolak
    });

    if (elMenunggu) elMenunggu.textContent = data.menunggu || 0;
    if (elDiterima) elDiterima.textContent = data.diterima || 0;
    if (elDitolak) elDitolak.textContent = data.ditolak || 0;
    
    console.log("✅ Stats updated successfully");
  } catch(e) {
    console.error("❌ Monitoring stats error:", e);
    console.error("Error details:", e.message);
    
    // Set default values on error
    const elMenunggu = document.getElementById('count-menunggu');
    const elDiterima = document.getElementById('count-diterima');
    const elDitolak = document.getElementById('count-ditolak');
    
    if (elMenunggu) elMenunggu.textContent = "?";
    if (elDiterima) elDiterima.textContent = "?";
    if (elDitolak) elDitolak.textContent = "?";
  }
}

// Load table history data
async function loadMonitoringHistory() {
  console.log("🔍 Loading monitoring history...");
  
  try {
    const url = `${API_BASE}/monitoring_history.php`;
    console.log("📡 Fetching from:", url);
    
    const res = await fetch(url, { credentials: "include" });
    console.log("📥 Response status:", res.status);
    
    if (res.status === 401) {
      console.error("❌ Unauthorized - redirecting to login");
      location.href = "loginadmin.php";
      return;
    }
    
    if (!res.ok) {
      const text = await res.text();
      console.error("❌ Response error:", text);
      throw new Error(`HTTP error! status: ${res.status}`);
    }
    
    _monitoringData = await res.json();
    console.log("📊 History data received:", _monitoringData);
    
    renderMonitoringTableByTab();
    console.log("✅ History loaded successfully");
  } catch(e) {
    console.error("❌ Monitoring history error:", e);
    console.error("Error details:", e.message);
    
    // Show error in table
    const tbody = document.getElementById('monitoring-table-body');
    if (tbody) {
      tbody.innerHTML = `
        <tr>
          <td colspan="6" class="py-8 text-center text-red-500 text-sm">
            Gagal memuat data. Silakan refresh halaman.
          </td>
        </tr>
      `;
    }
  }
}

// Render table based on active tab
function renderMonitoringTableByTab() {
  if (!_monitoringData) {
    console.log("⚠️ No monitoring data to render");
    return;
  }
  
  const tbody = document.getElementById('monitoring-table-body');
  if (!tbody) {
    console.error("❌ Table body element not found!");
    return;
  }
  
  tbody.innerHTML = "";
  
  let dataToRender = [];
  let statusBadge = { color: '', text: '' };
  
  switch(_currentMonitoringTab) {
    case 'sedang-berjalan':
      dataToRender = _monitoringData.sedang_berjalan || [];
      statusBadge = { color: 'bg-orange-100 text-orange-700', text: 'Sedang Berjalan' };
      break;
    case 'selesai':
      dataToRender = _monitoringData.selesai || [];
      statusBadge = { color: 'bg-green-100 text-green-700', text: 'Selesai' };
      break;
    case 'ditolak':
      dataToRender = _monitoringData.ditolak || [];
      statusBadge = { color: 'bg-red-100 text-red-700', text: 'Ditolak' };
      break;
  }
  
  console.log(`📋 Rendering ${dataToRender.length} items for tab: ${_currentMonitoringTab}`);

  // --- PERBAIKAN DI SINI ---
  // Ambil jumlah data yang akan dirender
  const dataCount = dataToRender.length;

  // Update elemen pagination
  const elShowing = document.getElementById('history-showing');
  const elTotal = document.getElementById('history-total');
  
  if (elShowing) elShowing.textContent = dataCount;
  if (elTotal) elTotal.textContent = dataCount;
  // --- AKHIR PERBAIKAN ---
  
  if (dataToRender.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="6" class="py-8 text-center text-gray-400 text-sm">
          Tidak ada data untuk kategori ini
        </td>
      </tr>
    `;
    return;
  }
  
  dataToRender.forEach(r => {
    const row = document.createElement("tr");
    row.className = "border-b border-gray-100 hover:bg-gray-50 transition-colors";
    
    // Format tanggal
    const tanggal = r.tanggal ? formatTanggal(r.tanggal) : '-';
    const waktu = r.slot_label || r.waktu || '-';
    
    row.innerHTML = `
      <td class="py-4 px-4">
        <div class="font-medium text-dark">${r.nama || "-"}</div>
      </td>
      <td class="py-4 px-4">
        <div class="text-sm text-gray-700">${r.kode_booking || "-"}</div>
      </td>
      <td class="py-4 px-4">
        <div class="text-sm text-gray-700">${r.meja || "-"}</div>
      </td>
      <td class="py-4 px-4">
        <div class="text-sm text-gray-700">${r.jumlah_orang || 0} orang</div>
      </td>
      <td class="py-4 px-4">
        <div class="text-sm text-gray-700">${tanggal}</div>
        <div class="text-xs text-gray-500">${waktu}</div>
      </td>
      <td class="py-4 px-4">
        <span class="px-3 py-1 text-xs rounded-full font-medium ${statusBadge.color}">${statusBadge.text}</span>
      </td>
    `;
    
    tbody.appendChild(row);
  });
}

// Load activity log
async function refreshActivityLog() {
  try {
    const res = await fetch(`${API_BASE}/dashboard_log.php`, { credentials: "include" });
    
    if (res.status === 401) {
      location.href = "loginadmin.php";
      return;
    }
    
    if (!res.ok) throw new Error("log api error");
    
    const logs = await res.json();
    const host = document.getElementById('activity-log');
    
    if (!host) {
      console.error("❌ Activity log element not found!");
      return;
    }
    
    host.innerHTML = "";

    if (!Array.isArray(logs) || logs.length === 0) {
      host.innerHTML = `<div class="text-gray-500 text-sm">Belum ada aktivitas tercatat.</div>`;
      return;
    }

    logs.forEach(l => {
      const row = document.createElement("div");
      row.className = "flex items-start space-x-3 text-sm";
      row.innerHTML = `
        <div class="w-2 h-2 bg-primary rounded-full mt-2"></div>
        <div>
          <p class="text-dark">${l.aktivitas}</p>
          <p class="text-gray-500 text-xs">${formatWaktu(l.timestamp)}</p>
        </div>
      `;
      host.appendChild(row);
    });
  } catch(e) {
    console.error("log error:", e);
  }
}

console.log("✅ Monitoring module loaded");

/* ==========================
   UTIL
   ========================== */
function rupiah(num) {
  const f = new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 });
  return f.format(+num || 0);
}
function formatTanggal(iso) {
  const d = new Date(iso);
  return d.toLocaleDateString("id-ID", { day:"2-digit", month:"long", year:"numeric" });
}
function formatWaktu(iso) {
  const d = new Date(iso);
  const t = d.toLocaleTimeString("id-ID", { hour:"2-digit", minute:"2-digit" });
  const dt = d.toLocaleDateString("id-ID", { day:"2-digit", month:"short", year:"numeric" });
  return `${dt} ${t}`;
}

// untuk log aktivitas (monitoring)
async function loadActivityLog() {
  const res = await fetch(`${API_BASE}/dashboard_log.php`);
  const data = await res.json();
  const container = document.getElementById("activity-log");
  container.innerHTML = "";
  data.forEach(item => {
    container.innerHTML += `
      <div class="flex items-start space-x-3 text-sm">
        <div class="w-2 h-2 bg-green-500 rounded-full mt-2"></div>
        <div>
          <p class="text-dark">${item.aktivitas}</p>
          <p class="text-gray-500 text-xs">${item.timestamp}</p>
        </div>
      </div>
    `;
  });
}

// untuk status sistem
async function loadSystemStatus() {
  const res = await fetch(`${API_BASE}/system_status.php`);
  const data = await res.json();
  document.querySelector("#status-api").textContent = data.api ? "Online" : "Offline";
  document.querySelector("#status-db").textContent = data.database ? "Connected" : "Disconnected";
  document.querySelector("#status-email").textContent = data.notifikasi ? "Active" : "Pending";
}

// untuk reservasi hari ini
async function loadTodayReservations() {
  const res = await fetch(`${API_BASE}/dashboard_today.php`);
  const data = await res.json();
  const container = document.getElementById("today-reservations");
  container.innerHTML = "";
  if (data.length === 0) {
    container.innerHTML = `<p class="text-gray-500 text-sm">Tidak ada reservasi hari ini.</p>`;
    return;
  }
  data.forEach(r => {
    container.innerHTML += `
      <div class="p-3 border border-gray-100 rounded-lg flex justify-between items-center">
        <div>
          <p class="font-medium text-dark">${r.nama} (${r.jumlah_orang} tamu)</p>
          <p class="text-gray-500 text-sm">${r.tanggal_reservasi} ${r.slot_label || r.waktu_reservasi}</p>
        </div>
        <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-700">Diterima</span>
      </div>
    `;
  });
}

// mapping select “Pilih Waktu” → slot_id
function getSelectedSlotId() {
  const val = document.getElementById("table-time-filter").value; // "12:00-14:00"
  const map = {
    "12:00-14:00": 1,
    "14:00-16:00": 2,
    "16:00-18:00": 3,
    "18:00-20:00": 4,
    "20:00-22:00": 5
  };
  return map[val] || 1;
}

function getSelectedDate() {
  const inp = document.getElementById("table-date-filter");
  return inp.value || new Date().toISOString().slice(0,10);
}

// Render grid meja
function renderTables(data) {
  const grid = document.getElementById("tables-grid");
  grid.innerHTML = "";

  (data.daftar_meja || []).forEach(meja => {
    let color, label, clickable = false;

    if (meja.status === "reservasi") {
      color = "bg-red-200 border-red-300";  label = "Terisi (Reservasi)";
    } else if (meja.status === "manual") {
      color = "bg-yellow-200 border-yellow-300"; label = "Terisi (Manual)";
    } else {
      color = "bg-green-200 border-green-300"; label = "Tersedia"; clickable = true;
    }

    const el = document.createElement("div");
    el.className = `p-4 rounded-lg text-center font-medium border ${color} select-none transition`;
    el.innerHTML = `
      <div class="font-semibold">Meja ${meja.meja_id}</div>
      <div class="text-xs">${label}</div>
      ${meja.nama_pelanggan ? `<div class="text-xs text-gray-600 mt-1">${meja.nama_pelanggan} (${meja.jumlah_orang||0} orang)</div>` : ""}
    `;

    if (clickable) {
      el.classList.add("cursor-pointer","hover:opacity-80");
      el.title = "Klik untuk set Terisi (Manual)";
      el.addEventListener("click", async () => {
        const tanggal = getSelectedDate();
        const slot_id = getSelectedSlotId();
        try {
          const res = await fetch(`${API_BASE}/update_table_status.php`, {
            method: "POST",
            headers: { "Content-Type":"application/json" },
            credentials: "include",
            body: JSON.stringify({ meja_id: meja.meja_id, tanggal, slot_id })
          });
          const out = await res.json();
          if (!out.success) throw new Error(out.error || "Gagal");
          refreshTableStatus(); // reload grid
        } catch (e) {
          alert(e.message || "Gagal set terisi.");
        }
      });
    } else {
      el.classList.add("cursor-not-allowed");
    }

    grid.appendChild(el);
  });
}

// Ambil status meja (dipanggil saat load & tombol Refresh)
async function refreshTableStatus() {
  const tanggal = getSelectedDate();
  const slot_id = getSelectedSlotId();

  const url = `${API_BASE}/tables_status.php?tanggal=${encodeURIComponent(tanggal)}&slot=${slot_id}`;
  const res = await fetch(url, { credentials: "include" });
  const data = await res.json();

  // Header info
  document.getElementById('reservation-count-info').textContent =
    `${data.reservasi_diterima || 0} reservasi diterima`;
  document.getElementById('occupied-count').textContent =
    `${data.meja_terisi || 0}/${data.total_meja || 0} meja terisi`;

  renderTables(data);
}

// Load list berdasarkan filter tab
async function loadReservations(status = 'all') {
  const res = await fetch(`${API_BASE}/reservations_list.php?status=${status}`, { credentials: 'include' });
  const data = await res.json();

  const container = document.getElementById('reservations-list');
  container.innerHTML = '';
  if (!data.length) {
    container.innerHTML = `<p class="text-gray-500 text-sm">Tidak ada data reservasi.</p>`;
    return;
  }

  data.forEach(r => {
    const badge =
      r.status === 'diterima' ? 'bg-green-100 text-green-700' :
      r.status === 'ditolak'  ? 'bg-red-100 text-red-700' :
                                'bg-yellow-100 text-yellow-700';
    // Fungsi bantu untuk kapitalisasi huruf pertama
function capitalizeFirstLetter(text) {
  return text.charAt(0).toUpperCase() + text.slice(1).toLowerCase();
}

container.innerHTML += `
  <div class="p-4 bg-white border border-gray-200 rounded-lg mb-3 flex justify-between items-center">
    <!-- Kiri: info pelanggan -->
    <div>
      <div class="mb-1">
  <span class="font-semibold text-dark text-base">${r.nama}</span>
  <span class="text-sm text-gray-500 ml-2">(${r.kode_booking || '-'})</span>
</div>
      <div class="text-sm text-gray-600">${r.no_wa || '-'}</div>
      <div class="text-sm text-gray-500 mt-1">${r.tanggal} • ${r.slot_label || r.waktu}</div>
      <div class="text-sm text-gray-500">${r.jumlah_orang} orang</div>
    </div>

    <!-- Kanan: status + tombol -->
    <div class="flex flex-col items-end space-y-2">
      <span class="px-3 py-1 text-xs rounded-lg font-medium ${badge}">
        ${capitalizeFirstLetter(r.status)}
      </span>

      <div class="flex space-x-2">
        <button class="px-3 py-1 text-sm rounded bg-blue-600 text-white hover:bg-blue-700"
          onclick="showReservationDetail(${r.reservasi_id})">Detail</button>

        ${r.status === 'menunggu' ? `
          <button class="px-3 py-1 text-sm rounded bg-green-600 text-white hover:bg-green-700"
            onclick="updateReservation(${r.reservasi_id}, 'accept')">Terima</button>
          <button class="px-3 py-1 text-sm rounded bg-red-600 text-white hover:bg-red-700"
            onclick="updateReservation(${r.reservasi_id}, 'reject')">Tolak</button>
        ` : ``}
      </div>
    </div>
  </div>
`;
  });
}

// Detail modal
// File: coba.js

// --- Letakkan fungsi helper modal ini di luar (top-level) ---
// (Anda mungkin sudah punya ini di global, pastikan tidak duplikat)

// preview bukti di modal gambar (global, dipanggil dari onclick HTML)
window.__openProof = function (src) {
    const m = document.getElementById('image-modal');
    const img = document.getElementById('modal-image');
    img.src = src;
    m.classList.remove('hidden'); m.classList.add('flex');
};

window.closeImageModal = function () {
    const m = document.getElementById('image-modal');
    const img = document.getElementById('modal-image');
    img.src = '';
    m.classList.add('hidden'); m.classList.remove('flex');
};

// ==== Ini adalah satu-satunya fungsi yang dipanggil dari list ====
async function showReservationDetail(id) {
    // Definisikan helper di dalam fungsi agar tidak polusi global
    const cap = s => s ? s.charAt(0).toUpperCase() + s.slice(1).toLowerCase() : s;
    const badge = s => {
        switch ((s || '').toLowerCase()) {
            case 'diterima': return 'bg-green-100 text-green-800';
            case 'ditolak' : return 'bg-red-100 text-red-800';
            default        : return 'bg-yellow-100 text-yellow-800'; // menunggu/unpaid/pending
        }
    };

    const res = await fetch(`${API_BASE}/reservation_detail.php?id=${id}`, { credentials: 'include' });
    const r   = await res.json();

    const modal   = document.getElementById('reservation-detail-modal');
    const content = document.getElementById('reservation-detail-content');

    // --- LOGIKA PROOFBLOCK DIPERBAIKI ---
    const proofUrl = `${API_BASE}/payment_proof.php?reservasi_id=${id}`;
    let hasProof = false;
    let notFound = false; // Flag untuk 404

    try {
        const head = await fetch(proofUrl, { method: 'HEAD', credentials: 'include' });
        hasProof = head.ok;
        if (head.status === 404) {
            notFound = true; // Tandai jika file tidak ditemukan
        }
    } catch (_) {
        notFound = true; // Anggap tidak ada jika koneksi gagal
    }

    const proofBlock = hasProof
      // JIKA BUKTI ADA: Tampilkan gambar + teks zoom
      ? '<img src="' + proofUrl + '" alt="Bukti Pembayaran" ' +
        'class="w-full rounded-lg border cursor-zoom-in" ' +
        'onclick="window.__openProof(\'' + proofUrl + '\')">' +
        // Teks zoom HANYA muncul jika ada gambar
        '<div class="text-xs text-gray-500 text-center mt-2">Klik gambar untuk memperbesar</div>'
      
      // JIKA BUKTI TIDAK ADA (404 atau Error)
      : '<div class="h-[84px] w-full rounded-lg border flex items-center justify-center text-gray-400 text-sm">' +
        // Ganti pesan menjadi lebih akurat
        'Belum ada bukti pembayaran' +
        '</div>';
    // --- AKHIR LOGIKA PROOFBLOCK ---

    let aksiBlock = ''; // Buat variabel kosong
    
    // Hanya isi variabel jika statusnya 'menunggu'
    if (r.status === 'menunggu') {
        aksiBlock = 
        '<div class="lg:col-span-2 bg-blue-50 rounded-2xl border border-blue-100 p-6">' +
          '<h4 class="text-lg font-semibold text-dark mb-4">Aksi Reservasi</h4>' +
          '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">' +
            '<button class="w-full px-5 py-3 rounded-lg bg-green-600 text-white font-semibold hover:bg-green-700 transition" ' +
              'onclick="updateReservation(' + id + ', \'accept\')">Terima Reservasi</button>' +
            '<button class="w-full px-5 py-3 rounded-lg bg-red-600 text-white font-semibold hover:bg-red-700 transition" ' +
              'onclick="updateReservation(' + id + ', \'reject\')">Tolak Reservasi</button>' +
          '</div>' +
        '</div>';
    }

    // RENDER — (Tampilan HTML ini TIDAK diubah, hanya 'proofBlock' yang disuntikkan)
    content.innerHTML =
      '<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">' +

        // Informasi pelanggan
        '<div class="bg-white rounded-2xl border border-gray-200 p-6">' +
          '<div class="flex items-center mb-4">' +
            '<span class="w-8 h-8 rounded-lg bg-yellow-100 text-yellow-600 flex items-center justify-center mr-3">' +
              '<svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10 10a4 4 0 100-8 4 4 0 000 8zm-7 8a7 7 0 1114 0H3z"/></svg>' +
            '</span>' +
            '<h4 class="text-lg font-semibold text-dark">Informasi Pelanggan</h4>' +
          '</div>' +
          '<div class="grid gap-3 text-sm">' +
            '<div class="flex"><span class="text-gray-500 w-24">Nama:</span><span class="font-medium text-dark">' + (r.nama || '-') + '</span></div>' +
            '<div class="flex"><span class="text-gray-500 w-24">Telepon:</span><span class="font-medium text-dark">' + (r.no_wa || '-') + '</span></div>' +
            '<div class="flex"><span class="text-gray-500 w-24">Email:</span><span class="font-medium text-dark">' + (r.email || '-') + '</span></div>' +
          '</div>' +
        '</div>' +

        // Bukti pembayaran (Disuntik dari proofBlock)
        '<div class="bg-white rounded-2xl border border-gray-200 p-6">' +
          '<div class="flex items-center mb-4">' +
            '<span class="w-8 h-8 rounded-lg bg-yellow-100 text-yellow-600 flex items-center justify-center mr-3">' +
              '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M21 19V5a2 2 0 0 0-2-2H5C3.9 3 3 3.9 3 5v14c0 1.1.9 2 2 2h14a2 2 0 0 0 2-2zM8.5 11.5A2.5 2.5 0 1 1 11 9a2.5 2.5 0 0 1-2.5 2.5zM5 19l4.5-6 3.5 4.5 2.5-3L19 19H5z"/></svg>' +
            '</span>' +
            '<h4 class="text-lg font-semibold text-dark">Bukti Pembayaran</h4>' +
          '</div>' +
          proofBlock + // <-- Variabel hasil revisi logika
        '</div>' +

        // Detail reservasi
        '<div class="bg-white rounded-2xl border border-gray-200 p-6">' +
          '<div class="flex items-center mb-4">' +
            '<span class="w-8 h-8 rounded-lg bg-yellow-100 text-yellow-600 flex items-center justify-center mr-3">' +
              '<svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor"><path d="M6 2a1 1 0 011 1v1h6V3a1 1 0 112 0v1h1a2 2 0 012 2v2H3V6a2 2 0 012-2h1V3a1 1 0 011-1z"/><path d="M3 9h14v7a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/></svg>' +
            '</span>' +
            '<h4 class="text-lg font-semibold text-dark">Detail Reservasi</h4>' +
          '</div>' +
          '<div class="grid gap-3 text-sm">' +
          '<div class="flex"><span class="text-gray-500 w-28">Kode Booking:</span><span class="font-medium text-dark">' + (r.kode_booking || '-') + '</span></div>' +
            '<div class="flex"><span class="text-gray-500 w-28">Tanggal:</span><span class="font-medium text-dark">' + (r.tanggal || '-') + '</span></div>' +
            '<div class="flex"><span class="text-gray-500 w-28">Waktu:</span><span class="font-medium text-dark">' + (r.slot_label || r.waktu || '-') + '</span></div>' +
            '<div class="flex"><span class="text-gray-500 w-28">Jumlah Tamu:</span><span class="font-medium text-dark">' + (r.jumlah_orang || 0) + ' orang</span></div>' +
          '</div>' +
        '</div>' +

        // Status & waktu
        '<div class="bg-white rounded-2xl border border-gray-200 p-6">' +
          '<div class="flex items-center mb-4">' +
            '<span class="w-8 h-8 rounded-lg bg-yellow-100 text-yellow-600 flex items-center justify-center mr-3">' +
              '<svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10 2a8 8 0 100 16 8 8 0 000-16zM9 5h2v5l4 2-1 1-5-3V5z"/></svg>' +
            '</span>' +
            '<h4 class="text-lg font-semibold text-dark">Status & Waktu</h4>' +
          '</div>' +
          '<div class="grid gap-3 text-sm">' +
            '<div class="flex items-center justify-between">' +
              '<span class="text-gray-500">Status:</span>' +
              '<span class="px-3 py-1 rounded-full text-xs font-semibold ' + badge(r.status) + '">' +
                cap(r.status || 'menunggu') +
              '</span>' +
            '</div>' +
            '<div class="flex items-center justify-between">' +
              '<span class="text-gray-500">Dibuat:</span>' +
              '<span class="font-medium text-dark">' + (r.tanggal_pembayaran ? formatWaktu(r.tanggal_pembayaran) : '-') + '</span>' +
            '</div>' +
          '</div>' +
        '</div>' +
        aksiBlock +
      '</div>';


    modal.classList.remove('hidden');
    modal.classList.add('flex');
};

function hideReservationDetail(){ 
  const modal = document.getElementById('reservation-detail-modal');
  modal.classList.add('hidden'); modal.classList.remove('flex');
}

function openImageModal(src) {
  $("#modal-image").src = src;
  $("#image-modal").classList.remove("hidden");
  $("#image-modal").classList.add("flex");
}

function closeImageModal() {
  $("#image-modal").classList.add("hidden");
  $("#image-modal").classList.remove("flex");
}

// Terima/Tolak
async function updateReservation(id, action) {
  const res = await fetch(`${API_BASE}/update_reservation_status.php`, {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    credentials: 'include',
    body: JSON.stringify({ reservasi_id: id, action })
  });
  const out = await res.json();
  if (!out.success) { alert(out.error || 'Gagal update status'); return; }
  // reload tab yang aktif
  const active = document.querySelector('.reservation-filter.active')?.dataset.status || 'all';
  loadReservations(active);
}

async function renderDashboardCharts() {
  const el = (id) => document.getElementById(id);

  const ensureCanvas = (hostId, canvasId) => {
    const host = el(hostId);
    let cvs = el(canvasId);
    if (!cvs) {
      host.innerHTML = ''; // bersihkan sisa
      cvs = document.createElement('canvas');
      cvs.id = canvasId;
      cvs.style.width = '100%';
      cvs.style.height = '100%';
      host.appendChild(cvs);
    }
    return cvs.getContext('2d');
  };

  try {
    const resp = await fetch(`${API_BASE}/dashboard_charts.php`, { credentials: 'include' });
    const data = await resp.json();

    // Line: Trend Reservasi
    new Chart(ensureCanvas('trend-chart', 'trendCanvas'), {
      type: 'line',
      data: {
        labels: data.trend.labels,
        datasets: [{ data: data.trend.data, tension: 0.35, fill: false, label: 'Jumlah Reservasi' }]
      },
      options: { responsive: true, maintainAspectRatio: false }
    });

    // Bar: Pendapatan Mingguan
    new Chart(ensureCanvas('revenue-chart', 'revenueCanvas'), {
      type: 'bar',
      data: {
        labels: data.revenue.labels,
        datasets: [{ data: data.revenue.data, label: 'Pendapatan (Rp)' }]
      },
      options: { responsive: true, maintainAspectRatio: false }
    });

    // Pie: Distribusi Status
    const dist = data.status_distribution;
    new Chart(ensureCanvas('status-pie-chart', 'statusPieCanvas'), {
      type: 'pie',
      data: {
        labels: ['Belum Bayar', 'Menunggu', 'Diterima', 'Ditolak'],
        datasets: [{ data: [dist.unpaid, dist.pending, dist.confirmed, dist.canceled] }]
      },
      options: { responsive: true, maintainAspectRatio: false }
    });

    // Area (line filled): Jam Sibuk
    new Chart(ensureCanvas('peak-hours-chart', 'peakCanvas'), {
      type: 'line',
      data: {
        labels: data.peak_hours.labels,
        datasets: [{ data: data.peak_hours.data, fill: true, tension: 0.35, label: 'Jumlah Reservasi per Slot' }]
      },
      options: { responsive: true, maintainAspectRatio: false }
    });

  } catch (e) {
    console.error('Gagal render charts:', e);
  }
}

// ---- Reservasi Hari Ini (dashboard) ----
async function renderTodayReservations() {
  const box = document.getElementById('today-reservations');
  if (!box) return; // elemen belum ada di DOM

  // skeleton/loading
  box.innerHTML = `
    <div class="animate-pulse">
      <div class="h-4 bg-gray-200 rounded w-1/2 mb-3"></div>
      <div class="h-3 bg-gray-200 rounded w-2/3 mb-2"></div>
      <div class="h-3 bg-gray-200 rounded w-1/3"></div>
    </div>
  `;

  try {
    const res = await fetch(`${API_BASE}/dashboard_today.php`, { credentials: 'include' });
    if (!res.ok) {
      const t = await res.text();
      throw new Error(`HTTP ${res.status}: ${t}`);
    }
    const data = await res.json();

    if (!Array.isArray(data) || data.length === 0) {
      box.innerHTML = `
        <div class="text-sm text-gray-500">Belum ada reservasi yang <b>diterima</b> untuk hari ini.</div>
      `;
      return;
    }

    // render list
    box.innerHTML = data.map(item => {
      const waktu = item.slot_label || (item.waktu_reservasi ? item.waktu_reservasi.slice(0,5) : '-');
      const meja  = item.meja || '-';
      return `
        <div class="border border-gray-200 rounded-lg p-4 flex items-center justify-between">
          <div>
            <p class="font-semibold text-dark">${item.nama || 'Walk-in'}</p>
            <p class="text-sm text-gray-600">${item.tanggal_reservasi} • ${waktu}</p>
            <p class="text-sm text-gray-600">Meja: ${meja} • ${item.jumlah_orang || 0} orang</p>
          </div>
          <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">Diterima</span>
        </div>
      `;
    }).join('');
  } catch (err) {
    console.error('renderTodayReservations error:', err);
    box.innerHTML = `
      <div class="text-sm text-red-600">
        Gagal memuat data. <span class="text-xs text-gray-500">Cek console log.</span>
      </div>
    `;
  }
}




// panggil saat halaman siap (pastikan ini sudah dipanggil di inisialisasi halaman Dashboard)
document.addEventListener('DOMContentLoaded', () => {
  // kalau halaman multi-tab, panggil hanya saat tab "Dashboard" aktif
  renderDashboardCharts();
});

// Hook tombol filter
document.querySelectorAll('.reservation-filter')?.forEach(btn=>{
  btn.addEventListener('click', ()=>{
    document.querySelectorAll('.reservation-filter').forEach(b=>b.classList.remove('active','bg-white','text-dark','shadow-sm'));
    btn.classList.add('active','bg-white','text-dark','shadow-sm');
    loadReservations(btn.dataset.status);
  });
});

// Panggil awal
// (panggil loadReservations('all') saat halaman dibuka / saat nav ke "Kelola Reservasi")
