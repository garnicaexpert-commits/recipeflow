const medBody = document.getElementById('med-body');
const addBtn = document.getElementById('add-med');
const form = document.getElementById('recipe-form');
const msg = document.getElementById('msg');
const printBtn = document.getElementById('print-btn');
const datalist = document.getElementById('medicamentos-list');
const vademecum = Array.isArray(window.VADEMECUM_DATA) ? window.VADEMECUM_DATA : [];
const byId = new Map(vademecum.map((m) => [Number(m.id), m]));
let lastRecipeId = 0;


function fillDatalist() {
  if (!datalist) return;
  datalist.innerHTML = vademecum
    .map((m) => `<option value="${m.nombre_comercial}" data-id="${m.id}">${m.nombre_comercial} (${m.presentacion})</option>`)
    .join('');
}

function resolveMedicineByName(name) {
  const q = String(name || '').trim().toLowerCase();
  if (!q) return null;
  return vademecum.find((m) => m.nombre_comercial.toLowerCase() === q)
    || vademecum.find((m) => m.nombre_comercial.toLowerCase().startsWith(q))
    || null;
}

function addRow(data = { medicine_id: 0, quantity: 1, name: '', dose: '', instructions: '' }) {
  const tr = document.createElement('tr');
  const medName = data.name || (byId.get(Number(data.medicine_id))?.nombre_comercial ?? '');

  tr.innerHTML = `
    <td class="Container" style="display: flex">
      <input class="m-search" list="medicamentos-list" value="${medName}" placeholder="Escriba para buscar" required style="width :500px">
      <input type="hidden" class="m-id" value="${data.medicine_id || 0}">
    </td>
    <td><input value="${data.quantity || 1}" type="number" min="1" class="m-qty" required  style="width :80px"></td>
    <td><input value="${data.dose || ''}" class="m-dose" required  style="width :200px"></td>
    <td><input value="${data.instructions || ''}" class="m-inst" required  style="width :320px"></td>
    <td><button type="button" class="btn-danger">Quitar</button></td>
  `;

  const searchInput = tr.querySelector('.m-search');
  const idInput = tr.querySelector('.m-id');
  const doseInput = tr.querySelector('.m-dose');

  const syncMedicine = () => {
    const m = resolveMedicineByName(searchInput.value);
    if (!m) {
      idInput.value = '0';
      return;
    }
    idInput.value = String(m.id);
    if (!doseInput.value.trim()) {
      doseInput.value = m.dosis || '';
    }
  };

  searchInput.addEventListener('input', syncMedicine);
  searchInput.addEventListener('change', syncMedicine);

  tr.querySelector('button').addEventListener('click', () => tr.remove());
  medBody.appendChild(tr);

  if (medName) syncMedicine();
}

function getRecipeData() {
  const fd = new FormData(form);
  const meds = [...medBody.querySelectorAll('tr')].map((row) => {
    const medId = Number(row.querySelector('.m-id').value || 0);
    const searchText = row.querySelector('.m-search').value.trim();
    const m = byId.get(medId) || resolveMedicineByName(searchText);

    return {
      medicine_id: m ? Number(m.id) : 0,
      name: m ? m.nombre_comercial : searchText,
      componente_quimico: m ? m.componente_quimico : '',
      presentacion: m ? m.presentacion : '',
      quantity: Number(row.querySelector('.m-qty').value || 1),
      dose: row.querySelector('.m-dose').value.trim(),
      instructions: row.querySelector('.m-inst').value.trim(),
    };
  });

  return {
    patient_name: (fd.get('patient_name') || '').toString().trim(),
    cedula: (fd.get('cedula') || '').toString().trim(),
    age: Number(fd.get('age') || 0),
    address: (fd.get('address') || '').toString().trim(),
    phone: (fd.get('phone') || '').toString().trim(),
    diagnosis: (fd.get('diagnosis') || '').toString().trim(),
    cie10: (fd.get('cie10') || '').toString().trim(),
    medications: meds,
  };
}


addBtn.addEventListener('click', () => addRow());
printBtn.addEventListener('click', () => {
  if (!lastRecipe) {
    msg.textContent = 'Primero guarda una receta para imprimir.';
    return;
  }
  window.open(`receta_pdf.php?id=${encodeURIComponent(lastRecipeId)}&autoprint=0`, '_blank', 'noopener');
});

form.addEventListener('submit', async (e) => {
  e.preventDefault();
  const recipe = getRecipeData();
  if (!recipe.medications.length) {
    msg.textContent = 'Agrega al menos un medicamento.';
    return;
  }
  if (recipe.medications.some((m) => !m.medicine_id)) {
    msg.textContent = 'Seleccione medicamentos válidos del vademécum con la búsqueda progresiva.';
    return;
  }

  try {
    const res = await fetch('recipes_api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(recipe),
    });
    const data = await res.json();
    if (!res.ok || !data.ok) {
      msg.textContent = data.message || 'No se pudo guardar';
      return;
    }
    const recipeUrl = `historial_detalle.php?id=${encodeURIComponent(data.id)}&autoprint=0`;
    const printTab = window.open(recipeUrl, '_blank', 'noopener');

    form.reset();
    medBody.innerHTML = '';
    addRow();
    lastRecipeId = Number(data.id) || 0;

    msg.textContent = printTab
      ? `Receta #${data.id} guardada correctamente. Se abrió la vista para PDF.`
      : `Receta #${data.id} guardada correctamente. Habilita ventanas emergentes para abrir el PDF automáticamente.`;
  } catch {
    msg.textContent = 'Error de conexión.';
  }
});

fillDatalist();
addRow();
