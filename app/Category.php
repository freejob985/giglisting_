<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Session;

class Category extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'category_name',
        'category_slug',
        'category_parent_id',
        'category_icon',
        'category_parent_id',
        'category_description',
        'category_description',
        'category_description',
        'Link',
        'photo',
        'Type',
        'lang',
        'admin',

    ];

//    public function items()
    //    {
    //        return $this->hasMany('App\Item');
    //    }

    /**
     * @return BelongsToMany
     */
    public function allItems()
    {
        return $this->belongsToMany('App\Item', 'category_item')->withTimestamps();
    }

//    public function customFields()
    //    {
    //        return $this->hasMany('App\CustomField');
    //    }

    /**
     * @return BelongsToMany
     */
    public function allCustomFields()
    {
        return $this->belongsToMany('App\CustomField', 'category_custom_field')->withTimestamps();
    }

    /**
     * Get the children categories
     * @return HasMany
     */
    public function children()
    {
        return $this->hasMany('App\Category', 'category_parent_id', 'id');
    }

    /**
     * Get the parent category
     * @return HasOne
     */
    public function parent()
    {
        return $this->hasOne('App\Category', 'id', 'category_parent_id');
    }

    public function allParents()
    {
        $all_parents = collect();
        $parent_category = $this;
        $parent_exist = true;

        while ($parent_exist) {
            $parent_category = $parent_category->parent()->get()->first();
            $parent_exist = empty($parent_category) ? false : true;

            if ($parent_exist) {
                $all_parents->prepend($parent_category);
            }
        }

        return $all_parents;
    }

    /**
     * @param $category
     * @param $children_categories
     */
    public function allChildren($category, &$children_categories)
    {
        $children_categories->push($category);

        $sub_categories = $category->children()->orderBy('category_name')->get();

        if ($sub_categories->count() > 0) {
            foreach ($sub_categories as $key => $sub_category) {
                self::allChildren($sub_category, $children_categories);
            }
        }
    }

    public function getItemsCount($setting_site_location_country_id)
    {
        $children_categories = collect();
        $this->allChildren($this, $children_categories);

        $children_categories_ids = array();
        foreach ($children_categories as $key => $children_category) {
            $children_categories_ids[] = $children_category->id;
        }

        return DB::table('category_item')
            ->join('items as i', 'category_item.item_id', '=', 'i.id')
            ->join('users as u', 'i.user_id', '=', 'u.id')
            ->whereIn('category_item.category_id', $children_categories_ids)
            ->where('i.country_id', $setting_site_location_country_id)
            ->where('i.item_status', Item::ITEM_PUBLISHED)
            ->where('u.email_verified_at', '!=', null)
            ->where('u.user_suspended', User::USER_NOT_SUSPENDED)
            ->distinct('item_id')
            ->count();
    }

    public function getPrintableCategories()
    {
        $printable_array = array();

        
        $root_categories = Category::where('category_parent_id', null)
            ->orderBy('category_name')
            ->where('lang',Session::get('lang'))
            ->get();

        foreach ($root_categories as $key_1 => $root_category) {
            $printable_array = array_merge($printable_array, self::getChildrenCategories($root_category));
        }

        return $printable_array;
    }
    public function getPrintableCategories_()
    {
        $printable_array = array();

        
        $root_categories = Category::where('category_parent_id', null)
            ->orderBy('category_name')
            ->where('lang',Session::get('lang'))
            ->where('admin',"0")
            ->get();

        foreach ($root_categories as $key_1 => $root_category) {
            $printable_array = array_merge($printable_array, self::getChildrenCategories($root_category));
        }

        return $printable_array;
    }
    private function getChildrenCategories($category, $level_deep = 0)
    {
        $dash_str = "";
        for ($i = 0; $i < $level_deep; $i++) {
            $dash_str .= "-";
        }
        if (!empty($dash_str)) {
            $dash_str .= " ";
        }
        $children_categories_array = array(['category_id' => $category->id, 'category_name' => $dash_str . $category->category_name]);

        $children_categories = $category->children()->orderBy('category_name')->get();

        if ($children_categories->count() > 0) {
            $level_deep += 1;

            foreach ($children_categories as $key => $children_category) {
                $children_categories_array = array_merge($children_categories_array, self::getChildrenCategories($children_category, $level_deep));
            }
        }

        return $children_categories_array;
    }

    public function getPrintableCategoriesNoDash()
    {
        $printable_array = array();

        $root_categories = Category::where('category_parent_id', null)
        ->where('lang',Session::get('lang'))
            ->orderBy('category_name')
            ->get();

        foreach ($root_categories as $key_1 => $root_category) {
            $printable_array = array_merge($printable_array, self::getChildrenCategoriesNoDash($root_category));
        }

        return $printable_array;
    }

    private function getChildrenCategoriesNoDash($category)
    {
        $children_categories_array = array(['category_id' => $category->id, 'category_name' => $category->category_name]);

        $children_categories = $category->children()->orderBy('category_name')->get();

        if ($children_categories->count() > 0) {
            foreach ($children_categories as $key => $children_category) {
                $children_categories_array = array_merge($children_categories_array, self::getChildrenCategoriesNoDash($children_category));
            }
        }

        return $children_categories_array;
    }
}
