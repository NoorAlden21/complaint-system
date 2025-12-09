<?php

return [
    'status' => [
        'pending'     => 'Pending',
        'open'        => 'Open',
        'in_progress' => 'In progress',
        'resolved'    => 'Resolved',
        'closed'      => 'Closed',
        'rejected'    => 'Rejected',
    ],
    'priority' => [
        'low'    => 'Low',
        'medium' => 'Medium',
        'high'   => 'High',
        'urgent' => 'Urgent',
    ],

    'locked_by_other' => 'This complaint is currently locked by another user.',
    'optimistic_lock_conflict' => 'This complaint has been modified by another user. Please refresh and try again.',
];
