<?php
// admin/manage_studios.php
require_once __DIR__ . '/templates/header.php';
?>
<div class="p-6 md:p-8">
    <div class="space-y-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">Manajemen Studio</h1>
            <p class="text-slate-500 mt-1">Tambah, edit, atau hapus data studio bioskop.</p>
        </div>

        <div class="bg-white p-6 md:p-8 rounded-2xl border border-slate-200">
            <h2 class="text-xl font-semibold mb-6 text-slate-800" id="form-studio-title">Tambah Studio Baru</h2>
            <form id="form-studio" class="space-y-6">
                <input type="hidden" name="id_studio" id="id_studio">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="name" class="block font-medium text-sm text-slate-600 mb-1">Nama Studio</label>
                        <input type="text" name="name" id="name" placeholder="Contoh: Studio 1" class="w-full p-2.5 border border-slate-300 rounded-lg shadow-sm focus:border-primary focus:ring focus:ring-primary/20 transition" required>
                    </div>
                    <div>
                        <label for="capacity" class="block font-medium text-sm text-slate-600 mb-1">Kapasitas Kursi</label>
                        <input type="number" name="capacity" id="capacity" placeholder="Contoh: 150" class="w-full p-2.5 border border-slate-300 rounded-lg shadow-sm focus:border-primary focus:ring focus:ring-primary/20 transition" required>
                    </div>
                </div>
            
                <div class="flex justify-end gap-4 pt-4 border-t border-slate-200 mt-2">
                    <button type="button" id="btn-cancel" class="bg-slate-200 hover:bg-slate-300 text-slate-800 font-bold px-5 py-2.5 rounded-lg transition">Batal</button>
                    <button type="submit" class="bg-primary hover:opacity-90 text-white font-bold px-5 py-2.5 rounded-lg transition">Simpan Studio</button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200">
            <div class="p-6">
                <h2 class="text-xl font-semibold text-slate-800">Daftar Studio</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="py-3 px-6 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Nama Studio</th>
                            <th class="py-3 px-6 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Kapasitas</th>
                            <th class="py-3 px-6 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="studio-table-body" class="text-slate-700"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('form-studio');
    const tableBody = document.getElementById('studio-table-body');
    const formTitle = document.getElementById('form-studio-title');
    const btnCancel = document.getElementById('btn-cancel');

    function loadStudios() {
        fetch('../backend/api/studio_handler.php?action=get_all')
            .then(response => response.json())
            .then(data => {
                tableBody.innerHTML = '';
                if (data.status === 'success' && data.data.length > 0) {
                    data.data.forEach(studio => {
                        const row = `
                            <tr class="hover:bg-slate-50 border-t border-slate-200">
                                <td class="py-4 px-6 font-semibold text-slate-800">${studio.name}</td>
                                <td class="py-4 px-6">${studio.capacity} kursi</td>
                                <td class="py-4 px-6 text-right whitespace-nowrap">
                                    <button onclick="editStudio(${studio.id_studio})" class="font-semibold text-indigo-600 hover:text-indigo-800 text-sm mr-4">Edit</button>
                                    <button onclick="deleteStudio(${studio.id_studio})" class="font-semibold text-primary hover:opacity-80 text-sm">Hapus</button>
                                </td>
                            </tr>
                        `;
                        tableBody.innerHTML += row;
                    });
                } else {
                    tableBody.innerHTML = `<tr><td colspan="3" class="text-center py-8 text-slate-500">Belum ada data studio.</td></tr>`;
                }
            });
    }

    function resetForm() {
        form.reset();
        document.getElementById('id_studio').value = '';
        formTitle.textContent = 'Tambah Studio Baru';
        form.querySelector('button[type="submit"]').textContent = 'Simpan Studio';
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(form);
        formData.append('action', 'save');

        fetch('../backend/api/studio_handler.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire({icon: 'success', title: 'Berhasil!', text: data.message, showConfirmButton: false, timer: 1500});
                resetForm();
                loadStudios();
            } else {
                Swal.fire({icon: 'error', title: 'Gagal!', text: data.message});
            }
        });
    });

    btnCancel.addEventListener('click', resetForm);

    window.editStudio = function(id) {
        fetch(`../backend/api/studio_handler.php?action=get_one&id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const studio = data.data;
                    document.getElementById('id_studio').value = studio.id_studio;
                    document.getElementById('name').value = studio.name;
                    document.getElementById('capacity').value = studio.capacity;
                    
                    formTitle.textContent = 'Edit Studio: ' + studio.name;
                    form.querySelector('button[type="submit"]').textContent = 'Update Studio';
                    window.scrollTo({top: 0, behavior: 'smooth'});
                }
            });
    }

    window.deleteStudio = function(id) {
        Swal.fire({
            title: 'Yakin ingin menghapus studio?',
            text: "Jadwal terkait mungkin akan terpengaruh!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e92932',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);

                fetch('../backend/api/studio_handler.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire({icon: 'success', title: 'Dihapus!', text: data.message, showConfirmButton: false, timer: 1500});
                        loadStudios();
                    } else {
                        Swal.fire({icon: 'error', title: 'Gagal!', text: data.message});
                    }
                });
            }
        });
    }

    loadStudios();
});
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>