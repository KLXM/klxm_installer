(function () {
  var root = document.documentElement;
  var key = 'klxm-theme';
  var stored = localStorage.getItem(key);

  if (stored === 'light' || stored === 'dark' || stored === 'auto') {
    root.setAttribute('data-theme', stored);
  }

  var btn = document.getElementById('themeToggle');
  if (btn) {
    btn.addEventListener('click', function () {
      var current = root.getAttribute('data-theme') || 'auto';
      var next = current === 'auto' ? 'light' : current === 'light' ? 'dark' : 'auto';
      root.setAttribute('data-theme', next);
      localStorage.setItem(key, next);
    });
  }

  function registerPasteSupport() {
    document.addEventListener('paste', function (event) {
      var target = event.target;
      if (!(target instanceof HTMLInputElement || target instanceof HTMLTextAreaElement)) {
        return;
      }

      var isAllowedField = target.hasAttribute('data-allow-paste');
      var isInsideAllowedForm = !!target.closest('[data-allow-paste-form]');
      if (!isAllowedField && !isInsideAllowedForm) {
        return;
      }

      if (target.disabled || target.readOnly || !event.clipboardData) {
        return;
      }

      var text = event.clipboardData.getData('text');
      if (typeof text !== 'string' || text.length === 0) {
        return;
      }

      event.preventDefault();

      var start = target.selectionStart;
      var end = target.selectionEnd;
      if (start === null || end === null) {
        target.value = text;
      } else {
        target.value = target.value.slice(0, start) + text + target.value.slice(end);
        var caret = start + text.length;
        target.setSelectionRange(caret, caret);
      }

      target.dispatchEvent(new Event('input', { bubbles: true }));
      target.dispatchEvent(new Event('change', { bubbles: true }));
    }, true);
  }

  function initMultiSelects() {
    var widgets = document.querySelectorAll('[data-multi-select]');
    widgets.forEach(function (widget) {
      var toggle = widget.querySelector('[data-multi-toggle]');
      var panel = widget.querySelector('[data-multi-panel]');
      var search = widget.querySelector('[data-multi-search]');
      var chips = widget.querySelector('[data-multi-chips]');
      var count = widget.querySelector('[data-multi-count]');

      if (!toggle || !panel || !chips || !count) {
        return;
      }

      function options() {
        return Array.prototype.slice.call(widget.querySelectorAll('[data-multi-option]'));
      }

      function update() {
        var checked = options().filter(function (opt) {
          return opt.checked;
        });

        count.textContent = checked.length + ' gewaehlt';
        chips.innerHTML = '';

        checked.slice(0, 5).forEach(function (opt) {
          var labelText = opt.parentElement ? opt.parentElement.textContent.trim() : opt.value;
          var chip = document.createElement('span');
          chip.className = 'chip';
          chip.textContent = labelText;
          chips.appendChild(chip);
        });

        if (checked.length > 5) {
          var rest = document.createElement('span');
          rest.className = 'chip';
          rest.textContent = '+' + (checked.length - 5);
          chips.appendChild(rest);
        }
      }

      toggle.addEventListener('click', function () {
        widget.classList.toggle('is-open');
      });

      document.addEventListener('click', function (event) {
        if (!widget.contains(event.target)) {
          widget.classList.remove('is-open');
        }
      });

      if (search) {
        search.addEventListener('input', function () {
          var term = search.value.toLowerCase();
          var rows = widget.querySelectorAll('[data-multi-row]');
          rows.forEach(function (row) {
            var text = row.textContent.toLowerCase();
            row.style.display = text.indexOf(term) !== -1 ? '' : 'none';
          });
        });
      }

      options().forEach(function (opt) {
        opt.addEventListener('change', update);
      });

      update();
    });
  }

  initMultiSelects();
  registerPasteSupport();

  var createUserToggle = document.querySelector('[data-toggle-create-user]');
  var createUserForm = document.getElementById('createUserForm');
  if (createUserToggle && createUserForm) {
    createUserToggle.addEventListener('click', function () {
      var isHidden = createUserForm.hasAttribute('hidden');
      if (isHidden) {
        createUserForm.removeAttribute('hidden');
      } else {
        createUserForm.setAttribute('hidden', 'hidden');
      }
      createUserToggle.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
    });
  }
})();
