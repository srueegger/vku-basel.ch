/*
 * dc-lite.js – kleiner Renderer für die VKU-Basel Prototyp-Seiten.
 * Liest das <template id="dc-tpl">, füllt {{Platzhalter}}, <sc-for> und <sc-if>
 * mit den Werten aus Component.renderVals() und aktualisiert das DOM bei setState().
 * Bestehende Elemente werden wiederverwendet, Eingaben bleiben so erhalten.
 */
(function () {
  'use strict';

  var pending = false;
  var app = null;

  function schedule() {
    if (pending) return;
    pending = true;
    Promise.resolve().then(function () { pending = false; if (app) app.render(); });
  }

  function DCLogic(props) {
    this.props = props || {};
    this.state = {};
  }
  DCLogic.prototype.setState = function (patch, cb) {
    var next = typeof patch === 'function' ? patch(this.state, this.props) : patch;
    this.state = Object.assign({}, this.state, next || {});
    schedule();
    if (typeof cb === 'function') Promise.resolve().then(cb);
  };
  DCLogic.prototype.forceUpdate = function () { schedule(); };
  window.DCLogic = DCLogic;

  var HOLE = /\{\{\s*([^}]+?)\s*\}\}/g;
  var WHOLE = /^\s*\{\{\s*([^}]+?)\s*\}\}\s*$/;

  function lookup(scope, path) {
    path = path.trim();
    if (path === 'true') return true;
    if (path === 'false') return false;
    if (path === 'null') return null;
    if (/^-?\d+(\.\d+)?$/.test(path)) return Number(path);
    if (/^(['"]).*\1$/.test(path)) return path.slice(1, -1);
    var parts = path.split('.');
    var v = scope[parts[0]];
    for (var i = 1; i < parts.length; i++) {
      if (v == null) return undefined;
      v = v[parts[i]];
    }
    return v;
  }

  function evalStr(str, scope) {
    var m = str.match(WHOLE);
    if (m) return lookup(scope, m[1]);
    if (str.indexOf('{{') < 0) return str;
    return str.replace(HOLE, function (_, p) {
      var v = lookup(scope, p);
      return v == null || v === false ? '' : String(v);
    });
  }

  var tid = 0;
  function tag(node) {
    node.__tid = ++tid;
    for (var c = node.firstChild; c; c = c.nextSibling) tag(c);
  }

  var PROPS = { value: 1, checked: 1, disabled: 1, selected: 1 };
  var TEXTY = { text: 1, email: 1, tel: 1, number: 1, search: 1, url: 1, password: 1, date: 1, '': 1 };

  function eventType(attr, el) {
    var t = attr.slice(2);
    if (t === 'change') {
      var tagName = el.tagName;
      if (tagName === 'TEXTAREA') return 'input';
      if (tagName === 'INPUT' && TEXTY[(el.getAttribute('type') || '').toLowerCase()]) return 'input';
    }
    return t;
  }

  function build(nodes, scope, prefix, out) {
    for (var i = 0; i < nodes.length; i++) {
      var n = nodes[i];
      var key = prefix + '/' + n.__tid;
      if (n.nodeType === 3) {
        out.push({ text: evalStr(n.nodeValue, scope), key: key });
        continue;
      }
      if (n.nodeType !== 1) continue;
      var name = n.tagName.toLowerCase();
      if (name === 'sc-for') {
        var list = evalStr(n.getAttribute('list') || '', scope) || [];
        var as = n.getAttribute('as') || 'item';
        for (var j = 0; j < list.length; j++) {
          var s = Object.create(scope);
          s[as] = list[j];
          s.$index = j;
          build(n.childNodes, s, key + '#' + j, out);
        }
        continue;
      }
      if (name === 'sc-if') {
        if (evalStr(n.getAttribute('value') || '', scope)) build(n.childNodes, scope, key, out);
        continue;
      }
      var v = { tag: name, key: key, attrs: {}, props: {}, events: {}, children: [], src: n };
      for (var a = 0; a < n.attributes.length; a++) {
        var at = n.attributes[a];
        var an = at.name;
        if (an.indexOf('hint-') === 0) continue;
        if (an.length > 2 && an.slice(0, 2) === 'on') {
          var fn = evalStr(at.value, scope);
          if (typeof fn === 'function') v.events[eventType(an, n)] = fn;
          continue;
        }
        var val = evalStr(at.value, scope);
        if (PROPS[an]) { v.props[an] = val; if (an !== 'value' && val && val !== 'false') v.attrs[an] = ''; continue; }
        if (val === undefined || val === null || val === false) continue;
        v.attrs[an] = String(val);
      }
      if (name === 'textarea' && n.childNodes.length) {
        v.children = [];
      } else {
        build(n.childNodes, scope, key, v.children);
      }
      out.push(v);
    }
    return out;
  }

  function create(v) {
    if (v.text !== undefined) {
      var t = document.createTextNode(v.text);
      t.__k = v.key;
      return t;
    }
    var el = document.createElement(v.tag);
    el.__k = v.key;
    update(el, v, true);
    return el;
  }

  function update(el, v, fresh) {
    var old = el.__attrs || {};
    for (var k in old) if (!(k in v.attrs)) el.removeAttribute(k);
    for (var a in v.attrs) if (old[a] !== v.attrs[a] || fresh) el.setAttribute(a, v.attrs[a]);
    el.__attrs = v.attrs;
    if ('value' in v.props) {
      var nv = v.props.value == null ? '' : String(v.props.value);
      if (el.value !== nv) el.value = nv;
    }
    if ('checked' in v.props) el.checked = !!v.props.checked && v.props.checked !== 'false';
    if ('disabled' in v.props) el.disabled = !!v.props.disabled && v.props.disabled !== 'false';
    if ('selected' in v.props) el.selected = !!v.props.selected;
    el.__h = v.events;
    el.__bound = el.__bound || {};
    for (var ev in v.events) {
      if (!el.__bound[ev]) {
        el.__bound[ev] = true;
        (function (type) {
          el.addEventListener(type, function (e) {
            var f = el.__h && el.__h[type];
            if (f) f(e);
          });
        })(ev);
      }
    }
    if (v.tag === 'textarea' && fresh && v.src.textContent) el.value = v.src.textContent;
    patch(el, v.children);
  }

  function patch(parent, vnodes) {
    var existing = {};
    var kids = Array.prototype.slice.call(parent.childNodes);
    for (var i = 0; i < kids.length; i++) if (kids[i].__k) existing[kids[i].__k] = kids[i];
    var used = {};
    var ref = parent.firstChild;
    for (var j = 0; j < vnodes.length; j++) {
      var v = vnodes[j];
      var node = existing[v.key];
      if (node && !used[v.key] && ((v.text !== undefined && node.nodeType === 3) || (v.tag && node.nodeType === 1 && node.tagName.toLowerCase() === v.tag))) {
        if (v.text !== undefined) { if (node.nodeValue !== v.text) node.nodeValue = v.text; }
        else update(node, v, false);
      } else {
        node = create(v);
      }
      used[v.key] = true;
      if (node !== ref) parent.insertBefore(node, ref);
      else ref = ref.nextSibling;
    }
    while (ref) { var nx = ref.nextSibling; parent.removeChild(ref); ref = nx; }
  }

  window.DC = {
    mount: function (Component, rootId) {
      var tpl = document.getElementById('dc-tpl');
      var root = document.getElementById(rootId || 'dc-root');
      var content = tpl.content;
      tag(content);
      var inst = new Component({});
      if (!inst.state) inst.state = {};
      app = {
        render: function () {
          var vals = inst.renderVals() || {};
          patch(root, build(content.childNodes, vals, '', []));
        }
      };
      app.render();
      if (typeof inst.componentDidMount === 'function') inst.componentDidMount();
      if (location.hash) {
        var target = document.getElementById(location.hash.slice(1));
        if (target) target.scrollIntoView();
      }
    }
  };
})();
