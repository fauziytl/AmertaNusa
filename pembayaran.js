// Data reservasi (biasanya dari session/localStorage/API)

const depositPerPerson = 25000;
let selectedMethod = null;

function formatCurrency(amount) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(amount);
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('id-ID', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    }).format(date);
}

function selectPaymentMethod(method) {
    selectedMethod = method;
    
    // Update button states
    document.querySelectorAll('.payment-method').forEach(btn => {
        btn.classList.remove('selected');
    });
    document.querySelector(`[data-method="${method}"]`).classList.add('selected');
    
    // Hide all payment details
    document.querySelectorAll('.payment-detail-content').forEach(el => {
        el.style.display = 'none';
    });
    
    // Show selected payment details
    document.getElementById('payment-details').style.display = 'block';
    document.getElementById(method + '-details').style.display = 'block';
    
    // Update payment button
    const paymentBtn = document.getElementById('payment-btn');
    paymentBtn.disabled = false;
    
    if (method === 'card') {
        paymentBtn.textContent = 'Bayar';
    } else {
        paymentBtn.textContent = 'Konfirmasi Pembayaran';
    }
}

function copyBookingCode() {
    const bookingCode = reservationData.bookingCode;
    navigator.clipboard.writeText(bookingCode).then(() => {
        showToast('Kode booking telah disalin!');
        
        // Change icon temporarily
        const copyBtn = document.getElementById('copy-btn');
        copyBtn.innerHTML = `
            <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
            </svg>
        `;
        
        setTimeout(() => {
            copyBtn.innerHTML = `
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/>
                </svg>
            `;
        }, 2000);
    });
}

function copyText(text, label) {
    navigator.clipboard.writeText(text).then(() => {
        showToast('Nomor rekening ' + label + ' disalin!');
    });
}

function processPayment() {
    if (!selectedMethod) {
        showToast('Silakan pilih metode pembayaran!');
        return;
    }

    const kode_booking = document.getElementById('booking-code').textContent.trim();

    if (!kode_booking) {
        showToast('Kode booking tidak ditemukan!');
        return;
    }

    console.log("DEBUG: Data yang akan dikirim ke server:");
    console.log({
        kode_booking: kode_booking,
        metode: selectedMethod
    });

    const formData = new FormData();
    formData.append('kode_booking', kode_booking);
    formData.append('metode', selectedMethod);

    fetch('pembayaran.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log("DEBUG: Response dari server:", data);
        if (data.success) {
            showToast('Pembayaran berhasil diproses!');
            setTimeout(() => {
                window.location.href = 'konfirmasi-pembayaran.php?code=' + kode_booking;
            }, 2000);
        } else {
            showToast('Terjadi kesalahan: ' + data.message);
        }
    })
    .catch(error => {
        showToast('Terjadi kesalahan koneksi!');
        console.error('Error:', error);
    });
}


function showToast(message) {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.classList.add('show');
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}
