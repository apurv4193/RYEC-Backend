<?php

return [

    'ADMIN_RECORD_PER_PAGE' => '10',
    'WEBSITE_RECORD_PER_PAGE' => '10',
    'WEBSITE_RECENTLY_ADDED_BUSINESS_RECORD_PER_PAGE' => '50',
    'WEBSITE_PLATFORM' => 'web',
    'MOBILE_PLATFORM' => 'mobile',
    'API_RECORD_PER_PAGE' => '20',
    'MOBILE_CHAT_RECORD_PER_PAGE' => '50',
    'WEB_CHAT_RECORD_PER_PAGE' => '30',
    'ACTIVE_FLAG' => '1',
    'INACTIVE_FLAG' => '2',
    'DELETED_FLAG' => '3',
    'USER_ROLE_ID' => '2',
    'SUPER_ADMIN_ROLE_ID' => '1',
    'CATEGORY_TEMP_PATH' => 'images/default2.png',
    'DEFAULT_IMAGE' => 'images/default2.png',
    'RYEC_DEFAULT_BANNER_IMAGE' => 'images/ryecDefault.png',
    'BUSINESS_ORIGINAL_IMAGE_PATH' => 'uploads/business/original/',
    'BUSINESS_THUMBNAIL_IMAGE_PATH' => 'uploads/business/thumbnail/',
    'SERVICE_ORIGINAL_IMAGE_PATH' => 'uploads/service/original/',
    'SERVICE_THUMBNAIL_IMAGE_PATH' => 'uploads/service/thumbnail/',
    'SERVICE_THUMBNAIL_IMAGE_HEIGHT' => '60',
    'SERVICE_THUMBNAIL_IMAGE_WIDTH' => '60',
    'PRODUCT_ORIGINAL_IMAGE_PATH' => 'uploads/product/original/',
    'PRODUCT_THUMBNAIL_IMAGE_PATH' => 'uploads/product/thumbnail/',
    'PRODUCT_THUMBNAIL_IMAGE_HEIGHT' => '60',
    'PRODUCT_THUMBNAIL_IMAGE_WIDTH' => '60',
    'USER_PROFILE_PIC_WIDTH' => '100',
    'USER_PROFILE_PIC_HEIGHT' => '100',
    'USER_ORIGINAL_IMAGE_PATH' => 'uploads/user/original/',
    'USER_THUMBNAIL_IMAGE_PATH' => 'uploads/user/thumbnail/',
    'USER_THUMBNAIL_IMAGE_HEIGHT' => '60',
    'USER_THUMBNAIL_IMAGE_WIDTH' => '60',
    'BUSINESS_THUMBNAIL_IMAGE_HEIGHT' => '200',
    'BUSINESS_THUMBNAIL_IMAGE_WIDTH' => '200',

//   CATEGORY CONSTANT
    'CATEGORY_LOGO_ORIGINAL_IMAGE_PATH' => 'uploads/category/category_logo/original/',
    'CATEGORY_LOGO_THUMBNAIL_IMAGE_PATH' => 'uploads/category/category_logo/thumbnail/',
    'CATEGORY_LOGO_THUMBNAIL_IMAGE_HEIGHT' => '60',
    'CATEGORY_LOGO_THUMBNAIL_IMAGE_WIDTH' => '60',

    'CATEGORY_BANNER_ORIGINAL_IMAGE_PATH' => 'uploads/category/category_banner_image/',

//  Investment Ideas
    'INVESTMENT_IDEAS_FILE_ORIGINAL_IMAGES_PATH' => 'uploads/investment_opportunities/investment_images/',
    'INVESTMENT_IDEAS_FILE_ORIGINAL_VIDEOS_PATH' => 'uploads/investment_opportunities/investment_videos/',
    'INVESTMENT_IDEAS_FILE_ORIGINAL_DOCS_PATH' => 'uploads/investment_opportunities/investment_docs/',

    'ANDROID_APP_VERSION' => env('ANDROID_APP_VERSION', '1'),
    'ANDROID_APP_FORCE_UPDATE' => env('ANDROID_APP_FORCE_UPDATE', 'TRUE'),
    'IOS_APP_VERSION' => env('IOS_APP_VERSION', '1'),
    'IOS_APP_FORCE_UPDATE' => env('IOS_APP_FORCE_UPDATE', 'TRUE'),
    'LANGUAGE_LABELS_VERSION' => env('LANGUAGE_LABELS_VERSION', '7'),

    'ADMIN_EMAIL' => env('ADMIN_EMAIL'),
    'AGENT_APPROVED_FLAG' => '1',
    'AGENT_DECLINE_FLAG' => '2',

    'BUSINESS_WORKING_OPEN_FLAG' => '1',
    'BUSINESS_WORKING_CLOSE_FLAG' => '0',
    'BUSINESS_DETAILS_RATINGS_LIMIT' => '2',
    'BUSINESS_RECORD_PER_PAGE' => '10',

    'OWNER_THUMBNAIL_IMAGE_PATH' => 'uploads/owner/thumbnail/',
    'OWNER_ORIGINAL_IMAGE_PATH' => 'uploads/owner/original/',
    'OWNER_THUMBNAIL_IMAGE_HEIGHT' => '60',
    'OWNER_THUMBNAIL_IMAGE_WIDTH' => '60',
    'SMS_API_KEY' => 'DQ1kB0BIoke5HSFvBGjguQ',
    'INDIA_CODE' => '+91',

    //   Country flag
    'COUNTRY_FLAG_IMAGE_PATH' => 'uploads/country/',
    'COUNTRY_FLAG_THUMBNAIL_IMAGE_WIDTH' => '60',
    'COUNTRY_FLAG_THUMBNAIL_IMAGE_HEIGHT' => '60',

    //subscription plan icon
    'SUBSCRIPTION_PLAN_ORIGINAL_IMAGE_PATH' => 'uploads/subscription_plan/original/',
    'SUBSCRIPTION_PLAN_THUMBNAIL_IMAGE_PATH' => 'uploads/subscription_plan/thumbnail/',
    'SUBSCRIPTION_PLAN_THUMBNAIL_IMAGE_HEIGHT' => '60',
    'SUBSCRIPTION_PLAN_THUMBNAIL_IMAGE_WIDTH' => '60',

    'PREMIUM_ICON_IMAGE' => 'images/premium.png',
    'BASIC_ICON_IMAGE' => 'images/basic.png',
    'LIFETIME_PREMIUM_ICON_IMAGE' => 'images/lifetime_premium.png',

    'FCM_KEY' => env('FCM_KEY'),
    'RYUVA_CLUB_EMAIL_ID' => 'rana@ryuva.club',


];
