document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.form-frc-captcha').forEach(function (g) {
        var a = g.closest('form');
        if (!a) return;
        a.addEventListener('submit', function () {
            var response = g.querySelector('input[name="frc-captcha-response"]');
            var hidden = g.querySelector('.form-frc-captcha-response');
            if (response && hidden) {
                hidden.value = response.value;
            }
        }, false);
    });
});
