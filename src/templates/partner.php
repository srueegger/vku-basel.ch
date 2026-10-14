<?php
/**
 * Partnerseite. Variablen aus public/partner/index.php:
 * @var string $view 'form'|'success'
 * @var array{schule:string, name:string, email:string, tel:string, consent:bool} $values
 * @var array<string,string> $errors
 * @var ?string $generalError
 * @var ?string $submittedEmail
 * @var string $csrfToken
 * @var string $tsToken
 */

declare(strict_types=1);

$fields = [
    ['key' => 'schule', 'label' => 'Name der Fahrschule', 'type' => 'text', 'autocomplete' => 'organization', 'inputmode' => 'text', 'hint' => null],
    ['key' => 'name', 'label' => 'Vorname und Name', 'type' => 'text', 'autocomplete' => 'name', 'inputmode' => 'text', 'hint' => null],
    ['key' => 'email', 'label' => 'E-Mail', 'type' => 'email', 'autocomplete' => 'email', 'inputmode' => 'email', 'hint' => null],
    ['key' => 'tel', 'label' => 'Telefon', 'type' => 'tel', 'autocomplete' => 'tel', 'inputmode' => 'tel', 'hint' => 'Am besten direkt erreichbar, z. B. Mobile'],
];

$errorCount = count($errors);
$summaryTitle = $errorCount === 1 ? 'Eine Angabe fehlt noch:' : $errorCount . ' Angaben fehlen noch:';
$asset = static fn (string $path): string => '/assets/' . $path . '?v=' . (@filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/' . $path) ?: '1');
$errorIcon = '<svg width="18" height="18" viewBox="0 0 18 18" aria-hidden="true"><circle cx="9" cy="9" r="9" fill="#B3121D"></circle><rect x="8" y="4" width="2" height="6.5" fill="#FFFFFF"></rect><rect x="8" y="12" width="2" height="2" fill="#FFFFFF"></rect></svg>';
?>
<!doctype html>
<html lang="de-CH">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $view === 'success' ? 'Danke · ' : ($errorCount > 0 ? 'Fehler im Formular · ' : '') ?>VKU-Basel · Partnerin oder Partner werden</title>
<meta name="description" content="Gemeinsam stark: Werde jetzt Partner von VKU-Basel. Für Fahrlehrerinnen und Fahrlehrer.">
<link rel="preload" href="/assets/fonts/noto-sans-400.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="/assets/fonts/noto-sans-700.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= e($asset('css/partner.css')) ?>">
<script src="<?= e($asset('js/partner.js')) ?>" defer></script>
</head>
<body>
<div class="page">

  <header class="site-header">
    <div class="wrap">
      <a href="#top" class="brand" aria-label="VKU-Basel, zum Seitenanfang">VKU-Basel</a>
      <a href="#anmeldung" class="ghost-btn"><span class="kurz">Anmelden</span><span class="lang">Interesse anmelden</span></a>
    </div>
  </header>

  <main id="top">

    <section class="hero wrap" aria-labelledby="hero-title">
      <div class="hero-inner">
        <div class="hero-kicker">
          <img class="hero-logo" src="/assets/img/logo.png" alt="" width="164" height="52">
          <p class="eyebrow">Für Fahrlehrerinnen und Fahrlehrer</p>
        </div>
        <h1 id="hero-title">Gemeinsam stark: Werde jetzt Partner</h1>
        <p class="lead">Mit der Marke «VKU-Basel» bietet reinis Fahrschule dir die Möglichkeit, ohne Risiko und ohne eigenes Theorie-Lokal künftig attraktive Angebote aus VKU und Fahrstunden für deine Schülerinnen und Schüler anzubieten.<br>Da reinis Fahrschule in den Hintergrund tritt und künftig keine Fahrstunden mehr anbietet, erhältst du als Partner von VKU-Basel exklusiv die Möglichkeit, neue Kundinnen und Kunden für dich zu gewinnen.</p>
      </div>
    </section>

    <div class="wrap"><div class="leitlinie" aria-hidden="true"></div></div>

    <section class="vorteile wrap" aria-labelledby="vorteile-title">
      <h2 id="vorteile-title">Was du davon hast</h2>
      <ul class="vorteile-liste">
        <li class="vorteil">
          <img src="/assets/img/icon-kosten.png" alt="" width="60" height="60">
          <div class="vorteil-text">
            <h3>Keine Kosten ohne Kundschaft</h3>
            <p>Kosten entstehen für dich erst, wenn du selbst eine Kundin oder einen Kunden, vermittelt durch VKU-Basel, auf sicher hast.</p>
          </div>
        </li>
        <li class="vorteil">
          <img src="/assets/img/icon-kundschaft.png" alt="" width="60" height="60">
          <div class="vorteil-text">
            <h3>Mehr Kundschaft ohne Mehraufwand</h3>
            <p>Das Kombiangebot aus VKU und Fahrstundenabo wird online und offline beworben, ohne Mehrkosten für dich. Dazu kommt ein Vermittlungsprogramm für Neukundinnen und Neukunden.</p>
          </div>
        </li>
        <li class="vorteil">
          <img src="/assets/img/icon-fair.png" alt="" width="60" height="60">
          <div class="vorteil-text">
            <h3>Fair und flexibel</h3>
            <p>Die Preisreduktion für die Kombiangebote teilen sich VKU-Basel und deine Fahrschule. Nach sechs Monaten entscheidest du, ob du verlängerst, die Stufe wechselst oder aufhörst.</p>
          </div>
        </li>
      </ul>
      <div class="stufen">
        <p class="stufen-titel">Drei Partnerstufen: Platin, Gold und Silber</p>
        <p class="stufen-text">Die Plätze sind beschränkt: 1 Platz Platin, 2 Plätze Gold, 3 Plätze Silber.<br><br>Gerne bespreche ich mit dir die Leistungen und Konditionen in einem persönlichen Gespräch.</p>
      </div>
    </section>

    <section id="anmeldung" class="anmeldung<?= $view === 'success' ? ' is-success' : '' ?>" aria-labelledby="<?= $view === 'success' ? 'danke-title' : 'form-title' ?>">
      <div class="wrap">
        <div class="anmeldung-intro">
          <div class="leitlinie" aria-hidden="true"></div>
          <h2 id="form-title">Ich habe Interesse</h2>
          <p>Trag dich unverbindlich ein. Danach bekommst du eine persönliche Rückmeldung, um alles Weitere zu besprechen.</p>
        </div>

        <div class="anmeldung-karte">
<?php if ($view === 'success'): ?>
          <div class="danke" role="status">
            <img class="danke-icon" src="/assets/img/icon-kosten.png" alt="" width="64" height="64">
            <h2 id="danke-title">Danke! Ich freue mich über dein Interesse!</h2>
            <p class="danke-lead">Deine Angaben sind angekommen.</p>
            <div class="danke-weiter">
              <h3>So geht es weiter</h3>
              <ol>
<?php if ($submittedEmail !== null): ?>
                <li>Du bekommst eine persönliche Rückmeldung per E-Mail an <strong><?= e($submittedEmail) ?></strong> oder telefonisch.</li>
<?php else: ?>
                <li>Du bekommst eine persönliche Rückmeldung per E-Mail oder telefonisch.</li>
<?php endif; ?>
                <li>Im persönlichen Gespräch klären wir alle Details und welche Partnerstufe für dich passt.</li>
              </ol>
            </div>
            <p class="danke-fragen">Fragen vorab? Schreib an <a href="mailto:info@reinis-fahrschule.ch">info@reinis-fahrschule.ch</a>.</p>
          </div>
<?php else: ?>
          <p class="pflicht">Alle Felder sind Pflichtfelder.</p>

<?php if ($generalError !== null): ?>
          <div class="alert" role="alert">
            <p class="alert-titel"><?= e($generalError) ?></p>
          </div>
<?php endif; ?>

          <div class="alert" id="fehler-liste" role="alert" tabindex="-1"<?= $errorCount === 0 ? ' hidden' : '' ?>>
            <p class="alert-titel"><?= e($summaryTitle) ?></p>
            <ul>
<?php foreach ($errors as $key => $message): ?>
              <li><a href="#f-<?= e($key) ?>"><?= e($message) ?></a></li>
<?php endforeach; ?>
            </ul>
          </div>

          <form class="form" method="post" action="/partner/#anmeldung" novalidate aria-labelledby="form-title">
            <input type="hidden" name="csrf" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="ts" value="<?= e($tsToken) ?>">
            <div class="hp" aria-hidden="true">
              <label for="f-website">Website nicht ausfüllen</label>
              <input id="f-website" type="text" name="<?= e(HONEYPOT_FIELD) ?>" value="" tabindex="-1" autocomplete="off">
            </div>

<?php foreach ($fields as $f):
    $id = 'f-' . $f['key'];
    $error = $errors[$f['key']] ?? null;
    $describedBy = trim(($f['hint'] !== null ? $id . '-hint ' : '') . ($error !== null ? $id . '-err' : ''));
?>
            <div class="feld" data-field="<?= e($f['key']) ?>">
              <label for="<?= e($id) ?>"><?= e($f['label']) ?></label>
<?php if ($f['hint'] !== null): ?>
              <span class="feld-hint" id="<?= e($id) ?>-hint"><?= e($f['hint']) ?></span>
<?php endif; ?>
              <input class="inp<?= $error !== null ? ' is-error' : '' ?>" id="<?= e($id) ?>" name="<?= e($f['key']) ?>" type="<?= e($f['type']) ?>" autocomplete="<?= e($f['autocomplete']) ?>" inputmode="<?= e($f['inputmode']) ?>" aria-required="true"<?= $error !== null ? ' aria-invalid="true"' : '' ?><?= $describedBy !== '' ? ' aria-describedby="' . e($describedBy) . '"' : '' ?> value="<?= e($values[$f['key']]) ?>">
              <p class="feld-fehler" id="<?= e($id) ?>-err"<?= $error === null ? ' hidden' : '' ?>><?= $errorIcon ?><span><?= e($error ?? '') ?></span></p>
            </div>
<?php endforeach; ?>

<?php $consentError = $errors['consent'] ?? null; ?>
            <div class="consent" data-field="consent">
              <div class="consent-zeile">
                <input id="f-consent" name="consent" value="1" type="checkbox" aria-required="true"<?= $values['consent'] ? ' checked' : '' ?><?= $consentError !== null ? ' aria-invalid="true" aria-describedby="f-consent-err"' : '' ?>>
                <label for="f-consent">Ich bin einverstanden, dass VKU-Basel meine Angaben speichert und mich kontaktiert.</label>
              </div>
              <p class="feld-fehler" id="f-consent-err"<?= $consentError === null ? ' hidden' : '' ?>><?= $errorIcon ?><span><?= e($consentError ?? '') ?></span></p>
            </div>

            <button type="submit" class="ww-btn">
              <span class="ww-fill">
                <span>Interesse anmelden</span>
                <svg width="22" height="22" viewBox="0 0 22 22" aria-hidden="true"><path d="M3 11h14M11 5l6 6-6 6" fill="none" stroke="#FFFFFF" stroke-width="2.6"></path></svg>
              </span>
            </button>
          </form>
<?php endif; ?>
        </div>
      </div>
    </section>
  </main>

  <footer class="foot">
    <div class="wrap">
      <div class="foot-brand">
        <span class="foot-name">VKU-Basel</span>
        <p class="foot-copy">© 2026 VKU-Basel · Ein Angebot von reinis Fahrschule</p>
      </div>
      <address>
        <span>reinis Fahrschule<br>Burgfelderstrasse 11<br>4055 Basel</span>
        <a href="mailto:info@reinis-fahrschule.ch">info@reinis-fahrschule.ch</a>
        <a href="tel:+41796386138">079 638 61 38</a>
      </address>
    </div>
  </footer>
</div>
</body>
</html>
