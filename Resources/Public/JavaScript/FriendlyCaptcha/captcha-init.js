document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.form-frc-captcha').forEach(function (g) {
        var a = g.closest('form');
        if (!a) return;
        a.addEventListener('submit', function () {
            var solution = g.querySelector('.frc-captcha-solution');
            var hidden = g.querySelector('.form-frc-captcha-solution');
            if (solution && hidden) {
                hidden.value = solution.value;
            }
        }, false);
    });
});
