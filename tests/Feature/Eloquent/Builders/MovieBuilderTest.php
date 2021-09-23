<?php

use Astrotomic\Tmdb\Models\Movie;
use Astrotomic\Tmdb\Models\Person;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

it('find: returns null when not found', function (): void {
    $movie = Movie::query()->find(0);

    expect($movie)->toBeNull();
});

it('find: creates movie from tmdb', function (): void {
    $movie = Movie::query()->find(335983);

    expect($movie)
        ->toBeInstanceOf(Movie::class)
        ->exists->toBeTrue()
        ->wasRecentlyCreated->toBeTrue()
        ->id->toBe(335983);
});

it('find: movie in database', function (): void {
    Movie::query()->find(335983);
    $movie = Movie::query()->find(335983);

    expect($movie)
        ->toBeInstanceOf(Movie::class)
        ->exists->toBeTrue()
        ->wasRecentlyCreated->toBeFalse()
        ->id->toBe(335983);
});

it('find: delegates to findMany', function (): void {
    $movies = Movie::query()->find([335983, 575788]);

    expect($movies)
        ->toBeInstanceOf(EloquentCollection::class)
        ->toHaveCount(2);
});

it('findMany: creates movies from tmdb', function (): void {
    $movies = Movie::query()->findMany([335983, 575788]);

    expect($movies)
        ->toBeInstanceOf(EloquentCollection::class)
        ->toHaveCount(2);
});

it('findMany: creates movie from tmdb and ignores not found', function (): void {
    $movies = Movie::query()->findMany([335983, 0]);

    expect($movies)
        ->toBeInstanceOf(EloquentCollection::class)
        ->toHaveCount(1);
});

it('findMany: creates movie from tmdb and finds movie in database', function (): void {
    Movie::query()->find(335983);
    $movies = Movie::query()->findMany([335983, 575788]);

    expect($movies)
        ->toBeInstanceOf(EloquentCollection::class)
        ->toHaveCount(2);
});

it('findMany: finds movies in database', function (): void {
    Movie::query()->find(335983);
    Movie::query()->find(575788);
    $movies = Movie::query()->findMany([335983, 575788]);

    expect($movies)
        ->toBeInstanceOf(EloquentCollection::class)
        ->toHaveCount(2);
});

it('findMany: returns empty collection without ids', function (): void {
    $movies = Movie::query()->findMany([]);

    expect($movies)
        ->toBeInstanceOf(EloquentCollection::class)
        ->toHaveCount(0);
});

it('findOrFail: creates movie from tmdb', function (): void {
    $movie = Movie::query()->findOrFail(335983);

    expect($movie)
        ->toBeInstanceOf(Movie::class)
        ->exists->toBeTrue()
        ->wasRecentlyCreated->toBeTrue()
        ->id->toBe(335983);
});

it('findOrFail: throws when not found', function (): void {
    Movie::query()->findOrFail(0);
})->throws(ModelNotFoundException::class);

it('findOrFail: throws when not all found', function (): void {
    Movie::query()->findOrFail([335983, 0]);
})->throws(ModelNotFoundException::class);

it('with: movie with genres', function (): void {
    $movie = Movie::query()->with('genres')->find(335983);

    expect($movie)
        ->toBeInstanceOf(Movie::class)
        ->exists->toBeTrue()
        ->wasRecentlyCreated->toBeTrue()
        ->id->toBe(335983)
        ->genres->toHaveCount(2);
});

it('with: movie with cast', function (): void {
    $movie = Movie::query()->with('cast')->find(335983);

    expect($movie)
        ->toBeInstanceOf(Movie::class)
        ->exists->toBeTrue()
        ->wasRecentlyCreated->toBeTrue()
        ->id->toBe(335983)
        ->credits->toHaveCount(121)
        ->cast->toHaveCount(58)
        ->crew->toHaveCount(63);

    expect(Person::query()->count())->toBe(114);
});

it('with: movie with crew', function (): void {
    $movie = Movie::query()->with('crew')->find(335983);

    expect($movie)
        ->toBeInstanceOf(Movie::class)
        ->exists->toBeTrue()
        ->wasRecentlyCreated->toBeTrue()
        ->id->toBe(335983)
        ->credits->toHaveCount(121)
        ->cast->toHaveCount(58)
        ->crew->toHaveCount(63);

    expect(Person::query()->count())->toBe(114);
});
