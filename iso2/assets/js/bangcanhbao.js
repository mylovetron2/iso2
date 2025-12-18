/**
 * JavaScript cho Bang Cảnh Báo - Quản lý Hiệu Chuẩn/Kiểm Định
 */

// Utility function for fetch with error handling
async function fetchAPI(url) {
    try {
        const response = await fetch(url);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return await response.json();
    } catch (error) {
        console.error('API Error:', error);
        throw error;
    }
}

// Auto-fill thông tin thiết bị khi chọn
function initThietBiAutoFill() {
    const selectTenmay = document.getElementById('tenmay');
    if (!selectTenmay) return;
    
    selectTenmay.addEventListener('change', async function() {
        const mavattu = this.value;
        if (!mavattu) return;
        
        try {
            // Lấy thông tin thiết bị
            const data = await fetchAPI(`api/bangcanhbao.php?action=get_thietbi_info&mavattu=${encodeURIComponent(mavattu)}`);
            
            if (data.success && data.data) {
                const thietbi = data.data;
                
                // Fill các trường
                document.getElementById('somay').value = thietbi.somay || '';
                document.getElementById('chuphuongtien').value = thietbi.bophansh || '';
            }
            
            // Lấy hồ sơ cũ để tham khảo
            const hosoData = await fetchAPI(`api/bangcanhbao.php?action=get_hoso_latest&mavattu=${encodeURIComponent(mavattu)}`);
            
            if (hosoData.success && hosoData.data) {
                const hoso = hosoData.data;
                
                // Pre-fill các thiết bị dẫn chuẩn từ lần trước
                for (let i = 1; i <= 5; i++) {
                    const select = document.querySelector(`select[name="thietbidc${i}"]`);
                    if (select && hoso[`thietbidc${i}`]) {
                        select.value = hoso[`thietbidc${i}`];
                    }
                }
                
                // Pre-fill nơi thực hiện
                const noithuchien = document.querySelector('select[name="noithuchien"]');
                if (noithuchien && hoso.noithuchien) {
                    noithuchien.value = hoso.noithuchien;
                }
            }
            
        } catch (error) {
            console.error('Error loading device info:', error);
        }
    });
}

// Tự động tạo số hồ sơ
function initGenerateSoHS() {
    const btnGenerate = document.getElementById('btnGenerateSoHS');
    if (!btnGenerate) return;
    
    btnGenerate.addEventListener('click', async function() {
        const ngayhcInput = document.getElementById('ngayhc');
        const ngayhc = ngayhcInput.value;
        
        if (!ngayhc) {
            alert('Vui lòng chọn ngày hiệu chuẩn trước!');
            ngayhcInput.focus();
            return;
        }
        
        const date = new Date(ngayhc);
        const month = date.getMonth() + 1;
        const year = date.getFullYear();
        
        try {
            btnGenerate.disabled = true;
            btnGenerate.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang tạo...';
            
            const data = await fetchAPI(`api/bangcanhbao.php?action=generate_sohs&month=${month}&year=${year}`);
            
            if (data.success) {
                document.getElementById('sohs').value = data.sohs;
            } else {
                alert('Không thể tạo số hồ sơ: ' + data.message);
            }
        } catch (error) {
            alert('Có lỗi xảy ra khi tạo số hồ sơ');
        } finally {
            btnGenerate.disabled = false;
            btnGenerate.innerHTML = '<i class="fas fa-magic"></i> Tự động';
        }
    });
}

// Tự động tính ngày HC tiếp theo dựa trên thời hạn KD
function initAutoCalculateNextHC() {
    const ngayhcInput = document.getElementById('ngayhc');
    const tenmaySelect = document.getElementById('tenmay');
    const ngayhcttInput = document.querySelector('input[name="ngayhctt"]');
    
    if (!ngayhcInput || !tenmaySelect || !ngayhcttInput) return;
    
    async function calculateNextHC() {
        const mavattu = tenmaySelect.value;
        const ngayhc = ngayhcInput.value;
        
        if (!mavattu || !ngayhc) return;
        
        try {
            const data = await fetchAPI(`api/bangcanhbao.php?action=get_thietbi_info&mavattu=${encodeURIComponent(mavattu)}`);
            
            if (data.success && data.data && data.data.thoihankd) {
                const thoihankd = parseInt(data.data.thoihankd);
                const date = new Date(ngayhc);
                date.setMonth(date.getMonth() + thoihankd);
                
                // Format: YYYY-MM-DD
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                
                ngayhcttInput.value = `${year}-${month}-${day}`;
            }
        } catch (error) {
            console.error('Error calculating next HC date:', error);
        }
    }
    
    ngayhcInput.addEventListener('change', calculateNextHC);
    tenmaySelect.addEventListener('change', calculateNextHC);
}

// Kiểm tra trùng lặp trước khi submit
function initDuplicateCheck() {
    const form = document.querySelector('form[action*="savehoso"]');
    if (!form) return;
    
    form.addEventListener('submit', async function(e) {
        const mavattu = document.getElementById('tenmay').value;
        const ngayhc = document.getElementById('ngayhc').value;
        
        if (!mavattu || !ngayhc) return;
        
        // Không check duplicate nếu đang edit (có ngayhc trong URL)
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('ngayhc')) return;
        
        e.preventDefault();
        
        try {
            const formData = new FormData();
            formData.append('mavattu', mavattu);
            formData.append('ngayhc', ngayhc);
            
            const response = await fetch('api/bangcanhbao.php?action=check_duplicate', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.exists) {
                if (confirm('Hồ sơ này đã tồn tại. Bạn có muốn cập nhật không?')) {
                    form.submit();
                }
            } else {
                form.submit();
            }
        } catch (error) {
            console.error('Error checking duplicate:', error);
            // Submit anyway if check fails
            form.submit();
        }
    });
}

// Validation form
function initFormValidation() {
    const form = document.querySelector('form[action*="savehoso"], form[action*="savekt"]');
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;
        let firstInvalid = null;
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                field.classList.add('border-red-500');
                if (!firstInvalid) firstInvalid = field;
            } else {
                field.classList.remove('border-red-500');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Vui lòng điền đầy đủ thông tin bắt buộc!');
            if (firstInvalid) {
                firstInvalid.focus();
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });
}

// Confirm before leaving with unsaved changes
function initUnsavedChangesWarning() {
    const form = document.querySelector('form[action*="savehoso"], form[action*="savekt"]');
    if (!form) return;
    
    let formChanged = false;
    
    form.addEventListener('change', function() {
        formChanged = true;
    });
    
    form.addEventListener('submit', function() {
        formChanged = false; // Don't warn on submit
    });
    
    window.addEventListener('beforeunload', function(e) {
        if (formChanged) {
            e.preventDefault();
            e.returnValue = '';
            return '';
        }
    });
}

// Initialize all functions on page load
document.addEventListener('DOMContentLoaded', function() {
    initThietBiAutoFill();
    initGenerateSoHS();
    initAutoCalculateNextHC();
    initDuplicateCheck();
    initFormValidation();
    initUnsavedChangesWarning();
});

// Export functions for external use
window.BangCanhBaoJS = {
    fetchAPI,
    initThietBiAutoFill,
    initGenerateSoHS,
    initAutoCalculateNextHC,
    initDuplicateCheck,
    initFormValidation,
    initUnsavedChangesWarning
};
