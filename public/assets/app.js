const form = document.getElementById('login-form');
const msg = document.getElementById('msg');

if (form) {
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    msg.textContent = 'Validando...';

    const payload = {
      username: form.username.value.trim(),
      password: form.password.value,
    };

    try {
      const res = await fetch('../public/login.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });

      const data = await res.json();
      if (!res.ok || !data.ok) {
        msg.textContent = data.message || 'No se pudo iniciar sesión';
        return;
      }

      window.location.href = 'dashboard.php';
    } catch {
      msg.textContent = 'No hay conexión con el servidor.';
    }
  });
}
