document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.topbar-user').forEach(function (el) {
    el.style.cursor = 'pointer';
    if (!el.getAttribute('title')) {
      el.setAttribute('title', 'Open Admin Profile');
    }
    el.addEventListener('click', function () {
      window.location.href = '/BarangaySystem/public/profile/index.php';
    });
  });
});
