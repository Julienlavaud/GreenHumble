document.getElementById("scanButton").addEventListener("click", () => {
    document.getElementById("result").innerHTML = "Scan en cours…";

    fetch("/api/audit")
        .then(response => response.text())
        .then(data => {
            document.getElementById("result").innerHTML = data;
        })
        .catch(err => {
            document.getElementById("result").innerHTML = "Erreur : " + err;
        });
});
