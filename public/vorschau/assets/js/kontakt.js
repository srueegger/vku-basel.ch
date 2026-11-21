class Component extends DCLogic {
  constructor(props) { super(props); this.state = { topic: 'wish', sent: false }; }
  renderVals() {
    const list = [
      ['wish', 'VKU Wunschdaten', 'Wunschdaten für einen VKU'],
      ['advice', 'Ich wünsche Beratung', 'Beratung'],
      ['voucher', 'Geschenk-Gutscheine', 'Frage zu Gutscheinen'],
      ['feedback', 'Lob / Reklamation', 'Lob oder Reklamation'],
      ['other', 'Etwas anderes', 'Deine Nachricht']
    ];
    const cur = list.find((x) => x[0] === this.state.topic);
    return {
      topics: list.map((x) => ({
        label: x[1], cls: 'ww ' + (x[0] === this.state.topic ? 'ww-orange' : 'ww-weiss'),
        pressed: x[0] === this.state.topic ? 'true' : 'false',
        pick: () => this.setState({ topic: x[0] })
      })),
      topicTitle: cur[2], isWish: this.state.topic === 'wish',
      req: this.state.topic !== 'wish',
      fieldHint: this.state.topic === 'wish' ? 'Alle Angaben sind freiwillig. Mit E-Mail oder Handynummer kann ich dich informieren, wenn ein passender Kurs ausgeschrieben wird.' : 'Felder mit * sind nötig, damit ich dir antworten kann.',
      nameLabel: this.state.topic === 'wish' ? 'Vor- und Nachname' : 'Vor- und Nachname *',
      mailLabel: this.state.topic === 'wish' ? 'E-Mail' : 'E-Mail *',
      telLabel: this.state.topic === 'wish' ? 'Handynummer' : 'Handynummer (freiwillig)',
      sent: this.state.sent, form: !this.state.sent,
      sentTitle: this.state.topic === 'wish' ? 'Danke für deine Wunschdaten.' : 'Danke, deine Nachricht ist angekommen.',
      sentText: this.state.topic === 'wish' ? 'Sie fliessen in die Planung künftiger Kurse ein. Eine Garantie für einen Kurs genau zu diesen Zeiten kann ich dir leider nicht geben.' : 'Die Antwort folgt so rasch wie möglich.',
      send: () => this.setState({ sent: true }),
      ovCls: 'mov' + (this.state.menu ? ' open' : ''), ovHidden: this.state.menu ? 'false' : 'true', navCls: 'nav' + (this.state.menu ? ' open' : ''), menuExp: this.state.menu ? 'true' : 'false',
      toggleMenu: () => this.setState({ menu: !this.state.menu })
    };
  }
}

DC.mount(Component);
