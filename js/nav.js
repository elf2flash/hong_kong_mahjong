(() => {
  document.querySelectorAll('.nav-item.has-submenu').forEach((item) => {
    const trigger = item.querySelector('.nav-trigger');
    if (!trigger) return;

    trigger.addEventListener('click', (e) => {
      e.stopPropagation();
      const open = item.classList.toggle('is-open');
      trigger.setAttribute('aria-expanded', open ? 'true' : 'false');

      document.querySelectorAll('.nav-item.has-submenu').forEach((other) => {
        if (other !== item) {
          other.classList.remove('is-open');
          const t = other.querySelector('.nav-trigger');
          if (t) t.setAttribute('aria-expanded', 'false');
        }
      });
    });
  });

  document.addEventListener('click', (e) => {
    // Клики по пунктам/кнопкам внутри меню не закрывают его
    if (e.target.closest('.nav-submenu, .nav-setting, .btn-size, .nav-mode')) return;
    document.querySelectorAll('.nav-item.has-submenu.is-open').forEach((item) => {
      item.classList.remove('is-open');
      const t = item.querySelector('.nav-trigger');
      if (t) t.setAttribute('aria-expanded', 'false');
    });
  });
})();
