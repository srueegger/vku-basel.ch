// Prüfung im Browser vor dem Absenden. Regeln und Meldungen spiegeln src/validation.php;
// der Server prüft immer zusätzlich.
(function () {
  'use strict';

  var form = document.querySelector('.form');
  var summary = document.getElementById('fehler-liste');
  if (!form || !summary) return;

  var order = ['schule', 'name', 'email', 'tel', 'consent'];
  var required = {
    schule: 'Bitte gib den Namen deiner Fahrschule ein.',
    name: 'Bitte gib deinen Vornamen und Namen ein.',
    email: 'Bitte gib deine E-Mail ein.',
    tel: 'Bitte gib deine Telefonnummer ein.'
  };
  var tooLong = 'Bitte kürze diese Angabe auf höchstens 200 Zeichen.';
  var attempted = !summary.hidden;

  function validate() {
    var errors = {};
    Object.keys(required).forEach(function (key) {
      var value = form.elements[key].value.trim();
      if (value === '') errors[key] = required[key];
      else if (Array.from(value).length > 200) errors[key] = tooLong;
    });
    var email = form.elements.email.value.trim();
    if (!errors.email && !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/u.test(email)) {
      errors.email = 'Diese E-Mail scheint unvollständig zu sein, z. B. name@fahrschule.ch.';
    }
    var tel = form.elements.tel.value.trim();
    if (!errors.tel && tel.replace(/\D/g, '').length < 9) {
      errors.tel = 'Bitte gib eine vollständige Telefonnummer ein, z. B. 061 123 45 67.';
    }
    if (!form.elements.consent.checked) {
      errors.consent = 'Bitte bestätige, dass VKU-Basel dich kontaktieren darf.';
    }
    return errors;
  }

  function render(errors) {
    var keys = order.filter(function (key) { return errors[key]; });

    order.forEach(function (key) {
      var input = document.getElementById('f-' + key);
      var err = document.getElementById('f-' + key + '-err');
      var hint = document.getElementById('f-' + key + '-hint');
      var message = errors[key];

      err.hidden = !message;
      err.querySelector('span').textContent = message || '';
      if (input.classList.contains('inp')) input.classList.toggle('is-error', !!message);
      if (message) input.setAttribute('aria-invalid', 'true');
      else input.removeAttribute('aria-invalid');

      var describedBy = [hint ? hint.id : '', message ? err.id : ''].filter(Boolean).join(' ');
      if (describedBy) input.setAttribute('aria-describedby', describedBy);
      else input.removeAttribute('aria-describedby');
    });

    var list = summary.querySelector('ul');
    list.textContent = '';
    keys.forEach(function (key) {
      var li = document.createElement('li');
      var a = document.createElement('a');
      a.href = '#f-' + key;
      a.textContent = errors[key];
      li.appendChild(a);
      list.appendChild(li);
    });
    summary.querySelector('.alert-titel').textContent =
      keys.length === 1 ? 'Eine Angabe fehlt noch:' : keys.length + ' Angaben fehlen noch:';
    summary.hidden = keys.length === 0;

    return keys.length;
  }

  form.addEventListener('submit', function (event) {
    attempted = true;
    if (render(validate()) > 0) {
      event.preventDefault();
      summary.focus();
    }
  });

  form.addEventListener('input', function () {
    if (attempted) render(validate());
  });
  form.addEventListener('change', function () {
    if (attempted) render(validate());
  });

  // Link in der Fehlerliste setzt den Fokus direkt ins Feld.
  summary.addEventListener('click', function (event) {
    var link = event.target.closest('a[href^="#f-"]');
    if (!link) return;
    var target = document.getElementById(link.getAttribute('href').slice(1));
    if (target) {
      event.preventDefault();
      target.focus();
      target.scrollIntoView({ block: 'center' });
    }
  });
})();
