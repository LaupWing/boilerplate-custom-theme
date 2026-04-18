(function () {
    'use strict';

    document.querySelectorAll('.snel-contact-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            var btn = form.querySelector('button[type="submit"]');
            if (btn.disabled) return;

            btn.disabled = true;
            var originalHTML = btn.innerHTML;
            btn.innerHTML = '<span class="inline-block w-4 h-4 border-2 border-current border-t-transparent rounded-full animate-spin"></span>';

            var prev = form.querySelector('.snel-form-message');
            if (prev) prev.remove();

            var data = new FormData(form);
            data.append('action', 'snel_form_submit');
            data.append('snel_form_type', 'contact');

            fetch(snelForms.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: data,
            })
                .then(function (res) { return res.json(); })
                .then(function (res) {
                    var msg = document.createElement('div');
                    msg.className = 'snel-form-message mt-4 border px-4 py-3 text-sm';

                    if (res.success) {
                        msg.classList.add('border-green-300', 'bg-green-50', 'text-green-800');
                        msg.textContent = res.data.message;
                        form.reset();
                    } else {
                        msg.classList.add('border-red-300', 'bg-red-50', 'text-red-800');
                        msg.textContent = res.data.message || 'Something went wrong. Please try again.';
                    }

                    form.appendChild(msg);
                })
                .catch(function () {
                    var msg = document.createElement('div');
                    msg.className = 'snel-form-message mt-4 border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800';
                    msg.textContent = 'Something went wrong. Please try again.';
                    form.appendChild(msg);
                })
                .finally(function () {
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                });
        });
    });
})();
