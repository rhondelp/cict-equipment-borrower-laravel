@extends("components.default")

@section("title", "CICT Equipment Borrower System — College of Information & Communications Technology, UNM")

{{-- IBM Plex Mono is used on this page only, for figures and step labels. --}}
@push('styles')
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
@endpush

@section("content")
@php
    /*
     | Built to design-reference/Landing.dc.html. Layout, type scale and
     | colour come from the mockup; every number, rule and claim comes from the
     | code or config. Where the mockup's copy contradicted the application it
     | has been corrected rather than copied — see CHANGELOG, 2026-09-26.
     */

    // Signed-in visitors land here too (the logo on every public page links
    // back to /). "Sign in" would be a dead end for them, so the same buttons
    // take them to their own dashboard instead.
    $user = auth()->user();
    $dashboard = $user
        ? route($user->user_type === 'Admin' ? 'admin.dashboard' : 'borrower.dashboard')
        : null;
    $primaryHref = $dashboard ?? route('login');
    $primaryLabel = $dashboard ? 'Go to your dashboard' : 'Sign in';

    $openToday = $hours->isOpenToday();

    // Shelf colours by Equipment::availabilityState() key — the same states
    // and the same 30% threshold as the inventory page.
    $shelfTone = [
        'out' => ['bar' => 'bg-[oklch(0.6_0.18_25)]', 'text' => 'text-[oklch(0.52_0.18_25)]'],
        'low' => ['bar' => 'bg-[oklch(0.72_0.15_70)]', 'text' => 'text-[oklch(0.52_0.13_62)]'],
        'partial' => ['bar' => 'bg-[oklch(0.55_0.17_258)]', 'text' => 'text-[oklch(0.5_0.012_258)]'],
        'all-in' => ['bar' => 'bg-[oklch(0.6_0.14_158)]', 'text' => 'text-[oklch(0.5_0.012_258)]'],
    ];

    $numberWords = [1 => 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten', 'eleven', 'twelve', 'thirteen', 'fourteen'];
    $loanWord = $numberWords[$loanDays] ?? (string) $loanDays;

    $facts = [
        ['value' => $inventory ? number_format($inventory['units']) : '—', 'label' => 'units tracked'],
        ['value' => $inventory ? number_format($inventory['types']) : '—', 'label' => 'item types'],
        ['value' => $loanDays.' '.str('day')->plural($loanDays), 'label' => 'default loan period'],
        ['value' => $hours->dayRange(short: true), 'label' => $hours->timeRange()],
    ];

    $steps = [
        ['n' => '01', 'title' => 'Request it',
         'body' => 'Pick the item and quantity from your dashboard, and say what it is for.',
         'note' => 'Live stock shown before you ask'],
        ['n' => '02', 'title' => 'The office approves it',
         'body' => 'An administrator reviews the request, and the decision shows on your dashboard.',
         'note' => 'Stock is held once approved'],
        ['n' => '03', 'title' => 'Collect, then bring it back',
         'body' => 'Pick it up at the counter. A custodian logs its condition when you return it and closes the loan.',
         'note' => 'Email reminder on the day it is due'],
    ];

    $rules = [
        ['n' => '1', 'title' => 'A request is not a reservation',
         'body' => 'Nothing is set aside for you until an administrator approves it. Request early for anything running low.'],
        ['n' => '2', 'title' => 'Loans run '.$loanWord.' '.str('day')->plural($loanDays).' by default',
         'body' => 'The return date is on your transaction. Need longer? Say so in the request and it is judged case by case.'],
        ['n' => '3', 'title' => 'Overdue items pause borrowing',
         'body' => 'While something is late, your account cannot send new requests until it is back on the shelf.'],
    ];

    $officeEmail = config('office.email');

    $focusDark = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[oklch(0.22_0.06_264)]';
    $focusLight = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[oklch(0.52_0.17_258)] focus-visible:ring-offset-2';
@endphp

<div data-landing class="bg-[oklch(0.985_0.003_258)] text-[oklch(0.24_0.012_258)]">

    <a href="#landing-main"
       class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-modal focus:rounded-[10px] focus:bg-white focus:px-4 focus:py-2.5 focus:text-[13.5px] focus:font-semibold focus:text-neutral-900 focus:shadow-pop">
        Skip to content
    </a>

    {{-- 1 · Hero -------------------------------------------------------- --}}
    <section class="relative overflow-hidden bg-[oklch(0.22_0.06_264)] text-white">
        <div class="absolute inset-0 pointer-events-none grid-hairlines" aria-hidden="true"></div>

        <nav class="relative mx-auto flex max-w-[1200px] items-center justify-between gap-6 px-5 py-[22px] sm:px-8" aria-label="Main">
            <a href="{{ url('/') }}" class="flex min-w-0 items-center gap-[11px] rounded-[10px] text-white {{ $focusDark }}">
                <span class="grid h-9 w-9 flex-none place-items-center rounded-[10px] bg-white text-[13px] font-bold text-[oklch(0.22_0.06_264)]" aria-hidden="true">CE</span>
                <span class="flex min-w-0 flex-col">
                    <span class="text-[14px] font-semibold leading-[1.2]">CICT Equipment</span>
                    <span class="truncate text-[11.5px] leading-[1.2] text-white/[0.58]">University of Northwestern Mindanao</span>
                </span>
            </a>
            <div class="flex items-center gap-7 text-[13.5px]">
                {{-- In-page shortcuts. Hidden on narrow screens, where the
                     sections are one short scroll away anyway. --}}
                <div class="hidden items-center gap-[26px] md:flex">
                    <a href="#how" class="rounded text-white/[0.72] transition-colors hover:text-white {{ $focusDark }}">How it works</a>
                    <a href="#rules" class="rounded text-white/[0.72] transition-colors hover:text-white {{ $focusDark }}">The rules</a>
                    <a href="#visit" class="rounded text-white/[0.72] transition-colors hover:text-white {{ $focusDark }}">Visit</a>
                </div>
                <a href="{{ $primaryHref }}"
                   class="flex h-[38px] items-center whitespace-nowrap rounded-full border border-white/[0.22] px-4 font-medium text-white transition-colors hover:bg-white/[0.08] {{ $focusDark }}">
                    {{ $dashboard ? 'Dashboard' : 'Sign in' }}
                </a>
            </div>
        </nav>

        <div id="landing-main" class="relative mx-auto grid max-w-[1200px] grid-cols-[repeat(auto-fit,minmax(min(100%,460px),1fr))] items-center gap-16 px-5 pb-20 pt-12 sm:px-8 sm:pb-24 sm:pt-[72px]">

            <div class="flex min-w-0 flex-col gap-7">
                {{-- Grows rather than clips when a phone wraps it, and the
                     hours never split mid-range: the break falls at the "·". --}}
                <span class="inline-flex min-h-[30px] items-center gap-[9px] self-start rounded-[15px] border border-white/[0.12] bg-white/[0.07] py-[5px] pl-2.5 pr-[13px] text-[12.5px] leading-[1.35] text-white/[0.78]"
                      data-open-status="{{ $openToday ? 'open' : 'closed' }}">
                    <span class="h-[7px] w-[7px] flex-none rounded-full {{ $openToday ? 'bg-[oklch(0.78_0.15_158)]' : 'bg-white/40' }}" aria-hidden="true"></span>
                    <span>
                        @if($openToday)
                            Equipment room open today · <span class="whitespace-nowrap">{{ $hours->timeRange() }}</span>
                        @else
                            Equipment room closed today · <span class="whitespace-nowrap">{{ $hours->dayRange(short: true) }}, {{ $hours->timeRange() }}</span>
                        @endif
                    </span>
                </span>

                <h1 class="text-[clamp(2.5rem,1.2rem+4.4vw,4rem)] font-semibold leading-[1.02] tracking-[-0.035em] text-balance">
                    The equipment room, without the paper logbook.
                </h1>

                <p class="max-w-[34em] text-[17px] leading-[1.65] text-white/70 text-pretty">
                    Projectors, laptops, cables and lab kits for CICT students and instructors — requested online,
                    handed over at the counter, and tracked until they come back.
                </p>

                <div class="flex flex-wrap items-center gap-3 pt-1">
                    <a href="{{ $primaryHref }}"
                       class="group flex h-[52px] items-center gap-[9px] rounded-xl bg-white px-6 text-[15px] font-semibold text-[oklch(0.22_0.06_264)] transition-colors hover:bg-[oklch(0.94_0.02_258)] active:translate-y-px {{ $focusDark }}">
                        {{ $primaryLabel }}
                        <svg class="transition-transform duration-200 group-hover:translate-x-0.5" width="15" height="15" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                    @unless($dashboard)
                        <a href="{{ route('register') }}"
                           class="flex h-[52px] items-center rounded-xl border border-white/[0.22] px-[22px] text-[15px] font-medium text-white transition-colors hover:bg-white/[0.07] active:translate-y-px {{ $focusDark }}">
                            Request an account
                        </a>
                    @endunless
                </div>

                {{-- The mockup adds "Accounts approved within one working day".
                     There is no account approval step and no defined
                     turnaround, so that half is not repeated here. --}}
                <span class="text-[13px] text-white/50">
                    @if($user)
                        Signed in as {{ $user->name }}.
                    @else
                        For anyone with an {{ '@'.\App\Models\User::SCHOOL_DOMAIN }} address.
                    @endif
                </span>
            </div>

            {{-- Shelf card, with the reminder overlapping its bottom-left
                 corner. The wrapper's 84px bottom padding is where the
                 reminder sits; the card's own 64px bottom padding is the empty
                 strip it overlaps into, so it can never cover a shelf row. --}}
            <div class="relative min-w-0 pb-[84px] pl-6 sm:pl-10" data-shelf-stage>
                <div class="flex flex-col gap-4 rounded-[20px] bg-white px-6 pb-16 pt-[22px] text-[oklch(0.24_0.012_258)] shadow-[0_40px_80px_oklch(0.1_0.05_264_/_0.5),0_0_0_1px_oklch(1_0_0_/_0.08)]"
                     data-welcome-shelf>
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex flex-col gap-0.5">
                            <span class="text-[15px] font-semibold tracking-[-0.01em]">On the shelf now</span>
                            <span class="text-[12px] text-[oklch(0.58_0.012_258)]" data-shelf-summary>
                                @if($inventory)
                                    <span class="font-mono">{{ number_format($inventory['units']) }}</span> {{ str('unit')->plural($inventory['units']) }}
                                    across <span class="font-mono">{{ number_format($inventory['types']) }}</span> item {{ str('type')->plural($inventory['types']) }}
                                @else
                                    Stock figures are unavailable right now
                                @endif
                            </span>
                        </div>
                        <span class="flex h-[26px] items-center gap-1.5 rounded-full bg-[oklch(0.96_0.03_158)] px-2.5 text-[11.5px] font-medium text-[oklch(0.45_0.13_158)]">
                            <span class="h-1.5 w-1.5 rounded-full bg-[oklch(0.6_0.14_158)]" aria-hidden="true"></span>
                            Live
                        </span>
                    </div>

                    <ul class="flex flex-col gap-[13px]">
                        @forelse($shelf as $row)
                            @php
                                $item = $row['item'];
                                $state = $row['state'];
                                $tone = $shelfTone[$state['key']] ?? $shelfTone['partial'];
                                $in = max(0, (int) $item->available_quantity);
                                $total = max(1, (int) $item->quantity);
                                $pct = min(100, (int) round($in / $total * 100));
                            @endphp
                            <li class="flex flex-col gap-[7px]" data-shelf-item data-state="{{ $state['key'] }}">
                                <div class="flex items-baseline justify-between gap-3">
                                    <span class="min-w-0 truncate text-[13.5px] font-medium">{{ $item->equipment_name }}</span>
                                    <span class="flex-none font-mono text-[12px] {{ $tone['text'] }}">
                                        {{ $in === 0 ? 'none left' : $in.' of '.$item->quantity }}
                                    </span>
                                </div>
                                <div class="h-[5px] overflow-hidden rounded-[3px] bg-[oklch(0.94_0.006_258)]"
                                     role="img" aria-label="{{ $state['label'] }} — {{ $in }} of {{ $item->quantity }} on the shelf">
                                    <div class="h-full rounded-[3px] {{ $tone['bar'] }}" style="width: {{ $pct }}%"></div>
                                </div>
                            </li>
                        @empty
                            <li class="text-[13px] text-[oklch(0.5_0.012_258)]">Nothing has been added to the inventory yet.</li>
                        @endforelse
                    </ul>
                </div>

                {{-- Illustrative, and deliberately static: it shows the kind
                     of reminder a borrower sees, not anyone's real loan. --}}
                <div class="absolute bottom-0 left-0 flex w-[min(300px,86%)] items-start gap-3 rounded-2xl border border-white/[0.14] bg-[oklch(0.3_0.08_264)] px-4 py-3.5 shadow-[0_24px_48px_oklch(0.1_0.05_264_/_0.45)]"
                     data-reminder-card aria-hidden="true">
                    <span class="grid h-[34px] w-[34px] flex-none place-items-center rounded-[10px] bg-[oklch(0.82_0.13_75_/_0.18)] text-[oklch(0.86_0.13_80)]">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M8 2.2a3.8 3.8 0 0 0-3.8 3.8c0 3-1.2 4-1.2 4h10s-1.2-1-1.2-4A3.8 3.8 0 0 0 8 2.2Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><path d="M6.8 12.4a1.4 1.4 0 0 0 2.4 0" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
                    </span>
                    <div class="min-w-0">
                        <div class="text-[13px] font-semibold text-white">Projector (Epson) is due tomorrow</div>
                        <div class="mt-0.5 text-[12px] leading-[1.5] text-white/[0.62] text-pretty">Return it to the equipment room by {{ $hours->closingTime() }}.</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- 2 · Key figures ------------------------------------------------- --}}
    <section class="border-b border-[oklch(0.92_0.006_258)] bg-white" aria-label="Key figures">
        <dl class="mx-auto grid max-w-[1200px] grid-cols-[repeat(auto-fit,minmax(min(100%,150px),1fr))] px-5 sm:px-8" data-welcome-facts>
            @foreach($facts as $fact)
                <div class="flex flex-col gap-1 py-[26px] pr-6">
                    <dt class="order-2 text-[13px] text-[oklch(0.55_0.012_258)]">{{ $fact['label'] }}</dt>
                    <dd class="order-1 text-[26px] font-semibold tracking-[-0.02em] tabular-nums">{{ $fact['value'] }}</dd>
                </div>
            @endforeach
        </dl>
    </section>

    {{-- 3 · How it works ------------------------------------------------ --}}
    <section id="how" class="scroll-mt-5" data-welcome-steps>
        <div class="mx-auto flex max-w-[1200px] flex-col gap-14 px-5 pb-20 pt-24 sm:px-8 sm:pb-[104px] sm:pt-28">
            <div class="flex flex-wrap items-end justify-between gap-8">
                <div class="flex max-w-[560px] flex-col gap-3.5">
                    <span class="text-[12px] font-semibold uppercase tracking-[0.12em] text-[oklch(0.52_0.17_258)]">How it works</span>
                    <h2 class="text-[clamp(1.875rem,1rem+2.4vw,2.625rem)] font-semibold leading-[1.1] tracking-[-0.028em] text-balance">
                        From request to return in three steps.
                    </h2>
                </div>
                <p class="max-w-[360px] text-[15px] leading-[1.65] text-[oklch(0.5_0.012_258)] text-pretty">
                    No more waiting at the counter to find out whether a projector is free. You know before you walk over.
                </p>
            </div>

            <ol class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,260px),1fr))] gap-5">
                @foreach($steps as $step)
                    <li class="flex flex-col gap-[18px] rounded-[20px] border border-[oklch(0.92_0.006_258)] bg-white px-7 pb-7 pt-[30px]">
                        <span class="font-mono text-[13px] text-[oklch(0.52_0.17_258)]" aria-hidden="true">{{ $step['n'] }}</span>
                        <div class="flex flex-col gap-[9px]">
                            <h3 class="text-[21px] font-semibold tracking-[-0.015em]">{{ $step['title'] }}</h3>
                            <p class="text-[14.5px] leading-[1.65] text-[oklch(0.48_0.012_258)] text-pretty">{{ $step['body'] }}</p>
                        </div>
                        <div class="mt-auto flex items-center gap-[9px] border-t border-[oklch(0.94_0.006_258)] pt-4 text-[13px] text-[oklch(0.4_0.012_258)]">
                            <span class="h-1.5 w-1.5 flex-none rounded-full bg-[oklch(0.55_0.17_258)]" aria-hidden="true"></span>
                            {{ $step['note'] }}
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- 4 · The rules --------------------------------------------------- --}}
    <section id="rules" class="scroll-mt-5 border-y border-[oklch(0.92_0.006_258)] bg-white" data-welcome-rules>
        <div class="mx-auto grid max-w-[1200px] grid-cols-[repeat(auto-fit,minmax(min(100%,380px),1fr))] items-start gap-16 px-5 py-20 sm:px-8 sm:py-[104px]">
            <div class="flex flex-col gap-4">
                <span class="text-[12px] font-semibold uppercase tracking-[0.12em] text-[oklch(0.52_0.17_258)]">The rules</span>
                <h2 class="text-[clamp(1.875rem,1rem+2.4vw,2.625rem)] font-semibold leading-[1.1] tracking-[-0.028em] text-balance">
                    Three things worth knowing before you borrow.
                </h2>
                <p class="max-w-[400px] text-[15px] leading-[1.65] text-[oklch(0.5_0.012_258)] text-pretty">
                    The full terms are short and written in plain language.
                </p>
                <a href="{{ route('legal.terms') }}"
                   class="mt-1 self-start rounded text-[14px] font-semibold text-[oklch(0.52_0.17_258)] transition-colors hover:text-[oklch(0.44_0.17_258)] {{ $focusLight }}">
                    Read the terms of service →
                </a>
            </div>
            <ol class="flex flex-col">
                @foreach($rules as $rule)
                    <li class="grid grid-cols-[56px_minmax(0,1fr)] gap-5 border-t border-[oklch(0.92_0.006_258)] py-[26px]">
                        <span class="text-[30px] font-semibold leading-none tracking-[-0.02em] text-[oklch(0.8_0.03_258)]" aria-hidden="true">{{ $rule['n'] }}</span>
                        <div class="flex flex-col gap-1.5">
                            <h3 class="text-[18px] font-semibold tracking-[-0.01em]">{{ $rule['title'] }}</h3>
                            <p class="text-[14.5px] leading-[1.65] text-[oklch(0.48_0.012_258)] text-pretty">{{ $rule['body'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- 5 · Visit ------------------------------------------------------- --}}
    <section id="visit" class="scroll-mt-5" data-welcome-visit>
        <div class="mx-auto max-w-[1200px] px-5 py-20 sm:px-8 sm:py-[104px]">
            <div class="relative grid grid-cols-[repeat(auto-fit,minmax(min(100%,340px),1fr))] items-center gap-12 overflow-hidden rounded-[28px] bg-[oklch(0.22_0.06_264)] p-7 text-white sm:p-10 lg:p-14">
                <div class="absolute inset-0 pointer-events-none grid-hairlines" aria-hidden="true"></div>

                <div class="relative flex flex-col gap-[18px]">
                    <h2 class="text-[clamp(1.875rem,1rem+2.2vw,2.5rem)] font-semibold leading-[1.1] tracking-[-0.028em] text-balance">
                        Ready when you are.
                    </h2>
                    {{-- The mockup ends this with "approves new accounts within
                         a working day"; no such step or timeframe exists. --}}
                    <p class="max-w-[420px] text-[15.5px] leading-[1.65] text-white/70 text-pretty">
                        Sign in with your school account, or request one with your {{ '@'.\App\Models\User::SCHOOL_DOMAIN }} address.
                    </p>
                    <div class="flex flex-wrap items-center gap-3 pt-1.5">
                        <a href="{{ $primaryHref }}"
                           class="flex h-[50px] items-center rounded-xl bg-white px-6 text-[15px] font-semibold text-[oklch(0.22_0.06_264)] transition-colors hover:bg-[oklch(0.94_0.02_258)] active:translate-y-px {{ $focusDark }}">
                            {{ $primaryLabel }}
                        </a>
                        @unless($dashboard)
                            <a href="{{ route('register') }}"
                               class="flex h-[50px] items-center rounded-xl border border-white/[0.22] px-[22px] text-[15px] font-medium text-white transition-colors hover:bg-white/[0.07] active:translate-y-px {{ $focusDark }}">
                                Request an account
                            </a>
                        @endunless
                    </div>
                </div>

                <dl class="relative flex flex-col rounded-[18px] border border-white/[0.12] bg-white/[0.06]">
                    <div class="flex flex-wrap items-baseline justify-between gap-5 border-b border-white/[0.08] px-[22px] py-[18px]">
                        <dt class="text-[12px] font-semibold uppercase tracking-[0.08em] text-white/[0.55]">Hours</dt>
                        <dd class="text-right text-[14.5px] font-medium [overflow-wrap:anywhere]">{{ $hours->dayRange() }}, {{ $hours->timeRange() }}</dd>
                    </div>
                    <div class="flex flex-wrap items-baseline justify-between gap-5 border-b border-white/[0.08] px-[22px] py-[18px]">
                        <dt class="text-[12px] font-semibold uppercase tracking-[0.08em] text-white/[0.55]">Where</dt>
                        <dd class="text-right text-[14.5px] font-medium [overflow-wrap:anywhere]">{{ config('office.location') }}</dd>
                    </div>
                    <div class="flex flex-wrap items-baseline justify-between gap-5 px-[22px] py-[18px]">
                        <dt class="text-[12px] font-semibold uppercase tracking-[0.08em] text-white/[0.55]">Contact</dt>
                        <dd class="text-right text-[14.5px] font-medium [overflow-wrap:anywhere]">
                            @if($officeEmail)
                                <a href="mailto:{{ $officeEmail }}" class="rounded text-white underline decoration-white/30 underline-offset-4 transition-colors hover:decoration-white {{ $focusDark }}">{{ $officeEmail }}</a>
                            @else
                                Ask at the equipment room
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </section>

    {{-- 6 · Footer ------------------------------------------------------ --}}
    <footer class="border-t border-[oklch(0.92_0.006_258)]">
        <div class="mx-auto flex max-w-[1200px] flex-wrap items-center justify-between gap-5 px-5 py-7 text-[13px] text-[oklch(0.55_0.012_258)] sm:px-8">
            <span>College of Information &amp; Communications Technology · University of Northwestern Mindanao</span>
            <nav class="flex items-center gap-[22px]" aria-label="Legal">
                <a href="{{ route('legal.privacy') }}" class="rounded text-[oklch(0.45_0.012_258)] transition-colors hover:text-[oklch(0.24_0.012_258)] {{ $focusLight }}">Privacy policy</a>
                <a href="{{ route('legal.terms') }}" class="rounded text-[oklch(0.45_0.012_258)] transition-colors hover:text-[oklch(0.24_0.012_258)] {{ $focusLight }}">Terms of service</a>
            </nav>
        </div>
    </footer>
</div>
@endsection
