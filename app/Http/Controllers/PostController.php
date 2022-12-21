<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\PostDetailResource;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::all();
        
        // return response()->json(['data' => $posts]);
        return PostDetailResource::collection($posts->loadMissing(['writer:id,username', 'comments:id,post_id,user_id,comments_content']));
    }
    public function show($id)
    {
        $post = Post::with('writer:id,username')->findOrFail($id);
        return new PostDetailResource($post->loadMissing(['writer:id,username', 'comments:id,post_id,user_id,comments_content']));
    }
    public function show2($id)
    {
        $post = Post::findOrFail($id);
        return new PostDetailResource($post);
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|max:255',
            'news_content' => 'required',
        ]);

        if ($request->file) {
            $fileName = $this->generateRandomString();
            $extension = $request->file->extension();
            $file = $fileName.'.'.$extension;

            Storage::putFileAs('image', $request->file, $file);
            $request['image'] = $file;
        }

        $request['author'] = Auth::user()->id;
        $post = Post::create($request->all());
        return new PostDetailResource($post->loadMissing('writer:id,username'));
    }
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'title' => 'required|max:255',
            'news_content' => 'required',
        ]);
        
        $post = Post::findOrfail($id);

        if ($request->file) {
            $fileName = $this->generateRandomString();
            $extension = $request->file->extension();
            $file = $fileName.'.'.$extension;

            Storage::putFileAs('image', $request->file, $file);
            Storage::disk('local')->delete('image/'.$post->image);
            $request['image'] = $file;
        }

        $post->update($request->all());
        return new PostDetailResource($post->loadMissing('writer:id,username'));
    }
    public function destroy($id)
    {
        $post = Post::findOrfail($id);
        $post->delete();
        return new PostDetailResource($post->loadMissing('writer:id,username'));
    }
    function generateRandomString($length = 30) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
