<!-- Delete Request Modal — flat light theme -->
<div id="delete-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-neutral-900/50 p-4">
    <div class="w-full max-w-md bg-white border border-neutral-200 rounded-xl shadow-flat flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-200">
            <h3 class="flex items-center gap-2 text-base font-semibold text-neutral-900">
                <i class="text-sm text-danger-600 fas fa-exclamation-triangle"></i> Delete Item Request
            </h3>
            <button type="button" class="w-8 h-8 grid place-items-center rounded-md text-neutral-500 hover:bg-neutral-100 hover:text-neutral-900" id="cancel-delete-x" aria-label="Close">
                <i class="text-xs fas fa-times"></i>
            </button>
        </div>

        <form id="delete-form" method="POST" class="flex flex-col flex-1">
            @csrf
            @method('DELETE')

            <div class="px-6 py-5 space-y-3">
                <p class="text-sm text-neutral-700">
                    Are you sure you want to delete <strong id="delete-item-name" class="font-semibold text-neutral-900"></strong>?
                </p>
                <p class="text-xs text-danger-600 flex items-start gap-1.5 leading-relaxed">
                    <i class="fas fa-info-circle mt-0.5"></i> This action is irreversible and will remove this request.
                </p>
            </div>

            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-neutral-200 bg-neutral-50">
                <button type="button" id="cancel-delete" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium rounded-md bg-white text-neutral-700 border border-neutral-300 hover:bg-neutral-50">Cancel</button>
                <button type="submit" class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium rounded-md bg-danger-600 text-white hover:bg-danger-700">
                    <i class="text-xs fas fa-trash-alt"></i> Confirm Delete
                </button>
            </div>
        </form>
    </div>
</div>
