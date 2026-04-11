/**
 * Hiển thị Toast Message
 * @param {string} message - Nội dung thông báo
 * @param {string} type - Loại thông báo: 'success', 'error', 'warning', 'info'
 */
function showToast(message, type = 'success') {
    let container = document.getElementById('universal-toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'universal-toast-container';
        // Container đặt ở góc phải dưới (bottom-6 right-6), z-index cao nhất
        container.className = 'fixed bottom-6 right-6 z-[9999] flex flex-col gap-3 pointer-events-none';
        document.body.appendChild(container);
    }

    const types = {
        success: { bg: 'bg-emerald-100', text: 'text-emerald-600', border: 'border-emerald-200', icon: 'fa-circle-check' },
        error:   { bg: 'bg-red-100', text: 'text-red-600', border: 'border-red-200', icon: 'fa-circle-exclamation' },
        warning: { bg: 'bg-amber-100', text: 'text-amber-600', border: 'border-amber-200', icon: 'fa-triangle-exclamation' },
        info:    { bg: 'bg-blue-100', text: 'text-blue-600', border: 'border-blue-200', icon: 'fa-circle-info' }
    };
    const style = types[type] || types.info;

    const toast = document.createElement('div');
    toast.className = `p-4 ${style.bg} ${style.text} border ${style.border} rounded-2xl text-sm font-bold flex items-center gap-3 shadow-lg transition-all duration-500 translate-y-10 opacity-0 backdrop-blur-md`;
    toast.innerHTML = `<i class="fa-solid ${style.icon} text-xl"></i> <span>${message}</span>`;
    
    container.appendChild(toast);

    requestAnimationFrame(() => {
        toast.classList.remove('translate-y-10', 'opacity-0');
        toast.classList.add('translate-y-0', 'opacity-100');
    });

    setTimeout(() => {
        toast.classList.remove('translate-y-0', 'opacity-100');
        toast.classList.add('translate-y-10', 'opacity-0');
        setTimeout(() => toast.remove(), 500); 
    }, 4000);
}