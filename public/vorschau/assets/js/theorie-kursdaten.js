class Component extends DCLogic {
  constructor(props) { super(props); this.state = { mode: 'theo' }; }
  renderVals() {
    const wd = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];
    const day = (iso) => {
      const d = new Date(iso + 'T12:00:00Z');
      return wd[d.getUTCDay()] + ' ' + iso.slice(8, 10) + '.' + iso.slice(5, 7) + '.' + iso.slice(2, 4);
    };
    const most = (arr) => { const c = {}; arr.forEach((v) => { c[v] = (c[v] || 0) + 1; }); return Object.keys(c).reduce((a, b) => (c[b] > c[a] ? b : a)); };
    const mk = (kind, code, free, parts) => {
      const r = free === 0, y = free > 0 && free < 7, g = free >= 7;
      const wds = parts.map((p) => String(new Date(p[0] + 'T12:00:00Z').getUTCDay()));
      const tms = parts.map((p) => p[1] + p[2]);
      const mw = most(wds), mt = most(tms);
      return {
        kind, code, free,
        href: kind === 'VKU-Kurs' ? 'buchung-vku.html' : 'buchung-theorie.html',
        btnCls: kind === 'VKU-Kurs' ? 'ww' : 'ww ww-weiss',
        r: r ? 'r' : '', y: y ? 'y' : '', g: g ? 'g' : '', txtCls: r ? 'at-r' : (y ? 'at-y' : 'at-g'),
        ampelLabel: r ? 'Ampel rot, ausgebucht' : (y ? 'Ampel orange' : 'Ampel grün'),
        open: free > 0, full: free === 0,
        parts: parts.map((p, i) => ({
          label: parts.length > 1 ? 'Teil ' + (i + 1) : 'Abend',
          day: day(p[0]), time: p[1] + ' bis ' + p[2] + ' Uhr', start: p[1], end: p[2],
          dayCls: wds[i] !== mw ? 'dev' : '', timeCls: tms[i] !== mt ? 'dev' : '',
          dayTip: wds[i] !== mw ? 'Achtung: abweichendes Datum' : undefined, dayTab: wds[i] !== mw ? '0' : undefined,
          timeTip: tms[i] !== mt ? 'Achtung: abweichende Zeit' : undefined, timeTab: tms[i] !== mt ? '0' : undefined
        }))
      };
    };
    const vku = [
      mk('VKU-Kurs', 'VK270101', 12, [['2027-01-04', '15:00', '17:00'], ['2027-01-11', '15:00', '17:00'], ['2027-01-18', '16:00', '18:00'], ['2027-01-25', '15:00', '17:00']]),
      mk('VKU-Kurs', 'VK270102', 6, [['2027-01-05', '19:00', '21:00'], ['2027-01-12', '19:00', '21:00'], ['2027-01-19', '19:00', '21:00'], ['2027-01-26', '19:00', '21:00']]),
      mk('VKU-Kurs', 'VK270103', 7, [['2027-01-07', '19:00', '21:00'], ['2027-01-14', '19:00', '21:00'], ['2027-01-21', '19:00', '21:00'], ['2027-01-29', '19:00', '21:00']]),
      mk('VKU-Kurs', 'VK270104', 6, [['2027-01-09', '13:00', '15:00'], ['2027-01-16', '13:00', '15:00'], ['2027-01-23', '13:00', '15:00'], ['2027-01-30', '13:00', '15:00']]),
      mk('VKU-Kurs', 'VK270201', 7, [['2027-02-01', '19:00', '21:00'], ['2027-02-08', '19:00', '21:00'], ['2027-02-15', '19:00', '21:00'], ['2027-02-22', '18:00', '20:00']]),
      mk('VKU-Kurs', 'VK270202', 0, [['2027-02-02', '19:00', '21:00'], ['2027-02-09', '19:00', '21:00'], ['2027-02-16', '19:00', '21:00'], ['2027-02-23', '19:00', '21:00']]),
      mk('VKU-Kurs', 'VK270301', 12, [['2027-03-03', '19:00', '21:00'], ['2027-03-10', '19:00', '21:00'], ['2027-03-17', '19:00', '21:00'], ['2027-03-24', '19:00', '21:00']])
    ];
    const theo = [
      mk('Theorievorbereitung', 'TV270101', 9, [['2027-01-13', '19:00', '21:00']]),
      mk('Theorievorbereitung', 'TV270102', 3, [['2027-01-27', '19:00', '21:00']]),
      mk('Theorievorbereitung', 'TV270201', 12, [['2027-02-17', '19:00', '21:00']])
    ];
    const isV = false;
    return {
      title: isV ? 'VKU-Kursdaten' : 'Theorie-Kursdaten',
      courses: isV ? vku : theo,
      showDevLegend: isV,
      pageCls: isV ? 'page vku' : 'page theo',
      ukCls: isV ? 'uk uk-gruen' : 'uk uk-weiss',
      ukArrow: isV ? 'assets/img/pfeil-gruen.png' : 'assets/img/pfeil-weiss.png',
      tabVku: 'tab' + (isV ? ' on' : ''),
      tabTheo: 'tab' + (isV ? '' : ' on'),
      showVku: () => this.setState({ mode: 'vku' }),
      showTheo: () => this.setState({ mode: 'theo' }),
      ovCls: 'mov' + (this.state.menu ? ' open' : ''), ovHidden: this.state.menu ? 'false' : 'true', navCls: 'nav' + (this.state.menu ? ' open' : ''), menuExp: this.state.menu ? 'true' : 'false',
      toggleMenu: () => this.setState({ menu: !this.state.menu })
    };
  }
}

DC.mount(Component);
