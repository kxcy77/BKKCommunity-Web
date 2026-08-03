<?php

declare(strict_types=1);

function demo_events(): array
{
    return [
        ['id' => 1, 'title' => 'Morning Movement Session', 'date' => '2026-08-05', 'time' => '09:00', 'end_time' => '10:00', 'location' => 'BKK Community Hall, Block B', 'category' => 'Wellness', 'tone' => 'green', 'description' => 'A gentle, chair-friendly movement class followed by tea and conversation.'],
        ['id' => 2, 'title' => 'Social Lunch Gathering', 'date' => '2026-08-07', 'time' => '12:00', 'end_time' => '14:00', 'location' => 'BKK Hall, Main Road', 'category' => 'Social', 'tone' => 'blue', 'description' => 'Meet neighbours and enjoy a relaxed community lunch. Please confirm attendance for catering.'],
        ['id' => 3, 'title' => 'Health Talk: Managing Diabetes', 'date' => '2026-08-10', 'time' => '14:00', 'end_time' => '15:30', 'location' => 'Clinic Room 2', 'category' => 'Health', 'tone' => 'red', 'description' => 'A practical discussion with a local health worker, with time for questions.'],
        ['id' => 4, 'title' => 'Community Safety Meeting', 'date' => '2026-08-14', 'time' => '10:00', 'end_time' => '11:30', 'location' => 'BKK Main Hall', 'category' => 'Community', 'tone' => 'gold', 'description' => 'Local safety updates, emergency contacts and an open community question session.'],
        ['id' => 5, 'title' => 'Chair Yoga and Tea', 'date' => '2026-08-18', 'time' => '09:00', 'end_time' => '10:30', 'location' => 'Recreation Room', 'category' => 'Wellness', 'tone' => 'teal', 'description' => 'Low-impact chair yoga suitable for beginners, followed by refreshments.'],
        ['id' => 6, 'title' => 'Digital Help Desk', 'date' => '2026-08-22', 'time' => '10:00', 'end_time' => '12:00', 'location' => 'BKK Community Library', 'category' => 'Support', 'tone' => 'blue', 'description' => 'Bring your phone for patient help with messages, photos and useful online services.'],
    ];
}

function demo_discounts(): array
{
    return [
        ['id' => 1, 'store_name' => 'Clicks Pharmacy', 'category' => 'Pharmacy', 'deal' => '10% off selected wellness items for customers aged 60+.', 'eligibility' => 'Customers aged 60 and over', 'claim_instructions' => 'Ask at the dispensary and show a valid identity document.', 'tone' => 'blue'],
        ['id' => 2, 'store_name' => 'Checkers', 'category' => 'Grocery', 'deal' => 'Selected Tuesday savings for senior shoppers.', 'eligibility' => 'Participating stores and selected products', 'claim_instructions' => 'Ask the service desk whether the offer is active before shopping.', 'tone' => 'green'],
        ['id' => 3, 'store_name' => 'Dis-Chem', 'category' => 'Pharmacy', 'deal' => 'Benefit savings on selected pharmacy and wellness purchases.', 'eligibility' => 'Benefit programme members', 'claim_instructions' => 'Present your benefit card before payment.', 'tone' => 'teal'],
        ['id' => 4, 'store_name' => 'Pick n Pay', 'category' => 'Grocery', 'deal' => 'Selected Smart Shopper pensioner benefits and coupons.', 'eligibility' => 'Smart Shopper members; terms vary', 'claim_instructions' => 'Load available offers and scan your Smart Shopper card.', 'tone' => 'green'],
        ['id' => 5, 'store_name' => 'Wimpy', 'category' => 'Restaurant', 'deal' => 'Selected breakfast or coffee offers at participating branches.', 'eligibility' => 'Offer and times vary by branch', 'claim_instructions' => 'Confirm the current senior offer with the branch before ordering.', 'tone' => 'red'],
        ['id' => 6, 'store_name' => 'Golden Arrow', 'category' => 'Transport', 'deal' => 'Reduced fares may be available on selected services.', 'eligibility' => 'Senior passengers; route conditions apply', 'claim_instructions' => 'Confirm identification and route requirements before travelling.', 'tone' => 'gold'],
    ];
}

function demo_services(): array
{
    return [
        ['id' => 1, 'name' => 'BKK Community Hall', 'category' => 'Community', 'phone' => '072 888 5030', 'address' => 'BKK Community Hall', 'hours' => 'Monday–Friday, 08:00–16:00', 'description' => 'Community events, referrals and general member support.', 'tone' => 'blue'],
        ['id' => 2, 'name' => 'Community Health Support Desk', 'category' => 'Health', 'phone' => '0800 029 999', 'address' => 'Confirm the nearest participating clinic', 'hours' => 'Weekdays, 08:00–16:00', 'description' => 'General health-service guidance and referral information.', 'tone' => 'red'],
        ['id' => 3, 'name' => 'SASSA Information Line', 'category' => 'Government', 'phone' => '0800 60 10 11', 'address' => 'Telephone information service', 'hours' => 'Weekdays during office hours', 'description' => 'General social-grant enquiries and official process guidance.', 'tone' => 'gold'],
        ['id' => 4, 'name' => 'Emergency Services', 'category' => 'Emergency', 'phone' => '112', 'address' => 'National mobile emergency number', 'hours' => '24 hours', 'description' => 'Call from a mobile phone when urgent police, fire or medical help is required.', 'tone' => 'red'],
        ['id' => 5, 'name' => 'Digital Help Desk', 'category' => 'Support', 'phone' => '072 888 5030', 'address' => 'BKK Community Library', 'hours' => 'Selected Saturdays, 10:00–12:00', 'description' => 'Patient help with smartphones, messages and online forms.', 'tone' => 'teal'],
        ['id' => 6, 'name' => 'Community Transport Desk', 'category' => 'Transport', 'phone' => '072 888 5030', 'address' => 'BKK Community Hall', 'hours' => 'Book at least one day ahead', 'description' => 'Information about volunteer lifts and nearby public transport.', 'tone' => 'green'],
    ];
}

