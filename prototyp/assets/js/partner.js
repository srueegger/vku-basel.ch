/*
 * Partnerdaten für den Prototyp. Der Build (scripts/build-prototyp.php) ersetzt die
 * leere Liste durch die Partnerinnen der jeweiligen Version aus prototyp/partner.json.
 * Preise in Rappen.
 */
window.VKU_PARTNER = /*PARTNER*/[];

window.VKU = {
  partner: window.VKU_PARTNER.map(function (p) {
    return Object.assign({}, p, { photo: 'assets/img/' + p.photo });
  }),
  chf: function (r) { return (r / 100).toFixed(2); },
  // Günstigster VKU-Preis in einem Kombi-Angebot, ohne Partnerin der normale VKU-Preis.
  abPreis: function () {
    var min = 11495;
    window.VKU_PARTNER.forEach(function (p) {
      p.offers.forEach(function (o) { if (o.vku < min) min = o.vku; });
    });
    return (min / 100).toFixed(2);
  }
};
