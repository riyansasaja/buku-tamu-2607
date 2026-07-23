import './bootstrap';

document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const input = document.getElementById(button.dataset.target);

        if (!input) {
            return;
        }

        const showPassword = input.type === 'password';

        input.type = showPassword ? 'text' : 'password';
        button.setAttribute('aria-pressed', String(showPassword));
        button.setAttribute('aria-label', showPassword ? 'Sembunyikan password' : 'Tampilkan password');

        button.querySelector('[data-password-visible="false"]')?.classList.toggle('hidden', showPassword);
        button.querySelector('[data-password-visible="true"]')?.classList.toggle('hidden', !showPassword);
    });
});
