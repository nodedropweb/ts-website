import { DecoratorNode } from 'lexical';

export class VideoNode extends DecoratorNode {
  __id;
  __source; // 'youtube' or 'vimeo'

  static getType() {
    return 'video';
  }

  static clone(node) {
    return new VideoNode(node.__id, node.__source, node.__key);
  }

  constructor(id, source, key) {
    super(key);
    this.__id = id;
    this.__source = source;
  }

  createDOM(config, editor) {
    const div = document.createElement('div');
    div.style.display = 'block';
    div.style.position = 'relative';
    div.style.textAlign = 'center';
    div.style.margin = '10px 0';
    div.style.background = '#000';
    div.contentEditable = 'false';

    const img = document.createElement('img');
    img.style.maxWidth = '100%';
    img.style.maxHeight = '400px';
    img.style.display = 'inline-block';
    
    if (this.__source === 'youtube') {
      img.src = `https://img.youtube.com/vi/${this.__id}/hqdefault.jpg`;
    } else {
      img.src = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="560" height="315"><rect width="100%" height="100%" fill="%23222"/><text x="50%" y="50%" fill="%23fff" font-size="24" text-anchor="middle" dominant-baseline="middle">Vimeo Video</text></svg>';
      fetch(`https://vimeo.com/api/v2/video/${this.__id}.json`)
        .then(res => res.json())
        .then(data => {
          if (data && data[0] && data[0].thumbnail_large) {
            img.src = data[0].thumbnail_large;
          }
        }).catch(e => console.error("Vimeo thumbnail error", e));
    }

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
        const node = this.getLatest();
        if (node) {
          node.remove();
        }
      });
    };
    
    // Play overlay indicator
    const playInd = document.createElement('div');
    playInd.style.position = 'absolute';
    playInd.style.top = '50%';
    playInd.style.left = '50%';
    playInd.style.transform = 'translate(-50%, -50%)';
    playInd.style.fontSize = '48px';
    playInd.style.color = 'rgba(255, 255, 255, 0.8)';
    playInd.style.pointerEvents = 'none';
    playInd.innerHTML = '<i class="far fa-play-circle"></i>';

    const theme = config.theme;
    const className = theme.video;
    if (className !== undefined) {
      div.className = className;
    }
    
    div.appendChild(img);
    div.appendChild(playInd);
    div.appendChild(overlay);
    return div;
  }

  updateDOM() {
    return false;
  }

  static importDOM() {
    return {
      iframe: (node) => ({
        conversion: convertIframeElement,
        priority: 0,
      }),
    };
  }

  exportDOM() {
    const element = document.createElement('iframe');
    const src = this.__source === 'youtube' 
        ? `https://www.youtube-nocookie.com/embed/${this.__id}`
        : `https://player.vimeo.com/video/${this.__id}`;
    
    // Support for Klaro consent manager
    element.setAttribute('data-name', this.__source);
    element.setAttribute('data-src', src);
    element.setAttribute('width', '560');
    element.setAttribute('height', '315');
    element.setAttribute('frameborder', '0');
    element.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture');
    element.setAttribute('allowfullscreen', 'true');
    return { element };
  }

  decorate() {
    const iframe = document.createElement('iframe');
    const src = this.__source === 'youtube' 
        ? `https://www.youtube-nocookie.com/embed/${this.__id}`
        : `https://player.vimeo.com/video/${this.__id}`;
    
    // In the editor itself, we'll just render it with src so it shows up
    iframe.src = src;
    iframe.width = '560';
    iframe.height = '315';
    iframe.frameBorder = '0';
    iframe.allowFullscreen = true;
    return iframe;
  }
}

function convertIframeElement(domNode) {
  if (domNode instanceof HTMLIFrameElement) {
    const dataName = domNode.getAttribute('data-name');
    const dataSrc = domNode.getAttribute('data-src') || domNode.getAttribute('src') || '';
    
    let source = '';
    let id = '';

    if (dataName === 'youtube' || dataSrc.includes('youtube') || dataSrc.includes('youtu.be')) {
      source = 'youtube';
      const match = dataSrc.match(/embed\/([^?]+)/);
      if (match) id = match[1];
    } else if (dataName === 'vimeo' || dataSrc.includes('vimeo')) {
      source = 'vimeo';
      const match = dataSrc.match(/video\/([^?]+)/);
      if (match) id = match[1];
    }

    if (id) {
      return { node: $createVideoNode(id, source) };
    }
  }
  return null;
}

export function $createVideoNode(id, source) {
  return new VideoNode(id, source);
}

export function $isVideoNode(node) {
  return node instanceof VideoNode;
}
