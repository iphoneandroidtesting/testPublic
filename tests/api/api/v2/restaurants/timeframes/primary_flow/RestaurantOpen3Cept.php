<?php

$I = new ApiGuy($scenario);
$I->am('Guest');
$I->wantToTest('if restaurant is open when operation time set 03:00 - 02:59 using API');
$I->haveHttpHeader('Content-Type', 'application/json');

$restaurant = $I->addRestaurantFixture(
    [
        'visible'        => 1,
        'operationTimes' => [
            ['day_of_the_week' => 1, 'time_from' => 10800, 'time_to' => 10799],
            ['day_of_the_week' => 2, 'time_from' => 10800, 'time_to' => 10799],
            ['day_of_the_week' => 3, 'time_from' => 10800, 'time_to' => 10799],
            ['day_of_the_week' => 4, 'time_from' => 10800, 'time_to' => 10799],
            ['day_of_the_week' => 5, 'time_from' => 10800, 'time_to' => 10799],
            ['day_of_the_week' => 6, 'time_from' => 10800, 'time_to' => 10799],
            ['day_of_the_week' => 7, 'time_from' => 10800, 'time_to' => 10799]
        ]
    ]
);

$user = $I->addAnonymousUserFixture();
$I->haveHttpHeader('Auth', 'DeviceToken ' . $user['deviceIdentity']);

$I->amGoingTo('check if restaurant is open');

$I->sendGET('/api/v2/restaurants/' . $restaurant['id']);

$I->seeResponseIsJson();
$I->seeResponseCodeIs(HTTP_RESPONSE_OK);
$I->seeResponseContainsJson(
    [
        'success' => true,
        'entries' => [
            ['isOpen' => true]
        ]
    ]
);
