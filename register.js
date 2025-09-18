document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("registerForm");

    form.addEventListener("submit", function(e) {
        e.preventDefault();

        let formData = new FormData(form);

        // Example validation with regex
        let email = formData.get("email");
        let phone = formData.get("contact");
        let emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        let phoneRegex = /^[0-9]{7,15}$/;

        if (!emailRegex.test(email)) {
            alert("Invalid email format");
            return;
        }
        if (!phoneRegex.test(phone)) {
            alert("Invalid phone number");
            return;
        }

        fetch("register_customer_action.php", {
            method: "POST",
            body: formData
        })
        .then(res => res.text())
        .then(data => {
            if (data === "success") {
                window.location.href = "login.php";
            } else {
                document.getElementById("response").innerText = data;
            }
        });
    });
});
