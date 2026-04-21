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

  // Каскадні селекти: parent.data-child → child, фільтр за data-cw
  document.querySelectorAll('.js-cascade-parent').forEach(parent => {
    const childId = parent.dataset.child;
    if (!childId) return;
    const child = document.getElementById(childId);
    if (!child) return;

    const applyFilter = () => {
      const cwId = parent.value;
      let currentValid = false;
      Array.from(child.options).forEach(opt => {
        if (!opt.value) { opt.hidden = false; return; }          // placeholder завжди видимий
        const matches = !cwId || opt.dataset.cw === cwId;
        opt.hidden    = !matches;
        opt.disabled  = !matches;
        if (matches && opt.value === child.value) currentValid = true;
      });
      if (!currentValid) child.value = '';
    };

    parent.addEventListener('change', applyFilter);
    applyFilter(); // на завантаженні форми (важливо для режиму редагування)
  });
</script>
</body>
</html>
