function loadFilmsAdmin() {
  const tbody = document.getElementById('film-tbody');
  if (!tbody) return;

  tbody.innerHTML = '<tr><td colspan="7" class="p-4 text-center text-gray-500">Memuat data film...</td></tr>';
  const status = document.getElementById('filter-film-status')?.value ?? '';
  fetch(`../backend/film_api.php?action=list&status=${status}`)
    .then(res => res.json().catch(() => { throw new Error("Respons bukan JSON") }))
    .then(response => {
      if (response.status !== 'success') throw new Error(response.message || "Gagal memuat data");
      const filmsData = response.data;
      
      tbody.innerHTML = '';
      if (!Array.isArray(filmsData) || filmsData.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="p-4 text-center text-gray-500">Tidak ada data film.</td></tr>';
        return;
      }

      filmsData.forEach((film) => {
        tbody.innerHTML += `
          <tr class="hover:bg-gray-50">
            <td class="px-6 py-4"><div class="text-sm font-medium text-gray-900">${film.title}</div></td>
            <td class="px-6 py-4 hidden md:table-cell"><div class="text-sm">${film.director || 'N/A'}</div></td>
            <td class="px-6 py-4 hidden lg:table-cell"><div class="text-sm">${film.genre || 'N/A'}</div></td>
            <td class="px-6 py-4 hidden lg:table-cell"><div class="text-sm">${film.duration ? film.duration + ' menit' : 'N/A'}</div></td>
            <td class="px-6 py-4"><span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ${statusColor(film.status)}">${statusLabel(film.status)}</span></td>
            <td class="px-6 py-4 text-right">
              <div class="flex justify-end items-center gap-2">
                <button onclick="detailFilm(${film.id_film})" class="text-gray-400 hover:text-green-600" title="Detail"><i class="fas fa-eye"></i></button>
                <button onclick="editFilm(${film.id_film})" class="text-gray-400 hover:text-blue-600" title="Edit"><i class="fas fa-pencil-alt"></i></button>
                <button onclick="hapusFilm(${film.id_film})" class="text-gray-400 hover:text-red-600" title="Hapus"><i class="fas fa-trash"></i></button>
              </div>
            </td>
          </tr>
        `;
      });
    })
    .catch(handleFetchError);
}

function submitFilmForm() {
    const form = document.getElementById('formFilm');
    const formData = new FormData(form);
    const filmId = form.id_film.value;
    const action = filmId ? 'update' : 'create';
    
    formData.append('action', action);

    fetch('../backend/film_api.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') {
                Swal.fire({ icon: 'success', title: 'Berhasil!', text: res.message, timer: 2000, showConfirmButton: false });
                batalEditFilm();
                refreshAllData(); 
            } else {
                Swal.fire({ icon: 'error', title: 'Gagal!', text: res.message || 'Terjadi kesalahan.' });
            }
        })
        .catch(handleFetchError);
}

function editFilm(id) {
  fetch(`../backend/film_api.php?action=get&id=${id}`)
    .then(res => res.json())
    .then(response => {
      if (response.status !== 'success') throw new Error(response.message);
      const film = response.data;
      const form = document.getElementById('formFilm');
      
      form.id_film.value = film.id_film;
      form.title.value = film.title;
      form.director.value = film.director;
      form.genre.value = film.genre;
      form.duration.value = film.duration;
      form.description.value = film.description;
      form.release_date.value = film.release_date;
      
      document.getElementById('status-wrapper').classList.remove('hidden');
      form.status.value = film.status;

      const preview = document.getElementById('preview-poster');
      preview.innerHTML = film.poster ? `<img src="../posters/${film.poster}" class="h-24 mt-2 rounded" alt="Preview Poster"/>` : '';
      
      document.getElementById('submit-film-button').innerText = 'Simpan Perubahan';
      document.getElementById('film-section').scrollIntoView({ behavior: 'smooth' });
    })
    .catch(handleFetchError);
}

function hapusFilm(id) {
  Swal.fire({
    title: 'Apakah Anda yakin?',
    text: 'Film akan dihapus permanen dan semua jadwal terkait juga bisa terpengaruh.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Ya, hapus!',
    cancelButtonText: 'Batal',
  }).then(result => {
    if (result.isConfirmed) {
      const formData = new FormData();
      formData.append('action', 'delete');
      formData.append('id', id);
      fetch(`../backend/film_api.php`, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(res => {
          if (res.status === 'success') {
            Swal.fire('Dihapus!', 'Film telah berhasil dihapus.', 'success');
            refreshAllData();
          } else {
            Swal.fire('Gagal!', `Gagal menghapus film. Error: ${res.message || 'Tidak diketahui'}`, 'error');
          }
        })
        .catch(handleFetchError);
    }
  });
}

function detailFilm(id) {
  fetch(`../backend/film_api.php?action=get&id=${id}`)
    .then(res => res.json())
    .then(response => {
      if(response.status !== 'success') throw new Error(response.message);
      const film = response.data;
      document.getElementById('modal-content-film').innerHTML = `
        <img src="../posters/${film.poster}" class="mb-4 rounded-lg w-full max-h-64 object-cover" alt="Poster Film"/>
        <h3 class="text-lg font-bold mb-2">${film.title}</h3>
        <p><strong>Sutradara:</strong> ${film.director || 'N/A'}</p>
        <p><strong>Genre:</strong> ${film.genre || 'N/A'}</p>
        <p><strong>Durasi:</strong> ${film.duration ? film.duration + ' menit' : 'N/A'}</p>
        <p><strong>Tanggal Rilis:</strong> ${film.release_date || 'N/A'}</p>
        <hr class="my-3"><h4 class="font-semibold">Deskripsi:</h4>
        <div class="prose prose-sm max-w-none text-gray-600">${film.description.replace(/\n/g, '<br>')}</div>
      `;
      document.getElementById('modal-film').classList.remove('hidden');
    })
    .catch(handleFetchError);
}

function batalEditFilm() {
  const form = document.getElementById('formFilm');
  form.reset();
  form.id_film.value = ''; 
  document.getElementById('preview-poster').innerHTML = '';
  document.getElementById('submit-film-button').innerText = 'Tambah Film';
  document.getElementById('status-wrapper').classList.add('hidden');
}


// ==== LOGIKA BOOKING MASUK ====
let bookingTerpilih = null;

function loadBookingsAdmin() {
  const tbody = document.getElementById('booking-tbody');
  if (!tbody) return;

  tbody.innerHTML = '<tr><td colspan="6" class="text-center p-4 text-gray-500">Memuat data booking...</td></tr>';
  const status = document.getElementById('filter-booking-status')?.value ?? '';
  const search = document.getElementById('search-nama-user')?.value ?? '';

  fetch(`../backend/booking_api.php?action=list&status=${encodeURIComponent(status)}&search=${encodeURIComponent(search)}`)
    .then(res => res.json())
    .then(response => {
      if (response.status !== 'success' || !response.data) {
          tbody.innerHTML = `<tr><td colspan="6" class="text-center text-red-600 p-4">Gagal memuat data booking.</td></tr>`;
          return;
      }

      const bookingList = response.data;
      tbody.innerHTML = '';
      if (!Array.isArray(bookingList) || bookingList.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-gray-500 p-4">Tidak ada data yang cocok.</td></tr>`;
        return;
      }
      bookingList.forEach(b => {
        tbody.innerHTML += `
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4"><div class="text-sm font-medium text-gray-900">${b.user_name}</div><div class="text-xs text-gray-500">${b.user_email}</div></td>
                <td class="px-6 py-4"><div class="text-sm text-gray-900 truncate max-w-xs">${b.film_title || '<em class="text-gray-400">Film Dihapus</em>'}</div></td>
                <td class="px-6 py-4"><div class="text-sm font-medium text-gray-900">Rp ${formatRupiah(b.amount)}</div></td>
                <td class="px-6 py-4 hidden lg:table-cell"><div class="text-xs text-gray-500">${new Date(b.booking_time).toLocaleString('id-ID')}</div></td>
                <td class="px-6 py-4"><span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ${statusColor(b.status)}">${statusLabel(b.status)}</span></td>
                <td class="px-6 py-4 text-right">
                    <div class="flex justify-end items-center gap-2">
                        <button onclick="bukaModalEditBooking(${b.id_booking})" class="text-gray-400 hover:text-blue-600" title="Ubah Status"><i class="fas fa-edit"></i></button>
                        <button onclick="hapusBooking(${b.id_booking})" class="text-gray-400 hover:text-red-600" title="Hapus"><i class="fas fa-trash"></i></button>
                    </div>
                </td>
            </tr>
        `;
      });
    })
    .catch(handleFetchError);
}

function hapusBooking(id) {
  Swal.fire({
    title: 'Yakin hapus booking ini?', text: 'Aksi ini akan menghapus data booking secara permanen.', icon: 'warning',
    showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6',
    confirmButtonText: 'Ya, Hapus!', cancelButtonText: 'Batal',
  }).then(result => {
    if (result.isConfirmed) {
      const formData = new FormData();
      formData.append('action', 'delete');
      formData.append('id', id);
      fetch(`../backend/booking_api.php`, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(res => {
          if (res.status === 'success') {
            Swal.fire('Terhapus!', 'Data booking telah dihapus.', 'success');
            refreshAllData();
          } else {
            Swal.fire('Gagal!', `Gagal menghapus data. ${res.message || ''}`, 'error');
          }
        })
        .catch(handleFetchError);
    }
  });
}

function bukaModalEditBooking(id) {
  fetch(`../backend/booking_api.php?action=get&id=${id}`)
    .then(res => res.json())
    .then(response => {
      if (response.status === 'success') {
        const b = response.data;
        bookingTerpilih = b;
        document.getElementById('modal-content-booking').innerHTML = `
            <p><strong>ID Booking:</strong> ${b.id_booking}</p>
            <p><strong>Nama User:</strong> ${b.user_name || '-'}</p>
            <p><strong>Film:</strong> ${b.film_title || 'N/A'}</p>
            <p><strong>Total Bayar:</strong> Rp ${formatRupiah(b.amount)}</p><hr class="my-3">
            <div>
                <label for="select-status" class="block text-sm font-bold text-gray-700">Ubah Status</label>
                <select id="select-status" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <option value="booked" ${b.status === 'booked' ? 'selected' : ''}>Booked</option>
                    <option value="paid" ${b.status === 'paid' ? 'selected' : ''}>Paid</option>
                    <option value="cancelled" ${b.status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                </select>
            </div>`;
        document.getElementById('modal-booking').classList.remove('hidden');
      } else {
        Swal.fire({ icon: 'error', title: 'Gagal', text: response.message || 'Gagal mengambil data.' });
      }
    })
    .catch(handleFetchError);
}

function simpanPerubahanStatusBooking() {
  if (!bookingTerpilih) return;
  const newStatus = document.getElementById('select-status').value;
  const formData = new FormData();
  formData.append('action', 'update_status');
  formData.append('id', bookingTerpilih.id_booking);
  formData.append('status', newStatus);

  fetch('../backend/booking_api.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
      if (data.status === 'success') {
        Swal.fire('Berhasil!', 'Status booking berhasil diperbarui.', 'success');
        tutupModalBooking();
        refreshAllData();
      } else {
        Swal.fire('Gagal!', `Gagal memperbarui status. ${data.message || ''}`, 'error');
      }
    })
    .catch(handleFetchError);
}

function tutupModalBooking() {
  document.getElementById('modal-booking').classList.add('hidden');
  bookingTerpilih = null;
}

function refreshAllData() {
    if (document.getElementById('film-tbody')) loadFilmsAdmin();
    if (document.getElementById('booking-tbody')) loadBookingsAdmin();
}

document.addEventListener('DOMContentLoaded', () => {
  refreshAllData(); 
  
  document.getElementById('formFilm')?.addEventListener('submit', function (e) {
    e.preventDefault();
    submitFilmForm();
  });

  document.getElementById('filter-film-status')?.addEventListener('change', loadFilmsAdmin);
  document.getElementById('filter-booking-status')?.addEventListener('change', loadBookingsAdmin);
  document.getElementById('search-nama-user')?.addEventListener('input', debounce(loadBookingsAdmin, 400));
  
  document.querySelector('input[name="poster"]')?.addEventListener('change', function () {
    const file = this.files[0];
    const preview = document.getElementById('preview-poster');
    if (file && preview) {
      const reader = new FileReader();
      reader.onload = e => { preview.innerHTML = `<img src="${e.target.result}" class="h-24 mt-2 rounded" alt="Preview"/>`; };
      reader.readAsDataURL(file);
    } else if (preview) {
      preview.innerHTML = '';
    }
  });
});


function handleFetchError(error) {
    console.error('Fetch Error:', error);
    Swal.fire({
      icon: 'error',
      title: 'Oops...',
      text: `Terjadi kesalahan saat mengambil data: ${error.message}`
    });
}

function statusColor(status) {
  const colors = {
    now_showing: 'bg-green-100 text-green-800',
    coming_soon: 'bg-yellow-100 text-yellow-800',
    archived: 'bg-gray-100 text-gray-800',
    paid: 'bg-blue-100 text-blue-800',
    booked: 'bg-orange-100 text-orange-800',
    cancelled: 'bg-red-100 text-red-800',
  };
  return colors[status] || 'bg-gray-100 text-gray-800';
}

function statusLabel(status) {
  if (!status) return 'Undefined';
  return status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}

function debounce(func, delay) {
  let timeout;
  return function (...args) {
    clearTimeout(timeout);
    timeout = setTimeout(() => func.apply(this, args), delay);
  };
}

function formatRupiah(number) {
  if (number === null || typeof number === 'undefined') return '0';
  return parseFloat(number).toLocaleString('id-ID');
}