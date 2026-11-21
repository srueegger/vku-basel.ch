class Component extends DCLogic {
  constructor(props) { super(props); this.state = { open: '', explain: false }; }
  renderVals() {
    const chf = (r) => (r / 100).toFixed(2);
    const offer = (id, first, title, lessons, regular, bundle, vku) => ({
      id, first, title, lessons,
      regular: chf(regular), bundle: chf(bundle), vku: chf(vku),
      perLesson: chf(Math.round((bundle - vku) / lessons)),
      open: this.state.open === id,
      moreLabel: this.state.open === id ? 'Weniger anzeigen' : 'Mehr zum Angebot',
      toggle: () => this.setState({ open: this.state.open === id ? '' : id })
    });
    const partners = VKU.partner.map((p) => Object.assign({}, p, {
      offers: p.offers.map((o) => offer(o.id, p.name, 'VKU + ' + o.lessons + ' Fahrstunden à ' + o.min + ' Minuten', o.lessons, o.regular, o.bundle, o.vku))
    }));
    return {
      partners,
      abPreis: VKU.abPreis(),
      monatId: partners.length ? partners[0].id : 'partner',
      explain: this.state.explain,
      explainLabel: this.state.explain ? 'Weniger anzeigen' : 'Erkläre mir mehr dazu',
      toggleExplain: () => this.setState({ explain: !this.state.explain }),
      ovCls: 'mov' + (this.state.menu ? ' open' : ''), ovHidden: this.state.menu ? 'false' : 'true', navCls: 'nav' + (this.state.menu ? ' open' : ''), menuExp: this.state.menu ? 'true' : 'false',
      toggleMenu: () => this.setState({ menu: !this.state.menu }),
      horn: () => {
        try {
          const Ctx = window.AudioContext || window.webkitAudioContext;
          const ctx = new Ctx();
          [554.37, 659.25, 440.0].forEach((f, i) => {
            const t = ctx.currentTime + i * 0.42;
            [1, 2, 3].forEach((h) => {
              const o = ctx.createOscillator(); const g = ctx.createGain();
              o.type = 'sawtooth'; o.frequency.value = f * h;
              g.gain.setValueAtTime(0.0001, t);
              g.gain.exponentialRampToValueAtTime(0.12 / h, t + 0.04);
              g.gain.setValueAtTime(0.12 / h, t + 0.34);
              g.gain.exponentialRampToValueAtTime(0.0001, t + 0.4);
              o.connect(g); g.connect(ctx.destination); o.start(t); o.stop(t + 0.42);
            });
          });
        } catch (e) {}
      }
    };
  }
}

DC.mount(Component);
