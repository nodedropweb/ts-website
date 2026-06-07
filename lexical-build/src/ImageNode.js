import { DecoratorNode } from 'lexical';

export class ImageNode extends DecoratorNode {
  __src;
  __altText;

  static getType() {
    return 'image';
  }

  static clone(node) {
    return new ImageNode(node.__src, node.__altText, node.__key);
  }

  constructor(src, altText, key) {
    super(key);
    this.__src = src;
    this.__altText = altText;
  }

  createDOM(config, editor) {
    const div = document.createElement('div');
    div.style.display = 'block';
    div.style.position = 'relative';
    div.style.textAlign = 'center';
    div.style.margin = '10px 0';
    div.contentEditable = 'false';

    const img = document.createElement('img');
    img.src = this.__src;
    img.alt = this.__altText;
    img.style.maxWidth = '100%';
    img.style.display = 'inline-block';
    
    const theme = config.theme;
    const className = theme.image;
    if (className !== undefined) {
      img.className = className;
    }

    // Convenient delete overlay
    const overlay = document.createElement('div');
    overlay.style.position = 'absolute';
    overlay.style.top = '0';
    overlay.style.right = '0';
    overlay.style.padding = '5px 10px';
    overlay.style.background = 'rgba(220, 53, 69, 0.8)';
    overlay.style.color = '#fff';
    overlay.style.borderRadius = '4px';
    overlay.style.cursor = 'pointer';
    overlay.innerHTML = '<i class="fas fa-trash"></i> Entfernen';
    overlay.onclick = () => {
      editor.update(() => {
        // Must import or access $getNodeByKey. Since we are inside the node instance:
        const node = this.getLatest();
        if (node) {
          node.remove();
        }
      });
    };

    div.appendChild(img);
    div.appendChild(overlay);
    return div;
  }

  updateDOM() {
    return false;
  }

  static importDOM() {
    return {
      img: (node) => ({
        conversion: convertImageElement,
        priority: 0,
      }),
    };
  }

  exportDOM() {
    const element = document.createElement('img');
    element.setAttribute('src', this.__src);
    element.setAttribute('alt', this.__altText);
    element.style.maxWidth = '100%';
    return { element };
  }

  decorate() {
    const img = document.createElement('img');
    img.src = this.__src;
    img.alt = this.__altText;
    img.style.maxWidth = '100%';
    img.style.display = 'block';
    return img;
  }
}

function convertImageElement(domNode) {
  if (domNode instanceof HTMLImageElement) {
    const { alt, src } = domNode;
    const node = $createImageNode(src, alt);
    return { node };
  }
  return null;
}

export function $createImageNode(src, altText) {
  return new ImageNode(src, altText);
}

export function $isImageNode(node) {
  return node instanceof ImageNode;
}
