<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_layout.php';

requireAdmin();

use Wruczek\TSWebsite\CacheManager;
use Wruczek\TSWebsite\Utils\CsrfUtils;
use Wruczek\TSWebsite\Utils\DatabaseUtils;

$flash     = $_GET['flash'] ?? null;
$flashType = $_GET['type']  ?? 'success';

// Load server groups from TS3 cache
$serverGroups = CacheManager::i()->getServerGroupList() ?? [];
$serverGroups = array_filter($serverGroups, fn($g) => ((int)($g['sgid'] ?? 0)) > 1 && ((int)($g['type'] ?? 0)) !== 0);

// Load current assignerconfig
$db  = DatabaseUtils::i()->getDb();
$raw = $db->get('config', 'value', ['identifier' => 'assignerconfig']);
$categories = [];
if ($raw) {
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) $categories = $decoded;
}

// FA icons
$faIcons = [
    'fas fa-globe'        => 'Globus',       'fas fa-gamepad'      => 'Controller',
    'fas fa-flag'         => 'Flagge',        'fas fa-star'         => 'Stern',
    'fas fa-music'        => 'Musik',         'fas fa-trophy'       => 'Pokal',
    'fas fa-shield-alt'   => 'Schild',        'fas fa-crown'        => 'Krone',
    'fas fa-heart'        => 'Herz',          'fas fa-bolt'         => 'Blitz',
    'fas fa-fire'         => 'Feuer',         'fas fa-users'        => 'Gruppe',
    'fas fa-user'         => 'Person',        'fas fa-language'     => 'Sprache',
    'fas fa-microphone'   => 'Mikrofon',      'fas fa-headphones'   => 'Kopfhörer',
    'fas fa-paint-brush'  => 'Pinsel',        'fas fa-code'         => 'Code',
    'fas fa-wrench'       => 'Werkzeug',      'fas fa-graduation-cap' => 'Abschluss',
    'fas fa-dumbbell'     => 'Hantel',        'fas fa-dragon'       => 'Drache',
    'fas fa-robot'        => 'Roboter',       'fas fa-cat'          => 'Katze',
    'fas fa-dog'          => 'Hund',          'fas fa-cross'        => 'Kreuz',
];

adminHeader('Assigner-Konfiguration', 'assigner');
?>

<?php if ($flash): ?>
<div class="alert alert-<?= htmlspecialchars($flashType) ?> alert-dismissible fade show">
    <?= htmlspecialchars($flash) ?>
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
<?php endif; ?>

<?php if (empty($serverGroups)): ?>
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle"></i>
    Keine Servergruppen gefunden. Stelle sicher, dass der TS3-Server verbunden ist und Servergruppen existieren.
</div>
<?php endif; ?>

<p class="text-muted">
    Definiere Kategorien für die Gruppen-Zuweisung. Jede Kategorie hat einen Namen, ein Icon, eine maximale Anzahl
    wählbarer Gruppen und die zugehörigen Servergruppen.
</p>

<div class="mb-3 d-flex justify-content-between">
    <button type="button" class="btn btn-primary" id="btn-add-category">
        <i class="fas fa-plus"></i> Kategorie hinzufügen
    </button>
    <button type="button" class="btn btn-success" id="btn-save">
        <i class="fas fa-save"></i> Speichern
    </button>
</div>

<div id="categories-container"></div>

<div class="text-right mt-3">
    <button type="button" class="btn btn-success btn-lg" id="btn-save-bottom">
        <i class="fas fa-save"></i> Speichern
    </button>
</div>

<script>
const ALL_SERVER_GROUPS = <?= json_encode(array_values(array_map(fn($g) => [
    'sgid' => (int)$g['sgid'],
    'name' => (string)$g['name'],
], $serverGroups)), JSON_UNESCAPED_UNICODE) ?>;

const FA_ICONS = <?= json_encode($faIcons, JSON_UNESCAPED_UNICODE) ?>;
const CSRF_TOKEN = <?= json_encode(CsrfUtils::getToken()) ?>;
const INITIAL_CATEGORIES = <?= json_encode($categories, JSON_UNESCAPED_UNICODE) ?>;

// Render icon picker HTML
function renderIconPicker(selectedIcon) {
    let html = '<div class="icon-picker d-flex flex-wrap" style="gap:.4rem;max-height:120px;overflow-y:auto">';
    for (const [icon, label] of Object.entries(FA_ICONS)) {
        const active = icon === selectedIcon ? ' active' : '';
        html += `<button type="button" class="btn btn-sm btn-outline-secondary icon-pick-btn${active}"
                    data-icon="${icon}" title="${label}" style="width:36px;height:36px;padding:0">
                    <i class="${icon}"></i></button>`;
    }
    html += '</div>';
    return html;
}

// Render group checkboxes
function renderGroupCheckboxes(selectedGroups, idx) {
    if (ALL_SERVER_GROUPS.length === 0) {
        return '<p class="text-muted small">Keine Servergruppen verfügbar.</p>';
    }
    let html = '<div class="group-list row">';
    for (const g of ALL_SERVER_GROUPS) {
        const checked = selectedGroups.includes(g.sgid) ? ' checked' : '';
        html += `<div class="col-md-4 col-sm-6">
            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input group-checkbox"
                    id="grp_${idx}_${g.sgid}" value="${g.sgid}"${checked}>
                <label class="custom-control-label" for="grp_${idx}_${g.sgid}">
                    ${g.name} <span class="text-muted small">(#${g.sgid})</span>
                </label>
            </div>
        </div>`;
    }
    html += '</div>';
    return html;
}

// Build a category card
function buildCard(cat, idx) {
    const name  = cat.name  || '';
    const icon  = cat.icon  || 'fas fa-star';
    const max   = cat.max   || 1;
    const groups = cat.groups || [];

    const div = document.createElement('div');
    div.className = 'card mb-3 category-card';
    div.innerHTML = `
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <i class="${icon} mr-2 cat-icon-preview" style="font-size:1.2rem"></i>
                <strong class="cat-name-preview">${name || 'Neue Kategorie'}</strong>
            </div>
            <div>
                <button type="button" class="btn btn-sm btn-outline-secondary btn-up" title="Hoch"><i class="fas fa-arrow-up"></i></button>
                <button type="button" class="btn btn-sm btn-outline-secondary btn-down" title="Runter"><i class="fas fa-arrow-down"></i></button>
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove"><i class="fas fa-trash"></i></button>
            </div>
        </div>
        <div class="card-body">
            <div class="form-row mb-3">
                <div class="col-md-5">
                    <label>Name</label>
                    <input type="text" class="form-control cat-name" value="${name}" placeholder="z.B. Land, Spiel, Hobby">
                </div>
                <div class="col-md-2">
                    <label>Max. Auswahl</label>
                    <input type="number" class="form-control cat-max" value="${max}" min="1" max="50">
                </div>
                <div class="col-md-5">
                    <label>Icon <span class="cat-icon-display text-muted small">(${icon})</span></label>
                    <input type="hidden" class="cat-icon-value" value="${icon}">
                    ${renderIconPicker(icon)}
                </div>
            </div>
            <label>Servergruppen in dieser Kategorie</label>
            ${renderGroupCheckboxes(groups, idx)}
        </div>`;

    // Events
    div.querySelector('.btn-remove').addEventListener('click', () => div.remove());
    div.querySelector('.btn-up').addEventListener('click', () => {
        const prev = div.previousElementSibling;
        if (prev) div.parentNode.insertBefore(div, prev);
    });
    div.querySelector('.btn-down').addEventListener('click', () => {
        const next = div.nextElementSibling;
        if (next) next.after(div);
    });

    const nameInput   = div.querySelector('.cat-name');
    const namePreview = div.querySelector('.cat-name-preview');
    nameInput.addEventListener('input', () => {
        namePreview.textContent = nameInput.value || 'Neue Kategorie';
    });

    div.querySelectorAll('.icon-pick-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const ic = btn.dataset.icon;
            div.querySelector('.cat-icon-value').value = ic;
            div.querySelector('.cat-icon-preview').className = ic + ' mr-2 cat-icon-preview';
            div.querySelector('.cat-icon-display').textContent = '(' + ic + ')';
            div.querySelectorAll('.icon-pick-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        });
    });

    return div;
}

// Load initial categories
const container = document.getElementById('categories-container');
INITIAL_CATEGORIES.forEach((cat, i) => container.appendChild(buildCard(cat, i)));

// Add new category
document.getElementById('btn-add-category').addEventListener('click', () => {
    const card = buildCard({}, Date.now());
    container.appendChild(card);
    card.scrollIntoView({behavior: 'smooth'});
});

// Save
function doSave() {
    const cards = container.querySelectorAll('.category-card');
    const result = [];
    cards.forEach(card => {
        const name   = card.querySelector('.cat-name').value.trim();
        const icon   = card.querySelector('.cat-icon-value').value.trim();
        const max    = parseInt(card.querySelector('.cat-max').value) || 1;
        const groups = [...card.querySelectorAll('.group-checkbox:checked')].map(cb => parseInt(cb.value));
        if (name) result.push({name, icon, max, groups});
    });

    fetch('api/config.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            'csrf-token': CSRF_TOKEN,
            'config[assignerconfig]': JSON.stringify(result)
        })
    }).then(() => {
        window.location = 'assigner.php?flash=' + encodeURIComponent('Assigner-Konfiguration gespeichert.') + '&type=success';
    }).catch(() => alert('Fehler beim Speichern.'));
}

document.getElementById('btn-save').addEventListener('click', doSave);
document.getElementById('btn-save-bottom').addEventListener('click', doSave);
</script>

<?php adminFooter(); ?>
