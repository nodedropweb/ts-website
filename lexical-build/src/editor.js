import { createEditor, $getRoot, $getSelection, $isRangeSelection, COMMAND_PRIORITY_LOW, FORMAT_TEXT_COMMAND, UNDO_COMMAND, REDO_COMMAND } from 'lexical';
import { registerRichText } from '@lexical/rich-text';
import { registerHistory } from '@lexical/history';
import { HeadingNode, QuoteNode, registerRichText as _ } from '@lexical/rich-text';
import { ListNode, ListItemNode, INSERT_UNORDERED_LIST_COMMAND, INSERT_ORDERED_LIST_COMMAND } from '@lexical/list';
import { LinkNode, AutoLinkNode, TOGGLE_LINK_COMMAND } from '@lexical/link';
import { $generateHtmlFromNodes, $generateNodesFromDOM } from '@lexical/html';
import { $createHeadingNode } from '@lexical/rich-text';
import { $setBlocksType } from '@lexical/selection';

import { ImageNode, $createImageNode } from './ImageNode.js';
import { VideoNode, $createVideoNode } from './VideoNode.js';

const theme = {
  text: {
    bold: 'lx-bold',
    italic: 'lx-italic',
    underline: 'lx-underline',
    strikethrough: 'lx-strikethrough',
    code: 'lx-code'
  },
  heading: {
    h1: 'lx-h1',
    h2: 'lx-h2',
    h3: 'lx-h3'
  },
  list: {
    ul: 'lx-ul',
    ol: 'lx-ol',
    listitem: 'lx-listitem'
  },
  link: 'lx-link',
  quote: 'lx-quote',
  paragraph: 'lx-paragraph'
};

function initEditor(containerId, hiddenInputId, initialHtml = "") {
  const container = document.getElementById(containerId);
  const hiddenInput = document.getElementById(hiddenInputId);
  if (!container || !hiddenInput) return null;

  const innerDiv = document.createElement("div");
  innerDiv.className = "lexical-editor-inner";
  innerDiv.contentEditable = "true";
  innerDiv.spellcheck = true;
  container.appendChild(innerDiv);

  const editor = createEditor({
    namespace: "TSWebsiteAdmin",
    theme: theme,
    nodes: [
      HeadingNode,
      ListNode,
      ListItemNode,
      QuoteNode,
      LinkNode,
      AutoLinkNode,
      ImageNode,
      VideoNode
    ],
    onError: error => console.error("Lexical error:", error)
  });

  editor.setRootElement(innerDiv);

  // Register basic features
  registerRichText(editor);
  
  // Try to use registerHistory if available, otherwise it's fine
  try {
      // Basic history
      const { createEmptyHistoryState, registerHistory } = require('@lexical/history');
      registerHistory(editor, createEmptyHistoryState(), 1000);
  } catch (e) {}

  if (initialHtml && initialHtml.trim()) {
    let cleanHtml = initialHtml.replace(/<!--[\s\S]*?-->/g, "");
    if (!/<(p|div|h[1-6]|ul|ol|li|blockquote)\b/i.test(cleanHtml)) {
      cleanHtml = cleanHtml.split(/<br\s*\/?>/i)
        .map(u => u.trim())
        .filter(u => u.length > 0)
        .map(u => `<p>${u}</p>`)
        .join("");
    }
    editor.update(() => {
      try {
        const parser = new DOMParser();
        const dom = parser.parseFromString(cleanHtml, "text/html");
        const nodes = $generateNodesFromDOM(editor, dom);
        const root = $getRoot();
        root.clear();
        root.append(...nodes);
      } catch (err) {
        console.warn("Lexical: HTML import failed", err);
      }
    });
  }

  editor.registerUpdateListener(() => {
    editor.read(() => {
      hiddenInput.value = $generateHtmlFromNodes(editor, null);
    });
  });

  const form = hiddenInput.closest("form");
  if (form) {
    form.addEventListener("submit", () => {
      editor.read(() => {
        hiddenInput.value = $generateHtmlFromNodes(editor, null);
      });
    }, { capture: true });
  }

  return editor;
}

function dispatchTextFormat(editor, format) {
  editor.dispatchCommand(FORMAT_TEXT_COMMAND, format);
}

function toggleHeading(editor, headingTag) {
  editor.update(() => {
    const selection = $getSelection();
    if ($isRangeSelection(selection)) {
      $setBlocksType(selection, () => $createHeadingNode(headingTag));
    }
  });
}

function promptLink(editor) {
  const url = prompt("URL eingeben (leer = Link entfernen):");
  if (url !== null) {
    editor.dispatchCommand(TOGGLE_LINK_COMMAND, url.trim() || null);
  }
}

function insertImage(editor) {
  // Trigger file selection for image upload
  const input = document.createElement('input');
  input.type = 'file';
  input.accept = 'image/*';
  input.onchange = async (e) => {
    const file = e.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('image', file);

    try {
      const response = await fetch('_upload_image.php', {
        method: 'POST',
        body: formData
      });
      const data = await response.json();
      if (data.success) {
        editor.update(() => {
          const imageNode = $createImageNode(data.url, file.name);
          const selection = $getSelection();
          if ($isRangeSelection(selection)) {
            selection.insertNodes([imageNode]);
          } else {
            $getRoot().append(imageNode);
          }
        });
      } else {
        alert("Upload fehlgeschlagen: " + (data.error || 'Unbekannter Fehler'));
      }
    } catch (err) {
      console.error(err);
      alert("Fehler beim Upload.");
    }
  };
  input.click();
}

function insertVideo(editor) {
  const url = prompt("YouTube oder Vimeo URL eingeben:");
  if (!url) return;

  let id = '';
  let source = '';

  if (url.includes('youtube.com/watch?v=')) {
    id = new URL(url).searchParams.get('v');
    source = 'youtube';
  } else if (url.includes('youtu.be/')) {
    id = url.split('youtu.be/')[1].split('?')[0];
    source = 'youtube';
  } else if (url.includes('vimeo.com/')) {
    id = url.split('vimeo.com/')[1].split('?')[0];
    source = 'vimeo';
  }

  if (id && source) {
    editor.update(() => {
      const videoNode = $createVideoNode(id, source);
      const selection = $getSelection();
      if ($isRangeSelection(selection)) {
        selection.insertNodes([videoNode]);
      } else {
        $getRoot().append(videoNode);
      }
    });
  } else {
    alert("Ungültige YouTube oder Vimeo URL.");
  }
}

function clearUploads() {
  if (confirm("Möchtest du wirklich alle hochgeladenen Bilder auf dem Server löschen? Diese Aktion kann nicht rückgängig gemacht werden!")) {
    fetch('clear_uploads.php', { method: 'POST' })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          alert(data.message || "Bilder erfolgreich gelöscht.");
        } else {
          alert("Fehler beim Löschen: " + data.error);
        }
      })
      .catch(err => {
        console.error(err);
        alert("Fehler beim Server-Request.");
      });
  }
}

function toggleSource(containerId, hiddenInputId, editor) {
  const container = document.getElementById(containerId);
  const cmContainer = document.getElementById("codemirror-container-" + containerId);
  const hiddenInput = document.getElementById(hiddenInputId);
  if (!container || !cmContainer || !hiddenInput) return;

  if (cmContainer.style.display === 'none') {
    // Switch to source mode
    editor.read(() => {
      const html = $generateHtmlFromNodes(editor, null);
      hiddenInput.value = html;
    });

    cmContainer.style.display = 'block';
    if (!cmContainer.cm) {
      cmContainer.cm = CodeMirror(cmContainer, {
        value: hiddenInput.value,
        mode: "htmlmixed",
        theme: "default",
        lineNumbers: true,
        lineWrapping: true
      });
      cmContainer.cm.on("change", () => {
        hiddenInput.value = cmContainer.cm.getValue();
      });
    } else {
      cmContainer.cm.setValue(hiddenInput.value);
    }
    // ensure codemirror layout is correct after show
    setTimeout(() => cmContainer.cm.refresh(), 10);
  } else {
    // Switch back to WYSIWYG
    const html = cmContainer.cm ? cmContainer.cm.getValue() : hiddenInput.value;
    
    // clean up HTML similar to initial load
    let cleanHtml = html.replace(/<!--[\s\S]*?-->/g, "");
    if (!/<(p|div|h[1-6]|ul|ol|li|blockquote)\b/i.test(cleanHtml)) {
      cleanHtml = cleanHtml.split(/<br\s*\/?>/i)
        .map(u => u.trim())
        .filter(u => u.length > 0)
        .map(u => `<p>${u}</p>`)
        .join("");
    }

    editor.update(() => {
      try {
        const parser = new DOMParser();
        const dom = parser.parseFromString(cleanHtml, "text/html");
        const nodes = $generateNodesFromDOM(editor, dom);
        const root = $getRoot();
        root.clear();
        root.append(...nodes);
      } catch (err) {
        console.warn("Lexical: HTML import failed", err);
      }
    });

    cmContainer.style.display = 'none';
  }
}

window.LexicalAdmin = {
  editors: {},
  inputs: {},
  init(containerId, hiddenInputId, initialHtml) {
    const editor = initEditor(containerId, hiddenInputId, initialHtml);
    if (editor) {
      this.editors[containerId] = editor;
      this.inputs[containerId] = hiddenInputId;
    }
    return editor;
  },
  bold(id) { dispatchTextFormat(this.editors[id], 'bold'); },
  italic(id) { dispatchTextFormat(this.editors[id], 'italic'); },
  underline(id) { dispatchTextFormat(this.editors[id], 'underline'); },
  strikethrough(id) { dispatchTextFormat(this.editors[id], 'strikethrough'); },
  code(id) { dispatchTextFormat(this.editors[id], 'code'); },
  h1(id) { toggleHeading(this.editors[id], 'h1'); },
  h2(id) { toggleHeading(this.editors[id], 'h2'); },
  h3(id) { toggleHeading(this.editors[id], 'h3'); },
  ul(id) { this.editors[id].dispatchCommand(INSERT_UNORDERED_LIST_COMMAND, undefined); },
  ol(id) { this.editors[id].dispatchCommand(INSERT_ORDERED_LIST_COMMAND, undefined); },
  link(id) { promptLink(this.editors[id]); },
  undo(id) { this.editors[id].dispatchCommand(UNDO_COMMAND, undefined); },
  redo(id) { this.editors[id].dispatchCommand(REDO_COMMAND, undefined); },
  image(id) { insertImage(this.editors[id]); },
  video(id) { insertVideo(this.editors[id]); },
  clearUploads() { clearUploads(); },
  toggleSource(id) { toggleSource(id, this.inputs[id], this.editors[id]); }
};
