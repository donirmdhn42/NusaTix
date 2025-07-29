<?php
date_default_timezone_set('Asia/Jakarta');
require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/../backend/models/film.php';
require_once __DIR__ . '/../backend/models/studio.php';

$all_films = getAllFilms($conn);
$schedulable_films = array_filter($all_films, fn($film) => $film['status'] !== 'archived');
$studios = getAllStudios($conn);
?>
<div class="p-6 md:p-8">
    <div class="space-y-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">Manajemen Jadwal Film</h1>
            <p class="text-slate-500 mt-1">Buat grup jadwal untuk satu film dalam rentang tanggal tertentu secara massal.</p>
        </div>

        <div class="bg-white p-6 md:p-8 rounded-2xl border border-slate-200">
            <h2 class="text-xl font-semibold mb-6 text-slate-800" id="form-title">Form Jadwal Massal Baru</h2>
            <form id="form-schedule" class="space-y-6">
                <input type="hidden" name="id_group" id="id_group" value="0">

                <fieldset>
                     <legend class="text-lg font-semibold text-slate-700 mb-2">Detail Penayangan</legend>
                     <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label for="id_film" class="block text-sm font-medium text-slate-600 mb-1">Film</label>
                            <select name="id_film" id="id_film" class="w-full p-2.5 border border-slate-300 rounded-lg shadow-sm focus:border-primary focus:ring focus:ring-primary/20 transition" required>
                                <option value="">-- Pilih Film --</option>
                                <?php foreach ($schedulable_films as $film): ?>
                                    <option value="<?= $film['id_film'] ?>"><?= htmlspecialchars($film['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="id_studio" class="block text-sm font-medium text-slate-600 mb-1">Studio</label>
                            <select name="id_studio" id="id_studio" class="w-full p-2.5 border border-slate-300 rounded-lg shadow-sm focus:border-primary focus:ring focus:ring-primary/20 transition" required>
                                <option value="">-- Pilih Studio --</option>
                                <?php foreach ($studios as $studio): ?>
                                    <option value="<?= $studio['id_studio'] ?>"><?= htmlspecialchars($studio['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                         <div>
                            <label for="price" class="block text-sm font-medium text-slate-600 mb-1">Harga Tiket (Rp)</label>
                            <input type="number" name="price" id="price" placeholder="Contoh: 45000" class="w-full p-2.5 border border-slate-300 rounded-lg shadow-sm focus:border-primary focus:ring focus:ring-primary/20 transition" required>
                        </div>
                    </div>
                </fieldset>
                
                <fieldset>
                    <legend class="text-lg font-semibold text-slate-700 mb-2">Periode & Sesi Tayang</legend>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-slate-600 mb-1">Tanggal Mulai Tayang</label>
                            <input type="date" name="start_date" id="start_date" class="w-full p-2.5 border border-slate-300 rounded-lg shadow-sm focus:border-primary focus:ring focus:ring-primary/20 transition" required>
                        </div>
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-slate-600 mb-1">Tanggal Selesai Tayang</label>
                            <input type="date" name="end_date" id="end_date" class="w-full p-2.5 border border-slate-300 rounded-lg shadow-sm focus:border-primary focus:ring focus:ring-primary/20 transition" required>
                        </div>
                    </div>
                     <div class="mt-6">
                        <label class="block text-sm font-medium text-slate-600 mb-1">Sesi Jam Tayang (Berlaku Setiap Hari)</label>
                        <div class="flex items-center gap-2 mt-1">
                            <input type="time" id="new_time_input" class="p-2.5 border border-slate-300 rounded-lg shadow-sm focus:border-primary focus:ring focus:ring-primary/20 transition">
                            <button type="button" id="add-time-btn" class="bg-slate-200 hover:bg-slate-300 text-slate-800 font-bold px-5 py-2.5 rounded-lg transition">Tambah</button>
                        </div>
                        <div id="show_times_container" class="flex flex-wrap gap-2 mt-3"></div>
                        <input type="hidden" name="show_times" id="show_times">
                    </div>
                </fieldset>
                
                <div class="flex justify-end gap-4 pt-4 border-t border-slate-200 mt-4">
                    <button type="button" id="btn-cancel" class="bg-slate-200 hover:bg-slate-300 text-slate-800 font-bold px-5 py-2.5 rounded-lg transition">Batal</button>
                    <button type="submit" class="bg-primary hover:opacity-90 text-white font-bold px-5 py-2.5 rounded-lg transition" id="submit-button">Simpan Grup Jadwal</button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200">
            <div class="p-6">
                <h2 class="text-xl font-semibold text-slate-800">Daftar Grup Jadwal</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="py-3 px-6 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Film & Studio</th>
                            <th class="py-3 px-6 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Periode</th>
                            <th class="py-3 px-6 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Sesi Harian</th>
                            <th class="py-3 px-6 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Harga</th>
                            <th class="py-3 px-6 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="schedule-group-table-body" class="text-slate-700"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('form-schedule');
    const tableBody = document.getElementById('schedule-group-table-body');
    const btnCancel = document.getElementById('btn-cancel');
    const timesContainer = document.getElementById('show_times_container');
    const newTimeInput = document.getElementById('new_time_input');
    const addTimeBtn = document.getElementById('add-time-btn');
    const showTimesHiddenInput = document.getElementById('show_times');
    const idGroupInput = document.getElementById('id_group');
    const formTitle = document.getElementById('form-title');
    const submitButton = document.getElementById('submit-button');
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    let showTimes = [];

    function renderTimeButtons() {
        timesContainer.innerHTML = '';
        showTimes.sort();
        showTimes.forEach(time => {
            const timeBtn = document.createElement('div');
            timeBtn.className = 'flex items-center bg-indigo-100 text-indigo-800 text-sm font-bold pl-3 pr-2 py-1 rounded-full';
            timeBtn.innerHTML = `<span>${time.substring(0,5)}</span><button type="button" class="ml-2 text-indigo-500 hover:text-indigo-700 focus:outline-none" data-time="${time}"><i class="fas fa-times-circle"></i></button>`;
            timesContainer.appendChild(timeBtn);
        });
        showTimesHiddenInput.value = showTimes.join(',');
    }

    addTimeBtn.addEventListener('click', () => {
        const newTime = newTimeInput.value;
        if (newTime && !showTimes.includes(newTime)) {
            showTimes.push(newTime);
            newTimeInput.value = '';
            renderTimeButtons();
        }
    });

    timesContainer.addEventListener('click', e => {
        const button = e.target.closest('button');
        if (button) {
            const timeToRemove = button.getAttribute('data-time');
            showTimes = showTimes.filter(time => time !== timeToRemove);
            renderTimeButtons();
        }
    });

    function formatRupiah(num) { return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(num); }
    function formatDate(dateStr) {
        return new Date(dateStr + 'T00:00:00').toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
    }

    function loadScheduleGroups() {
        fetch('../backend/api/schedule_handler.php?action=get_groups').then(res => res.json()).then(data => {
            tableBody.innerHTML = '';
            if (data.status === 'success' && data.data.length > 0) {
                data.data.forEach(g => {
                    const times = JSON.parse(g.show_times).map(t => `<span class="bg-slate-200 text-slate-700 font-medium px-2 py-1 rounded text-xs">${t.substring(0,5)}</span>`).join(' ');
                    const dateRange = `${formatDate(g.start_date)} - ${formatDate(g.end_date)}`;
                    tableBody.innerHTML += `<tr class="border-t border-slate-200 hover:bg-slate-50">
                        <td class="py-4 px-6">
                            <p class="font-semibold text-slate-800">${g.film_title}</p>
                            <p class="text-sm text-slate-500">${g.studio_name}</p>
                        </td>
                        <td class="py-4 px-6 text-slate-600">${dateRange}</td>
                        <td class="py-4 px-6"><div class="flex flex-wrap gap-2">${times}</div></td>
                        <td class="py-4 px-6 font-medium text-slate-800">${formatRupiah(g.price)}</td>
                        <td class="py-4 px-6 text-right whitespace-nowrap">
                            <button onclick="editGroup(${g.id_group})" class="font-semibold text-indigo-600 hover:text-indigo-800 text-sm mr-4">Edit</button>
                            <button onclick="deleteGroup(${g.id_group})" class="font-semibold text-primary hover:opacity-80 text-sm">Hapus</button>
                        </td>
                    </tr>`;
                });
            } else {
                tableBody.innerHTML = '<tr><td colspan="5" class="text-center py-8 text-slate-500">Belum ada grup jadwal yang dibuat.</td></tr>';
            }
        });
    }

    function resetForm() {
        form.reset();
        idGroupInput.value = '0';
        showTimes = [];
        renderTimeButtons();
        formTitle.textContent = 'Form Jadwal Massal Baru';
        submitButton.textContent = 'Simpan Grup Jadwal';
        const today = new Date().toISOString().split("T")[0];
        startDateInput.min = today;
        endDateInput.min = today;
    }

    form.addEventListener('submit', e => {
        e.preventDefault();
        if (showTimes.length === 0) {
            Swal.fire({icon: 'error', title: 'Gagal!', text: 'Anda harus menambahkan minimal satu sesi jam tayang.'}); return;
        }
        const formData = new FormData(form);
        if (formData.get('end_date') < formData.get('start_date')) {
            Swal.fire({icon: 'error', title: 'Gagal!', text: 'Tanggal selesai tidak boleh sebelum tanggal mulai.'}); return;
        }
        formData.append('action', 'save_group');
        
        Swal.fire({ title: 'Memproses...', text: 'Mohon tunggu, jadwal sedang dibuat.', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        fetch('../backend/api/schedule_handler.php', { method: 'POST', body: formData })
        .then(res => res.json()).then(data => {
            if (data.status === 'success') {
                Swal.fire({icon: 'success', title: 'Berhasil!', text: data.message, showConfirmButton: false, timer: 1500});
                resetForm();
                loadScheduleGroups();
            } else {
                Swal.fire({icon: 'error', title: 'Gagal!', text: data.message});
            }
        });
    });

    btnCancel.addEventListener('click', resetForm);

    window.editGroup = function(id) {
        fetch(`../backend/api/schedule_handler.php?action=get_group_details&id_group=${id}`)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    const group = data.data;
                    idGroupInput.value = group.id_group;
                    document.getElementById('id_film').value = group.id_film;
                    document.getElementById('id_studio').value = group.id_studio;
                    document.getElementById('price').value = parseFloat(group.price);
                    startDateInput.value = group.start_date;
                    endDateInput.value = group.end_date;
                    startDateInput.min = '';
                    endDateInput.min = group.start_date;
                    showTimes = JSON.parse(group.show_times);
                    renderTimeButtons();
                    formTitle.textContent = 'Edit Grup Jadwal';
                    submitButton.textContent = 'Perbarui Grup Jadwal';
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                } else {
                    Swal.fire({icon: 'error', title: 'Error!', text: data.message});
                }
            });
    }

    window.deleteGroup = function(id) {
        Swal.fire({
            title: 'Yakin hapus grup jadwal ini?',
            text: "Ini akan menghapus SEMUA jadwal terkait. Aksi ini tidak bisa dibatalkan.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e92932',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus Semua!',
            cancelButtonText: 'Batal'
        }).then(result => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('action', 'delete_group');
                formData.append('id_group', id);
                fetch('../backend/api/schedule_handler.php', { method: 'POST', body: formData })
                .then(res => res.json()).then(data => {
                    if (data.status === 'success') {
                        Swal.fire({icon: 'success', title: 'Dihapus!', text: data.message, showConfirmButton: false, timer: 1500});
                        loadScheduleGroups();
                    } else {
                        Swal.fire({icon: 'error', title: 'Gagal!', text: data.message});
                    }
                });
            }
        });
    }
    
    startDateInput.addEventListener('change', function() {
        endDateInput.min = this.value;
        if (endDateInput.value < this.value) {
            endDateInput.value = this.value;
        }
    });

    resetForm();
    loadScheduleGroups();
});
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>