document.addEventListener("DOMContentLoaded", function () {

    // Toggle sidebar
    const toggleBtn = document.getElementById("menuToggle");
    const sidebar = document.getElementById("sidebar");

    if (toggleBtn) {
        toggleBtn.addEventListener("click", function () {
            sidebar.classList.toggle("active");
        });
    }

    // Loader
    window.showLoader = function () {
        document.getElementById("loader").style.display = "block";
    };

    window.hideLoader = function () {
        document.getElementById("loader").style.display = "none";
    };

});