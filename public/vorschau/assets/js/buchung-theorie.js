class Component extends DCLogic {
  constructor(props) { super(props); this.state = { step: 1, vku: false, forOther: false, pay: 'twint' }; }
  renderVals() {
    const s = this.state;
    const chf = (r) => (r / 100).toFixed(2);
    const lines = [{ name: 'Vorbereitung Theorie', l: 2525, p: s.vku ? 1305 : 2525 }];
    if (s.vku) lines.push({ name: 'VKU', l: 11495, p: 11495 });
    const total = lines.reduce((a, x) => a + x.p, 0);
    const set = (o) => () => this.setState(o);
    const cls = (on) => 'opt' + (on ? ' on' : '');
    const stc = (n) => 'step' + (s.step >= n ? ' on' : '');
    const titles = { 1: 'Deine Auswahl', 2: 'Deine Angaben', 3: 'Bezahlen', 4: 'Bestätigt' };
    return {
      title: titles[s.step],
      s1: s.step === 1, s2: s.step === 2, s3: s.step === 3, s4: s.step === 4,
      st1: stc(1), st2: stc(2), st3: stc(3), st4: stc(4),
      go1: set({ step: 1 }), go2: set({ step: 2 }), go3: set({ step: 3 }), go4: set({ step: 4 }),
      vku: s.vku, vkuCls: cls(s.vku), toggleVku: () => this.setState({ vku: !s.vku }),
      lines: lines.map((x) => ({ name: x.name, list: chf(x.l), disc: x.l !== x.p ? chf(x.l - x.p) : '–', price: chf(x.p) })),
      total: chf(total),
      forOther: s.forOther, otherCls: cls(s.forOther), toggleOther: () => this.setState({ forOther: !s.forOther }),
      emailLabel: s.forOther ? 'E-Mail (freiwillig)' : 'E-Mail (für alle Bestätigungen)',
      pCard: s.pay === 'card', pTwint: s.pay === 'twint', pInv: s.pay === 'invoice', pCash: s.pay === 'cash',
      pCardCls: cls(s.pay === 'card'), pTwintCls: cls(s.pay === 'twint'), pInvCls: cls(s.pay === 'invoice'), pCashCls: cls(s.pay === 'cash'),
      payCard: set({ pay: 'card' }), payTwint: set({ pay: 'twint' }), payInv: set({ pay: 'invoice' }), payCash: set({ pay: 'cash' }),
      payBtn: { card: 'Jetzt mit Karte bezahlen', twint: 'Mit TWINT bezahlen', invoice: 'Verbindlich buchen', cash: 'Verbindlich buchen' }[s.pay],
      ics: (() => {
        const ev = (uid, d, a, b, title) => ['BEGIN:VEVENT', 'UID:' + uid + '@vku-basel.ch', 'DTSTAMP:20261103T120000Z',
          'DTSTART;TZID=Europe/Zurich:' + d.replace(/-/g, '') + 'T' + a.replace(':', '') + '00',
          'DTEND;TZID=Europe/Zurich:' + d.replace(/-/g, '') + 'T' + b.replace(':', '') + '00',
          'SUMMARY:' + title, 'LOCATION:Burgfelderstrasse 11\\, 4055 Basel', 'END:VEVENT'].join('\r\n');
        const evs = [ev('tv270101', '2027-01-13', '19:00', '21:00', 'Theorievorbereitung (TV270101)')];
        if (s.vku) [['2027-01-04', '15:00', '17:00'], ['2027-01-11', '15:00', '17:00'], ['2027-01-18', '16:00', '18:00'], ['2027-01-25', '15:00', '17:00']]
          .forEach((t, i) => evs.push(ev('vk270101-' + (i + 1), t[0], t[1], t[2], 'VKU Teil ' + (i + 1) + ' (VK270101)')));
        const cal = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//VKU-Basel//Buchung//DE'].concat(evs, ['END:VCALENDAR']).join('\r\n');
        return 'data:text/calendar;charset=utf-8,' + encodeURIComponent(cal);
      })()
    };
  }
}

DC.mount(Component);
