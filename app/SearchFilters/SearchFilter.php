<?php
/*
 * @Author: arvinlp 
 * @Date: 2020-01-14 21:05:01 
 * Copyright by Arvin Loripour 
 * WebSite : http://www.arvinlp.ir 
 * @Last Modified by: Arvin.Loripour
 * @Last Modified time: 2020-02-04 22:06:49
 */
namespace App\SearchFilters;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

class SearchFilter {

    public static function apply(Request $filters, $model, $fetchType = 'pagination', $with = null){
        $query = static::applyDecoratorsFromRequest($filters, $model->newQuery());
        if($with)
            $query = $query->with($with);
        
        switch($fetchType){
            case 'all':
                return static::getResults($query);
            case 'get':
                return static::getAllResults($query);
            case 'custom':
                return static::getCustomResults($query);
            default:
                return static::getResultsByPagination($query);
        }
    }

    private static function applyDecoratorsFromRequest(Request $request, Builder $query){
        foreach ($request->all() as $filterName => $value) {
            if($filterName == 'order_by'){
                $decorator = static::createFilterDecorator($filterName);
                if (array_search('asc',$request->all())){
                    $decorator = static::createFilterDecorator('order_by_asc');
                }
                if (array_search('desc',$request->all())){
                    $decorator = static::createFilterDecorator('order_by_desc');
                }
                if (static::isValidDecorator($decorator)) {
                    $query = $decorator::apply($query, $value);
                }
            }else{
                $decorator = static::createFilterDecorator($filterName);
                if (static::isValidDecorator($decorator)) {
                    $query = $decorator::apply($query, $value);
                }
            }
        }
        return $query;
    }
    private static function createFilterDecorator($name){
        return __NAMESPACE__ . '\\Filters\\' . Str::studly($name);
    }
    private static function isValidDecorator($decorator){
        return class_exists($decorator);
    }
    private static function getResults(Builder $query){
        return $query->get();
    }
    private static function getAllResults(Builder $query){
        return $query->all();
    }
    private static function getCustomResults(Builder $query){
        return $query;
    }
    private static function getResultsByPagination(Builder $query){
        return $query->paginate(env('PORTAL_PAGINATION_NUMBER',20));
    }
}