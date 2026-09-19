@extends('layouts.legal')

@section('legal-title', 'Privacy policy')
@section('legal-updated', '20 September 2026')

@section('legal-body')
    <section>
        <h2>What this covers</h2>
        <p>
            The CICT Equipment Borrower System is an internal service of the College of
            Information and Communications Technology at the University of Northwestern
            Mindanao. It is used by enrolled students, teaching staff and the equipment
            custodians who administer it. This page describes what the system stores and
            who can see it.
        </p>
    </section>

    <section>
        <h2>What we store</h2>
        <p>
            An account holds your name, institutional email address, contact number and
            role. Everything else the system keeps is a record of borrowing activity:
        </p>
        <ul>
            <li>Equipment requests you submit, including quantity, purpose and remarks.</li>
            <li>Borrow transactions created from an approved request, with borrow and return dates.</li>
            <li>Return logs recording the condition of an item when it came back, and which staff member received it.</li>
            <li>Return reminders sent to your email address, and when they were sent.</li>
        </ul>
        <p>
            The system does not use analytics or advertising trackers, and does not share
            records with anyone outside the college.
        </p>
    </section>

    <section>
        <h2>Who can see your records</h2>
        <p>
            You can see your own requests and transactions on your dashboard. Department
            administrators can see all records, because approving a request, recording a
            return and reconciling stock all require it. Other borrowers cannot see your
            records.
        </p>
    </section>

    <section>
        <h2>How long records are kept</h2>
        <p>
            Borrowing records are kept for the academic year in which they were created and
            the one following it, so that outstanding items and past equipment condition can
            be traced. Accounts remain active until the department removes them.
        </p>
    </section>

    <section>
        <h2>Cookies</h2>
        <p>
            The system sets one session cookie, which keeps you signed in while you use it,
            and a CSRF token cookie, which protects forms against cross-site submission.
            Both are required for the system to function and neither is used for tracking.
            Choosing "Remember me" at sign-in stores a second cookie that keeps you signed
            in between visits until you log out.
        </p>
    </section>

    <section>
        <h2>Correcting or removing your data</h2>
        <p>
            To correct your name, email or contact number, or to ask for your account to be
            removed, contact the CICT department office. Borrowing records tied to equipment
            that is still out cannot be removed until the item is returned.
        </p>
    </section>
@endsection
