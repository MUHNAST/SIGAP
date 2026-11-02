import './bootstrap';
import 'flowbite';
import TomSelect from 'tom-select';
import 'tom-select/dist/css/tom-select.css';
import Chart from 'chart.js/auto';
import { DataTable, exportCSV, exportJSON, exportTXT, exportSQL } from 'simple-datatables';
import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();
import $ from 'jquery';
window.$ = window.jQuery = $;
import '@fortawesome/fontawesome-free/css/all.min.css';
import '@fontsource/inter/300.css';
import '@fontsource/inter/400.css';
import '@fontsource/inter/500.css';
import '@fontsource/inter/600.css';
import '@fontsource/inter/700.css';
import '@fontsource/inter/800.css';
let pegawaiChart = null;
document.addEventListener('DOMContentLoaded', () => {
    initDataTablesAuto();
    initChart();
    initSidebar();
    initWelcomeCard();
    initPreviewUsername();
    initImagePreview();
    initLocationForm(document);
    initEmployeeSelect(document);
});
// UNIVERSAL DATATABLE AUTO INIT
// ===============================
function initDataTablesAuto() {
    const tables = document.querySelectorAll('.js-datatable');
    tables.forEach((table) => {
        const exportName = table.dataset.export || 'data';
        const hideColumns = table.dataset.hide
            ? table.dataset.hide.split(',').map(i => parseInt(i))
            : [];
        const perPage = table.dataset.perpage
            ? parseInt(table.dataset.perpage)
            : 10;
        const dataTable = new DataTable(table, {
            searchable: true,
            sortable: true,
            perPage,
            perPageSelect: [5, 10, 25, 50],
            labels: {
                placeholder: 'Cari data...',
                noRows: 'Data tidak ditemukan',
                info: 'Menampilkan {start}-{end} dari {rows}',
            },
            columns: hideColumns.map(index => ({
                select: index,
                sortable: false
            }))
        });
        setTimeout(styleDataTable, 100);
        styleDataTable();
    });
}
// ===============================
// STYLE TABLE
// ===============================
function styleDataTable() {
    document.querySelectorAll('.datatable-input').forEach(el => {
        el.classList.add('block', 'w-full', 'md:w-80', 'p-2.5', 'text-sm', 'bg-white', 'border', 'border-gray-200', 'rounded-xl', 'shadow-sm', 'focus:ring-2', 'focus:ring-emerald-500', 'focus:border-emerald-500');
    });
    document.querySelectorAll('.datatable-selector').forEach(el => {
        el.classList.add('bg-white', 'border', 'border-gray-200', 'text-sm', 'rounded-xl', 'px-3', 'py-2', 'shadow-sm', 'focus:ring-2', 'focus:ring-emerald-500', 'focus:border-emerald-500');
    });
}
// ==================== CHART ====================
let jenisChart = null;
let genderChart = null;
let reminderChart = null;
let statusChart = null;
let masaKerjaChart = null;
let usiaChart = null;
let riwayatChart = null;
let pangkatChart = null;
let pendidikanChart = null;
function createHorizontalBarChart(
    canvasId,
    dataArray,
    color = "#2563EB"
) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return null;
    Chart.getChart(canvas)?.destroy();
    const labels = dataArray.map(item => item.label);
    const values = dataArray.map(item => item.value);
    return new Chart(canvas, {
        type: "bar",
        data: {
            labels,
            datasets: [{
                label: "Jumlah Pegawai",
                data: values,
                backgroundColor: color,
                borderRadius: 8,
                barThickness: 18
            }]
        },
        options: {
            indexAxis: "y",
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label(context) {
                            return `${context.raw} Pegawai`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                },
                y: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}
function createDoughnutChart(
    canvasId,
    legendId,
    dataObject,
    colors,
    unitLabel = "Pegawai"
) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return null;
    Chart.getChart(canvas)?.destroy();
    let labels = [];
    let values = [];
    if (Array.isArray(dataObject)) {
        labels = dataObject.map(item => item.label);
        values = dataObject.map(item => item.value);
    } else {
        labels = Object.keys(dataObject);
        values = Object.values(dataObject);
    }
    const total = values.reduce((sum, value) => sum + value, 0);
    const chart = new Chart(canvas, {
        type: "doughnut",
        data: {
            labels,
            datasets: [{
                data: values,
                backgroundColor: colors,
                borderWidth: 0,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: "72%",
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label(context) {
                            const value = context.raw;
                            const percent = total === 0
                                ? 0
                                : ((value / total) * 100).toFixed(1);
                            return `${context.label}: ${value} ${unitLabel} (${percent}%)`;
                        }
                    }
                }
            }
        },
        plugins: [{
            id: "centerText",
            beforeDraw(chart) {
                const { width, height, ctx } = chart;
                ctx.save();
                ctx.textAlign = "center";
                ctx.textBaseline = "middle";
                ctx.font = "bold 28px sans-serif";
                ctx.fillStyle = "#0F172A";
                ctx.fillText(total, width / 2, height / 2 - 12);
                ctx.font = "13px sans-serif";
                ctx.fillStyle = "#64748B";
                ctx.fillText(unitLabel, width / 2, height / 2 + 16);
                ctx.restore();
            }
        }]
    });
    const legend = document.getElementById(legendId);
    if (legend) {
        legend.innerHTML = "";
        labels.forEach((label, index) => {
            const percent = total === 0
                ? 0
                : ((values[index] / total) * 100).toFixed(1);
            legend.innerHTML += `
                <div class="flex justify-between items-center py-1">
                    <div class="flex items-center gap-2">
                        <span
                            class="w-3 h-3 rounded-full"
                            style="background:${colors[index] ?? "#94A3B8"}">
                        </span>
                        <span class="text-sm text-slate-700">${label}</span>
                    </div>
                    <span class="font-semibold text-slate-800">
                        ${values[index]}
                        <span class="text-slate-400 text-xs">
                            (${percent}%)
                        </span>
                    </span>
                </div>
            `;
        });
    }
    return chart;
}
function initChart() {
    // =========================
    // Doughnut Chart
    // =========================
    if (window.chartJenisPegawai) {
        jenisChart = createDoughnutChart(
            "chartJenisPegawai",
            "legendJenisPegawai",
            window.chartJenisPegawai,
            ["#2563EB", "#10B981", "#F59E0B"],
            "Pegawai"
        );
    }
    if (window.ChartJenisKelamin) {
        genderChart = createDoughnutChart(
            "ChartJenisKelamin",
            "legendGender",
            window.ChartJenisKelamin,
            ["#3B82F6", "#EC4899"],
            "Pegawai"
        );
    }
    if (window.chartReminder) {
        reminderChart = createDoughnutChart(
            "pegawaiChart",
            "legendReminder",
            {
                "Naik Pangkat": window.chartReminder.total_pangkat,
                "KGB": window.chartReminder.total_kgb,
                "Pensiun": window.chartReminder.total_pensiun
            },
            ["#2563EB", "#8B5CF6", "#F97316"],
            "Event"
        );
    }
    if (window.chartStatus) {
        statusChart = createDoughnutChart(
            "chartStatus",
            "legendStatus",
            window.chartStatus,
            [
                "#10B981",
                "#EF4444",
                "#F59E0B"
            ],
            "Pegawai"
        );
    }
    if (window.chartMasaKerja) {
        masaKerjaChart = createDoughnutChart(
            "chartMasaKerja",
            "legendMasaKerja",
            window.chartMasaKerja,
            [
                "#2563EB",
                "#10B981",
                "#F59E0B",
                "#8B5CF6"
            ],
            "Pegawai"
        );
    }
    if (window.chartUsia) {
        usiaChart = createDoughnutChart(
            "chartUsia",
            "legendUsia",
            window.chartUsia,
            [
                "#0EA5E9",
                "#10B981",
                "#F59E0B",
                "#EF4444"
            ],
            "Pegawai"
        );
    }
    if (window.chartRiwayat) {
        riwayatChart = createDoughnutChart(
            "chartRiwayat",
            "legendRiwayat",
            {
                "KGB": window.chartRiwayat.total_kgb,
                "Naik Pangkat": window.chartRiwayat.total_pangkat,
                "Pensiun": window.chartRiwayat.total_pensiun
            },
            [
                "#8B5CF6",
                "#2563EB",
                "#F97316"
            ],
            "Pegawai"
        );
    }
    // =========================
    // Horizontal Bar Chart
    // =========================
    if (window.chartPangkat) {
        pangkatChart = createHorizontalBarChart(
            "chartPangkat",
            window.chartPangkat,
            "#2563EB"
        );
    }
    if (window.chartPendidikan) {
        pendidikanChart = createHorizontalBarChart(
            "chartPendidikan",
            window.chartPendidikan,
            "#0EA5E9"
        );
    }
}
function resizeChart() {
    [
        jenisChart,
        genderChart,
        reminderChart,
        pangkatChart,
        pendidikanChart,
        statusChart,
        usiaChart,
        masaKerjaChart,
    ].forEach(chart => {
        if (!chart) return;
        chart.resize();
        chart.update("none");
    });
} window.addEventListener("resize", () => {
    clearTimeout(window.chartResizeTimer);
    window.chartResizeTimer = setTimeout(() => {
        initSidebar();
        resizeChart();
    }, 150);
});
// ==================== WELCOME CARD ====================
function initWelcomeCard() {
    const card = document.getElementById("welcomeCard");
    if (!card) return;
    setTimeout(() => {
        card.classList.add("opacity-0", "-translate-y-3");
        setTimeout(() => {
            card.classList.add("hidden");
        }, 500);
    }, 8000);
}
// ==================== MODAL ====================
window.openModal = (id) => {
    const modal = document.getElementById(id);
    if (!modal) return;
    sessionStorage.setItem('activeModal', id);
    modal.classList.remove('hidden');
    modal.classList.add('flex');
};
window.closeAllModal = function () {
    document.querySelectorAll(".modal").forEach(modal => {
        modal.classList.add("hidden");
        modal.classList.remove("flex");
    });
    sessionStorage.removeItem("activeModal");
};
document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
        closeAllModal();
        closeAllDropdown();
    }
});
// ==================== SIDEBAR ====================
window.toggleSidebar = () => {
    const sidebar = document.getElementById("sidebar");
    const main = document.getElementById("mainContent");
    if (!sidebar || !main) return;
    sidebar.classList.toggle("-translate-x-full");
    // Desktop
    if (window.innerWidth >= 1024) {
        const hidden = sidebar.classList.contains("-translate-x-full");
        main.style.marginLeft = hidden ? "0" : "18rem";
    }
};
function initSidebar() {
    const sidebar = document.getElementById("sidebar");
    const main = document.getElementById("mainContent");
    if (!sidebar || !main) return;
    if (window.innerWidth < 1024) {
        sidebar.classList.add("-translate-x-full");
        main.style.marginLeft = "0";
    } else {
        sidebar.classList.remove("-translate-x-full");
        main.style.marginLeft = "18rem";
    }
}
window.addEventListener("resize", initSidebar);
document.addEventListener("DOMContentLoaded", initSidebar);
// ==================== DROPDOWN ====================
let dropdownTimeout = null;
function showDropdown(el) {
    if (!el) return;
    clearTimeout(dropdownTimeout);
    el.classList.remove("hidden");
    requestAnimationFrame(() => {
        el.classList.remove("opacity-0", "scale-95");
        el.classList.add("opacity-100", "scale-100");
    });
}
function hideDropdown(el) {
    if (!el || el.classList.contains("hidden")) return;
    el.classList.remove("opacity-100", "scale-100");
    el.classList.add("opacity-0", "scale-95");
    clearTimeout(dropdownTimeout);
    dropdownTimeout = setTimeout(() => {
        el.classList.add("hidden");
    }, 200);
}
// Agar bisa dipanggil dari onclick di HTML
window.hideDropdown = hideDropdown;
function closeAllDropdown() {
    hideDropdown(document.getElementById("user"));
    hideDropdown(document.getElementById("notif"));
}
window.toggleUser = function (event) {
    event?.stopPropagation();
    const user = document.getElementById("user");
    const notif = document.getElementById("notif");
    if (!user || !notif) return;
    hideDropdown(notif);
    if (user.classList.contains("hidden")) {
        showDropdown(user);
    } else {
        hideDropdown(user);
    }
};
window.toggleNotif = function (event) {
    event?.stopPropagation();
    const user = document.getElementById("user");
    const notif = document.getElementById("notif");
    if (!user || !notif) return;
    hideDropdown(user);
    if (notif.classList.contains("hidden")) {
        showDropdown(notif);
    } else {
        hideDropdown(notif);
    }
};
document.addEventListener("click", function (event) {
    if (
        !event.target.closest("#user") &&
        !event.target.closest("[data-dropdown-toggle='user']")
    ) {
        hideDropdown(document.getElementById("user"));
    }
    if (
        !event.target.closest("#notif") &&
        !event.target.closest("[data-dropdown-toggle='notif']")
    ) {
        hideDropdown(document.getElementById("notif"));
    }
});
document.addEventListener("DOMContentLoaded", () => {
    const search = document.getElementById("searchNotification");
    const kategori = document.getElementById("kategoriNotification");
    if (!search || !kategori) return;
    // Ketika memilih kategori
    kategori.addEventListener("change", function () {
        if (this.value !== "") {
            search.value = "";
        }
    });
    // Ketika mengetik pencarian
    search.addEventListener("input", function () {
        if (this.value.trim() !== "") {
            kategori.selectedIndex = 0;
        }
    });
});
let lastUnread = null;
let reminderInterval = null;
function shouldPlayOnLogin() {
    // Belum pernah login pada tab ini
    if (!sessionStorage.getItem("notificationSession")) {
        sessionStorage.setItem("notificationSession", "1");
        return true;
    }
    // Sudah pernah login / hanya refresh
    return false;
}
function playNotificationSound() {
    const audio = document.getElementById("notificationSound");
    if (!audio) return;
    audio.pause();
    audio.currentTime = 0;
    audio.play().catch(() => { });
}
function updateBadge(count) {
    const badge = document.getElementById("notificationBadge");
    if (!badge) return;
    if (count > 0) {
        badge.innerText = count;
        badge.classList.remove("hidden");
    } else {
        badge.classList.add("hidden");
    }
}
function startReminder() {
    if (reminderInterval) return;
    reminderInterval = setInterval(() => {
        if (lastUnread > 0) {
            playNotificationSound();
        }
    }, 60 * 5 * 1000); // 1 jam
}
function stopReminder() {
    if (reminderInterval) {
        clearInterval(reminderInterval);
        reminderInterval = null;
    }
}
function checkNotification(isInitial = false) {
    if (!window.notificationConfig?.unreadUrl) return;
    fetch(window.notificationConfig.unreadUrl)
        .then(res => res.json())
        .then(data => {
            updateBadge(data.count);
            // Saat halaman pertama kali dibuka
            if (isInitial) {
                // Bunyi hanya sekali saat awal login
                if (shouldPlayOnLogin() && data.count > 0) {
                    playNotificationSound();
                }
                lastUnread = data.count;
                if (data.count > 0) {
                    startReminder();
                } else {
                    stopReminder();
                }
                return;
            }
            // Ada notifikasi baru
            if (lastUnread !== null && data.count > lastUnread) {
                playNotificationSound();
            }
            if (data.count > 0) {
                startReminder();
            } else {
                stopReminder();
            }
            lastUnread = data.count;
        })
        .catch(console.error);
}
document.addEventListener("DOMContentLoaded", () => {
    // Bunyi sekali saat membuka / refresh jika masih ada unread
    checkNotification(true);
    // Cek notifikasi baru setiap 1 menit
    setInterval(() => {
        checkNotification(false);
    }, 60 * 1000);
});
// ==================== FOTO PREVIEW ====================
function initImagePreview(maxSize = 2 * 1024 * 1024) {
    document.querySelectorAll(".image-preview-input").forEach(fileInput => {
        // Hindari event listener terpasang dua kali
        if (fileInput.dataset.initialized) {
            return;
        }
        fileInput.dataset.initialized = "true";
        const previewImage = document.querySelector(fileInput.dataset.preview);
        if (!previewImage) {
            return;
        }
        const filenameElement = fileInput.dataset.filename
            ? document.querySelector(fileInput.dataset.filename)
            : null;
        const defaultImage = previewImage.src;
        let objectURL = null;
        fileInput.addEventListener("change", function () {
            const file = this.files[0];
            // Jika batal memilih file
            if (!file) {
                previewImage.src = defaultImage;
                if (filenameElement) {
                    filenameElement.innerHTML =
                        "Klik ikon kamera untuk memilih foto<br>Format JPG, JPEG, PNG • Maksimal 2 MB";
                }
                return;
            }
            // Validasi format
            const allowedTypes = [
                "image/jpeg",
                "image/png",
            ];
            if (!allowedTypes.includes(file.type)) {
                alert("Format file harus JPG, JPEG, atau PNG.");
                this.value = "";
                previewImage.src = defaultImage;
                if (filenameElement) {
                    filenameElement.innerHTML =
                        "Klik ikon kamera untuk memilih foto<br>Format JPG, JPEG, PNG • Maksimal 2 MB";
                }
                return;
            }
            // Validasi ukuran
            if (file.size > maxSize) {
                alert("Ukuran foto maksimal 2 MB.");
                this.value = "";
                previewImage.src = defaultImage;
                if (filenameElement) {
                    filenameElement.innerHTML =
                        "Klik ikon kamera untuk memilih foto<br>Format JPG, JPEG, PNG • Maksimal 2 MB";
                }
                return;
            }
            // Hapus object URL sebelumnya
            if (objectURL) {
                URL.revokeObjectURL(objectURL);
            }
            objectURL = URL.createObjectURL(file);
            previewImage.src = objectURL;
            if (filenameElement) {
                filenameElement.textContent = file.name;
            }
        });
    });
}
// ==================== PASSWORD TOGGLE ====================
window.togglePassword = function (inputId, button) {
    const input = document.getElementById(inputId);
    if (!input) {
        console.error("Input tidak ditemukan:", inputId);
        return;
    }
    const icon = button.querySelector("i");
    if (!icon) {
        console.error("Icon tidak ditemukan");
        return;
    }
    if (input.type === "password") {
        input.type = "text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
    } else {
        input.type = "password";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
    }
};
// ==================== TAB ====================
window.showTab = function (tab = "account") {
    const tabs = ["account", "security", "notification"];
    // Simpan tab aktif
    sessionStorage.setItem("active_tab", tab);
    tabs.forEach((t) => {
        const tabContent = document.getElementById(`${t}-tab`);
        const tabButton = document.getElementById(`tab-${t}`);
        tabContent?.classList.add("hidden");
        if (tabButton) {
            tabButton.classList.remove(
                "border-b-2",
                "border-blue-600",
                "text-blue-600",
                "font-medium"
            );
            tabButton.classList.add("text-slate-500");
        }
    });
    const activeContent = document.getElementById(`${tab}-tab`);
    const activeButton = document.getElementById(`tab-${tab}`);
    activeContent?.classList.remove("hidden");
    if (activeButton) {
        activeButton.classList.remove("text-slate-500");
        activeButton.classList.add(
            "border-b-2",
            "border-blue-600",
            "text-blue-600",
            "font-medium"
        );
    }
};
document.addEventListener("DOMContentLoaded", () => {
    // Jangan jalankan jika halaman ini bukan halaman Setting
    if (!document.getElementById("tab-account")) {
        return;
    }
    const activeTab =
        window.activeTabFromServer ||
        sessionStorage.getItem("active_tab") ||
        "account";
    showTab(activeTab);
});
function initPreviewUsername() {
    const nipInput = document.getElementById('nip');
    const preview = document.getElementById('previewUsername');
    if (!nipInput || !preview) return;
    nipInput.addEventListener('input', () => {
        preview.textContent =
            nipInput.value.trim() || 'Auto NIP';
    });
}
// ===============================
// TOMSELECT SEARCHABLE DROPDOWN
// ===============================
function searchableSelect(el, placeholder = 'Ketik untuk mencari...') {
    if (!el) return null;
    // Destroy instance lama
    if (el.tomselect) {
        // el.tomselect.destroy();
        return el.tomselect;
    }
    const ts = new TomSelect(el, {
        create: false,
        persist: false,
        allowEmptyOption: true,
        maxOptions: null, // tampilkan semua option
        searchField: ['text'],
        placeholder: placeholder,
        hidePlaceholder: true,
        openOnFocus: true,
        // preload: true,
        render: {
            option(data, escape) {
                return `<div>${escape(data.text)}</div>`;
            }
        },
        onInitialize() {
            // Hapus option kosong dari hasil dropdown
            // this.removeOption('');
            const selected = this.input.dataset.selected;
            if (selected) {
                this.setValue(String(selected), true);
            }
        },
        // onDropdownOpen() {
        //     this.setTextboxValue('');
        //     this.refreshOptions();
        // }
    });
    return ts;
}
// ===============================
// RESET SELECT
// ===============================
function resetSelect(el, placeholderText) {
    if (!el || !el.tomselect) return;
    const ts = el.tomselect;
    ts.clear(true);
    ts.clearOptions();
    ts.addOption({
        value: '',
        text: placeholderText
    });
    ts.refreshOptions(false);
}
// ===============================
// LOAD OPTIONS AJAX
// ===============================
function loadOptions(
    url,
    selectEl,
    placeholder,
    selected = null
) {
    if (!selectEl || !selectEl.tomselect) {
        return Promise.resolve();
    }
    const ts = selectEl.tomselect;
    ts.clear(true);
    ts.clearOptions();
    ts.addOption({
        value: '',
        text: 'Loading...'
    });
    ts.refreshOptions(false);
    return $.get(url)
        .then(function (data) {
            ts.clearOptions();
            ts.addOption({
                value: '',
                text: placeholder
            });
            data.forEach(item => {
                ts.addOption({
                    value: String(item.id),
                    text: item.name
                    // text: item.nama
                });
            });
            ts.refreshOptions(false);
            if (selected) {
                ts.setValue(
                    String(selected),
                    true
                );
            }
        });
}
// ===============================
// INIT FORM
// ===============================
function initLocationForm(container = document) {
    container.querySelectorAll("form").forEach(form => {
        const province = form.querySelector('.province-select');
        const city = form.querySelector('.city-select');
        const district = form.querySelector('.district-select');
        const village = form.querySelector('.village-select');
        const birthPlace = form.querySelector('.tempat-lahir-select');
        const employee = form.querySelector('.pegawai-select');
        if (!province || !city || !district || !village || !birthPlace) {
            return;
        }
        searchableSelect(province, 'Pilih Provinsi');
        searchableSelect(city, 'Pilih Kota/Kabupaten');
        searchableSelect(district, 'Pilih Kecamatan');
        searchableSelect(village, 'Pilih Kelurahan/Desa');
        searchableSelect(birthPlace, 'Pilih Tempat Lahir');
        if (employee) {
            searchableSelect(employee, 'Pilih Pegawai');
            const employeeId = employee.dataset.selected || '';
            if (employeeId) {
                employee.tomselect.setValue(String(employeeId), true);
            }
        }
        const provinceId = province.dataset.selected || '';
        const cityId = city.dataset.selected || '';
        const districtId = district.dataset.selected || '';
        const villageId = village.dataset.selected || '';
        const birthPlaceId = birthPlace.dataset.selected || '';
        if (birthPlaceId) {
            birthPlace.tomselect.setValue(String(birthPlaceId), true);
        }
        if (provinceId) {
            province.tomselect.setValue(String(provinceId), true);
        }
        province.tomselect.on(
            'change',
            value => {
                resetSelect(city, 'Pilih Kota/Kabupaten');
                resetSelect(district, 'Pilih Kecamatan');
                resetSelect(village, 'Pilih Kelurahan/Desa');
                if (value) {
                    loadOptions(
                        `/cities/${value}`,
                        city,
                        'Pilih Kota/Kabupaten'
                    );
                }
            }
        );
        city.tomselect.on(
            'change',
            value => {
                resetSelect(district, 'Pilih Kecamatan');
                resetSelect(village, 'Pilih Kelurahan/Desa');
                if (value) {
                    loadOptions(
                        `/districts/${value}`,
                        district,
                        'Pilih Kecamatan'
                    );
                }
            }
        );
        district.tomselect.on(
            'change',
            value => {
                resetSelect(village, 'Pilih Kelurahan/Desa');
                if (value) {
                    loadOptions(
                        `/urban-villages/${value}`,
                        village,
                        'Pilih Kelurahan/Desa'
                    );
                }
            }
        );
        // EDIT AUTO LOAD
        (async () => {
            if (!provinceId) return;
            await loadOptions(
                `/cities/${provinceId}`,
                city,
                'Pilih Kota/Kabupaten',
                cityId
            );
            if (cityId) {
                await loadOptions(
                    `/districts/${cityId}`,
                    district,
                    'Pilih Kecamatan',
                    districtId
                );
            }
            if (districtId) {
                await loadOptions(
                    `/urban-villages/${districtId}`,
                    village,
                    'Pilih Kelurahan/Desa',
                    villageId
                );
            }
        })();
    });
}
// ===============================
// INIT EMPLOYEE SELECT
// ===============================
function initEmployeeSelect(container = document) {
    container.querySelectorAll('.pegawai-select').forEach(select => {
        searchableSelect(select, 'Pilih Pegawai');
        const selected = select.dataset.selected;
        if (selected) {
            select.tomselect.setValue(String(selected), true);
        }
    });
}
// document.addEventListener('DOMContentLoaded', function () {
//     initLocationForm(document);
//     initEmployeeSelect(document);
// });
document.addEventListener("DOMContentLoaded", () => {
    const isPrintPage = document.body.hasAttribute("data-print");
    const editables = document.querySelectorAll(".editable");
    // ============================================
    // SESSION KEY PER PEGAWAI
    // ============================================
    const employeeId = document.getElementById("employee-id")?.value || "0";
    const historyId = document.getElementById("history-id")?.value || null;
    // History menggunakan ID history.
    // Surat biasa tetap menggunakan ID pegawai.
    const makeKey = (field) => {
        if (historyId) {
            return `kgb_history_${historyId}_${field}`;
        }
        return `kgb_${employeeId}_${field}`;
    };
    // ============================================
    // SIMPAN SEMUA EDITABLE
    // ============================================
    const saveAll = () => {
        editables.forEach((el) => {
            const field = el.dataset.field;
            if (!field) return;
            sessionStorage.setItem(
                makeKey(field),
                el.innerHTML
            );
        });
    };
    // ============================================
    // AMBIL TEXT
    // ============================================
    const getText = (field) => {
        const el = document.querySelector(
            `[data-field="${field}"]`
        );
        return el ? el.innerText.trim() : "";
    };
    // ============================================
    // AMBIL ANGKA
    // ============================================
    const getNumber = (field) => {
        return getText(field).replace(/[^\d]/g, "");
    };
    // ============================================
    // PARSE MASA KERJA
    // ============================================
    const getMasaKerja = (field) => {
        const text = getText(field);
        const tahun = text.match(/(\d+)\s*Tahun/i)?.[1] ?? text.match(/^(\d+)/)?.[1] ?? "";
        const bulan = text.match(/(\d+)\s*Bulan/i)?.[1] ?? text.match(/Tahun\s+(\d+)/i)?.[1] ?? "";
        return { tahun, bulan };
    };
    // ============================================
    // PREVIEW
    // ============================================
    if (!isPrintPage) {
        editables.forEach((el) => {
            const field = el.dataset.field;
            if (!field) return;
            const saved = sessionStorage.getItem(
                makeKey(field)
            );
            if (saved !== null) {
                el.innerHTML = saved;
            }
            el.addEventListener("input", saveAll);
            el.addEventListener("blur", saveAll);
        });
        document.getElementById("btnPrint")?.addEventListener("click", saveAll);
    }
    // // PRINT
    // if (isPrintPage) {
    //     editables.forEach((el) => {
    //         const field = el.dataset.field;
    //         if (!field) return;
    //         const saved = sessionStorage.getItem(
    //             makeKey(field)
    //         );
    //         if (saved !== null) {
    //             el.innerHTML = saved;
    //         }
    //     });
    //     /*
    //     |--------------------------------------------------------------------------
    //     | PRINT HISTORY
    //     |--------------------------------------------------------------------------
    //     |
    //     | PrintHistory.blade.php sudah memiliki window.print()
    //     | sendiri.
    //     |
    //     | Jadi app.js hanya menerapkan data edit
    //     | dan menangani setelah print selesai.
    //     |--------------------------------------------------------------------------
    //     */
    //     const isHistoryPrint = document.body.hasAttribute("data-history-print");
    //     /*
    //     |--------------------------------------------------------------------------
    //     | PRINT BIASA
    //     |--------------------------------------------------------------------------
    //     |
    //     | Hanya print.blade.php yang menggunakan window.print()
    //     | dari app.js.
    //     |--------------------------------------------------------------------------
    //     */
    //     if (!isHistoryPrint) {
    //         setTimeout(() => {
    //             window.print();
    //         }, 300);
    //     }
    //     /*
    //     |--------------------------------------------------------------------------
    //     | SETELAH PRINT SELESAI
    //     |--------------------------------------------------------------------------
    //     */
    //     window.onafterprint = () => {
    //         editables.forEach((el) => {
    //             const field = el.dataset.field;
    //             if (!field) return;
    //             sessionStorage.removeItem(
    //                 makeKey(field)
    //             );
    //         });
    //         window.location.replace(
    //             "/report/kgb"
    //         );
    //     };
    // }
    // PRINT
    if (isPrintPage) {
        editables.forEach((el) => {
            const field = el.dataset.field;
            if (!field) return;
            const saved = sessionStorage.getItem(
                makeKey(field)
            );
            if (saved !== null) {
                el.innerHTML = saved;
            }
        });
        /*
        |--------------------------------------------------------------------------
        | PRINT HISTORY
        |--------------------------------------------------------------------------
        |
        | PrintHistory.blade.php sudah memiliki window.print()
        | sendiri.
        |
        | Jadi app.js hanya menerapkan data edit
        | dan menangani setelah print selesai.
        |--------------------------------------------------------------------------
        */
        const isHistoryPrint = document.body.hasAttribute("data-history-print");
        /*
        |--------------------------------------------------------------------------
        | PRINT BIASA
        |--------------------------------------------------------------------------
        |
        | Hanya print.blade.php yang menggunakan window.print()
        | dari app.js.
        |--------------------------------------------------------------------------
        */
        if (!isHistoryPrint) {
            setTimeout(() => {
                window.print();
            }, 300);
        }
        /*
        |--------------------------------------------------------------------------
        | SETELAH PRINT SELESAI
        |--------------------------------------------------------------------------
        */
        window.onafterprint = () => {
            editables.forEach((el) => {
                const field = el.dataset.field;
                if (!field) return;
                sessionStorage.removeItem(
                    makeKey(field)
                );
            });
            /*
            |--------------------------------------------------------------------------
            | REDIRECT
            |--------------------------------------------------------------------------
            |
            | Print biasa  -> /report/kgb
            | Print history -> /history
            |--------------------------------------------------------------------------
            */
            if (isHistoryPrint) {
                window.location.replace(
                    window.historyKgbUrl || "/history"
                );
            } else {
                window.location.replace(
                    window.reportKgbUrl || "/report/kgb"
                );
            }
        };
    }
    // ============================================
    // FORM CETAK
    // ============================================
    const form = document.getElementById("kgb-print-form");
    if (!form) return;
    form.addEventListener("submit", () => {
        saveAll();
        // ====================================
        // SURAT BARU
        // ====================================
        document.getElementById("nomor_sk")?.setAttribute("value", getText("nomor_surat_baru"));
        document.getElementById("tanggal_sk")?.setAttribute("value", getText("tanggal_surat").replace(/^Pekalongan,\s*/i, ""));
        document.getElementById("pejabat")?.setAttribute("value", getText("oleh_pejabat"));
        // ====================================
        // SK LAMA
        // ====================================
        const nomorSkLama = document.getElementById("nomor_sk_lama");
        if (nomorSkLama) {
            nomorSkLama.value =
                getText("nomor_surat_lama");
        }
        const tanggalSkLama = document.getElementById("tanggal_sk_lama");
        if (tanggalSkLama) {
            tanggalSkLama.value =
                getText("tanggal_det_lama");
        }
        // ====================================
        // MASA KERJA LAMA
        // ====================================
        const mkLama = getMasaKerja("masa_kerja_golongan");
        document.getElementById("masa_kerja_tahun_lama").value = mkLama.tahun;
        document.getElementById("masa_kerja_bulan_lama").value = mkLama.bulan;
        // ====================================
        // MASA KERJA BARU
        // ====================================
        const mkBaru = getMasaKerja("masa_kerja_baru");
        document.getElementById("masa_kerja_tahun_baru").value = mkBaru.tahun;
        document.getElementById("masa_kerja_bulan_baru").value = mkBaru.bulan;
        // ====================================
        // GAJI
        // ====================================
        document.getElementById("gaji_lama").value = getNumber("gaji_pokok_lama");
        document.getElementById("gaji_baru").value = getNumber("gaji_pokok_baru");
        document.getElementById("dasar_hukum_kgb").value = getText("peraturan");
    });
});
document.addEventListener("DOMContentLoaded", function () {
    const search = document.getElementById("search");
    const aktivitas = document.getElementById("aktivitas");
    // Jika salah satu elemen tidak ada, hentikan script
    if (!search || !aktivitas) {
        return;
    }
    // Ketika mengetik pencarian
    search.addEventListener("input", function () {
        if (this.value.trim() !== "") {
            aktivitas.value = "";
        }
    });
    // Ketika memilih aktivitas
    aktivitas.addEventListener("change", function () {
        if (this.value !== "") {
            search.value = "";
        }
    });
});
document.addEventListener('DOMContentLoaded', function () {
    const firstInput = document.querySelector(
        'input:not([type="hidden"]):not([type="checkbox"]):not([type="radio"]):not([type="submit"]):not([type="button"]):not([disabled]), textarea:not([disabled]), select:not([disabled])'
    );
    if (firstInput) {
        firstInput.focus();
    }
});
document.addEventListener('DOMContentLoaded', function () {
    const educationSelect = document.getElementById('education_level_id');
    const rankSelect = document.getElementById('id_pangkat');
    if (!educationSelect || !rankSelect) {
        return;
    }
    const urlTemplate = @json(route('employee.ranks-by-education', ':id'));
    /*
    |--------------------------------------------------------------------------
    | Rank lama
    |--------------------------------------------------------------------------
    | Create : kosong
    | Edit   : rank pegawai yang sedang diedit
    | Error  : rank dari old input
    |--------------------------------------------------------------------------
    */
    const currentRankId = @json(old('id_pangkat', isset($employee) ? $employee -> id_pangkat : ''));
    async function loadRanks(
        educationId,
        selectedRankId = null
    ) {
        /*
        |--------------------------------------------------------------------------
        | Reset select
        |--------------------------------------------------------------------------
        */
        rankSelect.innerHTML = `
            <option value="">
                Memuat Pangkat/Golongan...
            </option>
        `;
        rankSelect.disabled = true;
        /*
        |--------------------------------------------------------------------------
        | Pendidikan belum dipilih
        |--------------------------------------------------------------------------
        */
        if (!educationId) {
            rankSelect.innerHTML = `
                <option value="">
                    Pilih Pendidikan Terlebih Dahulu
                </option>
            `;
            return;
        }
        try {
            const url = urlTemplate.replace(
                ':id',
                educationId
            );
            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });
            if (!response.ok) {
                throw new Error(
                    'Gagal mengambil data pangkat.'
                );
            }
            const ranks = await response.json();
            /*
            |--------------------------------------------------------------------------
            | Reset option
            |--------------------------------------------------------------------------
            */
            rankSelect.innerHTML = `
                <option value="">
                    Pilih Pangkat/Golongan
                </option>
            `;
            /*
            |--------------------------------------------------------------------------
            | Tampilkan pangkat sesuai pendidikan
            |--------------------------------------------------------------------------
            */
            ranks.forEach(rank => {
                const option = document.createElement('option');
                option.value = rank.id;
                option.textContent =
                    `${rank.code} - ${rank.name}`;
                /*
                |--------------------------------------------------------------------------
                | Pilih rank lama pada halaman edit
                |--------------------------------------------------------------------------
                */
                if (selectedRankId !== null && selectedRankId !== '' && String(rank.id) === String(selectedRankId)) {
                    option.selected = true;
                }
                rankSelect.appendChild(option);
            });
            /*
            |--------------------------------------------------------------------------
            | Jika tidak ada pangkat
            |--------------------------------------------------------------------------
            */
            if (ranks.length === 0) {
                rankSelect.innerHTML = `
                    <option value="">
                        Tidak ada Pangkat/Golongan untuk pendidikan ini
                    </option>
                `;
                rankSelect.disabled = true;
                return;
            }
            rankSelect.disabled = false;
        } catch (error) {
            console.error(error);
            rankSelect.innerHTML = `
                <option value="">
                    Gagal memuat Pangkat/Golongan
                </option>
            `;
            rankSelect.disabled = true;
        }
    }
    /*
    |--------------------------------------------------------------------------
    | Ketika pendidikan berubah
    |--------------------------------------------------------------------------
    */
    educationSelect.addEventListener(
        'change',
        function () {
            /*
            | Ketika user mengganti pendidikan,
            | rank lama tidak boleh dipertahankan.
            */
            loadRanks(this.value);
        }
    );
    /*
    |--------------------------------------------------------------------------
    | Saat halaman pertama kali dibuka
    |--------------------------------------------------------------------------
    */
    if (educationSelect.value) {
        loadRanks(
            educationSelect.value,
            currentRankId
        );
    } else {
        rankSelect.disabled = true;
        rankSelect.innerHTML = `
            <option value="">
                Pilih Pendidikan Terlebih Dahulu
            </option>
        `;
    }
});
