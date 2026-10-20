document.addEventListener('submit', function (event) {
    const form = event.target;

    if (!form.matches('[data-confirm-delete]')) {
        return;
    }

    const recordName =
        form.dataset.recordName || 'this record';

    const confirmed = window.confirm(
        `Are you sure you want to delete ${recordName}? ` +
        'This action may also delete related records.'
    );

    if (!confirmed) {
        event.preventDefault();
    }
});