<?php
require_once 'config/db.php';
requireLogin();
$user = currentUser();

// delete saved summary
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $did  = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM ai_summaries WHERE id = ? AND user_id = ?");
    $stmt->execute([$did, $user['id']]);
    header('Location: ' . BASE_URL . '/summarizer.php');
    exit;
}

// fetch saved summaries
$savedStmt = $pdo->prepare("SELECT * FROM ai_summaries WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
$savedStmt->execute([$user['id']]);
$savedSummaries = $savedStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Summarizer — StudyVerse</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        /* summarizer layout */
        .summarizer-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 28px;
        }
        @media (max-width: 768px) { .summarizer-grid { grid-template-columns: 1fr; } }

        /* saved summary cards */
        .saved-summary-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 16px;
            background: var(--bg-hover);
            border: 1px solid var(--border);
            border-radius: 10px;
            margin-bottom: 10px;
            transition: border-color 0.2s;
            gap: 12px;
        }
        .saved-summary-card:hover  { border-color: var(--accent); }
        .saved-summary-left  { display: flex; align-items: center; gap: 12px; flex: 1; overflow: hidden; }
        .saved-summary-right { display: flex; align-items: center; gap: 12px; flex-shrink: 0; }
        .summary-icon {
            width: 38px; height: 38px;
            background: var(--accent-dim);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; flex-shrink: 0;
        }
        .summary-title { font-weight: 500; font-size: 14px; color: var(--text); margin-bottom: 3px; }
        .summary-desc  { font-size: 12px; color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 500px; }
        .summary-date  { font-size: 12px; color: var(--muted); white-space: nowrap; }

        /* save title input */
        .save-title-wrap {
            display: none;
            margin-top: 14px;
            padding: 14px;
            background: #0a0a20;
            border-radius: 8px;
            border: 1px solid var(--border);
        }
        .save-title-wrap.show { display: block; }

        /* modal overlay */
        .modal-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .modal-overlay.open { display: flex; }
        .modal-box {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 28px;
            max-width: 640px;
            width: 100%;
            max-height: 80vh;
            overflow-y: auto;
        }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .modal-close  { background: none; border: none; color: var(--muted); font-size: 20px; cursor: pointer; }
        .modal-close:hover { color: var(--danger); }
    </style>
</head>
<body>
<div class="layout">
    <?php require_once 'includes/sidebar.php'; ?>
    <main class="main-content">

        <!-- page header -->
        <div class="page-header">
            <h1>✦ AI Note Summarizer</h1>
            <p>STUDY SMARTER WITH AI-POWERED SUMMARIES</p>
        </div>

        <!-- input + output grid -->
        <div class="summarizer-grid">

            <!-- input panel -->
            <div class="card">
                <div class="section-label">NOTES</div>
                <textarea id="notesInput" rows="12"
                          placeholder="PASTE YOUR NOTES.."></textarea>

                <div class="flex-center gap-10 mt-12">
                    <button id="summarizeBtn" class="btn btn-primary" onclick="runSummarizer()">
                        ✦ Summarize
                    </button>
                    <button class="btn btn-ghost" onclick="clearSummarizer()">Clear</button>
                </div>

                <!-- loading state -->
                <div id="summaryLoading" style="display:none;margin-top:16px;" class="loading">
                    <span class="spinner"></span>GENERATING SUMMARY...
                </div>
            </div>

            <!-- output panel -->
            <div id="summaryOutput" style="display:none;">
                <div class="card">
                    <div class="flex-between mb-12">
                        <div>
                            <div id="sumTitle" style="color:var(--accent-l);font-weight:600;font-size:15px;margin-bottom:6px;"></div>
                            <span id="sumDiff" class="badge"></span>
                        </div>
                        <!-- save button — shows title input first -->
                        <button class="btn btn-outline btn-sm" onclick="toggleSaveForm()">SAVE</button>
                    </div>

                    <!-- save title form -->
                    <div class="save-title-wrap" id="saveTitleWrap">
                        <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:6px;">
                            SUMMARY NAME : 
                        </label>
                        <div style="display:flex;gap:10px;">
                            <input type="text" id="summaryTitleInput"
                                   placeholder="e.g. Malaysia History — Chapter 3"
                                   style="flex:1;">
                            <button class="btn btn-primary btn-sm" onclick="saveSummaryNow()">SAVE</button>
                            <button class="btn btn-ghost btn-sm" onclick="toggleSaveForm()">CANNCEL</button>
                        </div>
                    </div>

                    <div class="divider"></div>
                    <div class="section-label">KEY POINT</div>
                    <div id="sumPoints"></div>
                    <div id="keyTermsWrap" style="margin-top:16px;display:none;">
                        <div class="section-label">KEY TERMS</div>
                        <div id="sumTerms" class="flex-center gap-6 flex-wrap"></div>
                    </div>
                </div>
            </div>

            <!-- placeholder when no output yet -->
            <div id="summaryPlaceholder">
                <div class="card" style="display:flex;align-items:center;justify-content:center;min-height:200px;">
                    <div style="text-align:center;color:var(--muted);">
                        <div style="font-size:32px;margin-bottom:8px;">✦</div>
                        <div style="font-size:13px;">YOUR SUMMARY APPEAR HERE</div>
                    </div>
                </div>
            </div>

        </div>

        <!-- saved summaries section -->
        <div class="card">
            <div class="flex-between mb-20">
                <div>
                    <div style="font-size:16px;font-weight:600;color:var(--text);margin-bottom:4px;">
                        📑 HISTORY
                    </div>
                    <div style="font-size:13px;color:var(--muted);">
                        <?= count($savedSummaries) ?> REVISIT YOUR SAVED AI SUMMARIES ANYTIME
                    </div>
                </div>
                <!-- search bar -->
                <input type="text" id="searchSummary" placeholder="SEARCH"
                       style="width:220px;" oninput="filterSummaries()">
            </div>

            <div id="summaryList">
                <?php if (empty($savedSummaries)): ?>
                    <div class="loading">NO SAVED YET. SUMMARIZE YOUR NOTES FIRST!</div>
                <?php else: ?>
                    <?php foreach ($savedSummaries as $s):
                        // decode saved summary json
                        $summaryData = json_decode($s['summary'], true);
                        $title       = $s['title'] ?? ($summaryData['title'] ?? 'AI Generated Summary');
                        $preview     = substr(strip_tags($s['original']), 0, 90);
                    ?>
                    <div class="saved-summary-card" data-title="<?= e(strtolower($title)) ?>">
                        <div class="saved-summary-left">
                            <div class="summary-icon">📄</div>
                            <div style="overflow:hidden;">
                                <div class="summary-title"><?= e($title) ?></div>
                                <div class="summary-desc"><?= e($preview) ?>...</div>
                            </div>
                        </div>
                        <div class="saved-summary-right">
                            <div class="summary-date">
                                <?= date('d M Y', strtotime($s['created_at'])) ?>
                            </div>
                            <!-- view button — opens modal -->
                            <button class="btn btn-primary btn-sm"
                                    onclick='openModal(<?= htmlspecialchars(json_encode($summaryData), ENT_QUOTES) ?>)'>
                                View
                            </button>
                            <!-- delete button -->
                            <a href="<?= BASE_URL ?>/summarizer.php?delete=<?= $s['id'] ?>"
                               class="btn btn-danger btn-sm"
                               data-confirm="Delete this summary?">
                                🗑
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </main>
</div>

<!-- view summary modal -->
<div class="modal-overlay" id="summaryModal" onclick="closeModalOutside(event)">
    <div class="modal-box">
        <div class="modal-header">
            <div id="modalTitle" style="font-weight:600;font-size:16px;color:var(--accent-l);"></div>
            <button class="modal-close" onclick="closeModal()">✕</button>
        </div>
        <div class="divider"></div>
        <div id="modalDiff" style="margin-bottom:14px;"></div>
        <div class="section-label">KEY POINTS</div>
        <div id="modalPoints"></div>
        <div id="modalTermsWrap" style="margin-top:16px;display:none;">
            <div class="section-label">KEY TERMS</div>
            <div id="modalTerms" class="flex-center gap-6 flex-wrap"></div>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script>
// store current summary data for saving
var currentSummaryData = null;
var currentNotes       = '';

// clear input and output
function clearSummarizer() {
    document.getElementById('notesInput').value = '';
    document.getElementById('summaryOutput').style.display      = 'none';
    document.getElementById('summaryPlaceholder').style.display = '';
    currentSummaryData = null;
    currentNotes       = '';
}

// toggle save title form
function toggleSaveForm() {
    var wrap = document.getElementById('saveTitleWrap');
    wrap.classList.toggle('show');
    if (wrap.classList.contains('show')) {
        // pre-fill with ai title
        var aiTitle = document.getElementById('sumTitle').textContent.replace('✦ ', '').trim();
        document.getElementById('summaryTitleInput').value = aiTitle;
        document.getElementById('summaryTitleInput').focus();
    }
}

// save summary with custom title
async function saveSummaryNow() {
    if (!currentSummaryData) return;

    var customTitle = document.getElementById('summaryTitleInput').value.trim();
    if (!customTitle) { showToast('Please enter a title!', 'danger'); return; }

    try {
        await fetch('<?= BASE_URL ?>/api/save_summary.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({
                notes:   currentNotes,
                summary: currentSummaryData,
                title:   customTitle
            })
        });
        showToast('Summary saved as "' + customTitle + '"!', 'success');
        setTimeout(() => location.reload(), 1500);
    } catch (err) {
        showToast('Failed to save: ' + err.message, 'danger');
    }
}

// search/filter saved summaries by title
function filterSummaries() {
    var q     = document.getElementById('searchSummary').value.toLowerCase();
    var cards = document.querySelectorAll('.saved-summary-card');
    cards.forEach(function(card) {
        card.style.display = card.dataset.title.includes(q) ? '' : 'none';
    });
}

// open view modal
function openModal(data) {
    if (!data) return;

    // title
    document.getElementById('modalTitle').textContent = '✦ ' + (data.title || 'Summary');

    // difficulty badge
    var diffEl  = document.getElementById('modalDiff');
    var diffMap = { Easy: 'badge-success', Medium: 'badge-amber', Hard: 'badge-danger' };
    diffEl.innerHTML = '<span class="badge ' + (diffMap[data.difficulty] || 'badge-muted') + '">Difficulty: ' + (data.difficulty || '—') + '</span>';

    // key points
    var ptsEl = document.getElementById('modalPoints');
    ptsEl.innerHTML = (data.points || []).map(function(pt, i) {
        return '<div class="flex-center gap-10" style="margin-bottom:10px;align-items:flex-start;">' +
               '<div style="width:20px;height:20px;border-radius:50%;background:var(--accent-dim);display:flex;align-items:center;justify-content:center;font-size:10px;color:var(--accent-l);flex-shrink:0;">' + (i+1) + '</div>' +
               '<span style="font-size:13px;line-height:1.5;color:var(--text);">' + pt + '</span>' +
               '</div>';
    }).join('');

    // key terms
    var termsEl   = document.getElementById('modalTerms');
    var termsWrap = document.getElementById('modalTermsWrap');
    if (data.keyTerms && data.keyTerms.length) {
        termsEl.innerHTML       = data.keyTerms.map(function(t) { return '<span class="badge badge-teal">' + t + '</span>'; }).join('');
        termsWrap.style.display = '';
    } else {
        termsWrap.style.display = 'none';
    }

    document.getElementById('summaryModal').classList.add('open');
}

// close modal
function closeModal() {
    document.getElementById('summaryModal').classList.remove('open');
}

// close modal when clicking outside box
function closeModalOutside(e) {
    if (e.target === document.getElementById('summaryModal')) closeModal();
}

// main summarizer — calls gemini api
window._runSummarizer = async function() {
    var notesEl  = document.getElementById('notesInput');
    var outputEl = document.getElementById('summaryOutput');
    var loadEl   = document.getElementById('summaryLoading');
    var phEl     = document.getElementById('summaryPlaceholder');
    var btn      = document.getElementById('summarizeBtn');

    if (!notesEl) return;
    var notes = notesEl.value.trim();
    if (!notes) { showToast('Please enter your notes first!', 'danger'); return; }

    // show loading
    if (loadEl)   loadEl.style.display   = 'block';
    if (outputEl) outputEl.style.display = 'none';
    if (phEl)     phEl.style.display     = 'none';
    if (btn)      btn.disabled           = true;

    try {
        var res  = await fetch('<?= BASE_URL ?>/api/summarize.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ notes: notes })
        });
        var data = await res.json();
        if (data.error) throw new Error(data.error);

        // store for saving later
        currentSummaryData = data;
        currentNotes       = notes;

        // render output
        renderSummary(data);
        if (outputEl) outputEl.style.display = '';

    } catch (err) {
        showToast('Failed: ' + err.message, 'danger');
        if (phEl) phEl.style.display = '';
    }

    // hide loading
    if (loadEl) loadEl.style.display = 'none';
    if (btn)    btn.disabled         = false;
};

// entry point — called by summarize button
async function runSummarizer() {
    await window._runSummarizer();
}
</script>
</body>
</html>