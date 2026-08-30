(function () {
    'use strict';

    const box = document.getElementById('ms_related_users');

    if (!box || box.dataset.repeaterReady) {
        return;
    }

    box.dataset.repeaterReady = '1';

    const rows = box.querySelector('.ms-related-users-rows');
    const template = box.querySelector('.ms-related-user-template');
    const addButton = box.querySelector('.ms-add-related-user');

    if (!rows || !template || !addButton) {
        return;
    }

    addButton.addEventListener('click', function () {
        rows.appendChild(template.content.cloneNode(true));
    });

    rows.addEventListener('click', function (event) {
        const removeButton = event.target.closest('.ms-remove-related-user');

        if (removeButton) {
            removeButton.closest('.ms-related-user-row').remove();
        }
    });
}());
