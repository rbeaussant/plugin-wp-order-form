document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('#ce-order-form');
  if (!form) return;

  const rows   = [...form.querySelectorAll('tbody tr')];
  const totalEl = document.getElementById('ce-total');

  const refresh = () => {
    let total = 0;

    rows.forEach(row => {
      const input = row.querySelector('.qte-input');
      const line  = row.querySelector('.line-total');
      if (!input || !line) return;

      const price = parseFloat(input.dataset.price || 0);
      const qty   = parseInt(input.value || 0);
      const sub   = price * qty;

      line.textContent = sub.toFixed(2).replace('.', ',') + ' €';
      total += sub;
    });

    totalEl.textContent = total.toFixed(2).replace('.', ',') + ' €';
  };

  // mise à jour en direct
  form.addEventListener('input', e => {
    if (e.target.classList.contains('qte-input')) refresh();
  });

  refresh(); // calcul initial

  // --- Ajout pour gestion du récapitulatif JS ---
  const recapDiv = document.getElementById('ce-recapitulatif');
  const formDiv = document.getElementById('ce-formulaire');
  const recapContent = document.getElementById('recap-content');
  const retourBtn = document.getElementById('retour-formulaire');
  const confirmerBtn = document.getElementById('confirmer-commande');

  // Récupération des produits côté JS (injecté par PHP)
  const produits = window.ceProduits || [];

  // Intercepte le submit UNIQUEMENT si le champ 'confirmer_commande' n'est PAS présent
  form.addEventListener('submit', function(e) {
    const isFinal = form.querySelector('input[name="confirmer_commande"]');
    if (!isFinal) {
      e.preventDefault();

      // Génération du récapitulatif
      let total = 0;
      let recapHtml = '<h2>Récapitulatif de votre commande</h2><ul>';
      rows.forEach((row, i) => {
        const input = row.querySelector('.qte-input');
        const qty = parseInt(input.value || 0);
        if (qty > 0 && produits[i]) {
          const produit = produits[i];
          const ligne = produit.prix_ce * qty;
          total += ligne;
          recapHtml += `<li>${produit.nom} × ${qty} = ${ligne.toFixed(2).replace('.', ',')} €</li>`;
        }
      });
      recapHtml += '</ul>';
      recapHtml += `<p><strong>Total : ${total.toFixed(2).replace('.', ',')} €</strong></p>`;

      // Infos client
      const nom = form.querySelector('input[name="nom"]')?.value || '';
      const prenom = form.querySelector('input[name="prenom"]')?.value || '';
      const email = form.querySelector('input[name="email"]')?.value || '';
      const tel = form.querySelector('input[name="telephone"]')?.value || '';
      recapHtml += `<p><strong>Nom :</strong> ${nom} ${prenom}<br><strong>Email :</strong> ${email}<br><strong>Téléphone :</strong> ${tel}</p>`;

      recapContent.innerHTML = recapHtml;
      if (formDiv) formDiv.style.display = 'none';
      if (recapDiv) recapDiv.style.display = 'block';
    }
    // Sinon, laisse le POST se faire (soumission finale)
  });

  if (retourBtn) {
    retourBtn.addEventListener('click', function() {
      if (recapDiv) recapDiv.style.display = 'none';
      if (formDiv) formDiv.style.display = 'block';
    });
  }

  if (confirmerBtn) {
    confirmerBtn.addEventListener('click', function() {
      // Ajoute un champ caché pour signaler la soumission finale
      let hidden = form.querySelector('input[name="confirmer_commande"]');
      if (!hidden) {
        hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'confirmer_commande';
        hidden.value = '1';
        form.appendChild(hidden);
      }
      // Ajoute la valeur de la case test_commande
      let testCheckbox = document.getElementById('test-commande');
      let testInput = form.querySelector('input[name="test_commande"]');
      if (testCheckbox && testCheckbox.checked) {
        if (!testInput) {
          testInput = document.createElement('input');
          testInput.type = 'hidden';
          testInput.name = 'test_commande';
          testInput.value = '1';
          form.appendChild(testInput);
        } else {
          testInput.value = '1';
        }
      } else if (testInput) {
        testInput.remove();
      }
      form.submit();
    });
  }
});