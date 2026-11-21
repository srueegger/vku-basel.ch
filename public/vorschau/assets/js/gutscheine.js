class Component extends DCLogic {
  constructor(props) { super(props); this.state = { type: 'value', amount: 100, free: '', custom: false, partner: 'doris', offer: 'd5', msg: 'Alles Gute zum 18. Geburtstag!' }; }
  renderVals() {
    const s = this.state;
    const pill = (on) => 'pill' + (on ? ' on' : '');
    const amt = s.custom && s.free !== '' ? Math.max(20, Number(s.free) || 20) : s.amount;
    const chf = (r) => (r / 100).toFixed(2);
    const P = [
      { id: 'doris', name: 'Doris', school: 'Fahrschule Doris Studer', region: 'Basel-Stadt', photo: 'assets/img/foto-doris.jpg',
        offers: [{ id: 'd5', lessons: 5, min: 50, regular: 56495, bundle: 52495, vku: 9495 }, { id: 'd10', lessons: 10, min: 55, regular: 101495, bundle: 92495, vku: 6995 }] },
      { id: 'gaby', name: 'Gaby', school: 'Step by Step', region: 'Region Zuzgen', photo: 'assets/img/foto-gaby.jpg',
        offers: [{ id: 'g5', lessons: 5, min: 50, regular: 69895, bundle: 47995, vku: 9995 }] }
    ];
    const cp = P.find((p) => p.id === s.partner) || P[0];
    const co = cp.offers.find((o) => o.id === s.offer) || cp.offers[0];
    const price = s.type === 'value' ? amt.toFixed(2) : (s.type === 'vku' ? '114.95' : (s.type === 'combo' ? '128.00' : chf(co.bundle)));
    const label = s.type === 'value' ? 'CHF ' + amt + '.–' : (s.type === 'vku' ? 'VKU' : (s.type === 'combo' ? 'VKU + Theorievorbereitung' : 'VKU + ' + co.lessons + ' Fahrstunden mit ' + cp.name));
    return {
      isValue: s.type === 'value', isVku: s.type === 'vku', isCombo: s.type === 'combo', isPartner: s.type === 'partner',
      tPartner: 'tab' + (s.type === 'partner' ? ' on' : ''), selPartner: s.type === 'partner' ? 'true' : 'false', pickPartner: () => this.setState({ type: 'partner' }),
      partnerName: cp.name,
      partners: P.map((p) => ({ name: p.name, school: p.school, region: p.region, photo: p.photo, cls: 'pcard' + (p.id === cp.id ? ' on' : ''), pressed: p.id === cp.id ? 'true' : 'false', pick: () => this.setState({ partner: p.id, offer: p.offers[0].id }) })),
      offers: cp.offers.map((o) => ({ title: 'VKU + ' + o.lessons + ' Fahrstunden à ' + o.min + ' Min.', vku: chf(o.vku), per: chf(Math.round((o.bundle - o.vku) / o.lessons)), regular: chf(o.regular), bundle: chf(o.bundle), on: o.id === co.id, cls: 'offer' + (o.id === co.id ? ' on' : ''), pick: () => this.setState({ offer: o.id }) })),
      selValue: s.type === 'value' ? 'true' : 'false', selVku: s.type === 'vku' ? 'true' : 'false', selCombo: s.type === 'combo' ? 'true' : 'false',
      tValue: 'tab' + (s.type === 'value' ? ' on' : ''), tVku: 'tab' + (s.type === 'vku' ? ' on' : ''), tCombo: 'tab' + (s.type === 'combo' ? ' on' : ''),
      pickValue: () => this.setState({ type: 'value' }), pickVku: () => this.setState({ type: 'vku' }), pickCombo: () => this.setState({ type: 'combo' }),
      a50: !s.custom && s.amount === 50 ? 'on' : '', a100: !s.custom && s.amount === 100 ? 'on' : '', a150: !s.custom && s.amount === 150 ? 'on' : '',
      set50: () => this.setState({ amount: 50, custom: false, free: '' }), set100: () => this.setState({ amount: 100, custom: false, free: '' }), set150: () => this.setState({ amount: 150, custom: false, free: '' }),
      betragCls: 'betrag' + (s.custom && s.free !== '' ? ' on' : ''), free: s.free,
      setFreeVal: (e) => { const v = e && e.target ? e.target.value : ''; this.setState({ free: v, custom: v !== '' }); },
      setMsg: (e) => this.setState({ msg: e && e.target ? e.target.value : '' }),
      msg: s.msg, price, label,
      ovCls: 'mov' + (s.menu ? ' open' : ''), ovHidden: s.menu ? 'false' : 'true', navCls: 'nav' + (s.menu ? ' open' : ''), menuExp: s.menu ? 'true' : 'false',
      toggleMenu: () => this.setState({ menu: !s.menu })
    };
  }
}

DC.mount(Component);
