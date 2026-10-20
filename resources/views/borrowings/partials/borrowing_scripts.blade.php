<script>
    window.borrowingDetailsMap = @json($borrowingDetailsMap ?? []);
    window.borrowingFilesMap = @json($borrowingFilesMap ?? []);

    window.openBorrowingDetailModal = function(id) {
        if (window.borrowingDetailsMap && window.borrowingDetailsMap[id]) {
            window.dispatchEvent(new CustomEvent('open-borrowing-detail-modal', {
                detail: window.borrowingDetailsMap[id]
            }));
        }
    };

    window.openBorrowingDispatchModal = function(id) {
        if (window.borrowingDetailsMap && window.borrowingDetailsMap[id]) {
            window.dispatchEvent(new CustomEvent('open-borrowing-dispatch-modal', {
                detail: window.borrowingDetailsMap[id]
            }));
        }
    };

    window.openBorrowingRejectModal = function(id) {
        if (window.borrowingDetailsMap && window.borrowingDetailsMap[id]) {
            window.dispatchEvent(new CustomEvent('open-borrowing-reject-modal', {
                detail: window.borrowingDetailsMap[id]
            }));
        }
    };

    window.openBorrowingFilePreview = function(id) {
        if (window.borrowingFilesMap && window.borrowingFilesMap[id]) {
            window.dmsPreviewFile(window.borrowingFilesMap[id]);
        }
    };

    function handleBorrowingSync(detail) {
        if (!detail || !detail.events) return;
        const hasBorrowingEvents = detail.events.some(ev => 
            ev.event_type === 'borrowing_created' || ev.event_type === 'borrowing_updated'
        );
        if (hasBorrowingEvents) {
            const banner = document.getElementById('borrowing_realtime_alert');
            if (banner) {
                banner.style.display = 'flex';
                if (window.lucide) window.lucide.createIcons();
            }
            const isTyping = document.activeElement && ['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName);
            const isModalOpen = document.querySelector('.modal-open') || document.querySelector('[role="dialog"][style*="display: block"]');
            if (!isTyping && !isModalOpen) {
                setTimeout(() => window.location.reload(), 400);
            }
        }
    }

    window.addEventListener('dms:realtime-sync', function(e) {
        handleBorrowingSync(e.detail);
    });
    window.addEventListener('message', function(e) {
        if (e.data && e.data.type === 'dms:realtime-sync') {
            handleBorrowingSync(e.data.detail);
        }
    });
    if (window.parent && window.parent !== window) {
        try {
            window.parent.addEventListener('dms:realtime-sync', function(e) {
                handleBorrowingSync(e.detail);
            });
        } catch(err) {}
    }
</script>