<?php
require_once __DIR__ . '/templates/header.php';
?>

<div class="p-6 md:p-8">
    <div class="space-y-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">Manajemen Promo</h1>
            <p class="text-slate-500 mt-1">Buat, edit, atau non-aktifkan kode promo untuk pengguna.</p>
        </div>

        <div class="bg-white p-6 md:p-8 rounded-2xl border border-slate-200">
            <h2 class="text-xl font-semibold mb-6 text-slate-800" id="form-promo-title">Tambah Promo Baru</h2>
            <form id="form-promo" class="space-y-6">
                <input type="hidden" name="id_promo" id="id_promo">

                <fieldset>
                    <legend class="text-lg font-semibold text-slate-700 mb-2">Detail Promo</legend>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="code" class="block font-medium text-sm text-slate-600 mb-1">Kode Promo</label>
                            <input type="text" name="code" id="code" placeholder="Contoh: HEMAT20" class="w-full p-2.5 border border-slate-300 rounded-lg shadow-sm focus:border-primary focus:ring focus:ring-primary/20 transition" required>
                        </div>
                        <div>
                            <label for="description" class="block font-medium text-sm text-slate-600 mb-1">Deskripsi</label>
                            <input type="text" name="description" id="description" placeholder="Diskon 20% untuk semua film" class="w-full p-2.5 border border-slate-300 rounded-lg shadow-sm focus:border-primary focus:ring focus:ring-primary/20 transition">
                        </div>
                    </div>
                </fieldset>
                
                <fieldset>
                    <legend class="text-lg font-semibold text-slate-700 mb-2">Nilai & Batasan</legend>
                     <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div>
                            <label for="discount_type" class="block font-medium text-sm text-slate-600 mb-1">Tipe Diskon</label>
                            <select name="discount_type" id="discount_type" class="w-full p-2.5 border border-slate-300 rounded-lg shadow-sm focus:border-primary focus:ring focus:ring-primary/20 transition">
                                <option value="percent">Persen (%)</option>
                                <option value="fixed">Tetap (Rp)</option>
                            </select>
                        </div>
                        <div>
                            <label for="discount_value" class="block font-medium text-sm text-slate-600 mb-1">Nilai Diskon</label>
                            <input type="number" step="any" name="discount_value" id="discount_value" placeholder="20 atau 10000" class="w-full p-2.5 border border-slate-300 rounded-lg shadow-sm focus:border-primary focus:ring focus:ring-primary/20 transition" required>
                        </div>
                        <div>
                            <label for="min_purchase" class="block font-medium text-sm text-slate-600 mb-1">Min. Pembelian (Rp)</label>
                            <input type="number" step="any" name="min_purchase" id="min_purchase" placeholder="50000" class="w-full p-2.5 border border-slate-300 rounded-lg shadow-sm focus:border-primary focus:ring focus:ring-primary/20 transition">
                        </div>
                        <div>
                            <label for="max_discount" class="block font-medium text-sm text-slate-600 mb-1">Max. Diskon (Rp)</label>
                            <input type="number" step="any" name="max_discount" id="max_discount" placeholder="Opsional" class="w-full p-2.5 border border-slate-300 rounded-lg shadow-sm focus:border-primary focus:ring focus:ring-primary/20 transition">
                        </div>
                    </div>
                </fieldset>

                <fieldset>
                    <legend class="text-lg font-semibold text-slate-700 mb-2">Masa Berlaku & Penggunaan</legend>
                     <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div>
                            <label for="valid_from" class="block font-medium text-sm text-slate-600 mb-1">Berlaku Dari</label>
                            <input type="date" name="valid_from" id="valid_from" class="w-full p-2.5 border border-slate-300 rounded-lg shadow-sm focus:border-primary focus:ring focus:ring-primary/20 transition">
                        </div>
                        <div>
                            <label for="valid_until" class="block font-medium text-sm text-slate-600 mb-1">Berlaku Hingga</label>
                            <input type="date" name="valid_until" id="valid_until" class="w-full p-2.5 border border-slate-300 rounded-lg shadow-sm focus:border-primary focus:ring focus:ring-primary/20 transition">
                        </div>
                        <div>
                            <label for="usage_limit_per_user" class="block font-medium text-sm text-slate-600 mb-1">Batas Pakai / User</label>
                            <input type="number" name="usage_limit_per_user" id="usage_limit_per_user" class="w-full p-2.5 border border-slate-300 rounded-lg shadow-sm focus:border-primary focus:ring focus:ring-primary/20 transition" value="1" placeholder="Kosongkan = ∞">
                        </div>
                        <div class="flex items-end pb-2">
                            <label for="is_active" class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="is_active" id="is_active" class="h-4 w-4 text-primary border-slate-300 rounded focus:ring-primary" checked>
                                <span class="text-sm text-slate-700 font-medium">Aktifkan Promo</span>
                            </label>
                        </div>
                    </div>
                </fieldset>

                <div class="flex justify-end gap-4 pt-4 border-t border-slate-200 mt-4">
                    <button type="button" id="btn-cancel" class="bg-slate-200 hover:bg-slate-300 text-slate-800 font-bold px-5 py-2.5 rounded-lg transition">Batal</button>
                    <button type="submit" class="bg-primary hover:opacity-90 text-white font-bold px-5 py-2.5 rounded-lg transition">Simpan Promo</button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200">
            <div class="p-6">
                <h2 class="text-xl font-semibold text-slate-800">Daftar Promo</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="py-3 px-6 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Kode</th>
                            <th class="py-3 px-6 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Diskon</th>
                            <th class="py-3 px-6 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Masa Berlaku</th>
                            <th class="py-3 px-6 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="py-3 px-6 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="promo-table-body" class="text-slate-700"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('form-promo');
    const tableBody = document.getElementById('promo-table-body');
    const formTitle = document.getElementById('form-promo-title');

    const formatRupiah = (num) => new Intl.NumberFormat('id-ID').format(num);
    const formatDate = (dateStr) => dateStr ? new Date(dateStr + 'T00:00:00').toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) : 'Selamanya';

    function loadPromos() {
        fetch('../backend/api/promo_handler.php?action=get_all')
            .then(res => res.json())
            .then(data => {
                tableBody.innerHTML = '';
                if (data.status === 'success' && data.data) {
                    data.data.forEach(p => {
                        const discount = p.discount_type === 'percent' ? `${parseFloat(p.discount_value)}%` : `Rp ${formatRupiah(p.discount_value)}`;
                        const status = p.is_active == 1 ? '<span class="bg-emerald-100 text-emerald-700 text-xs font-bold px-3 py-1 rounded-full">Aktif</span>' : '<span class="bg-slate-100 text-slate-600 text-xs font-bold px-3 py-1 rounded-full">Non-Aktif</span>';
                        
                        tableBody.innerHTML += `
                            <tr class="border-t border-slate-200 hover:bg-slate-50">
                                <td class="py-4 px-6">
                                    <p class="font-semibold text-primary">${p.code}</p>
                                    <p class="text-sm text-slate-500">${p.description}</p>
                                </td>
                                <td class="py-4 px-6 font-medium text-slate-800">${discount}</td>
                                <td class="py-4 px-6 text-slate-600">${formatDate(p.valid_until)}</td>
                                <td class="py-4 px-6">${status}</td>
                                <td class="py-4 px-6 text-right whitespace-nowrap">
                                    <button onclick="editPromo(${p.id_promo})" class="font-semibold text-indigo-600 hover:text-indigo-800 text-sm mr-4">Edit</button>
                                    <button onclick="deletePromo(${p.id_promo})" class="font-semibold text-primary hover:opacity-80 text-sm">Hapus</button>
                                </td>
                            </tr>`;
                    });
                } else {
                    tableBody.innerHTML = '<tr><td colspan="5" class="text-center py-8 text-slate-500">Belum ada promo yang ditambahkan.</td></tr>';
                }
            });
    }

    function resetForm() {
        form.reset();
        document.getElementById('id_promo').value = '';
        document.getElementById('is_active').checked = true;
        document.getElementById('usage_limit_per_user').value = '1';
        formTitle.textContent = 'Tambah Promo Baru';
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(form);
        formData.append('action', 'save');
        fetch('../backend/api/promo_handler.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({icon: 'success', title: 'Berhasil', text: data.message, showConfirmButton: false, timer: 1500});
                    resetForm();
                    loadPromos();
                } else {
                    Swal.fire({icon: 'error', title: 'Gagal', text: data.message});
                }
            });
    });
    
    document.getElementById('btn-cancel').addEventListener('click', resetForm);

    window.editPromo = function(id) {
        fetch(`../backend/api/promo_handler.php?action=get_one&id=${id}`)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    const p = data.data;
                    document.getElementById('id_promo').value = p.id_promo;
                    document.getElementById('code').value = p.code;
                    document.getElementById('description').value = p.description;
                    document.getElementById('discount_type').value = p.discount_type;
                    document.getElementById('discount_value').value = parseFloat(p.discount_value);
                    document.getElementById('min_purchase').value = parseFloat(p.min_purchase);
                    document.getElementById('max_discount').value = p.max_discount ? parseFloat(p.max_discount) : '';
                    document.getElementById('valid_from').value = p.valid_from;
                    document.getElementById('valid_until').value = p.valid_until;
                    document.getElementById('usage_limit_per_user').value = p.usage_limit_per_user;
                    document.getElementById('is_active').checked = p.is_active == 1;
                    formTitle.textContent = `Edit Promo: ${p.code}`;
                    window.scrollTo({top: 0, behavior: 'smooth'});
                } else {
                    Swal.fire({icon: 'error', title: 'Gagal', text: data.message});
                }
            });
    }

    window.deletePromo = function(id) {
        Swal.fire({
            title: 'Yakin ingin menghapus?',
            text: "Promo akan dihapus secara permanen.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e92932',
            cancelButtonColor: '#64748b',
            cancelButtonText: 'Batal',
            confirmButtonText: 'Ya, Hapus!'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);
                fetch('../backend/api/promo_handler.php', { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire({icon: 'success', title: 'Dihapus!', text: data.message, showConfirmButton: false, timer: 1500});
                            loadPromos();
                        } else {
                            Swal.fire({icon: 'error', title: 'Gagal!', text: data.message});
                        }
                    });
            }
        });
    }

    loadPromos();
});
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>