const container = document.querySelector('.container');
const registerBtn = document.querySelector('.register-btn');
const loginBtn = document.querySelector('.login-btn');

registerBtn?.addEventListener('click', () => {
    container?.classList.add('active');
});

loginBtn?.addEventListener('click', () => {
    container?.classList.remove('active');
});

document.addEventListener('DOMContentLoaded', () => {
    const successMessage = document.querySelector('.success-message');

    if (successMessage) {
        setTimeout(() => {
            successMessage.style.transition = 'opacity 0.5s ease';
            successMessage.style.opacity = '0';

            setTimeout(() => {
                successMessage.remove();
            }, 500);
        }, 5000); // 5 secunde
    }
});