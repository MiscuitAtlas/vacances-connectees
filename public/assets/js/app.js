// Améliorations progressives : le site fonctionne aussi sans JavaScript.
(function () {
  'use strict';

  var DAY = 24 * 60 * 60 * 1000;
  var euros = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 });

  function parseDate(value) {
    var match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value || '');
    return match ? Date.UTC(+match[1], +match[2] - 1, +match[3]) : null;
  }

  function formatDate(time) {
    return new Date(time).toISOString().slice(0, 10);
  }

  // Sur téléphone, les filtres d'une recherche déjà lancée se replient pour laisser voir les résultats.
  // Sur grand écran, ils restent toujours ouverts.
  document.querySelectorAll('details[data-collapsible]').forEach(function (details) {
    var wide = window.matchMedia('(min-width: 960px)');
    if (!wide.matches && details.hasAttribute('data-collapsed')) {
      details.open = false;
    }
    wide.addEventListener('change', function () {
      if (wide.matches) {
        details.open = true;
      }
    });
  });

  // Demande de confirmation avant une action irréversible (annulation, suppression).
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      if (!window.confirm(form.getAttribute('data-confirm'))) {
        event.preventDefault();
      }
    });
  });

  // Le départ ne peut pas précéder le lendemain de l'arrivée.
  document.querySelectorAll('input[data-pair]').forEach(function (arrival) {
    var departure = document.getElementById(arrival.getAttribute('data-pair'));
    if (!departure) {
      return;
    }
    function sync() {
      var start = parseDate(arrival.value);
      if (start === null) {
        return;
      }
      departure.min = formatDate(start + DAY);
      var end = parseDate(departure.value);
      if (end !== null && end <= start) {
        departure.value = formatDate(start + DAY);
      }
    }
    arrival.addEventListener('change', sync);
    sync();
  });

  // Prix du séjour affiché pendant la saisie des dates. Le serveur recalcule le montant réel.
  document.querySelectorAll('form[data-price]').forEach(function (form) {
    var price = parseInt(form.getAttribute('data-price'), 10);
    var output = form.querySelector('[data-quote]');
    var arrival = form.querySelector('[name="arrivee"]');
    var departure = form.querySelector('[name="depart"]');
    if (!output || !arrival || !departure) {
      return;
    }
    function update() {
      var start = parseDate(arrival.value);
      var end = parseDate(departure.value);
      if (start === null || end === null || end <= start) {
        return;
      }
      var nights = Math.round((end - start) / DAY);
      output.textContent = '';
      var detail = document.createElement('span');
      detail.textContent = euros.format(price) + ' × ' + nights + ' ' + (nights > 1 ? 'nuits' : 'nuit');
      var total = document.createElement('strong');
      total.textContent = euros.format(price * nights);
      output.append(detail, total);
    }
    arrival.addEventListener('change', update);
    departure.addEventListener('change', update);
  });
})();
