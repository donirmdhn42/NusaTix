<?php require_once __DIR__ . '/templates/header.php'; ?>

<div class="p-6 md:p-8">
    <div class="space-y-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">Manajemen Testimoni</h1>
            <p class="text-slate-500 mt-1">Lihat dan kelola semua testimoni yang diberikan oleh pengguna.</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm">
            <div class="p-6 border-b border-slate-200">
                <h2 class="text-xl font-semibold text-slate-800">Daftar Testimoni</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="py-3 px-6 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Pengguna</th>
                            <th class="py-3 px-6 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Pesan</th>
                            <th class="py-3 px-6 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Rating</th>
                            <th class="py-3 px-6 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="testimonial-table-body" class="text-slate-700">
                        </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tableBody = document.getElementById('testimonial-table-body');

    function loadTestimonials() {
        fetch('../backend/api/testimonial_handler.php?action=get_all')
            .then(res => res.json())
            .then(data => {
                tableBody.innerHTML = ''; 
                 if (data.status === 'success' && data.data && data.data.length > 0) { 
                    data.data.forEach(t => {
                        const ratingStars = '<div class="flex items-center gap-1 text-amber-500">' + '⭐'.repeat(t.rating) + '</div>';
                        const row = `
                            <tr class="hover:bg-slate-50 border-t border-slate-200">
                                <td class="py-4 px-6 align-top">
                                    <p class="font-semibold text-slate-800">${t.user_name}</p>
                                    <p class="text-sm text-slate-500">Film: ${t.film_title}</p>
                                </td>
                                <td class="py-4 px-6 max-w-md align-top">
                                    <p class="text-slate-600 italic">"${t.message}"</p>
                                </td>
                                <td class="py-4 px-6 align-top">${ratingStars}</td>
                                <td class="py-4 px-6 text-right align-top">
                                    <button onclick="deleteTestimonial(${t.id_testimonial})" class="font-semibold text-red-600 hover:text-red-800 text-sm">Hapus</button>
                                </td>
                            </tr>`;
                        tableBody.innerHTML += row;
                    });
                } else {
                    tableBody.innerHTML = `<tr><td colspan="4" class="text-center py-8 text-slate-500">Belum ada testimoni dari pengguna.</td></tr>`;
                }
            })
            .catch(error => {
                console.error('Error fetching testimonials:', error);
                tableBody.innerHTML = `<tr><td colspan="4" class="text-center py-8 text-slate-500">Gagal memuat data.</td></tr>`;
            });
    }
    
    window.deleteTestimonial = function(id) {
        Swal.fire({
            title: 'Yakin ingin menghapus?',
            text: "Testimoni akan dihapus secara permanen.",
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

                fetch('../backend/api/testimonial_handler.php', { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire({icon: 'success', title: 'Dihapus!', text: data.message, showConfirmButton: false, timer: 1500});
                            loadTestimonials(); 
                        } else {
                            Swal.fire({icon: 'error', title: 'Gagal!', text: data.message});
                        }
                    })
                    .catch(error => {
                         console.error('Error deleting testimonial:', error);
                         Swal.fire({icon: 'error', title: 'Error!', text: 'Terjadi kesalahan saat menghubungi server.'});
                    });
            }
        });
    }

    loadTestimonials();
});
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>