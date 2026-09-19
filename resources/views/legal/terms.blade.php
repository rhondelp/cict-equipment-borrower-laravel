@extends('layouts.legal')

@section('legal-title', 'Terms of service')
@section('legal-updated', '20 September 2026')

@section('legal-body')
    <section>
        <h2>Who may use this system</h2>
        <p>
            Accounts are for currently enrolled students and teaching staff of the College
            of Information and Communications Technology. Register with your institutional
            email address and your real name, since the name on your account is the name
            that appears on the borrow slip a custodian checks against.
        </p>
    </section>

    <section>
        <h2>Your account</h2>
        <p>
            You are responsible for what happens under your account. Do not share your
            password, and tell the department office if you think someone else has it. An
            account may be suspended where records suggest it is being used by someone
            other than its owner.
        </p>
    </section>

    <section>
        <h2>Borrowing equipment</h2>
        <ul>
            <li>A request reserves nothing until an administrator approves it. Stock is deducted at approval, not at request.</li>
            <li>Items are due on the return date shown on your transaction. The default loan period is seven days.</li>
            <li>An item that passes its return date is marked overdue, and a reminder goes to the email address on your account.</li>
            <li>Return the item to a custodian, who records its condition. A transaction is not closed until that return is logged.</li>
        </ul>
    </section>

    <section>
        <h2>Damage and loss</h2>
        <p>
            Report damage when you return the item rather than leaving it to be found. The
            return log records condition either way, and an accurate account of how damage
            happened is treated differently from one that is missing. Repair or replacement
            for items lost or damaged beyond normal use follows the college's existing
            property rules, which this system does not replace.
        </p>
    </section>

    <section>
        <h2>Suspension of borrowing</h2>
        <p>
            The department may decline further requests from an account with items still
            overdue, or with a pattern of late returns, until the outstanding items are
            back.
        </p>
    </section>

    <section>
        <h2>Availability</h2>
        <p>
            This is an internal departmental system. It may be taken offline for maintenance
            or during academic breaks. Where it is unavailable, borrowing falls back to the
            paper process at the department office.
        </p>
    </section>

    <section>
        <h2>Changes to these terms</h2>
        <p>
            These terms may be revised as departmental policy changes. The date at the top
            of this page shows when it was last revised. See also the
            <a href="{{ route('legal.privacy') }}">privacy policy</a> for what the system
            stores about you.
        </p>
    </section>
@endsection
