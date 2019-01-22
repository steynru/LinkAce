<?php

namespace App\Http\Controllers\Models;

use App\Http\Controllers\Controller;
use App\Http\Requests\TagDeleteRequest;
use App\Http\Requests\TagStoreRequest;
use App\Http\Requests\TagUpdateRequest;
use App\Models\Link;
use App\Models\Tag;
use Illuminate\Http\Request;

class TagController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $tags = Tag::byUser(auth()->id());

        if ($request->has('orderBy') && $request->has('orderDir')) {
            $tags->orderBy($request->get('orderBy'), $request->get('orderDir'));
        } else {
            $tags->orderBy('name', 'ASC');
        }

        $tags = $tags->paginate(getPaginationLimit());

        return view('models.tags.index', [
            'tags' => $tags,
            'route' => $request->getBaseUrl(),
            'order_by' => $request->get('orderBy'),
            'order_dir' => $request->get('orderDir'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create()
    {
        return view('models.tags.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param TagStoreRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(TagStoreRequest $request)
    {
        $data = $request->except(['tags', 'reload_view']);

        // Set the user ID
        $data['user_id'] = auth()->user()->id;

        $tag = Tag::create($data);

        Tag::flushCache();

        alert(trans('tag.added_successfully'), 'success');

        if ($request->get('reload_view')) {
            session()->flash('reload_view', true);
            return redirect()->route('tags.create');
        }

        return redirect()->route('tags.show', [$tag->id]);
    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @param  int    $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show(Request $request, $id)
    {
        $tag = Tag::find($id);

        if (empty($tag)) {
            abort(404);
        }

        if ($tag->user_id !== auth()->id()) {
            abort(403);
        }

        // Get links of the category
        $links = $tag->links()->byUser(auth()->id());

        if ($request->has('orderBy') && $request->has('orderDir')) {
            $links->orderBy($request->get('orderBy'), $request->get('orderDir'));
        } else {
            $links->orderBy('created_at', 'DESC');
        }

        $links = $links->paginate(getPaginationLimit());

        return view('models.tags.show', [
            'tag' => $tag,
            'tag_links' => $links,
            'route' => $request->getBaseUrl(),
            'order_by' => $request->get('orderBy'),
            'order_dir' => $request->get('orderDir'),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit($id)
    {
        $tag = Tag::find($id);

        if (empty($tag)) {
            abort(404);
        }

        if ($tag->user_id !== auth()->id()) {
            abort(403);
        }

        return view('models.tags.edit')->with('tag', $tag);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param TagUpdateRequest $request
     * @param  int             $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(TagUpdateRequest $request, $id)
    {
        $tag = Tag::find($id);

        if (empty($tag)) {
            abort(404);
        }

        if ($tag->user_id !== auth()->id()) {
            abort(403);
        }

        $data = $request->all();

        $tag->update($data);

        Tag::flushCache();

        alert(trans('tag.updated_successfully'), 'success');

        return redirect()->route('tags.show', [$tag->id]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param TagDeleteRequest $request
     * @param  int             $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy(TagDeleteRequest $request, $id)
    {
        $tag = Tag::find($id);

        if (empty($tag)) {
            abort(404);
        }

        if ($tag->user_id !== auth()->id()) {
            abort(403);
        }

        // Delete all attached links
        $tag->links()->detach();

        $tag->delete();

        Link::flushCache();
        Tag::flushCache();

        return redirect()->route('tags.index');
    }
}
