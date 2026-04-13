
// 


$(document).ready(function() {
    // Handle file upload using AJAX
    $('#uploadForm').on('submit', function(e) {
        e.preventDefault();

        const $messageEl = $('#uploadMessage');
        const file = $('#file')[0].files[0];
        const title = $('#title').val().trim();
        const category_id = $('#category_upload').val();
        const tags = $('#tags').val().trim();

        if (!file) {
            showMessage($messageEl, 'Please select a file', 'error');
            return;
        }

        if (file.size > 10 * 1024 * 1024) {
            
            showMessage($messageEl, 'File size exceeds 10 MB limit', 'error');
            return;
        }

        const formData = new FormData();
        formData.append('action', 'upload');
        formData.append('title', title);
        formData.append('category_id', category_id);
        formData.append('tags', tags);
        formData.append('file', file);

        $.ajax({
            url: 'api/handle.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json'
        }).done(function(data) {
            if (data.success) {
                showMessage($messageEl, data.message, 'success');
                $('#uploadForm')[0].reset();
                setTimeout(function() {
                    location.reload();
                }, 1500);
            } else {
                showMessage($messageEl, data.error, 'error');
            }
        }).fail(function(xhr, status, error) {
            showMessage($messageEl, 'An error occurred: ' + error, 'error');
        });
    });
});

function deleteDocument(documentId) {
    if (!confirm('Are you sure you want to delete this document? This action cannot be undone.')) {
        return;
    }

    $.ajax({
        url: 'api/handle.php',
        method: 'POST',
        data: {
            action: 'delete',
            document_id: documentId
        },
        dataType: 'json'
    }).done(function(data) {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    }).fail(function(xhr, status, error) {
        alert('An error occurred: ' + error);
    });
}

function applyFilters() {
    document.getElementById('filterForm').submit();
}

function showMessage(element, message, type) {
    const $el = $(element);
    $el.html(`<div class="alert alert-${type}">${message}</div>`);
    if (type === 'success') {
        setTimeout(function() {
            $el.html('');
        }, 5000);
    }
}
