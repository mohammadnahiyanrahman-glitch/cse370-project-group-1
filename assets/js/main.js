document.addEventListener('DOMContentLoaded', () => {
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

    const toastElList = document.querySelectorAll('.toast');
    const toastList = [...toastElList].map(toastEl => new bootstrap.Toast(toastEl));

    const oilMeters = document.querySelectorAll('.oil-meter-bar');
    oilMeters.forEach(meter => {
        const targetWidth = meter.getAttribute('data-value');
        setTimeout(() => {
            meter.style.width = targetWidth + '%';
            

            const val = parseFloat(targetWidth);
            if (val < 33) {
                meter.style.background = '#28a745'; 
            } else if (val < 66) {
                meter.style.background = '#ffc107'; 
            } else {
                meter.style.background = '#dc3545'; 
            }
        }, 300); 
    });
});

const politicianInput = document.getElementById('politician_search');
if(politicianInput) {
    politicianInput.addEventListener('change', function() {
        const list = document.getElementById('politician_list');
        const options = Array.from(list.options).map(opt => opt.value);
        const notice = document.getElementById('new_politician_notice');
        if (this.value && !options.includes(this.value)) {
            notice.classList.remove('d-none');
        } else {
            notice.classList.add('d-none');
        }
    });
}
