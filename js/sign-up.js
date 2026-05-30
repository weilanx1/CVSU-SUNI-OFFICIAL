function selectRole(role) {

    document.getElementById('selectedRole').value = role;

    document.getElementById('roleSelection').style.display = 'none';

    document.getElementById('registrationForm').style.display = 'block';

    if (role === 'student') {
        document.getElementById('studentFields').style.display = 'block';
        document.getElementById('facultyFields').style.display = 'none';
        document.getElementById('formSubtitle').innerText = "Student Registration";
    } else {
        document.getElementById('studentFields').style.display = 'none';
        document.getElementById('facultyFields').style.display = 'block';
        document.getElementById('formSubtitle').innerText = "Faculty Registration";
    }
}


    document.querySelectorAll('.custom-dropdown').forEach(dropdown => {
        const header = dropdown.querySelector('.dropdown-header');
        const list = dropdown.querySelector('.dropdown-list');
        const input = dropdown.querySelector('input[type="hidden"]');
        const headerText = header.querySelector('span');

        header.addEventListener('click', (e) => {
            e.stopPropagation();
            document.querySelectorAll('.custom-dropdown').forEach(other => {
                if (other !== dropdown) other.classList.remove('open');
            });
            dropdown.classList.toggle('open');
        });

        list.querySelectorAll('li').forEach(item => {
            item.addEventListener('click', (e) => {
                e.stopPropagation();
                headerText.innerText = item.innerText;
                headerText.style.color = "#111111"; 
                input.value = item.getAttribute('data-value');
                dropdown.classList.remove('open');
            });
        });
    });

    window.addEventListener('click', () => {
        document.querySelectorAll('.custom-dropdown').forEach(dropdown => {
            dropdown.classList.remove('open');
        });
    });

    document.querySelectorAll('.toggle-password-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-target');
            const targetInput = document.getElementById(targetId);
            const icon = btn.querySelector('.eye-icon');
            
            if (targetInput.type === 'password') {
                targetInput.type = 'text';
                icon.innerHTML = '<line x1="1" y1="1" x2="23" y2="23"></line><path d="M9 9a3 3 0 1 1 4.24 4.24"></path><path d="M17.65 17.65A10.93 10.93 0 0 1 12 19.5c-7 0-11-8-11-8a18.24 18.24 0 0 1 3.14-4.64m4.24-4.24A11.23 11.23 0 0 1 12 4.5c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"></path>';
            } else {
                targetInput.type = 'password';
                icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
            }
        });
    });

    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('confirmPassword');
    const lengthHint = document.getElementById('lengthHint');
    const matchHint = document.getElementById('matchHint');

    function liveValidate() {
        if (passwordInput.value.length >= 8) {
            lengthHint.innerText = "✓ At least 8 characters";
            lengthHint.classList.remove('invalid');
            lengthHint.classList.add('valid');
        } else {
            lengthHint.innerText = "✕ Must be at least 8 characters";
            lengthHint.classList.remove('valid');
            lengthHint.classList.add('invalid');
        }

        if (confirmInput.value.length > 0) {
            matchHint.style.display = "flex";
            if (passwordInput.value === confirmInput.value) {
                matchHint.innerText = "✓ Passwords match perfectly";
                matchHint.classList.remove('invalid');
                matchHint.classList.add('valid');
            } else {
                matchHint.innerText = "✕ Passwords do not match";
                matchHint.classList.remove('valid');
                matchHint.classList.add('invalid');
            }
        } else {
            matchHint.style.display = "none";
        }
    }

    passwordInput.addEventListener('input', liveValidate);
    confirmInput.addEventListener('input', liveValidate);

    const form = document.getElementById('registrationForm');
    const passGroup = document.getElementById('passGroup');
    const confirmGroup = document.getElementById('confirmGroup');
    const whitebox = document.querySelector('.signup-whitebox');

    form.addEventListener('submit', (e) => {
        passGroup.classList.remove('input-error');
        confirmGroup.classList.remove('input-error');
        whitebox.classList.remove('shake-error');

        let hasError = false;

        if (passwordInput.value.length < 8) {
            passGroup.classList.add('input-error');
            hasError = true;
        }

        if (passwordInput.value !== confirmInput.value) {
            confirmGroup.classList.add('input-error');
            hasError = true;
        }

        if (hasError) {
            e.preventDefault();
            void whitebox.offsetWidth; 
            whitebox.classList.add('shake-error');
        }
    });