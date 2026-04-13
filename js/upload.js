document.addEventListener("DOMContentLoaded", function () {

    const fileInput = document.getElementById("fileInput");
    const preview = document.getElementById("preview");
    const uploadBtn = document.getElementById("uploadBtn");

    // Preview file
    if (fileInput) {
        fileInput.addEventListener("change", function () {

            const file = fileInput.files[0];

            if (file) {
                const reader = new FileReader();

                reader.onload = function (e) {
                    preview.src = e.target.result;
                    preview.style.display = "block";
                };

                reader.readAsDataURL(file);
            }
        });
    }

    // Upload me AJAX
    if (uploadBtn) {
        uploadBtn.addEventListener("click", function () {

            const file = fileInput.files[0];

            if (!file) {
                alert("Zgjidh një file!");
                return;
            }

            let formData = new FormData();
            formData.append("file", file);

            let xhr = new XMLHttpRequest();
            xhr.open("POST", "ajax/upload.php", true);

            xhr.onload = function () {
                if (this.status === 200) {
                    alert("File u ngarkua me sukses!");
                    location.reload();
                }
            };

            xhr.send(formData);
        });
    }

});