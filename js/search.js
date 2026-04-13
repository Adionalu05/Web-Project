document.addEventListener("DOMContentLoaded", function () {

    const searchInput = document.getElementById("search");

    if (searchInput) {
        searchInput.addEventListener("keyup", function () {

            let query = searchInput.value;

            let xhr = new XMLHttpRequest();
            xhr.open("GET", "ajax/search.php?q=" + encodeURIComponent(query), true);

            xhr.onload = function () {
                if (this.status === 200) {
                    document.getElementById("results").innerHTML = this.responseText;
                }
            };

            xhr.send();
        });
    }

});