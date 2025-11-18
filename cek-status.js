// ==== UTILITIES ====
function showToast(msg, isErr = false) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.classList.toggle('error', !!isErr);
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 2200);
}

const input = document.getElementById('searchInput');
const btn = document.getElementById('searchBtn');
const changeBtn = document.getElementById('changeBtn');
const resultContainer = document.getElementById('resultContainer');
const notFound = document.getElementById('notFound');

// ==== STATUS HELPERS (DISESUAIKAN DENGAN DB 'reservasi.status') ====
function getStatusMessage(status) {
  switch (status) {
    case 'confirmed':
      return 'Selamat! Reservasi Anda telah dikonfirmasi. Kami tunggu kedatangan Anda.';
    case 'canceled':
      return 'Maaf, reservasi Anda tidak dapat diproses. Silakan hubungi kami untuk informasi lebih lanjut.';
    case 'pending':
      return 'Bukti pembayaran Anda sedang dalam proses verifikasi. Mohon tunggu konfirmasi dari kami.';
    case 'unpaid':
      return 'Maaf, Anda belum melakukan pembayaran. Silakan selesaikan proses pembayaran untuk mengonfirmasi reservasi anda.';
    default:
      return 'Status reservasi tidak diketahui.';
  }
}

function getStatusText(status) {
  switch (status) {
    case 'confirmed':
      return 'Diterima';
    case 'canceled':
      return 'Ditolak';
    case 'pending':
      return 'Menunggu Verifikasi';
    case 'unpaid':
      return 'Menunggu Pembayaran';
    default:
      return 'Tidak Ditemukan';
  }
}

function getStatusIcon(status) {
  const iconElement = document.getElementById('statusIcon');
  iconElement.innerHTML = '';

  const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
  svg.setAttribute('viewBox', '0 0 24 24');
  svg.setAttribute('fill', 'none');
  svg.setAttribute('stroke', 'currentColor');
  svg.setAttribute('stroke-width', '2');

  switch (status) {
    case 'confirmed':
      // Ikon Checkmark (Sukses)
      svg.innerHTML =
        '<circle cx="12" cy="12" r="10"/><polyline points="16 12 12 8 8 12"/><line x1="12" y1="16" x2="12" y2="8"/>';
      svg.style.color = 'var(--success)'; // Hijau
      break;
    case 'canceled':
      // Ikon X (Ditolak)
      svg.innerHTML = '<circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>';
      svg.style.color = 'var(--danger)'; // Merah
      break;
    case 'pending':
      // Ikon Jam (Menunggu)
      svg.innerHTML = '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>';
      svg.style.color = 'var(--warning)'; // Oranye
      break;
    case 'unpaid':
      // Ikon Dolar/Koin (Belum Bayar)
      svg.innerHTML = `
    <circle cx="12" cy="12" r="10"/>
    <ellipse cx="12" cy="10" rx="6" ry="3" stroke-width="1.5"/>
    <path d="M6 10v4a6 3 0 0 0 12 0v-4" stroke-width="1.5"/>
  `;
      svg.style.color = 'var(--bs-info)'; // Biru
      break;
  }

  iconElement.appendChild(svg);
}

// ==== FORMAT TANGGAL ====
function formatID(dt) {
  if (!dt) return '-';
  // Cek jika format dari DB adalah 'YYYY-MM-DD HH:MM:SS'
  const d = new Date(String(dt).replace(' ', 'T'));
  // Cek jika 'd' valid
  if (isNaN(d.getTime())) {
    return '-'; // Return strip jika tanggal tidak valid
  }
  return d.toLocaleString('id-ID', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}

// ==== RENDER STATUS ====
function paint(d) {
  notFound.style.display = 'none';
  resultContainer.style.display = 'block';

  const status = d.status || 'unpaid'; // Default ke unpaid jika status null

  // Status
  getStatusIcon(status);

  const title = document.getElementById('statusTitle');
  
  // --- INI ADALAH PERBAIKAN UTAMA ---
  // Sekarang kita cek status dari database ('confirmed', 'canceled', 'pending', 'unpaid')
  let colorClass = 'warning'; // Default oranye
  if (status === 'confirmed') {
    colorClass = 'success'; // Hijau
  } else if (status === 'canceled') {
    colorClass = 'danger'; // Merah
  } else if (status === 'unpaid') {
    colorClass = 'info'; // Biru
  }
  // Jika 'pending', akan tetap 'warning' (oranye)
  
  title.className = 'status-title ' + colorClass;
  title.textContent = getStatusText(status);
  // --- AKHIR PERBAIKAN ---


  // Kode dan Pesan
  document.getElementById('displayCode').textContent = d.code || '-';
  document.getElementById('statusMessage').textContent = getStatusMessage(status);

  // Tanggal upload bukti (Hanya tampil jika status BUKAN unpaid)
  const uploadDateRow = document.querySelector('.detail-row'); // Asumsi ini baris pertama
  if (status !== 'unpaid' && d.uploadedAt) {
      document.getElementById('uploadDate').textContent = formatID(d.uploadedAt);
      uploadDateRow.style.display = 'flex';
  } else if (status !== 'unpaid' && !d.uploadedAt) {
      document.getElementById('uploadDate').textContent = "Belum Upload";
      uploadDateRow.style.display = 'flex';
  } else {
      uploadDateRow.style.display = 'none'; // Sembunyikan tgl upload jika 'unpaid'
  }


  // (Ini bisa Anda gunakan nanti jika ada tanggal proses admin)
  const pr = document.getElementById('processedRow');
  pr.style.display = 'none'; 
  // if (d.processedAt) {
  //   pr.style.display = 'flex';
  //   document.getElementById('processedDate').textContent = formatID(d.processedAt);
  // }

  // Catatan sesuai status
  document.getElementById('approvedNote').style.display =
    (status === 'confirmed') ? 'block' : 'none';
  document.getElementById('pendingNote').style.display =
    (status === 'pending') ? 'block' : 'none';
  document.getElementById('rejectedActions').style.display =
    (status === 'canceled') ? 'flex' : 'none';
    
  // Tampilkan tombol pembayaran hanya jika status 'unpaid'
  const unpaidContainer = document.getElementById('unpaidActions');
  const paymentButton = document.getElementById('paymentButton');

  if (status === 'unpaid') {
    unpaidContainer.style.display = 'block';
    // Arahkan ke halaman konfirmasi-pembayaran (atau halaman pembayaran jika ada)
    paymentButton.href = 'konfirmasi-pembayaran.php?code=' + encodeURIComponent(d.code);
  } else {
    unpaidContainer.style.display = 'none';
  }
}

// ==== EVENT UNTUK INPUT MANUAL ====
input.addEventListener('input', (e) => (e.target.value = e.target.value.toUpperCase()));
input.addEventListener('keypress', (e) => {
  if (e.key === 'Enter') doSearch();
});
btn.addEventListener('click', doSearch);

// ==== FUNGSI PENCARIAN ====
async function doSearch() {
  const code = (input.value || '').trim().toUpperCase();
  if (!code) {
    showToast('Kode booking diperlukan', true);
    return;
  }

  btn.disabled = true;
  btn.textContent = 'Mencari...';
  notFound.style.display = 'none';
  resultContainer.style.display = 'none'; // Sembunyikan hasil lama

  try {
    const res = await fetch('cek-status.php?ajax=1&code=' + encodeURIComponent(code));
    const data = await res.json();

    if (!data || data.found === false) {
      resultContainer.style.display = 'none';
      notFound.style.display = 'block';
    } else {
      // 'data' akan berisi { found: true, code: '...', status: '...', uploadedAt: '...' }
      paint(data);
      input.disabled = true;
      showToast('Status ditemukan');
    }
  } catch (e) {
    showToast('Terjadi kesalahan jaringan', true);
    console.error(e);
  } finally {
    btn.disabled = false;
    btn.textContent = 'Cek';
  }
}

// ==== OTOMATIS TAMPIL SAAT HALAMAN DIBUKA ====
window.addEventListener('DOMContentLoaded', () => {
  // 'initialData' diambil dari <script> di file PHP
  if (typeof initialData !== 'undefined' && initialData) {
    paint(initialData);

    // isi otomatis input & disable
    input.value = initialData.code;
    input.disabled = true;

    // tampilkan hasilnya langsung
    resultContainer.style.display = 'block';
    notFound.style.display = 'none';
  } else if (input.value) {
      // Jika ada kode di URL tapi data tidak ketemu (cth: kode salah)
      notFound.style.display = 'block';
  }
});

// ==== FITUR GANTI KODE BOOKING ====
changeBtn.addEventListener('click', () => {
  input.disabled = false;
  input.value = '';
  input.focus();
  resultContainer.style.display = 'none';
  notFound.style.display = 'none';
  // Hapus data awal agar tidak bentrok
  window.initialData = null; 
});