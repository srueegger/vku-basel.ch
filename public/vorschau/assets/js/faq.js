class Component extends DCLogic {
  constructor(props) { super(props); this.state = { open: { 0: true } }; }
  renderVals() {
    const items = [
      { q: 'Wer sollte den VKU machen?', a: 'Für alle, die zum ersten Mal einen Lernfahrausweis erlangen möchten, ist der VKU obligatorisch.\nDer VKU ist aber nicht nur für Neulenkerinnen und Neulenker. Gerade erfahrene Automobilistinnen und Automobilisten können ihr Wissen in der Verkehrskunde spannend auffrischen.\nBesonders im Paket mit Fahrstunden bei einer Partnerschule bietet der VKU bei VKU-Basel ein unschlagbares Preis-Leistungs-Verhältnis.' },
      { q: 'Ist der VKU ein Theorie-Kurs?', a: '[Antworttext folgt, wird im Backend erfasst]', ph: true },
      { q: 'Was wird im VKU gelernt?', a: '[Antworttext folgt, wird im Backend erfasst]', ph: true },
      { q: 'Wie lange dauert ein VKU?', a: 'Der VKU besteht aus 4 Teilen à 2 Stunden. Die Teile werden der Reihe nach besucht, jeder an einem anderen Tag. Verpasst du einen Teil, kannst du ihn kostenlos in einem anderen Kurs nachholen.' },
      { q: 'VKU Mythen und Märchen', a: '[Antworttext folgt: entlarvt Lockangebote wie «inkl. Kursmittel» oder «inkl. Versicherung»]', ph: true }
    ];
    const open = this.state.open;
    const faqs = items.map((it, i) => ({
      q: it.q, a: it.a, open: !!open[i], exp: open[i] ? 'true' : 'false',
      cls: 'q' + (open[i] ? ' open' : ''), aCls: it.ph ? 'ph' : '',
      toggle: () => this.setState({ open: Object.assign({}, open, { [i]: !open[i] }) })
    }));
    const m = !!this.state.menu;
    return { faqs, anyOpen: Object.keys(open).some((k) => open[k]), ovCls: 'mov' + (m ? ' open' : ''), ovHidden: m ? 'false' : 'true', navCls: 'nav' + (m ? ' open' : ''), menuExp: m ? 'true' : 'false', toggleMenu: () => this.setState({ menu: !m }) };
  }
}

DC.mount(Component);
