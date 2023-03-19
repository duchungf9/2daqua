<?php

namespace Modules\Attribute\Entities;

use Modules\Support\Eloquent\Model;
use Modules\Support\Eloquent\Translatable;
use Modules\Attribute\Admin\AttributeTable;

class Attribute extends Model
{
    use Translatable;

    /**
     * The relations to eager load on every query.
     *
     * @var array
     */
    protected $with = ['translations'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['attribute_set_id', 'is_filterable'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_filterable' => 'boolean',
    ];

    /**
     * The attributes that are translatable.
     *
     * @var array
     */
    public $translatedAttributes = ['name'];

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($attribute) {
            $attribute->saveValues(request('values', []));
        });
    }

    public function attributeSet()
    {
        return $this->belongsTo(AttributeSet::class);
    }

    public function values()
    {
        return $this->hasMany(AttributeValue::class)->orderBy('position');
    }

    public function table()
    {
        return new AttributeTable($this->with('attributeSet'));
    }

    public function saveValues($values = [])
    {
        $ids = $this->getDeleteCandidates($values);

        if ($ids->isNotEmpty()) {
            $this->values()->whereIn('id', $ids)->delete();
        }

        foreach (array_reset_index($values) as $index => $value) {
            $this->values()->updateOrCreate(
                ['id' => $value['id']],
                $value + ['position' => $index]
            );
        }
    }

    private function getDeleteCandidates($values = [])
    {
        return $this->values()
            ->pluck('id')
            ->diff(array_pluck($values, 'id'));
    }
}
