<?php
/**
 * @author samva <vas@ciklum.com>
 */

$I = new ApiGuy($scenario);
$I->am('Guest');
$I->wantToTest('fail check-in into invisible restaurant using API');
$I->haveHttpHeader('Content-Type', 'application/json');

$user = $I->addAnonymousUserFixture();

$I->haveHttpHeader('Auth', 'DeviceToken ' . $user['deviceIdentity']);

$restaurant = $I->addRestaurantFixture(
    [
        'visible'   => 0
    ]
);

$I->amGoingTo('send check-in request to the backend server');

$I->dontSeeInDatabase('nmtn_restaurant_checkin', ['restaurant_id' => $restaurant['id']]);

$I->sendPOST('/api/v2/restaurants/' . $restaurant['id'] . '/checkin', ['table' => rand(1, 100)]);
$I->seeResponseIsJson();
$I->seeResponseCodeIs(HTTP_RESPONSE_NOT_FOUND);
$I->seeResponseContainsJson(
    [
        'success' => false,
        'message' => 'Restaurant not found.'
    ]
);
