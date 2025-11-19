// Ambil kode booking YANG SUDAH DICETAK PHP
const bookingCodeEl = document.getElementById('bookingCode');
const bookingCode = (bookingCodeEl ? bookingCodeEl.textContent : '').trim().toUpperCase();

// Safety check
if (!bookingCode) {
  // Jika sampai kosong, berarti halaman tidak dipanggil dengan ?code=...
  // Anda bisa tampilkan toast atau diamkan.
  console.warn('Kode booking kosong. Pastikan membuka halaman ini dengan ?code=KODE_BOOKING');
}

// Set tautan "Cek Status Reservasi" agar menyertakan kode
const cekStatusLink = document.getElementById('cekStatusLink');
if (cekStatusLink && bookingCode) {
  cekStatusLink.href = 'cek-status.php?code=' + encodeURIComponent(bookingCode);
}

// Toast
function showToast(message, isError = false) {
  const toast = document.getElementById('toast');
  toast.textContent = message;
  if (isError) toast.classList.add('error'); else toast.classList.remove('error');
  toast.classList.add('show');
  setTimeout(() => toast.classList.remove('show'), 3000);
}

// Upload state & elemen
let uploadedFile = null;
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');
const preview = document.getElementById('preview');
const previewImage = document.getElementById('previewImage');
const fileName = document.getElementById('fileName');
const removeBtn = document.getElementById('removeBtn');
const submitBtn = document.getElementById('submitBtn');

// Drag & Drop
dropZone.addEventListener('dragover', (e) => {
  e.preventDefault();
  dropZone.classList.add('dragging');
});
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragging'));
dropZone.addEventListener('drop', (e) => {
  e.preventDefault();
  dropZone.classList.remove('dragging');
  const file = e.dataTransfer.files[0];
  if (file && file.type.startsWith('image/')) handleFileUpload(file);
  else showToast('Format tidak valid. Silakan upload file gambar (JPG, PNG)', true);
});

// Klik untuk pilih file
dropZone.addEventListener('click', () => fileInput.click());
fileInput.addEventListener('change', (e) => {
  const file = e.target.files[0];
  if (file) handleFileUpload(file);
});

// Upload handler
function handleFileUpload(file) {
  uploadedFile = file;
  const reader = new FileReader();
  reader.onload = (e) => {
    previewImage.src = e.target.result;
    fileName.textContent = file.name;
    dropZone.style.display = 'none';
    preview.style.display = 'block';
  };
  reader.readAsDataURL(file);
  showToast('Berhasil diupload');
}

// Hapus file
removeBtn.addEventListener('click', (e) => {
  e.stopPropagation();
  uploadedFile = null;
  previewImage.src = '';
  fileName.textContent = '';
  preview.style.display = 'none';
  dropZone.style.display = 'block';
  fileInput.value = '';
});

// Submit
submitBtn.addEventListener('click', () => {
  if (!uploadedFile) {
    showToast('Bukti pembayaran diperlukan. Silakan upload bukti pembayaran terlebih dahulu', true);
    return;
  }
  if (!bookingCode) {
    showToast('Kode booking tidak ditemukan.', true);
    return;
  }

  const formData = new FormData();
formData.append('kode_booking', bookingCode);
formData.append('bukti', uploadedFile);

fetch('konfirmasi-pembayaran.php', {
  method: 'POST',
  body: formData
})
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      showToast('Bukti pembayaran berhasil dikirim!');
      setTimeout(() => {
        window.location.href = 'cek-status.php?code=' + encodeURIComponent(bookingCode);
      }, 1500);
    } else {
      showToast(data.message, true);
    }
  })
  .catch(err => {
    showToast('Terjadi kesalahan koneksi', true);
    console.error(err);
  });
});

// Fitur salin kode
const copyBtn = document.getElementById('copyBtn');
const codeText = document.getElementById('bookingCode');

// Pastikan ikon awal selalu copy
copyBtn.innerHTML = '<i class="bi bi-clipboard"></i>';

copyBtn.addEventListener('click', async () => {
  const text = codeText.textContent.trim();
  if (!text) return;

  try {
    await navigator.clipboard.writeText(text);
    showToast('Kode disalin');
    // ubah ikon sementara
    copyBtn.innerHTML = '<i class="bi bi-clipboard-check"></i>';
    setTimeout(() => {
      copyBtn.innerHTML = '<i class="bi bi-clipboard"></i>';
    }, 1200);
  } catch (err) {
    showToast('Gagal menyalin', true);
  }
  });
