<?php

namespace Astrotomic\Tmdb\Models;

use Astrotomic\Tmdb\Eloquent\Builders\PersonBuilder;
use Astrotomic\Tmdb\Enums\Gender;
use Astrotomic\Tmdb\Images\Poster;
use Astrotomic\Tmdb\Models\Concerns\HasTranslations;
use Astrotomic\Tmdb\Requests\Person\Details;
use Astrotomic\Tmdb\Requests\Person\Trending;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\LazyCollection;

/**
 * @property int $id
 * @property string|null $name
 * @property bool $adult
 * @property string[]|null $also_known_as
 * @property \Carbon\Carbon|null $birthday
 * @property \Carbon\Carbon|null $deathday
 * @property \Astrotomic\Tmdb\Enums\Gender $gender
 * @property string|null $homepage
 * @property string|null $imdb_id
 * @property string|null $known_for_department
 * @property string|null $place_of_birth
 * @property string|null $profile_path
 * @property float|null $popularity
 * @property string|null $biography
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read array $translations
 * @property-read \Illuminate\Database\Eloquent\Collection|\Astrotomic\Tmdb\Models\Credit[] $credits
 * @property-read \Illuminate\Database\Eloquent\Collection|\Astrotomic\Tmdb\Models\Credit[] $movie_credits
 *
 * @method \Astrotomic\Tmdb\Eloquent\Builders\PersonBuilder newModelQuery()
 * @method \Astrotomic\Tmdb\Eloquent\Builders\PersonBuilder newQuery()
 * @method static \Astrotomic\Tmdb\Eloquent\Builders\PersonBuilder query()
 *
 * @mixin \Astrotomic\Tmdb\Eloquent\Builders\PersonBuilder
 */
class Person extends Model
{
    use HasTranslations;

    protected $fillable = [
        'id',
        'name',
        'adult',
        'also_known_as',
        'biography',
        'birthday',
        'deathday',
        'gender',
        'homepage',
        'imdb_id',
        'known_for_department',
        'place_of_birth',
        'popularity',
        'profile_path',
    ];

    protected $casts = [
        'id' => 'int',
        'adult' => 'bool',
        'also_known_as' => 'array',
        'birthday' => 'date',
        'deathday' => 'date',
        'gender' => Gender::class,
        'popularity' => 'float',
    ];

    public array $translatable = [
        'biography',
    ];

    public static function trending(?int $limit, string $window = 'day'): EloquentCollection
    {
        $ids = Trending::request(window: $window)
            ->cursor()
            ->when($limit, fn (LazyCollection $collection) => $collection->take($limit))
            ->pluck('id');

        return static::query()->findMany($ids);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany|\Astrotomic\Tmdb\Eloquent\Builders\CreditBuilder
     */
    public function credits(): HasMany
    {
        return $this->hasMany(Credit::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany|\Astrotomic\Tmdb\Eloquent\Builders\CreditBuilder
     */
    public function movie_credits(): HasMany
    {
        return $this->credits()->whereMediaType(Movie::class);
    }

    public function fillFromTmdb(array $data, ?string $locale = null): static
    {
        $this->fill([
            'id' => $data['id'],
            'adult' => $data['adult'],
            'name' => $data['name'] ?: null,
            'also_known_as' => $data['also_known_as'] ?: [],
            'homepage' => $data['homepage'] ?: null,
            'imdb_id' => $data['imdb_id'] ? trim($data['imdb_id']) : null,
            'birthday' => $data['birthday'] ?: null,
            'deathday' => $data['deathday'] ?: null,
            'gender' => $data['gender'] ?: 0,
            'known_for_department' => $data['known_for_department'] ?: null,
            'place_of_birth' => $data['place_of_birth'] ?: null,
            'profile_path' => $data['profile_path'] ?: null,
            'popularity' => $data['popularity'] ?: null,
        ]);

        $locale ??= $this->getLocale();

        $this->setTranslation('biography', $locale, trim($data['biography']) ?: null);

        return $this;
    }

    public function updateFromTmdb(?string $locale = null, array $with = []): bool
    {
        $append = collect($with)
            ->map(fn (string $relation) => match ($relation) {
                'movie_credits' => Details::APPEND_MOVIE_CREDITS,
                default => null,
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        $data = rescue(
            fn () => Details::request($this->id)
                ->language($locale)
                ->append(...$append)
                ->send()
                ->json()
        );

        if ($data === null) {
            return false;
        }

        if (!$this->fillFromTmdb($data, $locale)->save()) {
            return false;
        }

        if (isset($data['movie_credits'])) {
            foreach ($data['movie_credits']['cast'] as $cast) {
                Credit::query()->findOrFail($cast['credit_id']);
            }
            foreach ($data['movie_credits']['crew'] as $crew) {
                Credit::query()->findOrFail($crew['credit_id']);
            }
        }

        return true;
    }

    public function newEloquentBuilder($query): PersonBuilder
    {
        return new PersonBuilder($query);
    }

    public function profile(): Poster
    {
        return new Poster(
            $this->profile_path,
            $this->name
        );
    }
}
