const medBody = document.getElementById('med-body');
const addBtn = document.getElementById('add-med');
const form = document.getElementById('recipe-form');
const msg = document.getElementById('msg');
const printBtn = document.getElementById('print-btn');
const datalist = document.getElementById('medicamentos-list');
const vademecum = Array.isArray(window.VADEMECUM_DATA) ? window.VADEMECUM_DATA : [];
const byId = new Map(vademecum.map((m) => [Number(m.id), m]));
let lastRecipe = null;

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
    <td>
      <input class="m-search" list="medicamentos-list" value="${medName}" placeholder="Escriba para buscar" required>
      <input type="hidden" class="m-id" value="${data.medicine_id || 0}">
    </td>
    <td><input value="${data.quantity || 1}" type="number" min="1" class="m-qty" required></td>
    <td><input value="${data.dose || ''}" class="m-dose" required></td>
    <td><input value="${data.instructions || ''}" class="m-inst" required></td>
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

function printRecipe(recipe) {
  const w = window.open('', '_blank');
  const left = recipe.medications
    .map((m) => `<li><b>${m.name}</b> x ${m.quantity} - ${m.dose}<br><small>${m.presentacion}</small></li>`)
    .join('');
  const right = recipe.medications
    .map((m) => `<li><b>${m.name}</b> x ${m.quantity}: ${m.instructions}</li>`)
    .join('');

  w.document.write(`
    <html><head><title>Receta</title>
    <style>
      @page { size: A4 landscape; margin: 12mm; }
      body { font-family: Arial; }
      .sheet { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
      .col { border:1px dashed #333; padding:10px; min-height:180mm; }
      h3,h4{margin:6px 0}
      ul{padding-left:18px}
    </style></head><body>
      <div class="sheet">
        <section class="col">
          <h3>Receta Médica</h3>
          <p><b>Paciente:</b> ${recipe.patient_name} | <b>Cédula:</b> ${recipe.cedula}</p>
          <p><b>Edad:</b> ${recipe.age} | <b>Tel:</b> ${recipe.phone}</p>
          <p><b>Dirección:</b> ${recipe.address}</p>
          <p><b>Diagnóstico:</b> ${recipe.diagnosis} | <b>CIE-10:</b> ${recipe.cie10}</p>
          <h4>Medicamentos</h4><ul>${left}</ul>
        </section>
        <section class="col">
          <h3>Receta Médica</h3>
          <p><b>Paciente:</b> ${recipe.patient_name} | <b>Cédula:</b> ${recipe.cedula}</p>
          <p><b>Edad:</b> ${recipe.age} | <b>Tel:</b> ${recipe.phone}</p>
          <p><b>Dirección:</b> ${recipe.address}</p>
          <p><b>Diagnóstico:</b> ${recipe.diagnosis} | <b>CIE-10:</b> ${recipe.cie10}</p>
          <h4>Indicaciones</h4><ul>${right}</ul>
        </section>
      </div>
    </body></html>
  `);
  w.document.close();
  w.focus();
  w.print();
}

addBtn.addEventListener('click', () => addRow());
printBtn.addEventListener('click', () => {
  if (!lastRecipe) {
    msg.textContent = 'Primero guarda una receta para imprimir.';
    return;
  }
  printRecipe(lastRecipe);
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
    const recipeUrl = `historial_detalle.php?id=${encodeURIComponent(data.id)}&autoprint=1`;
    const printTab = window.open(recipeUrl, '_blank', 'noopener');

    form.reset();
    medBody.innerHTML = '';
    addRow();
    lastRecipe = null;

    msg.textContent = printTab
      ? `Receta #${data.id} guardada correctamente. Se abrió la vista para PDF.`
      : `Receta #${data.id} guardada correctamente. Habilita ventanas emergentes para abrir el PDF automáticamente.`;
  } catch {
    msg.textContent = 'Error de conexión.';
  }
});

fillDatalist();
addRow();
