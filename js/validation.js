document.addEventListener("DOMContentLoaded", function () {

    const form = document.getElementById("authForm");

    if (form) {
        form.addEventListener("submit", function (e) {

            let email = document.getElementById("email").value.trim();
            let password = document.getElementById("password").value.trim();

            if (email === "" || password === "") {
                alert("Plotësoni të gjitha fushat!");
                e.preventDefault();
                return;
            }

            let emailPattern = /^[^ ]+@[^ ]+\.[a-z]{2,3}$/;

            if (!email.match(emailPattern)) {
                alert("Email i pavlefshëm!");
                e.preventDefault();
                return;
            }

            if (password.length < 6) {
                alert("Password duhet të ketë minimum 6 karaktere!");
                e.preventDefault();
                return;
            }
        });
    }

});