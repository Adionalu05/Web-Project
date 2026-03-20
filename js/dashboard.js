// Handle file upload
document.getElementById('uploadForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const title = document.getElementById('title').value;
    const file = document.getElementById('file').files[0];
    const category_id = document.getElementById('category_upload').value;
    const tags = document.getElementById('tags').value;
    const messageEl = document.getElementById('uploadMessage');

    if (!file) {
        showMessage(messageEl, 'Please select a file', 'error');
        return;
    }

    if (file.size > 10 * 1024 * 1024) {
        showMessage(messageEl, 'File size exceeds 10 MB limit', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'upload');
    formData.append('title', title);
    formData.append('category_id', category_id);
    formData.append('tags', tags);
    formData.append('file', file);

    try {
        const response = await fetch('api/handle.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showMessage(messageEl, data.message, 'success');
            document.getElementById('uploadForm').reset();
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showMessage(messageEl, data.error, 'error');
        }
    } catch (error) {
        showMessage(messageEl, 'An error occurred: ' + error.message, 'error');
    }
});

async function deleteDocument(documentId) {
    if (!confirm('Are you sure you want to delete this document? This action cannot be undone.')) {
        return;
    }

    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('document_id', documentId);

    try {
        const response = await fetch('api/handle.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    } catch (error) {
        alert('An error occurred: ' + error.message);
    }
}

function applyFilters() {
    document.getElementById('filterForm').submit();
}

function showMessage(element, message, type) {
    element.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
    if (type === 'success') {
        setTimeout(() => {
            element.innerHTML = '';
        }, 5000);
    }
}
