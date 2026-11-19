<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Faz os testes no estilo Pest (closures) dentro de tests/Feature usarem o
| TestCase do Laravel, com banco recriado a cada teste.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');
