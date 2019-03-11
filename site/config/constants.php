<?php

/**
 * page specific meta tags
 * @var array
 * * value selection sequence
 * *    [controller]->[action]
 * *    [controller]->'default'
 * *    [controller]
 * *    'default'
 */
$meta_tags = [
    'title' => [
        'default' => 'Todyl | Powerful, simple, affordable, cybersecurity - accessible to everyone',

        'index' => [
            'index' => 'Todyl | Powerful. Simple. Affordable.',
            'terms' => 'Todyl | Terms and Conditions',
            'privacy' => 'Todyl | Privacy Policy',
        ],

        'session' => [
            'forgotPassword' => 'Todyl | Forgot Your Password',
            'login' => 'Todyl | Log In to Todyl Protection',
            'signup' => 'Todyl | Sign up for Todyl Protection',
        ],

        'landing' => [
            'index' => 'Todyl | Protection for Legal Firms',
            'realestate' => 'Todyl | Protection for Real Estate',
            'expertcall' => 'Todyl | Speak with a Cybersecurity Expert',
            'finance' => 'Todyl | Protection for Financial Firms',
        ],

        'partners' => [
            'index' => 'Todyl | Partners',
        ],
        'about' => [
            'index' => 'Todyl | About Us',
        ],        
        'pricing' => [
            'index' => 'Todyl | Pricing',
        ],        
        'details' => [
            'index' => 'Todyl | Under the Hood',
        ],    
        'signup' => [
            'index' => 'Todyl | 15-Day Free Trial',
        ],            
    ],

    'keywords' => [
        'default' => 'cybersecurity for small business, internet security for small business, cyber threats, ransomware, ransomware prevention, data security, cybersecurity, small business network security, data security, data breach, internet security, protect my business, small business, small business data, business firewall, intrusion protections, cyber protection, antivirus, firewall, cyber risk, cyber security services, affordable cyber security, secure vpn, vpn',

        'landing' => [
            'index' => 'Todyl Protection for Legal Firms',
        ],
    ],

    'description' => [
        'default' => 'Todyl makes powerful cybersecurity simple, affordable, and accessible to everyone.',

        'index' => [
            'index' => 'Todyl is the first cybersecurity service designed specifically for small businesses.',
            'terms' => 'Todyl Terms of Use',
            'privacy' => 'Todyl Privacy Policy',
        ],

        'session' => [
            'forgotPassword' => 'Forgot Your Password',
            'login' => 'Log In to Todyl Protection',
            'signup' => 'Sign up for Todyl Protection',
        ],

        'landing' => [
            'index' => 'Learn why Law firms make particularly good targets.',
            'realestate' => 'Why do cyber criminals target real estate brokers and offices?',
            'expertcall' => 'We\'ll assess your current security program and help identify your business risks.',
            'finance' => 'Cyber crime is the fastest-growing type of crime in the United States, with losses of more than $450 billion worldwide in 2016.',
        ],

        'partners' => [
            'index' => 'Deliver cost-effective, enterprise-grade cybersecurity to your clients today.',
            'partnetCallLanding' => 'Schedule a call with a cybersecurity expert.',
        ],
        'about' => [
            'index' => 'Learn more about Todyl and its team.',
        ],
        'details' => [
            'index' => 'Learn more about how Todyl works.',
        ],      
        'pricing' => [
            'index' => 'Learn about Todyl\'s Pricing.',
        ],      

    ],

    'robots' => [
        'partners' => [
            'default' => 'noindex, nofollow',
        ],

    ],
];

/**
 * private navigation
 */
$private_navigation = [
    // dashboard
    [
        'controller' => 'dashboard',
        'action' => 'index',
        'link' => '/dashboard',
        'icon' => 'i-todyl',
        'title' => 'Dashboard',
    ],
    // take action
    [
        'controller' => 'dashboard',
        'action' => 'alerts',
        'link' => '/dashboard/alerts',
        'icon' => 'i-action',
        'title' => 'Take Action',
        'no_ftu' => true,
    ],
    // your devices
    // [
    //     'controller' => 'device',
    //     'link' => '/device',
    //     'icon' => 'i-laptop',
    //     'title' => 'Your Devices',
    //     'no_ftu' => true,
    // ],
    // users & devices
    [
        'controller' => 'user-device',
        'second_controller' => 'service',
        'link' => '/user-device',
        'icon' => 'i-laptop',
        'title' => 'Users & Devices',
        'title_user' => 'Your Devices',
        'no_ftu' => true,
    ],
    // shield
    [
        'controller' => 'shield',
        'link' => '/shield',
        'icon' => 'i-shield',
        'title' => 'Shield',
        'no_ftu' => true,
        'for_admin' => true,
        'email' => 'demo@todyl.com',
    ],
    // activity
    [
        'controller' => 'activity',
        'link' => '/activity',
        'icon' => 'i-activity',
        'title' => 'Activity',
        'no_ftu' => true,
    ],
    // marketplace
    // [
    //     'controller' => 'marketplace',
    //     'link' => '/marketplace',
    //     'icon' => 'i-marketplace',
    //     'title' => 'Marketplace',
    //     'no_ftu' => true,
    //     'for_admin' => true,
    //     'email' => 'demo@todyl.com',
    // ],
    // added services
    // [
    //     'controller' => 'service',
    //     'link' => '/service',
    //     'icon' => 'i-marketplace',
    //     'title' => 'Added Services',
    //     'no_ftu' => true,
    //     'for_admin' => true,
    //     'for_org_admin' => true,
    // ],
    // support
    [
        'controller' => 'support',
        'link' => '/support',
        'icon' => 'i-support',
        'title' => 'Need Help?',
        'show_suspended' => true,
    ],
    // review alerts
    [
        'controller' => 'review',
        'link' => '/review',
        'icon' => 'i-action',
        'title' => 'Review Alerts',
        'for_admin' => true,
    ],
    /* Customer Support Tools
    [
        'controller' => 'customersupport',
        'link' => '/customersupport',
        'icon' => 'i-identity',
        'title' => 'Customer Support',
        'no_ftu' => true,
        'for_admin' => true,
        'email' => 'demo@todyl.com',
    ],
    */
    // settings
    [
        'controller' => 'setting',
        'link' => '/setting',
        'icon' => 'i-settings',
        'title' => 'Settings',
        'for_admin' => true,
    ],
];
