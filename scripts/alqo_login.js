function loginattempt(event) {
    event.preventDefault();
    $.post("index.php", {
        fct: "login",
        userID: "admin",
        userPass: $("#inputPassword").val()
    },
            function (data) {
                if (data.search("authorized") !== -1) {
                    location.reload();
                } else {
                    toastr.options = {
                        "positionClass": "toast-top-right",
                        "closeButton": true,
                        "progressBar": false,
                        "showEasing": "swing",
                        "timeOut": "6000"
                    };
                    toastr.warning('Respone: ' + data);
                }
            });
    $("#loginForm")[0].reset();
}


$(document).ready(function () {
    $("#btnLogin").click(function (event) {
        loginattempt(event);
    });

    $('#inputPassword').keydown(function (event) {
        if (event.keyCode == 13) {
            loginattempt(event);
        }
    });
});
