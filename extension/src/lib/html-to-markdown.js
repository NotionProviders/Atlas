/*
 * A small, dependency-free HTML → Markdown converter, tuned for the markup
 * that rich-text editors like Loop's "scriptor" canvas emit (headings, lists,
 * tables, links, basic inline formatting). It runs in the content script on a
 * DOM element; the Atlas backend does the final tidy (MarkdownCleaner), so this
 * only needs to get the structure right — not be pixel-perfect.
 *
 * Exposes: window.AtlasHtmlToMarkdown(element) -> string
 */
(function () {
  const SKIP_TAGS = new Set(['SCRIPT', 'STYLE', 'NOSCRIPT', 'SVG', 'BUTTON', 'INPUT', 'TEXTAREA']);

  function isBlock(el) {
    return /^(DIV|P|SECTION|ARTICLE|HEADER|FOOTER|UL|OL|LI|BLOCKQUOTE|TABLE|TR|H[1-6]|HR|PRE|FIGURE)$/.test(el.tagName);
  }

  // Collapse runs of whitespace in a text node to single spaces.
  function textOf(node) {
    return node.nodeValue.replace(/\s+/g, ' ');
  }

  function inline(node) {
    let out = '';
    node.childNodes.forEach((child) => {
      if (child.nodeType === Node.TEXT_NODE) {
        out += textOf(child);
      } else if (child.nodeType === Node.ELEMENT_NODE) {
        out += inlineElement(child);
      }
    });
    return out;
  }

  function inlineElement(el) {
    if (SKIP_TAGS.has(el.tagName)) return '';
    const inner = inline(el).trim();
    switch (el.tagName) {
      case 'BR': return '\n';
      case 'STRONG': case 'B': return inner ? `**${inner}**` : '';
      case 'EM': case 'I': return inner ? `*${inner}*` : '';
      case 'S': case 'DEL': case 'STRIKE': return inner ? `~~${inner}~~` : '';
      case 'CODE': return inner ? '`' + inner + '`' : '';
      case 'A': {
        const href = el.getAttribute('href') || '';
        if (!href || href.startsWith('javascript:')) return inner;
        return inner ? `[${inner}](${href})` : `${href}`;
      }
      case 'IMG': return '';
      default: return inline(el);
    }
  }

  function listToMarkdown(el, ordered, depth) {
    const indent = '  '.repeat(depth);
    const lines = [];
    let i = 1;
    Array.from(el.children).forEach((li) => {
      if (li.tagName !== 'LI') return;
      // Split a list item into its own inline content vs. any nested lists.
      const nested = [];
      const clone = li.cloneNode(true);
      Array.from(clone.querySelectorAll(':scope > ul, :scope > ol')).forEach((sub) => {
        nested.push(sub);
        sub.remove();
      });
      const marker = ordered ? `${i}.` : '-';
      const text = inline(clone).trim().replace(/\n+/g, ' ');
      lines.push(`${indent}${marker} ${text}`.replace(/\s+$/, ''));
      nested.forEach((sub) => {
        lines.push(listToMarkdown(sub, sub.tagName === 'OL', depth + 1));
      });
      i++;
    });
    return lines.join('\n');
  }

  function tableToMarkdown(table) {
    const rows = Array.from(table.querySelectorAll('tr'));
    if (!rows.length) return '';
    const parsed = rows.map((tr) =>
      Array.from(tr.querySelectorAll('th,td')).map((c) => inline(c).trim().replace(/\|/g, '\\|').replace(/\n+/g, ' '))
    );
    const cols = Math.max(...parsed.map((r) => r.length));
    parsed.forEach((r) => { while (r.length < cols) r.push(''); });
    const out = [];
    out.push('| ' + parsed[0].join(' | ') + ' |');
    out.push('| ' + Array(cols).fill('---').join(' | ') + ' |');
    parsed.slice(1).forEach((r) => out.push('| ' + r.join(' | ') + ' |'));
    return out.join('\n');
  }

  function block(el) {
    if (SKIP_TAGS.has(el.tagName)) return '';

    switch (el.tagName) {
      case 'H1': case 'H2': case 'H3': case 'H4': case 'H5': case 'H6': {
        const n = Number(el.tagName[1]);
        const t = inline(el).trim();
        return t ? `${'#'.repeat(n)} ${t}` : '';
      }
      case 'HR': return '---';
      case 'UL': return listToMarkdown(el, false, 0);
      case 'OL': return listToMarkdown(el, true, 0);
      case 'TABLE': return tableToMarkdown(el);
      case 'PRE': {
        const code = el.textContent.replace(/\s+$/, '');
        return '```\n' + code + '\n```';
      }
      case 'BLOCKQUOTE': {
        const inner = children(el).trim();
        return inner.split('\n').map((l) => (l ? `> ${l}` : '>')).join('\n');
      }
      case 'FIGURE': case 'IMG': return '';
      default:
        return children(el);
    }
  }

  // Walk children, emitting block elements as \n\n-separated chunks and
  // gathering runs of inline content into paragraphs.
  function children(el) {
    const chunks = [];
    let inlineBuf = '';

    const flush = () => {
      const t = inlineBuf.replace(/[ \t]+\n/g, '\n').trim();
      if (t) chunks.push(t);
      inlineBuf = '';
    };

    el.childNodes.forEach((child) => {
      if (child.nodeType === Node.TEXT_NODE) {
        inlineBuf += textOf(child);
      } else if (child.nodeType === Node.ELEMENT_NODE) {
        if (isBlock(child)) {
          flush();
          const b = block(child);
          if (b.trim()) chunks.push(b);
        } else {
          inlineBuf += inlineElement(child);
        }
      }
    });
    flush();

    return chunks.join('\n\n');
  }

  window.AtlasHtmlToMarkdown = function (root) {
    if (!root) return '';
    const md = children(root);
    return md.replace(/\n{3,}/g, '\n\n').trim();
  };
})();
