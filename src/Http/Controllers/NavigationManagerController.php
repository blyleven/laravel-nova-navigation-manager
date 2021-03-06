<?php

namespace Voicecode\NavigationManager\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Voicecode\NavigationManager\Models\Navigation;
use Voicecode\NavigationManager\Models\NavigationItem;
use Voicecode\NavigationManager\Http\Controllers\Traits\NavigationItemsTrait;

class NavigationManagerController extends Controller
{
    use NavigationItemsTrait;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = Navigation::get();

        return response()->json($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store()
    {
        $data = request()->validate([
            'name' => 'required|string|max:255',
            'depth' => 'required|numeric|min:1',
        ]);

        $data = Navigation::create($data);

        return response()->json($data);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // Clear cache
        $this->clearCache($id);

        // Cache the navigation JSON.
        $parents = Cache::rememberForever('navigation_'.$id, function () use ($id) {

            // Get all navigation items with child items.
            $items = NavigationItem::where('navigation_id', $id)
                    ->where('parent_id', null)
                    ->orderBy('order')
                    ->with(['children' => function ($query) {
                        $query->orderBy('order');
                    }])
                    ->get();

            return $items;
        });

        foreach ($parents as $parent) {
            $parent->editable = false;
        }

        return response()->json($parents);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update()
    {
        $data = request()->validate([
            'id' => 'required|numeric|min:1',
            'name' => 'required|string|max:255',
            'depth' => 'required|numeric|min:1',
        ]);

        $navigation = Navigation::find(request('id'));
        $navigation->update($data);

        // Refresh cache.
        $this->refreshCache(request('id'));

        return response()->json($navigation);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $navigation = Navigation::find($id);
        $navigation->delete();

        // Clear cache.
        $this->clearCache($id);

        return response()->json([
            'success' => true,
            'message' => 'The navigation has been deleted',
        ]);
    }

    /**
     * Flush navigation caches when asked for.
     *
     * @param int   $id
     *
     * @return void
     */
    public function clearCache($id)
    {
        Cache::forget('navigation_'.$id);
    }

    /**
     * Refresh navigation cache.
     */
    public function refreshCache($id)
    {
        // Clear the current cache.
        $this->clearCache($id);

        // Cache the navigation JSON.
        $parents = Cache::rememberForever('navigation_'.$id, function () use ($id) {

            // Get all navigation items with child items.
            $items = NavigationItem::where('navigation_id', $id)
                    ->where('parent_id', null)
                    ->orderBy('order')
                    ->with(['children' => function ($query) {
                        $query->orderBy('order');
                    }])
                    ->get();

            return $items;
        });

        foreach ($parents as $parent) {
            $parent->editable = false;
        }

        return response()->json($parents);
    }
}
