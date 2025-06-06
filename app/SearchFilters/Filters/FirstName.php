<?php
/*
 * @Author: arvinlp 
 * @Date: 2020-01-14 21:05:01 
 * Copyright by Arvin Loripour 
 * WebSite : http://www.arvinlp.ir 
 * @Last Modified by: Arvin.Loripour
 * @Last Modified time: 2020-01-14 21:08:34
 */
namespace App\SearchFilters\Filters;

use Illuminate\Database\Eloquent\Builder;
class FirstName implements Filter{
    /**
     * Apply a given search value to the builder instance.
     *
     * @param Builder $builder
     * @param mixed $value
     * @return Builder $builder
     */
    public static function apply(Builder $builder, $value)
    {
        return $builder->where('first_name', 'LIKE', '%' . $value . '%');
    }
}