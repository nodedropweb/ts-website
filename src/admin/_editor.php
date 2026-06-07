<?php
/**
 * Renders a Lexical rich-text editor.
 *
 * @param string $fieldName   Name of the hidden textarea (submitted in form)
 * @param string $initialHtml Initial HTML content
 * @param string $editorId    Unique ID suffix (default: "main")
 */
function renderEditor(string $fieldName, string $initialHtml = '', string $editorId = 'main'): void {
    $containerId   = 'lexical-' . $editorId;
    $hiddenInputId = 'lexical-html-' . $editorId;
    $safeHtml      = htmlspecialchars($initialHtml, ENT_QUOTES);
    ?>
<div class="lexical-toolbar">
    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="LexicalAdmin.undo('<?= $containerId ?>')" title="Rückgängig"><i class="fas fa-undo"></i></button>
    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="LexicalAdmin.redo('<?= $containerId ?>')" title="Wiederholen"><i class="fas fa-redo"></i></button>
    <span class="toolbar-sep"></span>
    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="LexicalAdmin.bold('<?= $containerId ?>')" title="Fett"><b>B</b></button>
    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="LexicalAdmin.italic('<?= $containerId ?>')" title="Kursiv"><i>I</i></button>
    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="LexicalAdmin.underline('<?= $containerId ?>')" title="Unterstrichen"><u>U</u></button>
    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="LexicalAdmin.strikethrough('<?= $containerId ?>')" title="Durchgestrichen"><s>S</s></button>
    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="LexicalAdmin.code('<?= $containerId ?>')" title="Code"><i class="fas fa-code"></i></button>
    <span class="toolbar-sep"></span>
    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="LexicalAdmin.h1('<?= $containerId ?>')">H1</button>
    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="LexicalAdmin.h2('<?= $containerId ?>')">H2</button>
    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="LexicalAdmin.h3('<?= $containerId ?>')">H3</button>
    <span class="toolbar-sep"></span>
    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="LexicalAdmin.ul('<?= $containerId ?>')" title="Liste"><i class="fas fa-list-ul"></i></button>
    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="LexicalAdmin.ol('<?= $containerId ?>')" title="Nummerierte Liste"><i class="fas fa-list-ol"></i></button>
    <span class="toolbar-sep"></span>
    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="LexicalAdmin.link('<?= $containerId ?>')" title="Link"><i class="fas fa-link"></i></button>
    <span class="toolbar-sep"></span>
    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="LexicalAdmin.image('<?= $containerId ?>')" title="Bild einfügen/hochladen"><i class="fas fa-image"></i></button>
    <button type="button" class="btn btn-sm btn-outline-danger" onclick="LexicalAdmin.clearUploads()" title="Alle Bilder vom Server löschen"><i class="fas fa-trash-alt"></i></button>
    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="LexicalAdmin.video('<?= $containerId ?>')" title="YouTube/Vimeo einfügen"><i class="fab fa-youtube"></i></button>
    <span class="toolbar-sep"></span>
    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="LexicalAdmin.toggleSource('<?= $containerId ?>')" title="Quellcode anzeigen"><i class="fas fa-code"></i></button>
</div>
<div class="lexical-editor-wrap" style="position: relative;">
    <div id="<?= $containerId ?>"></div>
    <div id="codemirror-container-<?= $containerId ?>" style="display: none; position: absolute; top: 0; left: 0; right: 0; bottom: 0; z-index: 10;"></div>
</div>
<input type="hidden" name="<?= htmlspecialchars($fieldName) ?>" id="<?= $hiddenInputId ?>" value="<?= $safeHtml ?>">

<?php if (!defined('LEXICAL_BUNDLE_LOADED')): define('LEXICAL_BUNDLE_LOADED', true); ?>
<!-- CodeMirror CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/codemirror.min.css">
<!-- CodeMirror JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/mode/xml/xml.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/mode/javascript/javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/mode/css/css.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/mode/htmlmixed/htmlmixed.min.js"></script>
<script src="js/editor.bundle.js"></script>
<?php endif; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    LexicalAdmin.init(
        '<?= $containerId ?>',
        '<?= $hiddenInputId ?>',
        <?= json_encode($initialHtml) ?>
    );
});
</script>
<?php
}
