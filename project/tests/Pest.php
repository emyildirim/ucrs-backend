<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure provided to test functions is bound to a specific PHPUnit test
| case class. Default: "PHPUnit\Framework\TestCase". Use the "pest()" function
| to bind different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
 // ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| The "expect()" function provides access to expectation methods for asserting
| conditions in tests. The Expectation API can be extended at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| Expose project-specific testing helpers as global functions to reduce code
| duplication across test files.
|
*/

function something()
{
    // ..
}
