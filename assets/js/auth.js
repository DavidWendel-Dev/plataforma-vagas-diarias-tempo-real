/**
 * Autenticação JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    initTabs();
    initForms();
    initPhotoUpload();
});

// Tabs
function initTabs() {
    const tabs = document.querySelectorAll('.auth-tab');
    const forms = document.querySelectorAll('.auth-form');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const targetTab = tab.dataset.tab;

            // Update buttons
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');

            // Update forms
            forms.forEach(form => {
                form.classList.remove('active');
                if (form.dataset.form === targetTab) {
                    form.classList.add('active');
                }
            });
        });
    });
}

// Forms
function initForms() {
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }

    const prestadorForm = document.getElementById('prestadorForm');
    if (prestadorForm) {
        prestadorForm.addEventListener('submit', handlePrestador);
    }

    const empresaForm = document.getElementById('empresaForm');
    if (empresaForm) {
        empresaForm.addEventListener('submit', handleEmpresa);
    }
}

// Login
async function handleLogin(e) {
    e.preventDefault();
    
    const form = e.target;
    const btn = form.querySelector('button[type="submit"]');
    const msg = form.querySelector('.form-message');
    
    btn.disabled = true;
    btn.innerHTML = 'Entrando...';
    
    const formData = new FormData(form);
    formData.append('action', 'login');
    
    try {
        const res = await fetch('api/auth.php', { method: 'POST', body: formData });
        const data = await res.json();
        
        if (data.success) {
            msg.className = 'form-message success';
            msg.textContent = 'Login realizado!';
            setTimeout(() => window.location.href = data.redirect, 500);
        } else {
            msg.className = 'form-message error';
            msg.textContent = data.error || 'Erro ao fazer login.';
        }
    } catch (err) {
        msg.className = 'form-message error';
        msg.textContent = 'Erro de conexão.';
    }
    
    btn.disabled = false;
    btn.innerHTML = '<span>Entrar</span>';
}

// Prestador
async function handlePrestador(e) {
    e.preventDefault();
    
    const form = e.target;
    const btn = form.querySelector('button[type="submit"]');
    const msg = form.querySelector('.form-message');
    
    btn.disabled = true;
    btn.innerHTML = 'Cadastrando...';
    
    const formData = new FormData(form);
    formData.append('action', 'register_prestador');
    
    try {
        const res = await fetch('api/auth.php', { method: 'POST', body: formData });
        const data = await res.json();
        
        if (data.success) {
            msg.className = 'form-message success';
            msg.textContent = data.message || 'Cadastro realizado! Aguarde aprovação.';
            form.reset();
            document.getElementById('photoPreview').innerHTML = `
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                    <circle cx="12" cy="13" r="4"/>
                </svg>
                <span>Adicionar foto</span>
            `;
            document.getElementById('photoPreview').classList.remove('has-image');
        } else {
            msg.className = 'form-message error';
            msg.textContent = data.error || 'Erro ao cadastrar.';
        }
    } catch (err) {
        msg.className = 'form-message error';
        msg.textContent = 'Erro: ' + (err.message || 'conexão');
        console.error('Erro cadastro prestador:', err);
    }
    
    btn.disabled = false;
    btn.innerHTML = 'Cadastrar';
}

// Empresa
async function handleEmpresa(e) {
    e.preventDefault();
    
    const form = e.target;
    const btn = form.querySelector('button[type="submit"]');
    const msg = form.querySelector('.form-message');
    
    btn.disabled = true;
    btn.innerHTML = 'Cadastrando...';
    
    const formData = new FormData(form);
    formData.append('action', 'register_empresa');
    
    try {
        const res = await fetch('api/auth.php', { method: 'POST', body: formData });
        const data = await res.json();
        
        if (data.success) {
            msg.className = 'form-message success';
            msg.textContent = data.message || 'Empresa cadastrada com sucesso!';
            form.reset();
        } else {
            msg.className = 'form-message error';
            msg.textContent = data.error || 'Erro ao cadastrar.';
        }
    } catch (err) {
        msg.className = 'form-message error';
        msg.textContent = 'Erro de conexão.';
    }
    
    btn.disabled = false;
    btn.innerHTML = 'Cadastrar Empresa';
}

// Photo Upload
function initPhotoUpload() {
    const input = document.getElementById('foto_prestador');
    const preview = document.getElementById('photoPreview');
    
    if (input && preview) {
        preview.addEventListener('click', () => input.click());
        
        input.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                if (!file.type.startsWith('image/')) {
                    alert('Selecione uma imagem.');
                    return;
                }
                if (file.size > 5 * 1024 * 1024) {
                    alert('Imagem deve ter no máximo 5MB.');
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = (ev) => {
                    preview.innerHTML = `<img src="${ev.target.result}" alt="">`;
                    preview.classList.add('has-image');
                };
                reader.readAsDataURL(file);
            }
        });
    }
}
