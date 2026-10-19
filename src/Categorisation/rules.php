<?php
/**
 * The rule table, derived by Claude Fable 5.1 from the supplied transaction
 * history (docs/prompts/01-derive-rules.md) and transcribed here one to one.
 *
 * Order matters: the first rule whose pattern matches the description wins.
 * Dining Out sits before Transportation so "Uber Eats" is food and "Uber" is
 * a ride. No rule maps to Miscellaneous; that category is the fallback only.
 *
 * Patterns are case-insensitive and anchored on word boundaries so "NS" does
 * not fire inside "transactions" and "rent" does not fire inside "rental".
 */
declare(strict_types=1);

use App\Categorisation\Rule;
use App\Transactions\Category as C;

return [
    // Groceries
    new Rule('groceries.albert-heijn', C::Groceries, '/\balbert heijn\b/iu', 'The Netherlands\' largest supermarket; 55 rows'),
    new Rule('groceries.ah', C::Groceries, '/\bah\b/iu', 'Albert Heijn\'s abbreviation, as in "AH Online Groceries"; 40 rows'),
    new Rule('groceries.supermarkets', C::Groceries, '/\b(jumbo|lidl|aldi|plus supermarkt|dirk|spar|coop|picnic|crisp|groceries|supermarkt)\b/iu', 'Other Dutch supermarkets and the word itself; none in the sample'),

    // Dining Out (before Transportation: "Uber Eats" must beat "Uber")
    new Rule('dining.delivery', C::DiningOut, '/\b(thuisbezorgd|uber eats|deliveroo|takeaway|just eat)\b/iu', 'Food delivery platforms; none in the sample'),
    new Rule('dining.venues', C::DiningOut, '/\b(restaurant|cafe|caf\x{e9}|bistro|pizzeria|mcdonald\'?s|burger king|kfc|starbucks|bakery|bakkerij|snackbar)\b/iu', 'Places you eat; none in the sample'),

    // Utilities
    new Rule('utilities.energy', C::Utilities, '/\b(eneco|vattenfall|essent|greenchoice|budget energie|energy bill|gas bill|electricity)\b/iu', 'Energy suppliers; "Eneco Energy Bill" is 49 rows'),
    new Rule('utilities.water', C::Utilities, '/\b(vitens|waternet|evides|pwn|dunea|water bill)\b/iu', 'Water companies; none in the sample'),
    new Rule('utilities.telecom', C::Utilities, '/\b(ziggo|t-mobile|odido|kpn|vodafone|tele2|simyo|lebara|internet bill|phone bill|mobile bill)\b/iu', 'Internet, TV and mobile; "Payment to Ziggo" 54 rows, "T-Mobile Bill Payment" 43 rows'),

    // Transportation
    new Rule('transport.rail', C::Transportation, '/\b(ns|ov-chipkaart|ov chipkaart|prorail|thalys|eurostar)\b/iu', 'Dutch Railways and public transport cards; "NS Train Ticket" is 54 rows'),
    new Rule('transport.car', C::Transportation, '/\b(car lease|leaseplan|athlon|shell|bp|esso|tinq|tango|total ?energies|parking|q-park|anwb|fuel|tank)\b/iu', 'Lease, fuel and parking; "Car Lease Payment" is 60 rows'),
    new Rule('transport.ride', C::Transportation, '/\b(uber|bolt|taxi|flixbus|arriva|connexxion|gvb|qbuzz|ebs|bus ticket|train ticket)\b/iu', 'Rides, buses and regional operators; none in the sample'),

    // Entertainment
    new Rule('entertainment.streaming', C::Entertainment, '/\b(netflix|spotify|disney\+?|videoland|hbo max|prime video|youtube premium|apple music)\b/iu', 'Subscriptions; "Netflix Subscription" is 51 rows'),
    new Rule('entertainment.outings', C::Entertainment, '/\b(path\x{e9}|pathe|kinepolis|vue cinema|cinema|bioscoop|theatre|theater|concert|ticketmaster|museum|steam|playstation|xbox|nintendo)\b/iu', 'Cinemas, venues and games; none in the sample'),

    // Healthcare
    new Rule('healthcare.insurance', C::Healthcare, '/\b(zilveren kruis|cz|menzis|vgz|dsw|ohra|health insurance|zorgverzekering)\b/iu', 'Dutch health insurers; none in the sample'),
    new Rule('healthcare.providers', C::Healthcare, '/\b(apotheek|pharmacy|huisarts|doctor|dentist|tandarts|ziekenhuis|hospital|fysio|physio|optician|specsavers|pearle)\b/iu', 'Pharmacies, doctors, dentists, hospitals; none in the sample'),

    // Shopping
    new Rule('shopping.online', C::Shopping, '/\b(bol\.com|bol com|amazon|zalando|coolblue|wehkamp|aliexpress|etsy|marktplaats)\b/iu', 'Web shops; "Bol.com Purchase" is 49 rows'),
    new Rule('shopping.stores', C::Shopping, '/\b(mediamarkt|ikea|hema|action|primark|h&m|decathlon|kruidvat|etos|blokker|gamma|praxis)\b/iu', 'High-street and DIY stores; none in the sample'),

    // Housing
    new Rule('housing.rent', C::Housing, '/\b(rent|huur|mortgage|hypotheek|vve)\b/iu', 'Rent, mortgage and owners\' association; "Rent Payment" is 42 rows'),
    new Rule('housing.taxes', C::Housing, '/\b(municipal tax|gemeente|gemeentebelasting|waterschap|property tax|ozb)\b/iu', 'Municipal and property charges are costs of the home; "Municipal Tax Payment" is 59 rows'),

    // Education
    new Rule('education.institutions', C::Education, '/\b(university|universiteit|hogeschool|school|college|duo|tuition|collegegeld)\b/iu', 'Institutions and student finance (DUO); none in the sample'),
    new Rule('education.courses', C::Education, '/\b(udemy|coursera|course|textbook|studystore|bookshop)\b/iu', 'Courses and study materials; none in the sample'),
];
