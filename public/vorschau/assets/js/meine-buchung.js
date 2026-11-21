class Component extends DCLogic {
  constructor(props) { super(props); this.state = { pick: '', done: false }; }
  renderVals() {
    const s = this.state;
    const chip = (k) => 'chip ' + k;
    let parts = [
      { label: 'Teil 1', when: 'Sa 09.01.27, 13:00 bis 15:00 Uhr', status: 'besucht', chipCls: chip('c-ok') },
      { label: 'Teil 2', when: 'Sa 16.01.27, 13:00 bis 15:00 Uhr', status: 'verpasst', chipCls: chip('c-miss') },
      { label: 'Teil 3', when: 'Sa 23.01.27, 13:00 bis 15:00 Uhr', status: 'offen', chipCls: chip('c-open') },
      { label: 'Teil 4', when: 'Sa 30.01.27, 13:00 bis 15:00 Uhr', status: 'offen', chipCls: chip('c-open') }
    ];
    if (s.done) {
      const alt = s.pick === 'a'
        ? ['Mo 08.02.27, 19:00 bis 21:00 Uhr (VK270201)', 'Mo 15.02.27, 19:00 bis 21:00 Uhr (VK270201)', 'Mo 22.02.27, 18:00 bis 20:00 Uhr (VK270201)']
        : ['Mi 10.03.27, 19:00 bis 21:00 Uhr (VK270301)', 'Mi 17.03.27, 19:00 bis 21:00 Uhr (VK270301)', 'Mi 24.03.27, 19:00 bis 21:00 Uhr (VK270301)'];
      parts = [
        parts[0],
        { label: 'Teil 2', when: alt[0], status: 'nachholen', chipCls: chip('c-new') },
        { label: 'Teil 3', when: alt[1], status: 'verschoben', chipCls: chip('c-new') },
        { label: 'Teil 4', when: alt[2], status: 'verschoben', chipCls: chip('c-new') }
      ];
    }
    const cls = (on) => 'opt' + (on ? ' on' : '');
    return {
      courseCode: 'VK270104', parts,
      needsMakeup: !s.done, done: s.done,
      o1: s.pick === 'a', o3: s.pick === 'c', o1Cls: cls(s.pick === 'a'), o3Cls: cls(s.pick === 'c'),
      pick1: () => this.setState({ pick: 'a' }), pick3: () => this.setState({ pick: 'c' }),
      noPick: !s.pick,
      confirm: () => { if (s.pick) this.setState({ done: true }); }
    };
  }
}

DC.mount(Component);
