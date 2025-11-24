document.addEventListener('DOMContentLoaded', function() {

    const showBtn = document.getElementById('show-edit-form-btn');
    const cancelBtn = document.getElementById('cancel-edit-btn');
    const editForm = document.getElementById('edit-profile-form');

    if (showBtn && editForm) {
        showBtn.addEventListener('click', function(e) {
            e.preventDefault();
            editForm.style.display = 'block';
            showBtn.style.display = 'none';
        });
    }
    
    if (cancelBtn && editForm && showBtn) {
        cancelBtn.addEventListener('click', function(e) {
            e.preventDefault();
            editForm.style.display = 'none';
            showBtn.style.display = 'inline-flex';
        });
    }
    const messages = document.querySelectorAll('.profile-message');

    messages.forEach(message => {
        if (message) {
            setTimeout(() => {
                message.style.transition = 'opacity 0.5s ease';
                message.style.opacity = '0';
                setTimeout(() => message.remove(), 500);
            }, 3000);
        }
    });
    
    const rechargeForm = document.getElementById('recharge-form');
    const rechargeInput = document.getElementById('recharge-amount-input');
    const rechargeButtons = document.querySelectorAll('.recharge-btn');
    const modalBackdrop = document.getElementById('recharge-modal-backdrop');
    const modalText = document.getElementById('recharge-modal-text');
    const modalCancelBtn = document.getElementById('modal-cancel-btn');
    const modalConfirmBtn = document.getElementById('modal-confirm-btn');
    
    let currentRechargeAmount = 0;

    if (rechargeForm && rechargeInput && rechargeButtons && modalBackdrop) {
        
        rechargeButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault(); 
                const amount = this.dataset.amount;
                currentRechargeAmount = amount;
                modalText.textContent = 'Czy na pewno chcesz doładować konto o ' + amount + ' zł?';
                modalBackdrop.classList.add('modal-open');
            });
        });

        function closeModal() {
            modalBackdrop.classList.remove('modal-open');
            currentRechargeAmount = 0;
        }

        modalCancelBtn.addEventListener('click', closeModal);
        modalBackdrop.addEventListener('click', function(e) {
            if (e.target === modalBackdrop) {
                closeModal();
            }
        });

        modalConfirmBtn.addEventListener('click', function() {
            if (currentRechargeAmount > 0) {
                rechargeInput.value = currentRechargeAmount;
                rechargeForm.submit();
                closeModal();
            }
        });
    }
    
    const tabContents = document.querySelectorAll('.profile-tab-content');
    
    initializeSortSlider('.profile-tab-slider', (tabId) => {
        if (!tabId) return;

        tabContents.forEach(content => {
            content.classList.toggle('active', content.id === `profile-${tabId}-content`);
        });
    });
});