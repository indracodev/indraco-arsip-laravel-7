<script>
    function handleDestructionSync(detail) {
        if (!detail) return;
        const hasDestructionEvents = detail.events && detail.events.some(ev => 
            ev.event_type === 'destruction_requested' || 
            ev.event_type === 'destruction_approved' || 
            ev.event_type === 'extension_requested' ||
            ev.event_type === 'archive_destroyed'
        );
        if (hasDestructionEvents || (detail.badges && detail.badges.destructions !== undefined)) {
            const isTyping = document.activeElement && ['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName);
            const isModalOpen = document.querySelector('.modal-open') || document.querySelector('[role="dialog"][style*="display: block"]');
            if (!isTyping && !isModalOpen) {
                setTimeout(() => window.location.reload(), 400);
            }
        }
    }

    window.addEventListener('dms:realtime-sync', function(e) {
        handleDestructionSync(e.detail);
    });
    window.addEventListener('message', function(e) {
        if (e.data && e.data.type === 'dms:realtime-sync') {
            handleDestructionSync(e.data.detail);
        }
    });
    if (window.parent && window.parent !== window) {
        try {
            window.parent.addEventListener('dms:realtime-sync', function(e) {
                handleDestructionSync(e.detail);
            });
        } catch(err) {}
    }
</script>
