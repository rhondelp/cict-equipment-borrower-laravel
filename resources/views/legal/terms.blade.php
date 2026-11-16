@extends('layouts.legal')

@php
    $loanDays = (int) config('office.loan_days', 1);
    $numberWords = [1 => 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten', 'eleven', 'twelve', 'thirteen', 'fourteen'];
    $loanPeriod = ($numberWords[$loanDays] ?? (string) $loanDays).' '.str('day')->plural($loanDays);
@endphp

@php($doc = [
    'key' => 'terms',
    'title' => 'Terms of service',
    'lede' => 'The rules for borrowing equipment through this system. Written for the people who actually use it, not for lawyers.',
    'updated' => '20 September 2026',

    'summary' => [
        'A request is not a reservation — stock is only held once an administrator approves it.',
        'The default borrow period is '.$loanPeriod.', and the return is not closed until a custodian logs it.',
        'Overdue items pause your ability to borrow more until they come back.',
    ],

    'sections' => [
        [
            'id' => 'eligibility',
            'heading' => 'Who may use this system',
            'paras' => [
                'Accounts are for currently enrolled students and teaching staff of the College of Information and Communications Technology.',
                'Register with your institutional email address and your real name, since the name on your account is the name that appears on the borrow slip a custodian checks against.',
            ],
        ],
        [
            'id' => 'account',
            'heading' => 'Your account',
            'paras' => [
                'You are responsible for what happens under your account. Do not share your password, and tell the department office if you think someone else has it.',
                'An account may be suspended where records suggest it is being used by someone other than its owner.',
            ],
        ],
        [
            'id' => 'borrowing',
            'heading' => 'Borrowing equipment',
            'paras' => [
                'Every borrow follows the same four steps, and each one is recorded:',
            ],
            'items' => [
                'A request reserves nothing until an administrator approves it. Stock is deducted at approval, not at request.',
                'Items are due on the return date shown on your transaction. The default borrow period is '.$loanPeriod.'.',
                'An item that passes its return date is marked overdue, and a reminder goes to the email address on your account.',
                'Return the item to a custodian, who records its condition. A transaction is not closed until that return is logged.',
            ],
        ],
        [
            'id' => 'damage',
            'heading' => 'Damage and loss',
            'paras' => [
                'Report damage when you return the item rather than leaving it to be found. The return log records condition either way, and an accurate account of how damage happened is treated differently from one that is missing.',
                "Repair or replacement for items lost or damaged beyond normal use follows the college's existing property rules, which this system does not replace.",
            ],
        ],
        [
            'id' => 'suspension',
            'heading' => 'Suspension of borrowing',
            'paras' => [
                'The department may decline further requests from an account with items still overdue, or with a pattern of late returns, until the outstanding items are back.',
            ],
        ],
        [
            'id' => 'availability',
            'heading' => 'Availability',
            'paras' => [
                'This is an internal departmental system. It may be taken offline for maintenance or during academic breaks.',
            ],
            'note' => 'Where the system is unavailable, borrowing falls back to the paper process at the department office.',
        ],
        [
            'id' => 'changes',
            'heading' => 'Changes to these terms',
            'paras' => [
                'These terms may be revised as departmental policy changes. The date at the top of this page shows when it was last revised.',
            ],
        ],
    ],
])
