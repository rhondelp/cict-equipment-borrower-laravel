{{-- Renders session flash + validation errors. Include once near the top of a page's main content. --}}
@if (session('welcome'))
    <div role="status" class="mb-4 flex items-start gap-3 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
        <i class="fas fa-circle-info mt-0.5"></i>
        <span>{{ session('welcome') }}</span>
    </div>
@endif

@if (session('success'))
    <div role="status" class="mb-4 flex items-start gap-3 rounded-lg border-l-4 border-green-500 bg-green-50 px-4 py-3 text-sm text-green-800">
        <i class="fas fa-check-circle mt-0.5"></i>
        <span>{{ session('success') }}</span>
    </div>
@endif

@isset($errors)
    @if ($errors->any())
        <div role="alert" class="mb-4 rounded-lg border-l-4 border-red-500 bg-red-50 px-4 py-3 text-sm text-red-700">
            <p class="flex items-center gap-2 font-medium">
                <i class="fas fa-exclamation-circle"></i> Please fix the following:
            </p>
            <ul class="ml-6 mt-1 list-disc space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
@endisset
