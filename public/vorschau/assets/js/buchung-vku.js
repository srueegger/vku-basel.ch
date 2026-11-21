class Component extends DCLogic {
  constructor(props) {
    super(props);
    this.state = { step: 1, theo: false, bundle: 'none', forOther: false, pay: 'twint', code: '', voucher: false, codeMsg: '' };
  }
  renderVals() {
    const s = this.state;
    const chf = (r) => (r / 100).toFixed(2);
    const lines = [];
    if (s.bundle === 'doris') lines.push({ name: 'VKU + 5 Fahrstunden mit Doris', l: 56495, p: 52495 });
    else if (s.bundle === 'gaby') lines.push({ name: 'VKU + 5 Fahrstunden mit Gaby', l: 69895, p: 47995 });
    else lines.push({ name: 'VKU', l: 11495, p: 11495 });
    if (s.theo) lines.push({ name: 'Vorbereitung Theorie', l: 2525, p: 1305 });
    if (s.voucher) lines.push({ name: 'Gutschein GS2611-K7QF-3', l: 0, p: -5000 });
    const total = Math.max(0, lines.reduce((a, x) => a + x.p, 0));
    const set = (o) => () => this.setState(o);
    const cls = (on) => 'opt' + (on ? ' on' : '');
    const stc = (n) => 'step' + (s.step >= n ? ' on' : '');
    const titles = { 1: 'Deine Auswahl', 2: 'Deine Angaben', 3: 'Bezahlen', 4: 'Bestätigt' };
    const payHint = {
      card: 'Dein Platz ist 15 Minuten reserviert, bis die Zahlung abgeschlossen ist.',
      twint: 'Dein Platz ist 15 Minuten reserviert, bis die Zahlung mit TWINT abgeschlossen ist.',
      invoice: 'Dein Platz ist sofort fix, die Rechnung ist bis Kursbeginn zahlbar.',
      cash: 'Dein Platz ist sofort fix, bitte bring den Betrag am 1. Kurstag bar mit.'
    }[s.pay];
    const payBtn = { card: 'Jetzt mit Karte bezahlen', twint: 'Mit TWINT bezahlen', invoice: 'Verbindlich buchen', cash: 'Verbindlich buchen' }[s.pay];
    const doneHint = {
      card: 'Die Quittung und alle Details sind unterwegs an deine E-Mail-Adresse.',
      twint: 'Die Quittung und alle Details sind unterwegs an deine E-Mail-Adresse.',
      invoice: 'Die Rechnung als PDF ist unterwegs an deine E-Mail-Adresse. Zahlbar bis Kursbeginn am Mo 04.01.27, 15:00 Uhr.',
      cash: 'Die Bestätigung ist unterwegs an deine E-Mail-Adresse. Bitte bezahle am Mo 04.01.27 vor Kursbeginn bar vor Ort.'
    }[s.pay];
    return {
      title: titles[s.step],
      s1: s.step === 1, s2: s.step === 2, s3: s.step === 3, s4: s.step === 4,
      st1: stc(1), st2: stc(2), st3: stc(3), st4: stc(4),
      go1: set({ step: 1 }), go2: set({ step: 2 }), go3: set({ step: 3 }), go4: set({ step: 4 }),
      theo: s.theo, theoCls: cls(s.theo), toggleTheo: () => this.setState({ theo: !s.theo }),
      bNone: s.bundle === 'none', bDoris: s.bundle === 'doris', bGaby: s.bundle === 'gaby',
      bNoneCls: cls(s.bundle === 'none'), bDorisCls: cls(s.bundle === 'doris'), bGabyCls: cls(s.bundle === 'gaby'),
      pickNone: set({ bundle: 'none' }), pickDoris: set({ bundle: 'doris' }), pickGaby: set({ bundle: 'gaby' }),
      hasBundle: s.bundle !== 'none',
      lines: lines.map((x) => ({ name: x.name, list: x.l ? chf(x.l) : '', disc: x.l && x.l !== x.p ? chf(x.l - x.p) : '–', price: chf(x.p) })),
      total: chf(total),
      code: s.code,
      setCode: (e) => this.setState({ code: e && e.target ? e.target.value : '' }),
      applyCode: () => {
        const ok = s.code.trim().toUpperCase() === 'GS2611-K7QF-3';
        this.setState({ voucher: ok, codeMsg: ok ? 'Gutschein über CHF 50.00 eingelöst.' : 'Dieser Code ist nicht gültig. Bitte prüfe die Schreibweise.' });
      },
      codeMsg: s.codeMsg,
      forOther: s.forOther, otherCls: cls(s.forOther), toggleOther: () => this.setState({ forOther: !s.forOther }),
      emailLabel: s.forOther ? 'E-Mail (freiwillig)' : 'E-Mail (für alle Bestätigungen)',
      pCard: s.pay === 'card', pTwint: s.pay === 'twint', pInv: s.pay === 'invoice', pCash: s.pay === 'cash',
      pCardCls: cls(s.pay === 'card'), pTwintCls: cls(s.pay === 'twint'), pInvCls: cls(s.pay === 'invoice'), pCashCls: cls(s.pay === 'cash'),
      payCard: set({ pay: 'card' }), payTwint: set({ pay: 'twint' }), payInv: set({ pay: 'invoice' }), payCash: set({ pay: 'cash' }),
      payHint, payBtn, doneHint,
      ics: (() => {
        const ev = (d, a, b, n) => ['BEGIN:VEVENT', 'UID:vk270101-' + n + '@vku-basel.ch', 'DTSTAMP:20261103T120000Z',
          'DTSTART;TZID=Europe/Zurich:' + d.replace(/-/g, '') + 'T' + a.replace(':', '') + '00',
          'DTEND;TZID=Europe/Zurich:' + d.replace(/-/g, '') + 'T' + b.replace(':', '') + '00',
          'SUMMARY:VKU Teil ' + n + ' (VK270101)', 'LOCATION:Burgfelderstrasse 11\\, 4055 Basel', 'END:VEVENT'].join('\r\n');
        const cal = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//VKU-Basel//Buchung//DE',
          ev('2027-01-04', '15:00', '17:00', 1), ev('2027-01-11', '15:00', '17:00', 2), ev('2027-01-18', '16:00', '18:00', 3), ev('2027-01-25', '15:00', '17:00', 4),
          'END:VCALENDAR'].join('\r\n');
        return 'data:text/calendar;charset=utf-8,' + encodeURIComponent(cal);
      })()
    };
  }
}

DC.mount(Component);
