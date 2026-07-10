// Modal functions
function showModal(type) {
    const modal = document.getElementById('transactionModal');
    const title = document.getElementById('modalTitle');
    const transactionType = document.getElementById('transactionType');
    const cardDetails = document.getElementById('cardDetails');
    
    modal.style.display = 'block';
    transactionType.value = type;
    
    switch(type) {
        case 'deposit':
            title.textContent = '💰 Deposit Funds';
            cardDetails.style.display = 'block';
            document.getElementById('description').placeholder = 'e.g., Bank transfer, Cash deposit';
            break;
        case 'withdraw':
            title.textContent = '🏦 Withdraw Funds';
            cardDetails.style.display = 'block';
            document.getElementById('description').placeholder = 'e.g., ATM withdrawal';
            break;
        case 'payment':
            title.textContent = '💳 Make Payment';
            cardDetails.style.display = 'block';
            document.getElementById('description').placeholder = 'e.g., Tuition fee, Library fine';
            break;
    }
}

function closeModal() {
    document.getElementById('transactionModal').style.display = 'none';
    document.getElementById('transactionForm').reset();
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('transactionModal');
    if (event.target == modal) {
        closeModal();
    }
}

// Format currency
function formatCurrency(amount) {
    return '£' + parseFloat(amount).toFixed(2);
}

// Validate card number
function validateCardNumber(cardNumber) {
    return /^\d{16}$/.test(cardNumber.replace(/\s/g, ''));
}

// Auto-hide flash messages
document.addEventListener('DOMContentLoaded', function() {
    const messages = document.querySelectorAll('.flash-message');
    messages.forEach(function(msg) {
        setTimeout(function() {
            msg.style.display = 'none';
        }, 5000);
    });
});