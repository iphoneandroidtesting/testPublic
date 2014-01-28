<?php
/**
 * @author tiger
 */

$I = new ApiGuy($scenario);
$I->am('Solution Admin');
$I->wantToTest('being solution admin i can register new restaurant staff user through backoffice-API');
$I->haveHttpHeader('Content-Type', 'application/json');

$restaurant = $I->addRestaurantFixture();

$I->willEvaluateAuthorizationToken(SOLUTION_ADMIN_EMAIL, SOLUTION_ADMIN_PASSWORD);

$I->amGoingTo('send new user data to the backend server');
$params = [
    'email'     => 'restaurant.staff.reg.test@nmotion.pp.ciklum.com',
    'firstName' => 'foo',
    'lastName'  => 'bar'
];
$I->sendPOST('/backoffice/restaurants/' . $restaurant['id'] . '/staff', $params);
$I->seeResponseIsJson();
$I->seeResponseCodeIs(HTTP_RESPONSE_CREATED);
$I->seeResponseContainsJson(['success' => true]);
$I->seeInDatabase('nmtn_user', ['email' => $params['email'], 'registered' => 0]);
$userId = $I->grabDataFromJsonResponse('entries.0.id');
$I->seeInDatabase('nmtn_restaurant_staff', ['user_id' => $userId]);
