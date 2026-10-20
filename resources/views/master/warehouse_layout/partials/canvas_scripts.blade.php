<script>
function warehouseCanvasApp() {
    return {
        formatDate(d) {
            if (!d) return '-';
            if (typeof d === 'string' && (d.includes('T') || /^\d{4}-\d{2}-\d{2}/.test(d))) {
                try {
                    const dt = new Date(d);
                    if (!isNaN(dt.getTime())) {
                        return dt.toLocaleDateString('id-ID', { month: 'short', year: 'numeric' });
                    }
                } catch(e) { return d; }
            }
            return d;
        },
        loading: true,
        locations: [],
        departments: [],
        filterDepartment: '',
        selectedLocations: [],
        copiedLocations: [],
        draggedGroupPositions: [],
        activeGuideLines: [],

        get selectedLocation() {
            return this.selectedLocations.length > 0 ? this.selectedLocations[this.selectedLocations.length - 1] : null;
        },
        set selectedLocation(val) {
            if (!val) {
                this.selectedLocations = [];
            } else {
                const idx = this.selectedLocations.findIndex(l => Number(l.id) === Number(val.id));
                if (idx >= 0) {
                    this.selectedLocations[idx] = val;
                    this.selectedLocations = [...this.selectedLocations];
                } else {
                    this.selectedLocations = [val];
                }
            }
        },
        copiedLocation: null,
        toastMessage: '',
        toastOpen: false,
        toastTimer: null,
        scale: 1.0,
        isFullscreen: false,

        // Live Search & Autocomplete State
        searchQuery: '',
        searchDropdownOpen: false,
        searchResults: { rooms: [], racks: [], boxes: [] },

        // Canvas & Modal Pulsing / Blinking States
        blinkLocationId: null,
        blinkStartTime: 0,
        blinkAnimId: null,
        highlightedSlotCode: null,
        slotBlinkActive: false,
        slotBlinkTimer: null,

        rackGridModalOpen: false,
        selectedRackForModal: null,
        selectedSlotDetail: null,
        slotFilterStatus: 'all',
        slotSearchQuery: '',
        slotAssignMode: 'create_new',
        slotAssignLoading: false,
        slotAssignForm: {
            department_id: '',
            sub_department_id: '',
            title: '',
            box_number: '',
            periode_doc: '',
            document_type: 'UMUM',
            retention_years: 5,
            content_description: '',
            archive_id: '',
        },
        unassignedArchivesList: [],
        verifyModalOpen: false,
        verifyModalData: null,
        verifyingArchive: false,
        showRejectInput: false,
        rejectionNote: '',
        canvasWidth: parseInt(localStorage.getItem('wh_canvas_w')) || 950,
        canvasHeight: parseInt(localStorage.getItem('wh_canvas_h')) || 750,
        canvasResizeModalOpen: false,
        canvasResizeForm: { width: 950, height: 750 },
        isResizingCanvas: false,
        resizeCanvasStartX: 0,
        resizeCanvasStartY: 0,
        initialCanvasW: 950,
        initialCanvasH: 750,

        // Right-Click Context Menu State
        contextMenuOpen: false,
        contextMenuTarget: null,
        contextMenuX: 0,
        contextMenuY: 0,
        clickedCanvasX: 400,
        clickedCanvasY: 300,

        get roomObjects() {
            return this.locations.filter(l => l.location_type === 'room');
        },

        objectModalOpen: false,
        objectForm: {
            location_type: 'room',
            rack_code: '',
            room_sector: '',
            box_capacity: 100,
            canvas_width: 150,
            canvas_height: 150,
            custom_color: '#3b82f6',
            canvas_x: 400,
            canvas_y: 300,
        },

        editModalOpen: false,
        editForm: { room_sector: '', rack_code: '', shelf_code: '', box_capacity: 50, assigned_department_id: '', assigned_department_ids: [], custom_color: '', canvas_width: 100, canvas_height: 100, orientation: 'horizontal' },

        colorPresets: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#6366f1', '#14b8a6', '#f97316', '#334155'],

        canvas: null,
        ctx: null,
        hoveredLocation: null,

        // Drag & Resize State
        isDragging: false,
        dragMode: null, // 'move', 'resize-nw', 'resize-ne', 'resize-se', 'resize-sw'
        draggedTarget: null,
        dragStartX: 0,
        dragStartY: 0,
        initialX: 0,
        initialY: 0,
        nudgeTimer: null,

        @include('master.warehouse_layout.partials.canvas_tools')
        @include('master.warehouse_layout.partials.canvas_transform')
        @include('master.warehouse_layout.partials.canvas_navigation')
        @include('master.warehouse_layout.partials.canvas_engine')
        @include('master.warehouse_layout.partials.canvas_renderer')
        @include('master.warehouse_layout.partials.canvas_events')
        @include('master.warehouse_layout.partials.canvas_modals')
        @include('master.warehouse_layout.partials.canvas_slots')
        @include('master.warehouse_layout.partials.canvas_search')
}
</script>