<?php
// ui/partials/layout_foot.php
?>
  </main>
</div><!-- /.admin-layout -->

<script>
  // Auto-dismiss flash після 4 с
  const flash = document.getElementById('js-flash');
  if (flash) {
    setTimeout(() => { flash.style.opacity = '0'; }, 3600);
    setTimeout(() => { flash.remove(); }, 4000);
  }

  // Підтвердження перед видаленням
  document.querySelectorAll('.js-delete-form').forEach(f => {
    f.addEventListener('submit', e => {
      if (!confirm('Видалити запис? Цю дію неможливо відмінити.')) {
        e.preventDefault();
      }
    });
  });
</script>
</body>
</html>
