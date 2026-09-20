@extends('components.default')
@section('title', 'Notifications - CICT Equipment Borrower System')
@section('content')
@include('components.admin.navbar')

@php
    $today = $notifications->filter(fn ($n) => $n->send_date && \Carbon\Carbon::parse($n->send_date)->isToday());
    $types = $notifications->pluck('notification_type')->unique()->filter()->values();

    // Every row written by the nightly job carries the same type, so the "Type"
    // column said "Return Notice" over and over. It is stated once above the
    // list instead, and only comes back as a column if a second type appears.
    $oneType = $notifications->isNotEmpty() && $types->count() <= 1;
@endphp

<div class="min-h-[100dvh] page-bg md:ml-64">

    {{-- Essential for keyboard users: the sidebar is six links deep, so without
         this every page begins with six tab stops before the content. --}}
    <a href="#main-content"
       class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-toast focus:rounded-md focus:border focus:border-primary-200 focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-primary-700">
        Skip to content
    </a>

    <x-ui.page-header eyebrow="History" title="Notifications sent">
        {{ $notifications->count() }} {{ str('message')->plural($notifications->count()) }} · {{ $today->count() }} today
        <x-slot:actions>
            <a href="{{ route('admin.send-return-alerts') }}"
               class="inline-flex items-center gap-2 min-h-[44px] px-5 py-3 text-base font-semibold bg-white border rounded-md text-neutral-700 border-neutral-300 hover:bg-neutral-50">
                <i class="text-base fas fa-paper-plane" aria-hidden="true"></i> Run reminders now
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    <main id="main-content" class="p-4 mx-auto space-y-5 sm:p-6 max-w-content">
        <x-ui.panel data-list data-active-chip="all">
            @if($notifications->isEmpty())
                <x-ui.empty-state icon="fa-bell" title="No reminders sent yet"
                                  message="The daily job runs at 08:00 and writes a line here for each borrower it emails." />
            @else
                <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 border-b sm:px-5 border-neutral-200">
                    <p class="text-sm text-neutral-600">
                        @if($oneType)
                            All messages are <span class="font-semibold text-neutral-800">{{ $types->first() }}</span> reminders,
                            sent automatically at 08:00 on the day a loan falls due.
                        @else
                            {{ $types->implode(' · ') }}
                        @endif
                    </p>
                    <label class="relative flex-1 min-w-[12rem] max-w-xs">
                        <span class="sr-only">Search notifications</span>
                        <i class="absolute text-sm -translate-y-1/2 pointer-events-none fas fa-search left-4 top-1/2 text-neutral-500" aria-hidden="true"></i>
                        <input type="search" data-list-search autocomplete="off" placeholder="Search borrower or message"
                               class="w-full min-h-[40px] rounded-md border border-neutral-300 py-2 pl-10 pr-3 text-base text-neutral-900 placeholder:text-neutral-500 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30">
                    </label>
                </div>

                <div class="divide-y divide-neutral-200">
                    @foreach ($notifications as $notification)
                        @php $sent = $notification->send_date ? \Carbon\Carbon::parse($notification->send_date) : null; @endphp
                        <div data-list-row
                             data-search="{{ strtolower(($notification->user->name ?? '').' '.$notification->message) }}"
                             class="flex items-start gap-4 px-4 py-4 sm:px-5 hover:bg-neutral-50">
                            <span class="grid border rounded-lg w-9 h-9 shrink-0 place-items-center bg-primary-50 border-primary-100" aria-hidden="true">
                                <i class="text-sm fas fa-bell text-primary-600"></i>
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                                    <p class="text-base font-semibold text-neutral-900">{{ $notification->user->name ?? 'Deleted user' }}</p>
                                    <p class="text-sm text-neutral-600">
                                        {{ $sent?->format('M j, g:i A') ?? 'Date not recorded' }}
                                        @unless($oneType) · {{ $notification->notification_type }} @endunless
                                    </p>
                                </div>
                                <p class="mt-1 text-sm text-neutral-700 text-pretty">{{ $notification->message }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>

                <p data-list-empty hidden class="px-5 py-12 text-base text-center text-neutral-600">
                    No messages match that search.
                </p>

                <div class="px-5 py-3 text-sm border-t text-neutral-600 border-neutral-200">
                    <span data-list-count data-total="{{ $notifications->count() }}" data-noun="messages"></span>
                </div>
            @endif
        </x-ui.panel>
    </main>
</div>
@endsection
