class Component extends DCLogic {
  renderVals() {
    const m = !!(this.state && this.state.menu);
    return { ovCls: 'mov' + (m ? ' open' : ''), ovHidden: m ? 'false' : 'true', navCls: 'nav' + (m ? ' open' : ''), menuExp: m ? 'true' : 'false', toggleMenu: () => this.setState({ menu: !m }) };
  }
}

DC.mount(Component);
