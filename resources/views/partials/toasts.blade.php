<script>
    document.addEventListener('DOMContentLoaded', function () {
        @if(session('success'))
        logSentinelToast('success', @json(session('success')));
        @endif

        @if(session('error'))
        logSentinelToast('error', @json(session('error')));
        @endif

        @if(session('warning'))
        logSentinelToast('warning', @json(session('warning')));
        @endif

        @if(session('info'))
        logSentinelToast('info', @json(session('info')));
        @endif

        document.querySelectorAll('form[data-confirm]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (form.dataset.confirmed === 'true') {
                    return;
                }

                event.preventDefault();

                Swal.fire({
                    title: form.dataset.confirmTitle || 'Are you sure?',
                    text: form.dataset.confirmText || 'This action cannot be undone.',
                    icon: form.dataset.confirmIcon || 'warning',
                    showCancelButton: true,
                    confirmButtonText: form.dataset.confirmButton || 'Yes, continue',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: form.dataset.confirmColor || '#b91c1c'
                }).then(function (result) {
                    if (result.isConfirmed) {
                        form.dataset.confirmed = 'true';
                        form.submit();
                    }
                });
            });
        });
    });

    function logSentinelToast(icon, title) {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3500,
            timerProgressBar: true
        });

        Toast.fire({
            icon: icon,
            title: title
        });
    }
</script>
