
// Folder data injected by dashboard.php — fallback to empty if missing
var folderData = window.folderData || [];

// File icon map
const FILE_ICONS = {
    pdf: '📄', doc: '📝', docx: '📝',
    xls: '📊', xlsx: '📊', txt: '📃',
    jpg: '🖼️', jpeg: '🖼️', png: '🖼️'
};

function getFileIcon(ext) {
    return FILE_ICONS[ext.toLowerCase()] || '📎';
}

// ─── Upload ───────────────────────────────────────────────────────────────────

$(document).ready(function () {
    $('#uploadForm').on('submit', function (e) {
        e.preventDefault();

        const $msg   = $('#uploadMessage');
        const $submit = $(this).find('button[type="submit"]');
        const file   = $('#file')[0].files[0];
        const title  = $('#title').val().trim();

        if (!file) { showMessage($msg, 'Please select a file', 'error'); return; }
        if (file.size > 10 * 1024 * 1024) { showMessage($msg, 'File size exceeds 10 MB limit', 'error'); return; }

        const fd = new FormData();
        fd.append('action', 'upload');
        fd.append('title', title);
        fd.append('category_id', $('#category_upload').val());
        fd.append('folder_id', $('#upload_folder').val());
        fd.append('tags', $('#tags').val().trim());
        fd.append('file', file);

        showMessage($msg, 'Uploading document...', 'info');
        setButtonLoading($submit, true, 'Uploading...');

        $.ajax({
            url: 'api/handle.php',
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            dataType: 'json'
        }).done(function (data) {
            if (data.success) {
                showMessage($msg, data.message, 'success');
                $('#uploadForm')[0].reset();
                setTimeout(() => location.reload(), 1500);
            } else {
                showMessage($msg, data.error, 'error');
            }
        }).fail(function (xhr, status, error) {
            showMessage($msg, 'An error occurred: ' + error, 'error');
        }).always(function () {
            setButtonLoading($submit, false);
        });
    });

    // Load shared documents when tab is first opened
    $('#tabShared').on('click', function () {
        const container = $('#sharedContainer');
        if (container.data('loaded')) return;
        loadSharedDocuments();
    });
});

// ─── Delete ──────────────────────────────────────────────────────────────────

function deleteDocument(documentId) {
    if (!confirm('Are you sure you want to delete this document? This action cannot be undone.')) return;

    $.ajax({
        url: 'api/handle.php',
        method: 'POST',
        data: { action: 'delete', document_id: documentId },
        dataType: 'json'
    }).done(function (data) {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    }).fail(function (xhr, status, error) {
        alert('An error occurred: ' + error);
    });
}

// ─── Filters ─────────────────────────────────────────────────────────────────

function applyFilters() {
    document.getElementById('filterForm').submit();
}

// ─── Tabs ─────────────────────────────────────────────────────────────────────

function switchTab(tab) {
    if (tab === 'my') {
        $('#panelMyDocs').show();
        $('#panelShared').hide();
        $('#tabMyDocs').addClass('active');
        $('#tabShared').removeClass('active');
    } else {
        $('#panelMyDocs').hide();
        $('#panelShared').show();
        $('#tabMyDocs').removeClass('active');
        $('#tabShared').addClass('active');
        const container = $('#sharedContainer');
        if (!container.data('loaded')) loadSharedDocuments();
    }
}

function loadSharedDocuments() {
    const container = $('#sharedContainer');
    container.html(loadingHtml('Loading shared documents...'));

    $.ajax({
        url: 'api/handle.php?action=get_shared_documents',
        method: 'GET',
        dataType: 'json'
    }).done(function (data) {
        if (!data.success || !data.documents.length) {
            container.html('<div class="empty-state"><p>No documents have been shared with you yet.</p></div>');
        } else {
            container.html(buildDocumentsTable(data.documents, true));
        }
        container.data('loaded', true);
    }).fail(function () {
        $('#sharedContainer').html('<div class="alert alert-error">Failed to load shared documents.</div>');
    });
}

// ─── Folders ─────────────────────────────────────────────────────────────────

function loadFolder(folderId, el) {
    // Highlight active folder
    document.querySelectorAll('.folder-item').forEach(function (e) { e.classList.remove('active'); });
    if (el) el.classList.add('active');
    $('#documentsContainer').html(loadingHtml('Loading folder documents...'));

    $.ajax({
        url: 'api/handle.php?action=get_folder_documents&folder_id=' + folderId,
        method: 'GET',
        dataType: 'json'
    }).done(function (data) {
        if (data.success) {
            $('#documentsContainer').html(buildFolderPane(folderId, data.documents));
        }
    }).fail(function () {
        $('#documentsContainer').html('<div class="alert alert-error">Failed to load folder documents.</div>');
    });
}

function toggleFolder(id, e) {
    e.stopPropagation();
    var $children = $('#folder-children-' + id);
    var open = $children.slideToggle(150).is(':visible');
    $(e.currentTarget).text(open ? '▼' : '▶');
}

// ─── Folder Pane Tree (main content area) ────────────────────────────────────

function buildFolderPane(rootId, allDocs) {
    // Group all fetched documents by their folder_id
    var byFolder = {};
    allDocs.forEach(function (doc) {
        var fid = parseInt(doc.folder_id) || 0;
        if (!byFolder[fid]) byFolder[fid] = [];
        byFolder[fid].push(doc);
    });

    var hasChildFolders = folderData.some(function (f) { return parseInt(f.parent_id) === rootId; });
    if (!allDocs.length && !hasChildFolders) {
        return '<div class="empty-state"><p>This folder is empty.</p></div>';
    }

    return '<div class="folder-pane">' + renderPaneNode(rootId, byFolder, 0) + '</div>';
}

function renderPaneNode(folderId, byFolder, depth) {
    var pad = depth * 20;
    var html = '';

    // Child folders first
    folderData
        .filter(function (f) { return parseInt(f.parent_id) === folderId; })
        .forEach(function (folder) {
            var fid = parseInt(folder.id);
            var hasSubFolders = folderData.some(function (f) { return parseInt(f.parent_id) === fid; });
            var hasDocs = byFolder[fid] && byFolder[fid].length > 0;
            var hasChildren = hasSubFolders || hasDocs;

            html += '<div class="pane-folder-item" style="padding-left:' + pad + 'px">';
            if (hasChildren) {
                html += '<span class="folder-toggle" onclick="togglePaneFolder(' + fid + ', event)">▶</span>';
            } else {
                html += '<span class="folder-toggle" style="visibility:hidden">▶</span>';
            }
            html += '<span class="pane-folder-name">📂 ' + esc(folder.name) + '</span>';
            html += '</div>';

            if (hasChildren) {
                html += '<div class="folder-children" id="pane-children-' + fid + '">';
                html += renderPaneNode(fid, byFolder, depth + 1);
                html += '</div>';
            }
        });

    // Direct documents in this folder
    (byFolder[folderId] || []).forEach(function (doc) {
        html += buildPaneDocRow(doc, pad);
    });

    return html;
}

function buildPaneDocRow(doc, pad) {
    var icon    = getFileIcon(doc.file_format || '');
    var tagsArr = doc.tags ? doc.tags.split(', ').map(function (t) {
        return '<span class="tag">' + esc(t.trim()) + '</span>';
    }).join('') : '';
    var tagsHtml = tagsArr ? '<div class="tags">' + tagsArr + '</div>' : '<span class="pane-doc-no-tags">—</span>';

    var html = '<div class="pane-doc-row" style="padding-left:' + (pad + 28) + 'px">';
    html += '<span class="pane-doc-icon">' + icon + '</span>';
    html += '<span class="pane-doc-title">' + esc(doc.title) + '</span>';
    html += '<span class="pane-doc-tags">' + tagsHtml + '</span>';
    html += '<span class="pane-doc-meta">' + esc(doc.category_name || 'N/A') + ' · ' + formatSize(doc.file_size) + ' · ' + formatDate(doc.uploaded_at) + '</span>';
    html += '<span class="pane-doc-actions">';
    html += '<a href="download.php?doc_id=' + doc.id + '" class="btn btn-small btn-primary">Download</a> ';
    html += '<button class="btn btn-small btn-secondary" onclick=\'openEditModal(' + doc.id + ', "' + esc(doc.title) + '", ' + (doc.category_id || 0) + ', "' + esc(doc.tags || '') + '", "' + esc(doc.description || '') + '")\'>Edit</button> ';
    html += '<button class="btn btn-small btn-success" onclick="openShareModal(' + doc.id + ')">Share</button> ';
    html += '<button class="btn btn-small btn-danger" onclick="deleteDocument(' + doc.id + ')">Delete</button>';
    html += '</span>';
    html += '</div>';
    return html;
}

function togglePaneFolder(id, e) {
    e.stopPropagation();
    var $children = $('#pane-children-' + id);
    var open = $children.slideToggle(150).is(':visible');
    $(e.currentTarget).text(open ? '▼' : '▶');
}

function loadAllDocuments() {
    document.querySelectorAll('.folder-item').forEach(function (e) { e.classList.remove('active'); });
    $('#documentsContainer').html(loadingHtml('Loading documents...'));

    $.ajax({
        url: 'api/handle.php?action=get_documents',
        method: 'GET',
        dataType: 'json'
    }).done(function (data) {
        if (data.success) {
            $('#documentsContainer').html(
                data.documents.length
                    ? buildDocumentsTable(data.documents, false)
                    : '<div class="empty-state"><p>No documents found. Start by uploading a file!</p></div>'
            );
        }
    }).fail(function () {
        $('#documentsContainer').html('<div class="alert alert-error">Failed to load documents.</div>');
    });
}

function openNewFolderModal() {
    $('#new_folder_name').val('');
    $('#folderMessage').html('');
    openModal('folderModalOverlay');
}

function submitNewFolder() {
    const name = $('#new_folder_name').val().trim();
    const $button = $('#folderModal .btn-primary');
    if (!name) { showMessage($('#folderMessage'), 'Please enter a folder name.', 'error'); return; }

    showMessage($('#folderMessage'), 'Creating folder...', 'info');
    setButtonLoading($button, true, 'Creating...');

    $.ajax({
        url: 'api/handle.php',
        method: 'POST',
        data: { action: 'create_folder', name: name },
        dataType: 'json'
    }).done(function (data) {
        if (data.success) {
            closeModal('folderModalOverlay');
            location.reload();
        } else {
            showMessage($('#folderMessage'), data.error, 'error');
        }
    }).fail(function () {
        showMessage($('#folderMessage'), 'Request failed.', 'error');
    }).always(function () {
        setButtonLoading($button, false);
    });
}

// ─── Edit Document ────────────────────────────────────────────────────────────

function openEditModal(id, title, categoryId, tags, description) {
    $('#edit_doc_id').val(id);
    $('#edit_title').val(title);
    $('#edit_category').val(categoryId || '');
    $('#edit_tags').val(tags);
    $('#edit_description').val(description);
    $('#editMessage').html('');
    openModal('editModalOverlay');
}

$('#editForm').on('submit', function (e) {
    e.preventDefault();
    const $button = $(this).find('button[type="submit"]');

    showMessage($('#editMessage'), 'Saving changes...', 'info');
    setButtonLoading($button, true, 'Saving...');

    $.ajax({
        url: 'api/handle.php',
        method: 'POST',
        data: {
            action:      'edit_document',
            document_id: $('#edit_doc_id').val(),
            title:       $('#edit_title').val(),
            category_id: $('#edit_category').val(),
            tags:        $('#edit_tags').val(),
            description: $('#edit_description').val()
        },
        dataType: 'json'
    }).done(function (data) {
        if (data.success) {
            closeModal('editModalOverlay');
            location.reload();
        } else {
            showMessage($('#editMessage'), data.error, 'error');
        }
    }).fail(function () {
        showMessage($('#editMessage'), 'Request failed.', 'error');
    }).always(function () {
        setButtonLoading($button, false);
    });
});

// ─── Share Document ───────────────────────────────────────────────────────────

function openShareModal(docId) {
    $('#share_doc_id').val(docId);
    $('#share_username').val('');
    $('#shareMessage').html('');
    openModal('shareModalOverlay');
}

function submitShare() {
    const docId    = $('#share_doc_id').val();
    const username = $('#share_username').val().trim();
    const $button = $('#shareModal .btn-primary');
    if (!username) { showMessage($('#shareMessage'), 'Please enter a username.', 'error'); return; }

    showMessage($('#shareMessage'), 'Sharing document...', 'info');
    setButtonLoading($button, true, 'Sharing...');

    $.ajax({
        url: 'api/handle.php',
        method: 'POST',
        data: { action: 'share_document', document_id: docId, username: username },
        dataType: 'json'
    }).done(function (data) {
        if (data.success) {
            showMessage($('#shareMessage'), data.message, 'success');
            setTimeout(function () { closeModal('shareModalOverlay'); }, 1500);
        } else {
            showMessage($('#shareMessage'), data.error, 'error');
        }
    }).fail(function () {
        showMessage($('#shareMessage'), 'Request failed.', 'error');
    }).always(function () {
        setButtonLoading($button, false);
    });
}

// ─── Modal Helpers ────────────────────────────────────────────────────────────

function openModal(overlayId) {
    const overlay = document.getElementById(overlayId);
    const box = overlay.nextElementSibling;
    overlay.classList.add('open');
    box.classList.add('open');
}

function closeModal(overlayId) {
    const overlay = document.getElementById(overlayId);
    const box = overlay.nextElementSibling;
    overlay.classList.remove('open');
    box.classList.remove('open');
}

// ─── Table Builder ────────────────────────────────────────────────────────────

function buildDocumentsTable(docs, isShared) {
    let html = '<table class="documents-table"><thead><tr>' +
        '<th>Type</th><th>Title</th><th>Category</th><th>Tags</th><th>Size</th><th>Uploaded</th>';
    html += isShared ? '<th>Shared By</th>' : '';
    html += '<th>Actions</th></tr></thead><tbody>';

    docs.forEach(function (doc) {
        const icon     = getFileIcon(doc.file_format || '');
        const tagsArr  = doc.tags ? doc.tags.split(', ').map(function (t) { return '<span class="tag">' + esc(t.trim()) + '</span>'; }).join('') : '—';
        const tagsHtml = doc.tags ? '<div class="tags">' + tagsArr + '</div>' : tagsArr;
        const size     = formatSize(doc.file_size);
        const date     = formatDate(doc.uploaded_at);

        html += '<tr>';
        html += '<td style="font-size:1.4rem;text-align:center;">' + icon + '</td>';
        html += '<td>' + esc(doc.title) + '</td>';
        html += '<td>' + esc(doc.category_name || 'N/A') + '</td>';
        html += '<td>' + tagsHtml + '</td>';
        html += '<td>' + size + '</td>';
        html += '<td>' + date + '</td>';
        if (isShared) html += '<td>' + esc(doc.owner_username || '') + '</td>';
        html += '<td>';
        html += '<a href="download.php?doc_id=' + doc.id + '" class="btn btn-small btn-primary">Download</a> ';
        if (!isShared) {
            html += '<button class="btn btn-small btn-secondary" onclick=\'openEditModal(' + doc.id + ', "' + esc(doc.title) + '", ' + (doc.category_id || 0) + ', "' + esc(doc.tags || '') + '", "' + esc(doc.description || '') + '")\'>Edit</button> ';
            html += '<button class="btn btn-small btn-success" onclick="openShareModal(' + doc.id + ')">Share</button> ';
            html += '<button class="btn btn-small btn-danger" onclick="deleteDocument(' + doc.id + ')">Delete</button>';
        }
        html += '</td></tr>';
    });

    html += '</tbody></table>';
    return html;
}

// ─── Utilities ────────────────────────────────────────────────────────────────

function showMessage(element, message, type) {
    $(element).html('<div class="alert alert-' + type + '">' + message + '</div>');
    if (type === 'success') {
        setTimeout(function () { $(element).html(''); }, 5000);
    }
}

function esc(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function formatSize(bytes) {
    const units = ['B', 'KB', 'MB', 'GB'];
    let i = 0;
    while (bytes >= 1024 && i < units.length - 1) { bytes /= 1024; i++; }
    return Math.round(bytes * 100) / 100 + ' ' + units[i];
}

function formatDate(dateStr) {
    const d = new Date(dateStr);
    return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}
function loadingHtml(message) {
    return '<div class="loading-state"><span class="loading-spinner" aria-hidden="true"></span><span>' + esc(message) + '</span></div>';
}

function setButtonLoading($button, isLoading, loadingText) {
    if (!$button.length) return;

    if (isLoading) {
        $button.data('original-text', $button.html());
        $button.prop('disabled', true).addClass('btn-loading').html(
            '<span class="loading-spinner loading-spinner-small" aria-hidden="true"></span>' + esc(loadingText)
        );
    } else {
        $button.prop('disabled', false).removeClass('btn-loading').html($button.data('original-text'));
    }
}
