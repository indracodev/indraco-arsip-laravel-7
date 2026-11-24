{{--
    The one confirm dialog of the app (included once by the layouts). Used through
    data-confirm* attributes or window.showConfirmModal({...}) - see public/js/modal.js.
--}}
<div class="modal modal--danger" id="confirm-modal" role="alertdialog" aria-modal="true" aria-labelledby="confirm-modal-title" aria-describedby="confirm-modal-message" hidden>
    <div class="modal__dialog">
        <div class="modal__header">
            <div class="cluster">
                <span class="modal__icon"><x-icon name="alert" /></span>
                <h2 class="modal__title" id="confirm-modal-title" data-confirm-title>Konfirmasi Tindakan</h2>
            </div>
        </div>
        <div class="modal__body">
            <p class="modal__message" id="confirm-modal-message" data-confirm-message>Apakah Anda yakin ingin melanjutkan tindakan ini?</p>
        </div>
        <div class="modal__footer">
            <x-button data-modal-close data-confirm-cancel>Batal</x-button>
            <x-button variant="danger" data-confirm-ok>Ya, Lanjutkan</x-button>
        </div>
    </div>
</div>
