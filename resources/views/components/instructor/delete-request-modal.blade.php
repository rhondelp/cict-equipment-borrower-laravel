<!-- Delete Request Modal — flat light theme -->
<div id="delete-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-neutral-900/50 p-4">
    <div class="w-full max-w-md bg-white border border-neutral-200 rounded-xl shadow-flat flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-200">
            <h3 class="flex items-center gap-2 text-lg font-semibold text-neutral-900">
                <i class="text-base text-danger-600 fas fa-exclamation-triangle"></i> Delete Item Request
            </h3>
            <button type="button" class="cancel-delete w-10 h-10 grid place-items-center rounded-md text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900" id="cancel-delete-x" aria-label="Close">
                <i class="text-base fas fa-times"></i>
            </button>
        </div>

        <form id="delete-form" method="POST" class="flex flex-col flex-1">
            @csrf
            @method('DELETE')

            <div class="px-6 py-5 space-y-3">
                <p class="text-base text-neutral-800">
                    Are you sure you want to delete <strong id="delete-item-name" class="font-semibold text-neutral-900"></strong>?
                </p>
                <p class="text-base text-danger-700 bg-danger-50 border border-danger-200 rounded-md px-4 py-3 flex items-start gap-2 leading-relaxed">
                    <i class="fas fa-info-circle mt-0.5"></i> This action is irreversible and will remove this request.
                </p>
            </div>

            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-neutral-200 bg-neutral-50">
                <button type="button" id="cancel-delete" class="inline-flex items-center justify-center min-h-[44px] px-5 py-3 text-base font-semibold rounded-md bg-white text-neutral-700 border border-neutral-300 hover:bg-neutral-50">Cancel</button>
                <button type="submit" class="inline-flex items-center justify-center gap-2 min-h-[44px] px-5 py-3 text-base font-semibold rounded-md bg-danger-600 text-white hover:bg-danger-700">
                    <i class="text-base fas fa-trash-alt"></i> Confirm Delete
                </button>
            </div>
        </form>
    </div>
</div>
