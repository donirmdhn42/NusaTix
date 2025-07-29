<?php
require_once __DIR__ . '/templates/header.php';
?>

<div class="p-6 md:p-8">
    <div class="space-y-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">Manajemen Film</h1>
            <p class="text-slate-500 mt-1">Tambah, edit, atau hapus data film di platform.</p>
        </div>
        
        <div class="bg-white p-6 md:p-8 rounded-2xl border border-slate-200 shadow-md">
            <h2 class="text-xl font-semibold mb-6 text-slate-800" id="form-film-title">Tambah Film Baru</h2>
            <form id="form-film">
                <input type="hidden" name="id_film" id="id_film">
                <input type="hidden" name="current_poster" id="current_poster">
                
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-2 space-y-6">
                        <fieldset class="space-y-6 p-4 border border-slate-200 rounded-lg">
                            <legend class="text-lg font-semibold text-slate-700 mb-2 px-2">Informasi Dasar</legend>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="title" class="block font-medium text-sm text-slate-600 mb-1">Judul Film <span class="text-red-500">*</span></label>
                                    <input type="text" name="title" id="title" class="form-input" placeholder="Masukkan judul film" required>
                                </div>
                                <div>
                                    <label for="director" class="block font-medium text-sm text-slate-600 mb-1">Sutradara <span class="text-red-500">*</span></label>
                                    <input type="text" name="director" id="director" class="form-input" placeholder="Nama sutradara" required>
                                </div>
                            </div>
                        </fieldset>

                         <fieldset class="space-y-6 p-4 border border-slate-200 rounded-lg">
                            <legend class="text-lg font-semibold text-slate-700 mb-2 px-2">Detail Film</legend>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label for="genre" class="block font-medium text-sm text-slate-600 mb-1">Genre <span class="text-red-500">*</span></label>
                                    <input type="text" name="genre" id="genre" class="form-input" placeholder="Contoh: Action, Drama" required>
                                </div>
                                <div>
                                    <label for="duration" class="block font-medium text-sm text-slate-600 mb-1">Durasi (menit) <span class="text-red-500">*</span></label>
                                    <input type="number" name="duration" id="duration" class="form-input" placeholder="Contoh: 120" min="1" required>
                                </div>
                                <div>
                                    <label for="release_date" class="block font-medium text-sm text-slate-600 mb-1">Tanggal Rilis <span class="text-red-500">*</span></label>
                                    <input type="date" name="release_date" id="release_date" class="form-input" required>
                                </div>
                            </div>
                             <div>
                                <label for="description" class="block font-medium text-sm text-slate-600 mb-1">Deskripsi <span class="text-red-500">*</span></label>
                                <textarea name="description" id="description" rows="4" class="form-input resize-y" placeholder="Masukkan sinopsis atau deskripsi film..." required></textarea>
                            </div>
                         </fieldset>
                    </div>
                    
                    <div class="lg:col-span-1 space-y-6">
                         <fieldset class="p-4 border border-slate-200 rounded-lg h-full flex flex-col">
                            <legend class="text-lg font-semibold text-slate-700 mb-2 px-2">Poster & Status</legend>
                            <div class="flex-grow flex flex-col">
                                <label class="block font-medium text-sm text-slate-600 mb-1">Poster <span class="text-red-500">*</span></label>
                                <label id="poster-dropzone" for="poster" class="flex flex-col items-center justify-center w-full flex-grow border-2 border-slate-300 border-dashed rounded-lg cursor-pointer bg-slate-50 hover:bg-slate-100 transition relative">
                                    <div id="poster-preview" class="flex flex-col items-center justify-center text-center p-4 w-full h-full">
                                        <i class="fas fa-cloud-upload-alt text-4xl text-slate-400 mb-2"></i>
                                        <p class="mb-1 text-sm text-slate-500"><span class="font-semibold">Klik untuk unggah</span> atau seret gambar</p>
                                        <p class="text-xs text-slate-500">(PNG, JPG, WEBP | MAX. 2MB)</p>
                                        <p id="poster-filename" class="text-xs text-slate-600 mt-2 font-medium break-all"></p>
                                    </div>
                                    <button type="button" id="clear-poster-btn" class="absolute top-2 right-2 p-1 bg-red-500 text-white rounded-full text-xs hidden" title="Hapus Gambar">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </label>
                                <input type="file" name="poster" id="poster" class="sr-only" accept="image/png, image/jpeg, image/webp">
                                <p id="poster-error" class="text-red-500 text-sm mt-1 hidden"></p>
                            </div>
                            <div class="mt-6">
                                <label for="status" class="block font-medium text-sm text-slate-600 mb-1">Status Penayangan <span class="text-red-500">*</span></label>
                                <select name="status" id="status" class="form-input" required>
                                    <option value="coming_soon">Coming Soon</option>
                                    <option value="now_showing">Now Showing</option>
                                    <option value="archived">Archived</option>
                                </select>
                            </div>
                        </fieldset>
                    </div>
                </div>
               
                <div class="flex justify-end gap-4 pt-4 border-t border-slate-200 mt-8">
                    <button type="button" id="btn-cancel" class="bg-slate-200 hover:bg-slate-300 text-slate-800 font-bold px-5 py-2.5 rounded-lg transition duration-200 ease-in-out">Batal</button>
                    <button type="submit" class="bg-primary hover:opacity-90 text-white font-bold px-5 py-2.5 rounded-lg transition duration-200 ease-in-out">Simpan Film</button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-md">
             <div class="p-6">
                <h2 class="text-xl font-semibold text-slate-800">Daftar Film</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="py-3 px-6 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Film</th>
                            <th class="py-3 px-6 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Durasi</th>
                            <th class="py-3 px-6 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="py-3 px-6 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="film-table-body" class="bg-white divide-y divide-slate-200 text-slate-700"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('form-film');
    const tableBody = document.getElementById('film-table-body');
    const formTitle = document.getElementById('form-film-title');
    const btnCancel = document.getElementById('btn-cancel');
    const posterInput = document.getElementById('poster');
    const posterDropzone = document.getElementById('poster-dropzone');
    const posterPreview = document.getElementById('poster-preview');
    const posterFilenameDisplay = document.getElementById('poster-filename');
    const posterErrorDisplay = document.getElementById('poster-error');
    const clearPosterBtn = document.getElementById('clear-poster-btn');
    const defaultPreviewHTML = posterPreview.innerHTML;
    const MAX_FILE_SIZE = 2 * 1024 * 1024; 

    const style = document.createElement('style');
    style.innerHTML = `
        .form-input {
            width: 100%;
            padding: 0.625rem 1rem; /* 10px top/bottom, 16px left/right */
            border: 1px solid #cbd5e1; /* slate-300 */
            border-radius: 0.5rem; /* rounded-lg */
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); /* shadow-sm */
            transition: all 0.2s ease-in-out;
            color: #334155; /* slate-700 */
        }
        .form-input:focus {
            border-color: #4f46e5; /* primary color */
            outline: none;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2); /* ring primary/20 */
        }
        .form-input::placeholder {
            color: #94a3b8; /* slate-400 */
        }
        .form-input:required:valid:not(:placeholder-shown) {
            border-color: #10b981; /* green-500 */
        }
        .form-input:required:invalid:not(:placeholder-shown) {
            border-color: #ef4444; /* red-500 */
        }
    `;
    document.head.appendChild(style);

    function loadFilms() {
        fetch('../backend/api/film_handler.php?action=get_all')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    tableBody.innerHTML = '';
                    if(data.data.length === 0) {
                        tableBody.innerHTML = `<tr><td colspan="4" class="text-center py-8 text-slate-500">Belum ada data film.</td></tr>`;
                        return;
                    }
                    data.data.forEach(film => {
                        let statusClass = 'bg-slate-100 text-slate-600';
                        if (film.status === 'now_showing') statusClass = 'bg-emerald-100 text-emerald-700';
                        if (film.status === 'coming_soon') statusClass = 'bg-amber-100 text-amber-700';
                        const posterSrc = film.poster ? `../uploads/posters/${film.poster}` : 'https://via.placeholder.com/80x120?text=No+Image';

                        const row = `
                            <tr class="hover:bg-slate-50 border-t border-slate-200">
                                <td class="py-3 px-6">
                                    <div class="flex items-center gap-4">
                                        <img src="${posterSrc}" alt="Poster" class="w-16 h-24 object-cover rounded-md bg-slate-200">
                                        <div>
                                            <p class="font-semibold text-slate-800">${film.title}</p>
                                            <p class="text-sm text-slate-500">${film.director}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-6 text-sm text-slate-600">${film.duration} min</td>
                                <td class="py-3 px-6"><span class="text-xs font-bold py-1 px-3 rounded-full ${statusClass}">${film.status.replace('_', ' ').toUpperCase()}</span></td>
                                <td class="py-3 px-6 text-right">
                                    <button onclick="editFilm(${film.id_film})" class="font-semibold text-indigo-600 hover:text-indigo-800 text-sm mr-4 transition duration-200 ease-in-out">Edit</button>
                                    <button onclick="deleteFilm(${film.id_film})" class="font-semibold text-red-600 hover:text-red-800 text-sm transition duration-200 ease-in-out">Hapus</button>
                                </td>
                            </tr>
                        `;
                        tableBody.innerHTML += row;
                    });
                }
            })
            .catch(error => {
                console.error('Error loading films:', error);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Gagal memuat daftar film.' });
            });
    }

    function resetForm() {
        form.reset();
        document.getElementById('id_film').value = '';
        document.getElementById('current_poster').value = '';
        posterPreview.innerHTML = defaultPreviewHTML;
        posterFilenameDisplay.textContent = '';
        posterErrorDisplay.classList.add('hidden');
        clearPosterBtn.classList.add('hidden');
        posterDropzone.classList.remove('border-primary', 'bg-primary/10', 'border-red-500'); 
        formTitle.textContent = 'Tambah Film Baru';
        form.querySelector('button[type="submit"]').textContent = 'Simpan Film';
        document.querySelectorAll('.form-input').forEach(input => {
            input.classList.remove('border-green-500', 'border-red-500');
        });
    }

    function handleFile(file) {
        posterErrorDisplay.classList.add('hidden'); 
        posterDropzone.classList.remove('border-red-500'); 

        if (!file) {
            posterPreview.innerHTML = defaultPreviewHTML;
            posterFilenameDisplay.textContent = '';
            clearPosterBtn.classList.add('hidden');
            return;
        }

        if (!file.type.startsWith('image/')) {
            posterErrorDisplay.textContent = 'File yang diunggah harus berupa gambar (PNG, JPG, WEBP).';
            posterErrorDisplay.classList.remove('hidden');
            posterPreview.innerHTML = defaultPreviewHTML;
            posterFilenameDisplay.textContent = '';
            clearPosterBtn.classList.add('hidden');
            posterDropzone.classList.add('border-red-500');
            return;
        }

        if (file.size > MAX_FILE_SIZE) {
            posterErrorDisplay.textContent = 'Ukuran gambar maksimal 2MB.';
            posterErrorDisplay.classList.remove('hidden');
            posterPreview.innerHTML = defaultPreviewHTML;
            posterFilenameDisplay.textContent = '';
            clearPosterBtn.classList.add('hidden');
            posterDropzone.classList.add('border-red-500');
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            posterPreview.innerHTML = `<img src="${e.target.result}" class="max-h-full max-w-full object-contain rounded-lg">`;
            posterFilenameDisplay.textContent = file.name;
            clearPosterBtn.classList.remove('hidden');
        }
        reader.readAsDataURL(file);
    }

    posterInput.addEventListener('change', (e) => handleFile(e.target.files[0]));
    
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        posterDropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
        }, false);
    });
    ['dragenter', 'dragover'].forEach(eventName => {
        posterDropzone.addEventListener(eventName, () => posterDropzone.classList.add('border-primary', 'bg-primary/10'), false);
    });
    ['dragleave', 'drop'].forEach(eventName => {
        posterDropzone.addEventListener(eventName, () => posterDropzone.classList.remove('border-primary', 'bg-primary/10'), false);
    });

    posterDropzone.addEventListener('drop', (e) => {
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            posterInput.files = files; 
            handleFile(files[0]);
        }
    });

    clearPosterBtn.addEventListener('click', () => {
        posterInput.value = ''; 
        document.getElementById('current_poster').value = ''; 
        handleFile(null); 
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const idFilm = document.getElementById('id_film').value;
        const currentPoster = document.getElementById('current_poster').value;
        const posterFile = posterInput.files[0];

        if (!idFilm && !posterFile && !currentPoster) { 
            posterErrorDisplay.textContent = 'Poster film wajib diunggah.';
            posterErrorDisplay.classList.remove('hidden');
            posterDropzone.classList.add('border-red-500');
            return; 
        }

        if (posterFile) {
            if (!posterFile.type.startsWith('image/') || posterFile.size > MAX_FILE_SIZE) {
                Swal.fire({ icon: 'error', title: 'Validasi Gagal', text: 'Mohon periksa kembali file gambar Anda (tipe dan ukuran).' });
                return; 
            }
        }

        const formData = new FormData(form);
        formData.append('action', 'save');

        fetch('../backend/api/film_handler.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire({ icon: 'success', title: 'Berhasil!', text: data.message, showConfirmButton: false, timer: 1500 });
                resetForm();
                loadFilms();
            } else {
                Swal.fire({ icon: 'error', title: 'Gagal!', text: data.message });
            }
        })
        .catch(error => {
            console.error('Error submitting form:', error);
            Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan saat menyimpan data film.' });
        });
    });

    btnCancel.addEventListener('click', resetForm);

    window.editFilm = function(id) {
        fetch(`../backend/api/film_handler.php?action=get_one&id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const film = data.data;
                    document.getElementById('id_film').value = film.id_film;
                    document.getElementById('title').value = film.title;
                    document.getElementById('director').value = film.director;
                    document.getElementById('genre').value = film.genre;
                    document.getElementById('duration').value = film.duration;
                    document.getElementById('release_date').value = film.release_date;
                    document.getElementById('status').value = film.status;
                    document.getElementById('description').value = film.description;
                    document.getElementById('current_poster').value = film.poster; 

                    posterInput.value = '';
                    
                    if (film.poster) {
                        posterPreview.innerHTML = `<img src="../uploads/posters/${film.poster}" class="max-h-full max-w-full object-contain rounded-lg">`;
                        posterFilenameDisplay.textContent = film.poster; 
                        clearPosterBtn.classList.remove('hidden');
                    } else {
                         posterPreview.innerHTML = defaultPreviewHTML;
                         posterFilenameDisplay.textContent = '';
                         clearPosterBtn.classList.add('hidden');
                    }
                    
                    formTitle.textContent = 'Edit Film: ' + film.title;
                    form.querySelector('button[type="submit"]').textContent = 'Update Film';
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal!', text: data.message });
                }
            })
            .catch(error => {
                console.error('Error fetching film for edit:', error);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Gagal memuat data film untuk diedit.' });
            });
    }

    window.deleteFilm = function(id) {
        Swal.fire({
            title: 'Apakah Anda yakin?',
            text: "Film akan dihapus secara permanen!",
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#e92932', cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, hapus!', cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);
                fetch('../backend/api/film_handler.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire({ icon: 'success', title: 'Dihapus!', text: data.message, showConfirmButton: false, timer: 1500 });
                        loadFilms();
                    } else {
                        Swal.fire({ icon: 'error', title: 'Gagal!', text: data.message });
                    }
                })
                .catch(error => {
                    console.error('Error deleting film:', error);
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan saat menghapus film.' });
                });
            }
        });
    }

    loadFilms();
});
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>