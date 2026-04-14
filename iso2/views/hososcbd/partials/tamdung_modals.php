<!-- Modal Quản lý Tạm dừng (Gom tất cả) -->
<div id="quanLyTamDungModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] flex flex-col">
        <!-- Header -->
        <div id="modalHeader" class="bg-blue-600 text-white px-4 py-3 rounded-t-lg flex justify-between items-center">
            <h3 class="text-base font-bold flex items-center">
                <i class="fas fa-cog mr-2 text-sm"></i>
                Quản lý Tạm dừng
            </h3>
            <button onclick="closeQuanLyTamDungModal()" class="text-white hover:text-gray-200">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
        
        <div class="p-4 overflow-y-auto flex-1">
            <!-- Thông tin hồ sơ -->
            <div class="mb-3 pb-3 border-b">
                <p class="text-gray-700 mb-1 text-sm">
                    <strong>Hồ sơ:</strong> <span id="quanly_hoso_display" class="font-mono text-blue-600 font-bold"></span>
                </p>
                <p class="text-gray-700 text-sm">
                    <strong>Thiết bị:</strong> <span id="quanly_thietbi_display"></span>
                </p>
            </div>
            
            <!-- Form Tạm dừng -->
            <div id="tamDungSection" class="hidden mb-4">
                <div class="bg-orange-50 border border-orange-200 rounded-lg p-3">
                    <h4 class="font-bold text-orange-700 mb-2 flex items-center text-sm">
                        <i class="fas fa-pause-circle mr-1 text-xs"></i>
                        Tạm dừng hồ sơ
                    </h4>
                    
                    <form id="tamDungForm">
                        <input type="hidden" id="tam_dung_hoso" name="hoso">
                        
                        <div class="mb-2">
                            <label class="block text-gray-700 font-bold mb-1 text-xs" for="lydo_tamdung">
                                Lý do tạm dừng <span class="text-red-500">*</span>
                            </label>
                            <textarea 
                                id="lydo_tamdung" 
                                name="lydo_tamdung" 
                                rows="2" 
                                required
                                class="w-full px-2 py-1.5 border border-gray-300 rounded focus:outline-none focus:border-orange-500 text-xs"
                                placeholder="Nhập lý do tạm dừng..."
                            ></textarea>
                        </div>
                        
                        <div class="bg-yellow-50 border-l-2 border-yellow-400 p-2 mb-2">
                            <p class="text-xs text-yellow-700">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                Hồ sơ tạm dừng sẽ không xuất hiện trong báo cáo SCBĐ.
                            </p>
                        </div>
                        
                        <div class="flex gap-1.5 justify-end">
                            <button 
                                type="button" 
                                onclick="closeQuanLyTamDungModal()" 
                                class="px-2.5 py-1.5 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded text-xs font-semibold"
                            >
                                <i class="fas fa-times mr-1"></i>Hủy
                            </button>
                            <button 
                                type="submit" 
                                class="px-2.5 py-1.5 bg-orange-500 hover:bg-orange-600 text-white rounded text-xs font-semibold"
                            >
                                <i class="fas fa-pause-circle mr-1"></i>Tạm dừng
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Form Tiếp tục -->
            <div id="tiepTucSection" class="hidden mb-4">
                <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                    <h4 class="font-bold text-green-700 mb-2 flex items-center text-sm">
                        <i class="fas fa-play-circle mr-1 text-xs"></i>
                        Tiếp tục hồ sơ
                    </h4>
                    
                    <div id="tam_dung_info_box" class="mb-2">
                        <!-- Will be filled with JS -->
                    </div>
                    
                    <form id="tiepTucForm">
                        <input type="hidden" id="tiep_tuc_hoso" name="hoso">
                        
                        <div class="mb-2">
                            <label class="block text-gray-700 font-bold mb-1 text-xs" for="ghichu_tieptuc">
                                Ghi chú (tùy chọn)
                            </label>
                            <textarea 
                                id="ghichu_tieptuc" 
                                name="ghichu_tieptuc" 
                                rows="2" 
                                class="w-full px-2 py-1.5 border border-gray-300 rounded focus:outline-none focus:border-green-500 text-xs"
                                placeholder="Nhập ghi chú nếu cần..."
                            ></textarea>
                        </div>
                        
                        <div class="bg-green-100 border-l-2 border-green-400 p-2 mb-2">
                            <p class="text-xs text-green-700">
                                <i class="fas fa-info-circle mr-1"></i>
                                Hồ sơ sẽ được đưa trở lại báo cáo SCBĐ.
                            </p>
                        </div>
                        
                        <div class="flex gap-1.5 justify-end">
                            <button 
                                type="button" 
                                onclick="closeQuanLyTamDungModal()" 
                                class="px-2.5 py-1.5 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded text-xs font-semibold"
                            >
                                <i class="fas fa-times mr-1"></i>Hủy
                            </button>
                            <button 
                                type="submit" 
                                class="px-2.5 py-1.5 bg-green-500 hover:bg-green-600 text-white rounded text-xs font-semibold"
                            >
                                <i class="fas fa-play-circle mr-1"></i>Tiếp tục
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Lịch sử (Chuyển xuống dưới) -->
            <div class="border-t pt-3">
                <div class="flex items-center gap-2 mb-2">
                    <button 
                        type="button"
                        onclick="toggleLichSu()"
                        class="flex-1 flex items-center justify-between bg-gray-100 hover:bg-gray-200 px-3 py-2 rounded transition-colors"
                    >
                        <span class="font-bold text-gray-700 flex items-center text-sm">
                            <i class="fas fa-history mr-1.5 text-blue-600 text-xs"></i>
                            Lịch sử
                        </span>
                        <i id="lichSuToggleIcon" class="fas fa-chevron-right text-gray-600 text-xs"></i>
                    </button>
                    
                    <a 
                        href="baocao_hososcbd_tamdung.php" 
                        target="_blank"
                        class="inline-flex items-center bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded text-xs font-semibold transition-colors whitespace-nowrap"
                        title="Xem báo cáo đầy đủ"
                    >
                        <i class="fas fa-chart-bar mr-1"></i>Báo cáo
                    </a>
                </div>
                
                <div id="lichSuContent" class="space-y-2 hidden">
                    <!-- Will be filled with JS -->
                </div>
            </div>
        </div>
    </div>
</div>


<script>
let lichSuVisible = false; // Lịch sử mặc định ẩn (vì ở dưới rồi)

// Toggle lịch sử
function toggleLichSu() {
    const content = document.getElementById('lichSuContent');
    const icon = document.getElementById('lichSuToggleIcon');
    
    if (lichSuVisible) {
        content.classList.add('hidden');
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-right');
    } else {
        content.classList.remove('hidden');
        icon.classList.remove('fa-chevron-right');
        icon.classList.add('fa-chevron-down');
    }
    lichSuVisible = !lichSuVisible;
}

// Mở modal quản lý tạm dừng (thay thế cho openTamDungModal, openTiepTucModal, openLichSuModal)
async function openQuanLyTamDungModal(hoso, mavt = '', somay = '', isTamDung = false) {
    // Hiển thị thông tin hồ sơ
    document.getElementById('quanly_hoso_display').textContent = hoso;
    document.getElementById('quanly_thietbi_display').textContent = `${mavt} / ${somay}`;
    
    // Set hidden inputs
    document.getElementById('tam_dung_hoso').value = hoso;
    document.getElementById('tiep_tuc_hoso').value = hoso;
    
    // Reset forms
    document.getElementById('lydo_tamdung').value = '';
    document.getElementById('ghichu_tieptuc').value = '';
    
    // Hiển thị section tương ứng
    const tamDungSection = document.getElementById('tamDungSection');
    const tiepTucSection = document.getElementById('tiepTucSection');
    const modalHeader = document.getElementById('modalHeader');
    
    if (isTamDung) {
        // Đang tạm dừng -> hiện form tiếp tục
        tamDungSection.classList.add('hidden');
        tiepTucSection.classList.remove('hidden');
        modalHeader.classList.remove('bg-blue-600');
        modalHeader.classList.add('bg-green-600');
        
        // Load thông tin tạm dừng
        try {
            const response = await fetch(`/iso2/api/hososcbd_tamdung.php?action=check_status&hoso=${encodeURIComponent(hoso)}`);
            const data = await response.json();
            
            if (data.success && data.info) {
                const infoBox = document.getElementById('tam_dung_info_box');
                const info = data.info;
                const ngayTamDung = new Date(info.ngay_thuchien).toLocaleString('vi-VN');
                
                infoBox.innerHTML = `
                    <div class="bg-yellow-50 border border-yellow-200 rounded p-2">
                        <h5 class="font-bold text-gray-700 mb-1 text-xs">Thông tin tạm dừng:</h5>
                        <div class="space-y-0.5 text-xs">
                            <p><strong>Người:</strong> ${info.nguoi_thuchien}</p>
                            <p><strong>Ngày:</strong> ${ngayTamDung}</p>
                            <p><strong>Lý do:</strong> ${info.lydo_tamdung}</p>
                        </div>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error loading pause info:', error);
        }
    } else {
        // Chưa tạm dừng -> hiện form tạm dừng
        tamDungSection.classList.remove('hidden');
        tiepTucSection.classList.add('hidden');
        modalHeader.classList.remove('bg-green-600');
        modalHeader.classList.add('bg-blue-600');
    }
    
    // Load lịch sử
    try {
        const response = await fetch(`/iso2/api/hososcbd_tamdung.php?action=lich_su&hoso=${encodeURIComponent(hoso)}`);
        const data = await response.json();
        
        if (data.success) {
            const content = document.getElementById('lichSuContent');
            
            if (data.data.length === 0) {
                content.innerHTML = '<p class="text-gray-500 text-center py-3 text-xs"><i class="fas fa-inbox text-xl mb-1 block"></i>Chưa có lịch sử</p>';
            } else {
                content.innerHTML = `<div class="relative border-l-2 border-gray-300 ml-3 pl-4 space-y-2">` + 
                    data.data.map((item, index) => {
                        const ngay = new Date(item.ngay_thuchien).toLocaleString('vi-VN', {
                            day: '2-digit',
                            month: '2-digit',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                        const isTamDungRecord = item.trangthai === 'dang_tam_dung' || item.trangthai === 'tamdung';
                        const iconColor = isTamDungRecord ? 'bg-orange-500' : 'bg-green-500';
                        const textColor = isTamDungRecord ? 'text-orange-700' : 'text-green-700';
                        const bgColor = isTamDungRecord ? 'bg-orange-50' : 'bg-green-50';
                        const borderColor = isTamDungRecord ? 'border-orange-200' : 'border-green-200';
                        
                        return `
                            <div class="relative">
                                <div class="absolute -left-[1.4rem] w-6 h-6 ${iconColor} rounded-full flex items-center justify-center shadow-sm">
                                    <i class="fas ${isTamDungRecord ? 'fa-pause' : 'fa-play'} text-white" style="font-size: 9px;"></i>
                                </div>
                                <div class="${bgColor} border ${borderColor} rounded p-2 shadow-sm hover:shadow transition-shadow">
                                    <div class="flex justify-between items-start mb-0.5">
                                        <span class="font-bold ${textColor} text-xs">${isTamDungRecord ? '⏸️ TẠM DỪNG' : '▶️ TIẾP TỤC'}</span>
                                        <span class="text-xs text-gray-600">${ngay}</span>
                                    </div>
                                    <div class="text-xs text-gray-700 mb-0.5">
                                        👤 <strong>${item.nguoi_thuchien}</strong>
                                    </div>
                                    ${isTamDungRecord && item.lydo_tamdung ? `
                                        <div class="text-xs text-gray-700 mt-1 bg-white bg-opacity-50 rounded p-1.5">
                                            <strong>Lý do:</strong> ${item.lydo_tamdung}
                                        </div>
                                    ` : ''}
                                    ${!isTamDungRecord && item.ghichu_tieptuc ? `
                                        <div class="text-xs text-gray-700 mt-1 bg-white bg-opacity-50 rounded p-1.5">
                                            <strong>Ghi chú:</strong> ${item.ghichu_tieptuc}
                                        </div>
                                    ` : ''}
                                </div>
                            </div>
                        `;
                    }).join('') + `</div>`;
            }
        }
    } catch (error) {
        console.error('Error loading history:', error);
        document.getElementById('lichSuContent').innerHTML = 
            '<p class="text-red-500 text-center text-sm">Có lỗi xảy ra khi tải lịch sử</p>';
    }
    
    // Hiển thị modal
    document.getElementById('quanLyTamDungModal').classList.remove('hidden');
}

function closeQuanLyTamDungModal() {
    document.getElementById('quanLyTamDungModal').classList.add('hidden');
}

// Form submissions
document.getElementById('tamDungForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'tam_dung');
    
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Đang xử lý...';
    
    try {
        const response = await fetch('/iso2/api/hososcbd_tamdung.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Tạm dừng hồ sơ thành công!');
            closeQuanLyTamDungModal();
            location.reload();
        } else {
            alert('Lỗi: ' + (data.error || 'Không thể tạm dừng hồ sơ'));
        }
    } catch (error) {
        alert('Có lỗi xảy ra: ' + error.message);
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-pause-circle mr-1"></i>Tạm dừng';
    }
});

document.getElementById('tiepTucForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'tiep_tuc');
    
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Đang xử lý...';
    
    try {
        const response = await fetch('/iso2/api/hososcbd_tamdung.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Tiếp tục hồ sơ thành công!');
            closeQuanLyTamDungModal();
            location.reload();
        } else {
            alert('Lỗi: ' + (data.error || 'Không thể tiếp tục hồ sơ'));
        }
    } catch (error) {
        alert('Có lỗi xảy ra: ' + error.message);
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-play-circle mr-1"></i>Tiếp tục';
    }
});

// Close modal when clicking outside or pressing ESC
document.getElementById('quanLyTamDungModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeQuanLyTamDungModal();
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeQuanLyTamDungModal();
    }
});
</script>
