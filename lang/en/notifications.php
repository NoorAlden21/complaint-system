<?php

return [
    'complaints' => [
        'created' => [
            'title' => 'New Complaint Created',
            'body'  => 'A new complaint has been created with reference number :reference.',
        ],

        'status_changed' => [
            'title' => 'Complaint status updated',
            'body'  => 'Your complaint :reference status has been updated to :status.',
        ],

        'more_info_requested' => [
            'title' => 'More information required',
            'body'  => 'More information has been requested for your complaint :reference.',
        ],

        'reassigned' => [
            'title' => 'New complaint assigned',
            'body'  => 'A new complaint has been assigned to your department.',
        ],

        'all_marked_as_read' => 'All notifications have been marked as read.',

        'info_replied' => [
            'title' => 'Reply received',
            'body'  => 'The user replied to the information request for complaint :reference.',
        ],
    ],
];
