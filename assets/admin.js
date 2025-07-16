document.addEventListener('DOMContentLoaded', function () {
  function addRow(tableId, rowHtml) {
    const tbody = document.querySelector(`#${tableId} tbody`);
    const newRow = document.createElement('tr');
    const index = tbody.querySelectorAll('tr').length;
    newRow.innerHTML = rowHtml.replace(/__INDEX__/g, index);
    tbody.appendChild(newRow);
  }

  // Ajouter produit
  const addProduitBtn = document.getElementById('add-produit-row');
  if (addProduitBtn) {
    addProduitBtn.addEventListener('click', function () {
      const html = `
        <tr>
          <td><input type="text" name="items[__INDEX__][nom]" required></td>
          <td><input type="number" step="0.01" name="items[__INDEX__][prix_normal]" required></td>
          <td><input type="number" step="0.01" name="items[__INDEX__][prix_ce]" required></td>
          <td><input type="number" name="items[__INDEX__][stock]" required></td>
          <td><input type="url" name="items[__INDEX__][url]"></td>
          <td><button class="button remove-row">×</button></td>
        </tr>`;
      addRow('ce-produits-table', html);
    });
  }

  // Ajouter CE
  const addCodeBtn = document.getElementById('add-code-row');
  if (addCodeBtn) {
    addCodeBtn.addEventListener('click', function () {
      const html = `
        <tr>
          <td><input type="text" name="codes[__INDEX__][nom]" required></td>
          <td><input type="text" name="codes[__INDEX__][code]" required></td>
          <td><input type="date" name="codes[__INDEX__][validite]" required></td>
          <td><button class="button remove-row">×</button></td>
        </tr>`;
      addRow('ce-codes-table', html);
    });
  }

  // Suppression de ligne
  document.body.addEventListener('click', function (e) {
    if (e.target.classList.contains('remove-row')) {
      e.preventDefault();
      const row = e.target.closest('tr');
      if (row) row.remove();
    }
  });
});
