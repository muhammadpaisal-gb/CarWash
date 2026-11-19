document.addEventListener('DOMContentLoaded', function () {
  var t = document.getElementById('toggleSidebar');
  if (t) t.addEventListener('click', function () { document.getElementById('sidebar').classList.toggle('show'); });
  // Cegah klik ganda pada tombol submit
  document.querySelectorAll('form[data-once]').forEach(function (f) {
    f.addEventListener('submit', function () {
      var b = f.querySelector('button[type=submit]');
      if (b) setTimeout(function () { b.disabled = true; }, 0);
    });
  });
  // Tutup alert otomatis
  setTimeout(function () {
    document.querySelectorAll('.alert-success.alert-dismissible').forEach(function (a) {
      if (window.bootstrap) bootstrap.Alert.getOrCreateInstance(a).close();
    });
  }, 4000);
});
