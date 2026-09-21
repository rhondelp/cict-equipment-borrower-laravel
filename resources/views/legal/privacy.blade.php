{{-- Content only. layouts/legal.blade.php renders it, and also builds the
     contents rail from these headings — see the note at the top of that file.

     The prose is unchanged from the previous revision except where a long
     paragraph has been split in two at a sentence boundary, where the deletion
     fallback has been lifted out into `note`, and in one place where the
     document was inaccurate: see the comment on the sessions bullet under
     "What we store". --}}
@extends('layouts.legal')

@php($doc = [
    'key' => 'privacy',
    'title' => 'Privacy policy',
    'lede' => 'What this system stores about you, who can see it, and how long it is kept.',
    'updated' => '20 September 2026',

    'summary' => [
        'The system stores your name, school email, contact number and role, plus a record of everything you borrow.',
        'Administrators can see all records; other borrowers cannot see yours.',
        'No analytics, no advertising trackers, and nothing is shared outside the college.',
    ],

    'sections' => [
        [
            'id' => 'scope',
            'heading' => 'What this covers',
            'paras' => [
                'The CICT Equipment Borrower System is an internal service of the College of Information and Communications Technology at the University of Northwestern Mindanao. It is used by enrolled students, teaching staff and the equipment custodians who administer it.',
                'This page describes what the system stores and who can see it.',
            ],
        ],
        [
            'id' => 'stored',
            'heading' => 'What we store',
            'paras' => [
                'An account holds your name, institutional email address, contact number and role. The records below are what the system keeps beyond that:',
            ],
            'items' => [
                'Equipment requests you submit, including quantity, purpose and remarks.',
                'Borrow transactions created from an approved request, with borrow and return dates.',
                'Return logs recording the condition of an item when it came back, and which staff member received it.',
                'Return reminders sent to your email address, and when they were sent.',
                // Added, because it was true and undisclosed: sessions are
                // stored in the database (SESSION_DRIVER=database) and the
                // sessions table carries ip_address and user_agent. The list
                // above previously claimed to be everything the system kept.
                'Sign-in sessions, which record the IP address and browser your account signed in from, until the session ends or you log out.',
            ],
        ],
        [
            'id' => 'trackers',
            'heading' => 'Analytics and sharing',
            'paras' => [
                'The system does not use analytics or advertising trackers, and does not share records with anyone outside the college.',
            ],
        ],
        [
            'id' => 'access',
            'heading' => 'Who can see your records',
            'paras' => [
                'You can see your own requests and transactions on your dashboard.',
                'Department administrators can see all records, because approving a request, recording a return and reconciling stock all require it. Other borrowers cannot see your records.',
            ],
        ],
        [
            'id' => 'retention',
            'heading' => 'How long records are kept',
            'paras' => [
                'Borrowing records are kept for the academic year in which they were created and the one following it, so that outstanding items and past equipment condition can be traced.',
                'Accounts remain active until the department removes them.',
            ],
        ],
        [
            'id' => 'cookies',
            'heading' => 'Cookies',
            'paras' => [
                'The system sets one session cookie, which keeps you signed in while you use it, and a CSRF token cookie, which protects forms against cross-site submission. Both are required for the system to function and neither is used for tracking.',
                'Choosing "Keep me signed in" at sign-in stores a second cookie that keeps you signed in between visits until you log out.',
            ],
        ],
        [
            'id' => 'corrections',
            'heading' => 'Correcting or removing your data',
            'paras' => [
                'To correct your name, email or contact number, or to ask for your account to be removed, contact the CICT department office.',
            ],
            // Lifted out of the paragraph above. Someone asking for deletion
            // needs to know this before they ask, not in the reply.
            'note' => 'Borrowing records tied to equipment that is still out cannot be removed until the item is returned.',
        ],
    ],
])
