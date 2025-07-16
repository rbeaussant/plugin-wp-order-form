document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('#ce-order-form');
  if (!form) return;

  const rows = [...form.querySelectorAll('tbody tr')];
  const totalEl = document.getElementById('ce-total');
  const produits = window.ceProduits || [];

  /**
   * 🔢 Récupère le bon produit à partir de l'index combiné catIndex_prodIndex
   * Exemple : name="qte[0_2]" => catIndex = 0, prodIndex = 2
   */
  function getProduitForRow(row) {
    const input = row.querySelector('.qte-input');
    if (!input) return null;

    const match = input.name.match(/qte\[(\d+)_(\d+)\]/);
    if (!match) return null;

    const catIndex = parseInt(match[1]);
    const prodIndex = parseInt(match[2]);

    if (
      produits[catIndex] &&
      produits[catIndex].produits &&
      produits[catIndex].produits[prodIndex]
    ) {
      return produits[catIndex].produits[prodIndex];
    }
    return null;
  }

  const refresh = () => {
    let total = 0;

    rows.forEach(row => {
      const input = row.querySelector('.qte-input');
      const line = row.querySelector('.line-total');
      if (!input || !line) return;

      const price = parseFloat(input.dataset.price || 0);
      const qty = parseInt(input.value || 0);
      const sub = price * qty;

      line.textContent = sub.toFixed(2).replace('.', ',') + ' €';
      total += sub;
    });

    totalEl.textContent = total.toFixed(2).replace('.', ',') + ' €';
  };

  form.addEventListener('input', e => {
    if (e.target.classList.contains('qte-input')) refresh();
  });

  refresh();

  // --- Récapitulatif ---
  const recapDiv = document.getElementById('ce-recapitulatif');
  const formDiv = document.getElementById('ce-formulaire');
  const recapContent = document.getElementById('recap-content');
  const retourBtn = document.getElementById('retour-formulaire');
  const confirmerBtn = document.getElementById('confirmer-commande');

  form.addEventListener('submit', function (e) {
    const isFinal = form.querySelector('input[name="confirmer_commande"]');
    if (!isFinal) {
      e.preventDefault();

      let total = 0;
      let recapHtml = '<ul>';

      rows.forEach(row => {
        const input = row.querySelector('.qte-input');
        const qty = parseInt(input.value || 0);
        if (qty > 0) {
          const produit = getProduitForRow(row);
          if (produit) {
            const ligne = produit.prix_ce * qty;
            total += ligne;
            recapHtml += `<li>${qty} x ${produit.nom}</li>`;
          }
        }
      });

      recapHtml += '</ul>';
      recapHtml += `<div class="total-recap"><strong>Total : ${total.toFixed(2).replace('.', ',')} €</strong></div>`;

      recapContent.innerHTML = recapHtml;
      if (formDiv) formDiv.style.display = 'none';
      if (recapDiv) recapDiv.style.display = 'block';
    }
  });

  if (retourBtn) {
    retourBtn.addEventListener('click', function () {
      if (recapDiv) recapDiv.style.display = 'none';
      if (formDiv) formDiv.style.display = 'block';
    });
  }

  if (confirmerBtn) {
    confirmerBtn.addEventListener('click', function () {
      let hasProduct = false;
      document.querySelectorAll('.qte-input').forEach(input => {
        if (parseInt(input.value) > 0) {
          hasProduct = true;
        }
      });

      if (!hasProduct) {
        alert("Veuillez sélectionner au moins un produit avant de confirmer votre commande.");
        return;
      }

      let hidden = form.querySelector('input[name="confirmer_commande"]');
      if (!hidden) {
        hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'confirmer_commande';
        hidden.value = '1';
        form.appendChild(hidden);
      }

      form.submit();
    });
  }
  
    document.querySelectorAll('.ce-qty-input').forEach(input => {
    
        const checkMax = function () {
            const max = parseInt(this.max || 0);
            const val = parseInt(this.value || 0);
            if (max > 0 && val > max) {
                alert(`Vous avez atteint la quantité maximale disponible pour ce produit (${max}).`);
                this.value = max;
            }
        };
    
        input.addEventListener('input', checkMax);
        input.addEventListener('change', checkMax);
        input.addEventListener('click', checkMax);
    });

  
});


document.addEventListener('DOMContentLoaded', function() {
    const updateLineAndTotal = function(input) {
        const price = parseFloat(input.dataset.price.replace(',', '.')) || 0;
        const qty = parseInt(input.value) || 0;
        const lineTotalCell = input.closest('tr').querySelector('.line-total');
        const lineTotal = (price * qty).toFixed(2).replace('.', ',') + ' €';
        lineTotalCell.textContent = lineTotal;

        // Recalculer le total général
        let total = 0;
        document.querySelectorAll('.line-total').forEach(function(cell) {
            const val = parseFloat(cell.textContent.replace(',', '.').replace(' €', '')) || 0;
            total += val;
        });
        document.getElementById('ce-total').textContent = total.toFixed(2).replace('.', ',') + ' €';
    };

    document.querySelectorAll('.qty-control').forEach(function(control) {
        const input = control.querySelector('input.ce-qty-input');
        const plus = control.querySelector('.qty-btn.plus');
        const minus = control.querySelector('.qty-btn.minus');
        const max = parseInt(control.dataset.max) || 999;

        plus.addEventListener('click', function() {
            let val = parseInt(input.value) || 0;
            if (val < max) {
                input.value = val + 1;
                input.dispatchEvent(new Event('change'));
            }
        });

        minus.addEventListener('click', function() {
            let val = parseInt(input.value) || 0;
            if (val > 0) {
                input.value = val - 1;
                input.dispatchEvent(new Event('change'));
            }
        });

        input.addEventListener('change', function() {
            updateLineAndTotal(input);
        });
    });
});
