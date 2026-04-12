// Konfirmim për fshirje dokumenti
function deleteDocument(id) {

    if (confirm("A doni ta fshini këtë dokument?")) {

        let xhr = new XMLHttpRequest();
        xhr.open("GET", "ajax/delete.php?id=" + id, true);

        xhr.onload = function () {
            if (this.status === 200) {
                alert("Dokumenti u fshi!");
                location.reload();
            }
        };

        xhr.send();
    }
}